import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../services/auth_service.dart';
import '../config/environment.dart';

class RegistrationScreen extends StatefulWidget {
  final String signInMethod; // 'email', 'google', or 'apple'
  final String? email;
  final String? password;
  final String? name;

  const RegistrationScreen({
    super.key,
    required this.signInMethod,
    this.email,
    this.password,
    this.name,
  });

  @override
  State<RegistrationScreen> createState() => _RegistrationScreenState();
}

class _RegistrationScreenState extends State<RegistrationScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  final _nameController = TextEditingController();
  final _authService = AuthService();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  bool _acceptTerms = false;

  bool get _isSignedInWithSocialProvider {
    final user = FirebaseAuth.instance.currentUser;
    if (user == null) return false;
    return user.providerData.any(
      (p) => p.providerId == 'google.com' || p.providerId == 'apple.com',
    );
  }

  bool get _isEmailFlow {
    // Treat this as "email registration" only when explicitly requested AND
    // there isn't already a Firebase user signed in via a social provider.
    // This prevents a common issue where a Google sign-in user is routed to
    // registration with `signInMethod = email`, which then tries to create a
    // new email/password account for an email that already exists.
    return widget.signInMethod == 'email' && !_isSignedInWithSocialProvider;
  }

  @override
  void initState() {
    super.initState();
    _emailController.text = widget.email ?? '';
    _passwordController.text = widget.password ?? '';
    _nameController.text = widget.name ?? '';
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    if (!_acceptTerms) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Please accept the Terms & Privacy Policy',
            style: GoogleFonts.inter(),
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      if (_isEmailFlow) {
        await _registerEmailUser();
      } else {
        await _updateThirdPartyProfile();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error: $e', style: GoogleFonts.inter()),
            backgroundColor: Colors.red,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _registerEmailUser() async {
    try {
      final userCredential = await FirebaseAuth.instance
          .createUserWithEmailAndPassword(
            email: _emailController.text.trim(),
            password: _passwordController.text,
          );

      await userCredential.user?.updateDisplayName(_nameController.text.trim());

      final token = await userCredential.user?.getIdToken();
      if (token == null) throw Exception('Failed to get auth token');

      final response = await http.post(
        Uri.parse('${Environment.apiUrl}/verify_token.php'),
        body: jsonEncode({'idToken': token}),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        await _updateProfileName(token, _nameController.text.trim());
        await _authService.saveSession(data, token, 'email');

        if (mounted) {
          Navigator.pushReplacementNamed(context, '/inactive');
        }
      } else {
        throw Exception('Failed to create database record');
      }
    } on FirebaseAuthException catch (e) {
      String message = 'Registration failed';
      if (e.code == 'email-already-in-use') {
        message = 'This email is already registered';
      } else if (e.code == 'weak-password') {
        message = 'Password is too weak (min 6 characters)';
      } else if (e.code == 'invalid-email') {
        message = 'Invalid email address';
      }
      throw Exception(message);
    }
  }

  Future<void> _updateThirdPartyProfile() async {
    try {
      final user = FirebaseAuth.instance.currentUser;
      if (user == null) throw Exception('Not authenticated');

      final token = await user.getIdToken();
      if (token == null) throw Exception('Failed to get auth token');

      await _updateProfileName(token, _nameController.text.trim());

      final response = await http.post(
        Uri.parse('${Environment.apiUrl}/verify_token.php'),
        body: jsonEncode({'idToken': token}),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final loginMethod = data['user']['login_method'] ?? 'google';
        await _authService.saveSession(data, token, loginMethod);

        if (mounted) {
          Navigator.pushReplacementNamed(context, '/inactive');
        }
      } else {
        throw Exception('Failed to fetch user data');
      }
    } catch (e) {
      throw Exception('Failed to update profile: $e');
    }
  }

  Future<void> _updateProfileName(String token, String name) async {
    final response = await http.post(
      Uri.parse('${Environment.apiUrl}/update_profile.php'),
      body: jsonEncode({'idToken': token, 'name': name}),
      headers: {'Content-Type': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to update profile');
    }
  }

  Future<void> _handleSocialSignup(
    Future<Map<String, dynamic>> Function() action,
    String loginType,
  ) async {
    setState(() => _isLoading = true);

    final result = await action();

    setState(() => _isLoading = false);

    if (result['success']) {
      if (!mounted) return;

      final userData = result['data']['user'];
      final isNewUser = result['data']['is_new_user'] == true;

      if (isNewUser) {
        // Already on complete profile, just update name field
        _nameController.text = userData['name'] ?? '';
        _emailController.text = userData['email'] ?? '';
      } else {
        // Existing user, navigate based on status
        final status = userData['status'];
        if (status == 'active') {
          Navigator.pushReplacementNamed(context, '/dashboard');
        } else {
          Navigator.pushReplacementNamed(context, '/inactive');
        }
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result['message'] ?? 'Authentication failed',
            style: GoogleFonts.inter(),
          ),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEmailSignIn = _isEmailFlow;
    final isReadOnlyEmail = !isEmailSignIn;
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      // backgroundColor: const Color(0xFFF0EEE9), // Cloud Dancer
      // Inherit from theme
      body: SafeArea(
        child: Stack(
          children: [
            // Back Button (Fixed Top-Left)
            Positioned(
              top: 16,
              left: 16,
              child: IconButton(
                icon: Icon(
                  Icons.arrow_back,
                  color: theme.iconTheme.color, // Color(0xFF1E293B)
                ),
                onPressed: () => Navigator.pop(context),
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
              ),
            ),

            // Centered Content
            Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(24),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      // Main Card
                      Container(
                        decoration: BoxDecoration(
                          color: theme.cardTheme.color, // Colors.white
                          borderRadius: BorderRadius.circular(24),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.08),
                              blurRadius: 30,
                              offset: const Offset(0, 10),
                            ),
                          ],
                        ),
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Title
                            Text(
                              'Register',
                              style: GoogleFonts.inter(
                                fontSize: 28,
                                fontWeight: FontWeight.bold,
                                color: theme
                                    .colorScheme
                                    .onSurface, // Color(0xFF1E293B)
                              ),
                            ),
                            const SizedBox(height: 32),

                            // Full Name
                            TextFormField(
                              controller: _nameController,
                              textCapitalization: TextCapitalization.words,
                              style: theme.textTheme.bodyMedium,
                              decoration: InputDecoration(
                                hintText: 'Full name',
                                prefixIcon: const Icon(Icons.person_outline),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide:
                                      theme
                                          .inputDecorationTheme
                                          .border
                                          ?.borderSide ??
                                      BorderSide.none,
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide:
                                      theme
                                          .inputDecorationTheme
                                          .enabledBorder
                                          ?.borderSide ??
                                      BorderSide(color: Colors.grey[300]!),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide:
                                      theme
                                          .inputDecorationTheme
                                          .focusedBorder
                                          ?.borderSide ??
                                      BorderSide(
                                        color: theme
                                            .colorScheme
                                            .primary, // EDS Blue
                                        width: 2,
                                      ),
                                ),
                                filled: true,
                                fillColor: theme
                                    .inputDecorationTheme
                                    .fillColor, // Colors.grey[50]
                              ),
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) {
                                  return 'Name is required';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),

                            // Email
                            TextFormField(
                              controller: _emailController,
                              readOnly: isReadOnlyEmail,
                              keyboardType: TextInputType.emailAddress,
                              style: theme.textTheme.bodyMedium,
                              decoration: InputDecoration(
                                hintText: 'Email',
                                prefixIcon: const Icon(Icons.email_outlined),
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide:
                                      theme
                                          .inputDecorationTheme
                                          .border
                                          ?.borderSide ??
                                      BorderSide.none,
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide:
                                      theme
                                          .inputDecorationTheme
                                          .enabledBorder
                                          ?.borderSide ??
                                      BorderSide(color: Colors.grey[300]!),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(12),
                                  borderSide:
                                      theme
                                          .inputDecorationTheme
                                          .focusedBorder
                                          ?.borderSide ??
                                      BorderSide(
                                        color: theme
                                            .colorScheme
                                            .primary, // EDS Blue
                                        width: 2,
                                      ),
                                ),
                                filled: true,
                                fillColor: isReadOnlyEmail
                                    ? (isDark
                                          ? Colors.grey[800]
                                          : Colors.grey[100])
                                    : theme
                                          .inputDecorationTheme
                                          .fillColor, // Colors.grey[50]
                              ),
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) {
                                  return 'Email is required';
                                }
                                if (!value.contains('@')) {
                                  return 'Invalid email address';
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),

                            // Password (only for email)
                            if (isEmailSignIn) ...[
                              TextFormField(
                                controller: _passwordController,
                                obscureText: _obscurePassword,
                                style: theme.textTheme.bodyMedium,
                                decoration: InputDecoration(
                                  hintText: 'Password',
                                  prefixIcon: const Icon(Icons.lock_outline),
                                  suffixIcon: IconButton(
                                    icon: Icon(
                                      _obscurePassword
                                          ? Icons.visibility_outlined
                                          : Icons.visibility_off_outlined,
                                    ),
                                    onPressed: () {
                                      setState(
                                        () => _obscurePassword =
                                            !_obscurePassword,
                                      );
                                    },
                                  ),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide:
                                        theme
                                            .inputDecorationTheme
                                            .border
                                            ?.borderSide ??
                                        BorderSide.none,
                                  ),
                                  enabledBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide:
                                        theme
                                            .inputDecorationTheme
                                            .enabledBorder
                                            ?.borderSide ??
                                        BorderSide(color: Colors.grey[300]!),
                                  ),
                                  focusedBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide:
                                        theme
                                            .inputDecorationTheme
                                            .focusedBorder
                                            ?.borderSide ??
                                        BorderSide(
                                          color: theme
                                              .colorScheme
                                              .primary, // EDS Blue
                                          width: 2,
                                        ),
                                  ),
                                  filled: true,
                                  fillColor: theme
                                      .inputDecorationTheme
                                      .fillColor, // Colors.grey[50]
                                ),
                                validator: (value) {
                                  if (value == null || value.isEmpty) {
                                    return 'Password is required';
                                  }
                                  if (value.length < 6) {
                                    return 'Password must be at least 6 characters';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 16),

                              // Confirm Password
                              TextFormField(
                                controller: _confirmPasswordController,
                                obscureText: _obscureConfirmPassword,
                                style: theme.textTheme.bodyMedium,
                                decoration: InputDecoration(
                                  hintText: 'Confirm Password',
                                  prefixIcon: const Icon(Icons.lock_outline),
                                  suffixIcon: IconButton(
                                    icon: Icon(
                                      _obscureConfirmPassword
                                          ? Icons.visibility_outlined
                                          : Icons.visibility_off_outlined,
                                    ),
                                    onPressed: () {
                                      setState(
                                        () => _obscureConfirmPassword =
                                            !_obscureConfirmPassword,
                                      );
                                    },
                                  ),
                                  border: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide:
                                        theme
                                            .inputDecorationTheme
                                            .border
                                            ?.borderSide ??
                                        BorderSide.none,
                                  ),
                                  enabledBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide:
                                        theme
                                            .inputDecorationTheme
                                            .enabledBorder
                                            ?.borderSide ??
                                        BorderSide(color: Colors.grey[300]!),
                                  ),
                                  focusedBorder: OutlineInputBorder(
                                    borderRadius: BorderRadius.circular(12),
                                    borderSide:
                                        theme
                                            .inputDecorationTheme
                                            .focusedBorder
                                            ?.borderSide ??
                                        BorderSide(
                                          color: theme
                                              .colorScheme
                                              .primary, // EDS Blue
                                          width: 2,
                                        ),
                                  ),
                                  filled: true,
                                  fillColor: theme
                                      .inputDecorationTheme
                                      .fillColor, // Colors.grey[50]
                                ),
                                validator: (value) {
                                  if (value != _passwordController.text) {
                                    return 'Passwords do not match';
                                  }
                                  return null;
                                },
                              ),
                              const SizedBox(height: 16),
                            ],

                            // Terms & Privacy
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                SizedBox(
                                  height: 20,
                                  width: 20,
                                  child: Checkbox(
                                    value: _acceptTerms,
                                    onChanged: (value) {
                                      setState(
                                        () => _acceptTerms = value ?? false,
                                      );
                                    },
                                    activeColor:
                                        theme.colorScheme.primary, // EDS Blue
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(4),
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    'By continuing you agree with our Term and Privacy Policy',
                                    style: GoogleFonts.inter(
                                      fontSize: 12,
                                      color: theme.textTheme.bodySmall?.color
                                          ?.withOpacity(
                                            0.8,
                                          ), // Color(0xFF64748B)
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 24),

                            // Sign Up Button
                            SizedBox(
                              width: double.infinity,
                              height: 56,
                              child: ElevatedButton(
                                onPressed: _isLoading ? null : _submit,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor:
                                      theme.colorScheme.secondary, // EDS Red
                                  foregroundColor: Colors.white,
                                  elevation: 0,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(
                                      32,
                                    ), // 32px for modern feel
                                  ),
                                ),
                                child: _isLoading
                                    ? const SizedBox(
                                        height: 20,
                                        width: 20,
                                        child: CircularProgressIndicator(
                                          color: Colors.white,
                                          strokeWidth: 2,
                                        ),
                                      )
                                    : Text(
                                        'Sign up',
                                        style: GoogleFonts.inter(
                                          fontSize: 16,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                              ),
                            ),
                            const SizedBox(height: 24),

                            // Divider
                            Row(
                              children: [
                                Expanded(
                                  child: Divider(color: theme.dividerColor),
                                ),
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 16,
                                  ),
                                  child: Text(
                                    'Or sign up with',
                                    style: GoogleFonts.inter(
                                      fontSize: 14,
                                      color: theme.textTheme.bodyMedium?.color
                                          ?.withOpacity(
                                            0.5,
                                          ), // Color(0xFF94A3B8)
                                    ),
                                  ),
                                ),
                                Expanded(
                                  child: Divider(color: theme.dividerColor),
                                ),
                              ],
                            ),
                            const SizedBox(height: 24),

                            // Social Sign Up Buttons
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                // Google
                                _SocialButton(
                                  onPressed: _isLoading
                                      ? null
                                      : () => _handleSocialSignup(
                                          () => _authService.loginWithGoogle(),
                                          'google',
                                        ),
                                  child: Image.asset(
                                    'assets/google_icon.png',
                                    height: 24,
                                    width: 24,
                                    errorBuilder: (context, error, stackTrace) {
                                      return const Icon(
                                        Icons.g_mobiledata_rounded,
                                        size: 28,
                                        color: Color(0xFF4285F4),
                                      );
                                    },
                                  ),
                                ),
                                const SizedBox(width: 20),

                                // Apple
                                _SocialButton(
                                  onPressed: _isLoading
                                      ? null
                                      : () => _handleSocialSignup(
                                          () => _authService.loginWithApple(),
                                          'apple',
                                        ),
                                  child: Icon(
                                    Icons.apple,
                                    size: 28,
                                    color:
                                        theme.iconTheme.color, // Colors.black
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 24),

                            // Sign in link
                            Center(
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    'Already have an account? ',
                                    style: GoogleFonts.inter(
                                      fontSize: 14,
                                      color: theme.textTheme.bodyMedium?.color
                                          ?.withOpacity(
                                            0.7,
                                          ), // Color(0xFF64748B)
                                    ),
                                  ),
                                  TextButton(
                                    onPressed: () {
                                      Navigator.pushNamed(context, '/login');
                                    },
                                    style: TextButton.styleFrom(
                                      padding: EdgeInsets.zero,
                                      minimumSize: Size.zero,
                                      tapTargetSize:
                                          MaterialTapTargetSize.shrinkWrap,
                                    ),
                                    child: Text(
                                      'Sign in',
                                      style: GoogleFonts.inter(
                                        fontSize: 14,
                                        color: theme
                                            .colorScheme
                                            .primary, // EDS Blue
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SocialButton extends StatelessWidget {
  final Widget child;
  final VoidCallback? onPressed;

  const _SocialButton({required this.child, this.onPressed});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return InkWell(
      onTap: onPressed,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        width: 56,
        height: 56,
        decoration: BoxDecoration(
          border: Border.all(color: theme.dividerColor),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(child: child),
      ),
    );
  }
}
