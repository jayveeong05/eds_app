import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/auth_service.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _authService = AuthService();
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _rememberMe = true; // Default to true
  String _errorMessage = '';

  @override
  void initState() {
    super.initState();
    _loadRememberMe();
  }

  Future<void> _loadRememberMe() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() {
      _rememberMe = prefs.getBool('remember_me') ?? true;
    });
  }

  Future<void> _saveRememberMe(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('remember_me', value);
  }

  Future<void> _handleAuthAction(
    Future<Map<String, dynamic>> Function() action, {
    String loginType = 'email',
  }) async {
    debugPrint('🔐 [LOGIN] Starting authentication - Type: $loginType');

    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      debugPrint('🔐 [LOGIN] Calling auth action...');
      final result = await action();

      debugPrint('🔐 [LOGIN] Auth result received: $result');
      debugPrint('🔐 [LOGIN] Result type: ${result.runtimeType}');
      debugPrint('🔐 [LOGIN] Success key: ${result['success']}');
      debugPrint('🔐 [LOGIN] Success type: ${result['success'].runtimeType}');

      setState(() {
        _isLoading = false;
      });

      if (result['success'] == true) {
        debugPrint('🔐 [LOGIN] Authentication successful!');

        // Save remember me preference
        await _saveRememberMe(_rememberMe);

        if (!mounted) return;

        final userData = result['data']['user'];
        debugPrint('🔐 [LOGIN] User data: $userData');

        final status = userData['status'];
        final isNewUser = result['data']['is_new_user'] == true;

        debugPrint('🔐 [LOGIN] User status: $status');
        debugPrint('🔐 [LOGIN] Is new user: $isNewUser');

        if (isNewUser && (loginType == 'google' || loginType == 'apple')) {
          debugPrint('🔐 [LOGIN] Redirecting new social user to registration');
          Navigator.pushNamed(
            context,
            '/register',
            arguments: {
              // Force social method here. Some backends may return a default
              // `login_method` like "email" even for social sign-ins, which
              // would incorrectly show the email/password registration form.
              'signInMethod': loginType,
              'email': userData['email'],
              'name': userData['name'] ?? '',
            },
          );
        } else if (isNewUser && loginType == 'email') {
          debugPrint('🔐 [LOGIN] New email user - showing error');
          setState(() {
            _errorMessage = 'Account not found. Please register first.';
          });
        } else if (status == 'deleted') {
          debugPrint('🔐 [LOGIN] Deleted user - showing error');
          setState(() {
            _errorMessage = 'Your account has been deleted. Please contact support for assistance.';
          });
        } else if (status == 'active') {
          debugPrint('🔐 [LOGIN] Active user - redirecting to dashboard');
          Navigator.pushReplacementNamed(context, '/dashboard');
        } else {
          debugPrint(
            '🔐 [LOGIN] Inactive user - redirecting to inactive screen',
          );
          Navigator.pushReplacementNamed(context, '/inactive');
        }
      } else {
        debugPrint('🔐 [LOGIN] Authentication failed: ${result['message']}');

        // Check for deleted account message
        if (result['message'] != null &&
            result['message'].toString().toLowerCase().contains('deleted')) {
          setState(() {
            _errorMessage = 'Your account has been deleted. Please contact support for assistance.';
          });
        } else if (result['message'] != null &&
            result['message'].toString().toLowerCase().contains(
              'user-not-found',
            )) {
          setState(() {
            _errorMessage = 'Account not found. Please register first.';
          });
        } else {
          setState(() {
            _errorMessage = result['message'] ?? 'Authentication failed';
          });
        }
      }
    } catch (e, stackTrace) {
      debugPrint('🔐 [LOGIN] ❌ EXCEPTION: $e');
      debugPrint('🔐 [LOGIN] Stack trace: $stackTrace');

      setState(() {
        _isLoading = false;
        _errorMessage = 'Error: $e';
      });
    }
  }

  void _showForgotPasswordDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text(
          'Forgot Password',
          style: GoogleFonts.inter(fontWeight: FontWeight.bold),
        ),
        content: Text(
          'Please contact our support team at support@eds.com to reset your password.',
          style: GoogleFonts.inter(),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(
              'OK',
              style: GoogleFonts.inter(
                color: Theme.of(context).colorScheme.primary, // Deep Slate
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    // final isDark = theme.brightness == Brightness.dark;

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
                            'Welcome Back',
                            style: GoogleFonts.inter(
                              fontSize: 28,
                              fontWeight: FontWeight.bold,
                              color: theme
                                  .colorScheme
                                  .onSurface, // Color(0xFF1E293B)
                            ),
                          ),
                          const SizedBox(height: 32),

                          // Email Field
                          TextField(
                            controller: _emailController,
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
                                    const BorderSide(
                                      color: Color(0xFF1A73E8),
                                      width: 2,
                                    ),
                              ),
                              filled: true,
                              fillColor: theme
                                  .inputDecorationTheme
                                  .fillColor, // Colors.grey[50]
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Password Field
                          TextField(
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
                                    () => _obscurePassword = !_obscurePassword,
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
                                    const BorderSide(
                                      color: Color(0xFF1A73E8),
                                      width: 2,
                                    ),
                              ),
                              filled: true,
                              fillColor: theme
                                  .inputDecorationTheme
                                  .fillColor, // Colors.grey[50]
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Remember Me & Forgot Password
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Row(
                                children: [
                                  SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: Checkbox(
                                      value: _rememberMe,
                                      onChanged: (value) {
                                        setState(
                                          () => _rememberMe = value ?? false,
                                        );
                                      },
                                      activeColor: theme
                                          .colorScheme
                                          .primary, // Royal Blue
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(4),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Text(
                                    'Remember me',
                                    style: GoogleFonts.inter(
                                      fontSize: 14,
                                      color: theme.textTheme.bodyMedium?.color
                                          ?.withOpacity(
                                            0.7,
                                          ), // Color(0xFF64748B)
                                    ),
                                  ),
                                ],
                              ),
                              TextButton(
                                onPressed: _showForgotPasswordDialog,
                                style: TextButton.styleFrom(
                                  padding: EdgeInsets.zero,
                                  minimumSize: Size.zero,
                                  tapTargetSize:
                                      MaterialTapTargetSize.shrinkWrap,
                                ),
                                child: Text(
                                  'Forgot password?',
                                  style: GoogleFonts.inter(
                                    fontSize: 14,
                                    color:
                                        theme.colorScheme.primary, // Deep Slate
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 24),

                          // Error Message
                          if (_errorMessage.isNotEmpty)
                            Container(
                              padding: const EdgeInsets.all(12),
                              margin: const EdgeInsets.only(bottom: 16),
                              decoration: BoxDecoration(
                                color: Colors.red.shade50,
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.error_outline,
                                    color: Colors.red,
                                    size: 20,
                                  ),
                                  const SizedBox(width: 8),
                                  Expanded(
                                    child: Text(
                                      _errorMessage,
                                      style: GoogleFonts.inter(
                                        color: Colors.red,
                                        fontSize: 14,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            ),

                          // Login Button
                          SizedBox(
                            width: double.infinity,
                            height: 56,
                            child: ElevatedButton(
                              onPressed: _isLoading
                                  ? null
                                  : () {
                                      if (_emailController.text
                                              .trim()
                                              .isEmpty ||
                                          _passwordController.text
                                              .trim()
                                              .isEmpty) {
                                        setState(() {
                                          _errorMessage =
                                              'Email and Password are required';
                                        });
                                        return;
                                      }
                                      _handleAuthAction(
                                        () => _authService.loginWithEmail(
                                          _emailController.text.trim(),
                                          _passwordController.text.trim(),
                                        ),
                                        loginType: 'email',
                                      );
                                    },
                              style: ElevatedButton.styleFrom(
                                backgroundColor:
                                    theme.colorScheme.primary, // EDS Blue
                                foregroundColor: Colors.white,
                                elevation: 0,
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(32),
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
                                      'Login',
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
                                  'Or login with',
                                  style: GoogleFonts.inter(
                                    fontSize: 14,
                                    color: theme.textTheme.bodyMedium?.color
                                        ?.withOpacity(0.5), // Color(0xFF94A3B8)
                                  ),
                                ),
                              ),
                              Expanded(
                                child: Divider(color: theme.dividerColor),
                              ),
                            ],
                          ),
                          const SizedBox(height: 24),

                          // Social Login Buttons
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              // Google
                              _SocialButton(
                                onPressed: _isLoading
                                    ? null
                                    : () => _handleAuthAction(
                                        () => _authService.loginWithGoogle(),
                                        loginType: 'google',
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
                                    : () => _handleAuthAction(
                                        () => _authService.loginWithApple(),
                                        loginType: 'apple',
                                      ),
                                child: Icon(
                                  Icons.apple,
                                  size: 28,
                                  color: theme
                                      .iconTheme
                                      .color, // Colors.black or white
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 24),

                          // Sign up link
                          Center(
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  "Don't have an account? ",
                                  style: GoogleFonts.inter(
                                    fontSize: 14,
                                    color: theme.textTheme.bodyMedium?.color
                                        ?.withOpacity(0.7), // Color(0xFF64748B)
                                  ),
                                ),
                                TextButton(
                                  onPressed: () {
                                    Navigator.pushNamed(
                                      context,
                                      '/register',
                                      arguments: {'signInMethod': 'email'},
                                    );
                                  },
                                  style: TextButton.styleFrom(
                                    padding: EdgeInsets.zero,
                                    minimumSize: Size.zero,
                                    tapTargetSize:
                                        MaterialTapTargetSize.shrinkWrap,
                                  ),
                                  child: Text(
                                    'Sign up',
                                    style: GoogleFonts.inter(
                                      fontSize: 14,
                                      color: theme
                                          .colorScheme
                                          .secondary, // EDS Red
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
