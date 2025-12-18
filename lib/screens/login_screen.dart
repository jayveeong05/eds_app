import 'package:flutter/material.dart';
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
  String _errorMessage = '';

  Future<void> _handleAuthAction(
    Future<Map<String, dynamic>> Function() action,
  ) async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    final result = await action();

    setState(() {
      _isLoading = false;
    });

    if (result['success']) {
      if (!mounted) return;

      final userData = result['data']['user'];
      final status = userData['status'];
      final isNewUser = result['data']['is_new_user'] == true;

      // Check if this is a new third-party user (Google/Apple)
      if (isNewUser) {
        // Navigate to Complete Profile for name confirmation
        Navigator.pushNamed(
          context,
          '/complete-profile',
          arguments: {
            'signInMethod': userData['login_method'] ?? 'google',
            'email': userData['email'],
            'name': userData['name'] ?? '', // May be from Google/Apple
          },
        );
      } else if (status == 'active') {
        Navigator.pushReplacementNamed(context, '/dashboard');
      } else {
        Navigator.pushReplacementNamed(context, '/inactive');
      }
    } else {
      // Handle specific error for non‑existent account
      if (result['message'] != null &&
          result['message'].toString().toLowerCase().contains(
            'user-not-found',
          )) {
        // Show dialog offering registration
        _showCreateAccountDialog();
      } else {
        setState(() {
          _errorMessage = result['message'] ?? 'Authentication failed';
        });
      }
    }
  }

  void _showCreateAccountDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Account Not Found'),
        content: const Text(
          'No account exists with this email. Would you like to create one?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () {
              Navigator.pop(context);
              // Navigate to Complete Profile with pre-filled email/password
              Navigator.pushNamed(
                context,
                '/complete-profile',
                arguments: {
                  'signInMethod': 'email',
                  'email': _emailController.text.trim(),
                  'password': _passwordController.text.trim(),
                },
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3F51B5),
              foregroundColor: Colors.white,
            ),
            child: const Text('Create Account'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [
              const Color(0xFF3F51B5).withOpacity(0.1), // EDS Blue light
              Colors.white,
              const Color(0xFFE53935).withOpacity(0.05), // EDS Red hint
            ],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // App Icon/Logo
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: const Color(0xFF3F51B5), // EDS Blue
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF3F51B5).withOpacity(0.3),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: const Icon(
                      Icons.description_rounded,
                      size: 60,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 24),

                  // App Name
                  const Text(
                    'EDS App',
                    style: TextStyle(
                      fontSize: 32,
                      fontWeight: FontWeight.bold,
                      color: Colors.deepPurple,
                      letterSpacing: 1,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'E-Document Solutions',
                    style: TextStyle(
                      fontSize: 16,
                      color: Colors.grey[600],
                      letterSpacing: 0.5,
                    ),
                  ),
                  const SizedBox(height: 48),

                  // Login Card
                  Card(
                    elevation: 8,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        children: [
                          // Email Field
                          TextField(
                            controller: _emailController,
                            decoration: InputDecoration(
                              labelText: 'Email',
                              prefixIcon: const Icon(Icons.email_outlined),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              filled: true,
                              fillColor: Colors.grey[50],
                            ),
                            keyboardType: TextInputType.emailAddress,
                          ),
                          const SizedBox(height: 16),

                          // Password Field
                          TextField(
                            controller: _passwordController,
                            decoration: InputDecoration(
                              labelText: 'Password',
                              prefixIcon: const Icon(Icons.lock_outline),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(12),
                              ),
                              filled: true,
                              fillColor: Colors.grey[50],
                            ),
                            obscureText: true,
                          ),
                          const SizedBox(height: 8),

                          // Error Message
                          if (_errorMessage.isNotEmpty)
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: Colors.red.shade50,
                                borderRadius: BorderRadius.circular(8),
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
                                      style: const TextStyle(color: Colors.red),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          const SizedBox(height: 24),

                          // Login/Register Buttons
                          if (_isLoading)
                            const CircularProgressIndicator()
                          else
                            Column(
                              children: [
                                // Login Button
                                SizedBox(
                                  width: double.infinity,
                                  height: 50,
                                  child: ElevatedButton.icon(
                                    icon: const Icon(Icons.login_rounded),
                                    label: const Text(
                                      'Login',
                                      style: TextStyle(fontSize: 16),
                                    ),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: const Color(
                                        0xFF3F51B5,
                                      ), // EDS Blue
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                      elevation: 2,
                                    ),
                                    onPressed: () {
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
                                      );
                                    },
                                  ),
                                ),
                                const SizedBox(height: 12),

                                // Register Button
                                SizedBox(
                                  width: double.infinity,
                                  height: 50,
                                  child: OutlinedButton.icon(
                                    icon: const Icon(Icons.person_add_rounded),
                                    label: const Text(
                                      'Register',
                                      style: TextStyle(fontSize: 16),
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: const Color(
                                        0xFF3F51B5,
                                      ), // EDS Blue
                                      side: const BorderSide(
                                        color: Color(0xFF3F51B5), // EDS Blue
                                        width: 2,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                    onPressed: () {
                                      // Navigate to Complete Profile screen (empty form)
                                      Navigator.pushNamed(
                                        context,
                                        '/complete-profile',
                                        arguments: {'signInMethod': 'email'},
                                      );
                                    },
                                  ),
                                ),

                                const SizedBox(height: 24),

                                // Divider with "OR"
                                Row(
                                  children: [
                                    Expanded(
                                      child: Divider(color: Colors.grey[300]),
                                    ),
                                    Padding(
                                      padding: const EdgeInsets.symmetric(
                                        horizontal: 16,
                                      ),
                                      child: Text(
                                        'OR',
                                        style: TextStyle(
                                          color: Colors.grey[600],
                                          fontWeight: FontWeight.w500,
                                        ),
                                      ),
                                    ),
                                    Expanded(
                                      child: Divider(color: Colors.grey[300]),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 24),

                                // Google Sign In
                                SizedBox(
                                  width: double.infinity,
                                  height: 50,
                                  child: OutlinedButton.icon(
                                    icon: Image.asset(
                                      'assets/google_icon.png',
                                      height: 24,
                                      width: 24,
                                      errorBuilder:
                                          (context, error, stackTrace) {
                                            return const Icon(
                                              Icons.g_mobiledata_rounded,
                                              size: 28,
                                            );
                                          },
                                    ),
                                    label: const Text(
                                      'Sign in with Google',
                                      style: TextStyle(fontSize: 15),
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: Colors.black87,
                                      side: BorderSide(
                                        color: Colors.grey[300]!,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                    onPressed: () => _handleAuthAction(
                                      () => _authService.loginWithGoogle(),
                                    ),
                                  ),
                                ),
                                const SizedBox(height: 12),

                                // Apple Sign In
                                SizedBox(
                                  width: double.infinity,
                                  height: 50,
                                  child: OutlinedButton.icon(
                                    icon: const Icon(Icons.apple, size: 24),
                                    label: const Text(
                                      'Sign in with Apple',
                                      style: TextStyle(fontSize: 15),
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: Colors.black,
                                      side: BorderSide(
                                        color: Colors.grey[300]!,
                                      ),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                    onPressed: () => _handleAuthAction(
                                      () => _authService.loginWithApple(),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Footer
                  Text(
                    '© 2025 EDS App',
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
