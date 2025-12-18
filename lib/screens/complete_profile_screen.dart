import 'package:flutter/material.dart';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';

class CompleteProfileScreen extends StatefulWidget {
  final String signInMethod; // 'email', 'google', or 'apple'
  final String? email;
  final String? password;
  final String? name;

  const CompleteProfileScreen({
    super.key,
    required this.signInMethod,
    this.email,
    this.password,
    this.name,
  });

  @override
  State<CompleteProfileScreen> createState() => _CompleteProfileScreenState();
}

class _CompleteProfileScreenState extends State<CompleteProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _nameController = TextEditingController();
  bool _isLoading = false;
  bool _obscurePassword = true;

  @override
  void initState() {
    super.initState();
    // Pre-fill fields if provided
    _emailController.text = widget.email ?? '';
    _passwordController.text = widget.password ?? '';
    _nameController.text = widget.name ?? '';
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _nameController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isLoading = true);

    try {
      if (widget.signInMethod == 'email') {
        // Register new email/password user
        await _registerEmailUser();
      } else {
        // Update profile for third-party user (Google/Apple)
        await _updateThirdPartyProfile();
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
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
      // 1. Create Firebase account
      final userCredential = await FirebaseAuth.instance
          .createUserWithEmailAndPassword(
            email: _emailController.text.trim(),
            password: _passwordController.text,
          );

      // 2. Update display name in Firebase
      await userCredential.user?.updateDisplayName(_nameController.text.trim());

      // 3. Get ID token and create database record
      final token = await userCredential.user?.getIdToken();
      if (token == null) throw Exception('Failed to get auth token');

      final response = await http.post(
        Uri.parse('http://10.0.2.2:8000/api/verify_token.php'),
        body: jsonEncode({'idToken': token}),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        // 4. Update name in database
        await _updateProfileName(token, _nameController.text.trim());

        // 5. Save session data
        await _saveSession(data, token, 'email');

        if (mounted) {
          // Navigate to inactive screen (new users start as inactive)
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
      // Get current Firebase user's token
      final user = FirebaseAuth.instance.currentUser;
      if (user == null) throw Exception('Not authenticated');

      final token = await user.getIdToken();
      if (token == null) throw Exception('Failed to get auth token');

      // Update profile name in database
      await _updateProfileName(token, _nameController.text.trim());

      // Get user data from backend
      final response = await http.post(
        Uri.parse('http://10.0.2.2:8000/api/verify_token.php'),
        body: jsonEncode({'idToken': token}),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        // Save session data
        final loginMethod = data['user']['login_method'] ?? 'google';
        await _saveSession(data, token, loginMethod);

        if (mounted) {
          // Navigate to inactive screen (new users start as inactive)
          Navigator.pushReplacementNamed(context, '/inactive');
        }
      } else {
        throw Exception('Failed to fetch user data');
      }
    } catch (e) {
      throw Exception('Failed to update profile: $e');
    }
  }

  Future<void> _saveSession(
    Map<String, dynamic> data,
    String firebaseToken,
    String loginMethod,
  ) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', firebaseToken);

    // Save user data
    final userId = data['user']['id'];
    if (userId != null) {
      await prefs.setString('userId', userId.toString());
    }

    await prefs.setString('user_role', data['user']['role'] ?? 'user');
    await prefs.setString('user_status', data['user']['status'] ?? 'inactive');
    await prefs.setString('user_email', data['user']['email'] ?? '');
    await prefs.setString('login_method', loginMethod);
  }

  Future<void> _updateProfileName(String token, String name) async {
    final response = await http.post(
      Uri.parse('http://10.0.2.2:8000/api/update_profile.php'),
      body: jsonEncode({'idToken': token, 'name': name}),
      headers: {'Content-Type': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to update profile');
    }
  }

  @override
  Widget build(BuildContext context) {
    final isEmailSignIn = widget.signInMethod == 'email';
    final isReadOnlyEmail =
        !isEmailSignIn; // Email is read-only for third-party

    return Scaffold(
      appBar: AppBar(
        title: Text(isEmailSignIn ? 'Create Account' : 'Complete Profile'),
        backgroundColor: const Color(0xFF3F51B5),
        foregroundColor: Colors.white,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Header
              Text(
                isEmailSignIn ? 'Create your account' : 'Just one more step!',
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                isEmailSignIn
                    ? 'Fill in your details to get started'
                    : 'Please confirm your name to continue',
                style: TextStyle(fontSize: 16, color: Colors.grey[600]),
              ),
              const SizedBox(height: 32),

              // Email Field
              TextFormField(
                controller: _emailController,
                readOnly: isReadOnlyEmail,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(
                  labelText: 'Email',
                  prefixIcon: const Icon(Icons.email),
                  border: const OutlineInputBorder(),
                  filled: isReadOnlyEmail,
                  fillColor: isReadOnlyEmail ? Colors.grey[100] : null,
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

              // Password Field (only for email sign-in)
              if (isEmailSignIn) ...[
                TextFormField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  decoration: InputDecoration(
                    labelText: 'Password',
                    prefixIcon: const Icon(Icons.lock),
                    border: const OutlineInputBorder(),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword
                            ? Icons.visibility
                            : Icons.visibility_off,
                      ),
                      onPressed: () {
                        setState(() => _obscurePassword = !_obscurePassword);
                      },
                    ),
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
              ],

              // Name Field
              TextFormField(
                controller: _nameController,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(
                  labelText: 'Full Name',
                  prefixIcon: Icon(Icons.person),
                  border: OutlineInputBorder(),
                ),
                validator: (value) {
                  if (value == null || value.trim().isEmpty) {
                    return 'Name is required';
                  }
                  return null;
                },
              ),
              const SizedBox(height: 32),

              // Submit Button
              ElevatedButton(
                onPressed: _isLoading ? null : _submit,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF3F51B5),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
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
                        isEmailSignIn ? 'Create Account' : 'Continue',
                        style: const TextStyle(fontSize: 16),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
