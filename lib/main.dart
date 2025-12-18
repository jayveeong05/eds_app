import 'package:eds_app/screens/inactive_screen.dart';
import 'package:eds_app/screens/main_navigation.dart';
import 'package:eds_app/screens/login_screen.dart';
import 'package:eds_app/screens/complete_profile_screen.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Firebase.initializeApp();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'EDS App',
      debugShowCheckedModeBanner: false, // Remove debug banner
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF3F51B5), // EDS Royal Blue
          primary: const Color(0xFF3F51B5), // EDS Royal Blue
          secondary: const Color(0xFFE53935), // EDS Red
        ),
        useMaterial3: true,
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF3F51B5), // EDS Blue
          foregroundColor: Colors.white,
        ),
      ),
      initialRoute: '/login',
      routes: {
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
