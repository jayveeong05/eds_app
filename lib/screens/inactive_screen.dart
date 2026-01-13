import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:qr_flutter/qr_flutter.dart';
import 'profile_screen.dart';

// Main navigation wrapper for inactive users
class InactiveScreen extends StatefulWidget {
  const InactiveScreen({super.key});

  @override
  State<InactiveScreen> createState() => _InactiveScreenState();
}

class _InactiveScreenState extends State<InactiveScreen> {
  int _selectedIndex = 0; // Home for inactive

  final List<Widget> _screens = [
    const InactiveHomeContent(), // Home for inactive users
    const ProfileScreen(),
  ];

  void _onItemTapped(int index) {
    setState(() {
      _selectedIndex = index;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // backgroundColor: const Color(0xFFF0EEE9),
      body: Stack(
        children: [
          // Main content
          IndexedStack(index: _selectedIndex, children: _screens),
          // Floating bottom nav
          Positioned(
            left: 0,
            right: 0,
            bottom: 20,
            child: _buildModernBottomNav(),
          ),
        ],
      ),
    );
  }

  Widget _buildModernBottomNav() {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
      padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 4),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface, // Pure White
        borderRadius: BorderRadius.circular(30),
        boxShadow: [
          // Top shadow for depth
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
          // Bottom shadow for floating effect
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 24,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _buildNavItem(0, Icons.home_rounded, 'Home'),
          _buildNavItem(1, Icons.person, 'Profile'),
        ],
      ),
    );
  }

  Widget _buildNavItem(int index, IconData icon, String label) {
    final isSelected = _selectedIndex == index;
    final primaryColor = Theme.of(context).colorScheme.secondary; // EDS Red
    final inactiveColor = Theme.of(
      context,
    ).colorScheme.onSurface.withOpacity(0.5);

    return Expanded(
      child: GestureDetector(
        onTap: () => _onItemTapped(index),
        behavior: HitTestBehavior.opaque,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeInOut,
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Icon with rounded pill background for active state
              AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                curve: Curves.easeInOut,
                padding: EdgeInsets.symmetric(
                  horizontal: isSelected ? 12 : 0,
                  vertical: isSelected ? 5 : 0,
                ),
                decoration: BoxDecoration(
                  color: isSelected
                      ? primaryColor.withOpacity(0.1) // Deep Slate tint
                      : Colors.transparent,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Icon(
                  icon,
                  color: isSelected ? primaryColor : inactiveColor,
                  size: 22,
                ),
              ),
              const SizedBox(height: 2),
              // Label
              AnimatedDefaultTextStyle(
                duration: const Duration(milliseconds: 300),
                curve: Curves.easeInOut,
                style: TextStyle(
                  color: isSelected ? primaryColor : inactiveColor,
                  fontSize: 11,
                  fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
                ),
                child: Text(label),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// Home content for inactive users (QR code and activation)
class InactiveHomeContent extends StatefulWidget {
  const InactiveHomeContent({super.key});

  @override
  State<InactiveHomeContent> createState() => _InactiveHomeContentState();
}

class _InactiveHomeContentState extends State<InactiveHomeContent> {
  String? _userId;
  bool _isChecking = false;

  @override
  void initState() {
    super.initState();
    _loadUserId();
  }

  Future<void> _loadUserId() async {
    final prefs = await SharedPreferences.getInstance();
    final userId = prefs.getString('userId');
    setState(() {
      _userId = userId;
    });
  }

  Future<void> _checkActivationStatus() async {
    setState(() {
      _isChecking = true;
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');

      final response = await http.post(
        Uri.parse('http://10.0.2.2:8000/api/check_activation.php'),
        body: jsonEncode({'idToken': token}),
        headers: {'Content-Type': 'application/json'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['status'] == 'active') {
          // User is now active, navigate to dashboard
          if (mounted) {
            Navigator.pushReplacementNamed(context, '/dashboard');
          }
        } else {
          // Still inactive
          if (mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Account still pending approval'),
                backgroundColor: Colors.orange,
              ),
            );
          }
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    } finally {
      setState(() {
        _isChecking = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // backgroundColor: const Color(0xFFF0EEE9), // REMOVED: Let theme handle it
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.only(
              left: 32.0,
              right: 32.0,
              top: 32.0,
              bottom: 100.0, // Extra padding for bottom nav
            ),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // Header with EDS Logo
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  child: Center(
                    child: Image.asset(
                      'assets/images/eds_logo.png',
                      height: 80,
                      fit: BoxFit.contain,
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                // Title - Bold geometric sans-serif
                Text(
                  "Account Pending Approval",
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    // color: Theme.of(context).colorScheme.primary, // REMOVED: Use default text color
                    fontWeight: FontWeight.bold,
                    letterSpacing: -0.5, // Tight geometric spacing
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 16),

                // Subtitle
                Text(
                  "Show this QR code to admin for approval",
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 32), // Airy spacing
                // Large Floating Card with QR Code
                Container(
                  padding: const EdgeInsets.all(40),
                  decoration: BoxDecoration(
                    color: Theme.of(context).colorScheme.surface,
                    borderRadius: BorderRadius.circular(
                      32,
                    ), // Increased to 32px
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.04), // 4% opacity
                        blurRadius: 32, // 32px blur for floating effect
                        offset: const Offset(0, 8),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      // QR Code (removed pending icon)
                      if (_userId != null)
                        QrImageView(
                          data: 'EDSAPP:USER:$_userId',
                          version: QrVersions.auto,
                          size: 250.0,
                          backgroundColor: Colors.white,
                        )
                      else
                        CircularProgressIndicator(
                          color: Theme.of(context).colorScheme.primary,
                        ), // EDS Blue

                      const SizedBox(height: 32), // Airy spacing
                      // User ID Display inside card
                      if (_userId != null)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 20,
                            vertical: 12,
                          ),
                          decoration: BoxDecoration(
                            color: Theme.of(context).colorScheme.primary
                                .withOpacity(
                                  0.1,
                                ), // Increased opacity for visibility
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                Icons.fingerprint,
                                size: 20,
                                color: Theme.of(
                                  context,
                                ).colorScheme.primary, // EDS Blue
                              ),
                              const SizedBox(width: 8),
                              Text(
                                'ID: ${_userId!.substring(0, 8)}...',
                                style: TextStyle(
                                  fontSize: 14,
                                  color: Theme.of(
                                    context,
                                  ).colorScheme.onSurface,
                                  fontFamily: 'monospace',
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),

                const SizedBox(height: 32), // Airy spacing
                // Check Activation Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: _isChecking ? null : _checkActivationStatus,
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 18),
                      backgroundColor: Theme.of(
                        context,
                      ).colorScheme.primary, // EDS Blue
                      foregroundColor: Colors.white,
                      elevation: 4,
                      shadowColor: Theme.of(
                        context,
                      ).colorScheme.primary.withOpacity(0.3), // EDS Blue
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(32), // 32px radius
                      ),
                    ),
                    icon: _isChecking
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Icon(Icons.refresh),
                    label: Text(
                      _isChecking ? 'Checking...' : 'Check Activation Status',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        letterSpacing: 0.5,
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 20),

                // Help Text
                Text(
                  'After admin approval, tap the button above to activate your account',
                  style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: Theme.of(context).colorScheme.onSurfaceVariant,
                  ),
                  textAlign: TextAlign.center,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
