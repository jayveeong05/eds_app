import 'package:eds_app/screens/inactive_screen.dart';
import 'package:eds_app/screens/main_navigation.dart';
import 'package:eds_app/screens/login_screen.dart';
import 'package:eds_app/screens/complete_profile_screen.dart';
import 'package:eds_app/screens/landing_screen.dart';
import 'package:eds_app/services/auth_service.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({super.key});

  @override
  State<MyApp> createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  String _initialRoute = '/landing';
  bool _isCheckingSession = true;
  final _authService = AuthService();

  @override
  void initState() {
    super.initState();
    _checkSession();
  }

  Future<void> _checkSession() async {
    try {
      final isLoggedIn = await _authService.isLoggedIn();
      if (isLoggedIn) {
        final status = await _authService.getUserStatus();
        if (status == 'active') {
          _initialRoute = '/dashboard';
        } else {
          _initialRoute = '/inactive';
        }
      }
    } catch (e) {
      debugPrint('Error checking session: $e');
    } finally {
      if (mounted) {
        setState(() {
          _isCheckingSession = false;
        });
      }
    }
  }

  Widget _getHomeWidget() {
    switch (_initialRoute) {
      case '/dashboard':
        return const MainNavigation(); // New 5-button nav with HomeScreen
      case '/inactive':
        return const InactiveScreen(); // 2-button nav for inactive users
      default:
        return const LandingScreen();
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isCheckingSession) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          scaffoldBackgroundColor: const Color(0xFFF0EEE9),
          useMaterial3: true,
        ),
        home: const Scaffold(
          body: Center(
            child: CircularProgressIndicator(color: Color(0xFF2C3E50)),
          ),
        ),
      );
    }

    return MaterialApp(
      title: 'EDS App',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        // Cloud Dancer Color System
        scaffoldBackgroundColor: const Color(0xFFF0EEE9), // Cloud Dancer
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2C3E50), // Deep Slate
          primary: const Color(0xFF2C3E50), // Deep Slate
          secondary: const Color(0xFF8A9A5B), // Soft Sage
          surface: const Color(0xFFFFFFFF), // Pure White
        ),
        useMaterial3: true,

        // Typography - Inter Font Family
        textTheme: GoogleFonts.interTextTheme().copyWith(
          // Main Title: 28pt, Bold, Letter-spacing: -0.5px
          headlineLarge: GoogleFonts.inter(
            fontSize: 28,
            fontWeight: FontWeight.bold,
            letterSpacing: -0.5,
            color: const Color(0xFF2C3E50), // Deep Slate
          ),
          // Page Headers: 24pt
          headlineMedium: GoogleFonts.inter(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF2C3E50), // Deep Slate
          ),
          // Card Titles: 22pt
          headlineSmall: GoogleFonts.inter(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF2C3E50), // Deep Slate
          ),
          // Large Buttons: 18pt, Semibold
          titleLarge: GoogleFonts.inter(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: const Color(0xFF2C3E50), // Deep Slate
          ),
          // Section Titles: 16pt, Semibold
          titleMedium: GoogleFonts.inter(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: const Color(0xFF2C3E50), // Deep Slate
          ),
          // Body Large/Input Labels: 14px, Medium
          titleSmall: GoogleFonts.inter(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
          // Main Body Text: 16px
          bodyLarge: GoogleFonts.inter(
            fontSize: 16,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
          // Standard Body/Metadata: 14px
          bodyMedium: GoogleFonts.inter(
            fontSize: 14,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
          // Small Helper Text: 12px
          bodySmall: GoogleFonts.inter(
            fontSize: 12,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
          // Button Label: 14px, Bold
          labelLarge: GoogleFonts.inter(
            fontSize: 14,
            fontWeight: FontWeight.bold,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
          // Medium Labels: 12px
          labelMedium: GoogleFonts.inter(
            fontSize: 12,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
          // Smallest Labels: 10px
          labelSmall: GoogleFonts.inter(
            fontSize: 10,
            color: const Color(0xFFA39382), // Warm Taupe
          ),
        ),

        // AppBar Theme - Flat and transparent
        appBarTheme: AppBarTheme(
          backgroundColor: Colors.transparent,
          elevation: 0,
          foregroundColor: const Color(0xFF2C3E50),
          titleTextStyle: GoogleFonts.inter(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF2C3E50),
          ),
        ),

        // Card Theme - 32.0 radius, no elevation, custom shadow
        cardTheme: const CardThemeData(
          color: Color(0xFFFFFFFF),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.all(Radius.circular(32.0)),
          ),
        ),

        // Button Themes
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF8A9A5B), // Soft Sage
            foregroundColor: Colors.white,
            elevation: 0,
            shadowColor: Colors.black.withOpacity(0.04),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(32.0),
            ),
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
          ),
        ),

        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFFFFFFFF),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16.0),
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16.0),
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(16.0),
            borderSide: const BorderSide(
              color: Color(0xFF2C3E50),
              width: 2,
            ), // Deep Slate
          ),
        ),
      ),
      home: _getHomeWidget(),
      routes: {
        '/landing': (context) => const LandingScreen(),
        '/login': (context) => const LoginScreen(),
        '/dashboard': (context) => const MainNavigation(),
        '/inactive': (context) => const InactiveScreen(),
      },
      onGenerateRoute: (settings) {
        if (settings.name == '/complete-profile') {
          final args = settings.arguments as Map<String, dynamic>?;
          return MaterialPageRoute(
            builder: (context) => CompleteProfileScreen(
              signInMethod: args?['signInMethod'] ?? 'email',
              email: args?['email'],
              password: args?['password'],
              name: args?['name'],
            ),
          );
        }
        return null;
      },
    );
  }
}
