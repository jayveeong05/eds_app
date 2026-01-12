import 'package:eds_app/theme/eds_theme.dart';
import 'package:eds_app/screens/inactive_screen.dart';
import 'package:eds_app/screens/main_navigation.dart';
import 'package:eds_app/screens/login_screen.dart';
import 'package:eds_app/screens/registration_screen.dart';
import 'package:eds_app/screens/landing_screen.dart';
import 'package:eds_app/services/auth_service.dart';
import 'package:eds_app/services/kb_chat_service.dart';
import 'package:eds_app/services/printer_chat_service.dart';
import 'package:eds_app/providers/theme_provider.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

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
        theme: EDSTheme.lightTheme,
        home: const Scaffold(
          body: Center(
            child: CircularProgressIndicator(color: EDSTheme.deepRoyalBlue),
          ),
        ),
      );
    }

    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => ThemeProvider()),
        ChangeNotifierProvider(create: (_) => KbChatService()),
        ChangeNotifierProvider(create: (_) => PrinterChatService()),
      ],
      child: Consumer<ThemeProvider>(
        builder: (context, themeProvider, _) {
          return MaterialApp(
            title: 'EDS App',
            debugShowCheckedModeBanner: false,
            theme: EDSTheme.lightTheme,
            darkTheme: EDSTheme.darkTheme,
            themeMode: themeProvider.themeMode,
            home: _getHomeWidget(),
            routes: {
              '/landing': (context) => const LandingScreen(),
              '/login': (context) => const LoginScreen(),
              '/dashboard': (context) => const MainNavigation(),
              '/inactive': (context) => const InactiveScreen(),
            },
            onGenerateRoute: (settings) {
              if (settings.name == '/register') {
                final args = settings.arguments as Map<String, dynamic>?;
                return MaterialPageRoute(
                  builder: (context) => RegistrationScreen(
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
        },
      ),
    );
  }
}
