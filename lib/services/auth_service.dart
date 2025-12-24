import 'dart:convert';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  // Replace with your actual local IP or localhost.
  // For Android Emulator -> 10.0.2.2
  // For iOS Simulator -> localhost
  // For Web -> localhost
  static const String baseUrl = 'http://10.0.2.2:8000/api';

  final FirebaseAuth _auth = FirebaseAuth.instance;
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    // Use the web client ID from google-services.json
    serverClientId:
        '937666435898-iluqu91pjhkkhi1pimouvq7j8un180vi.apps.googleusercontent.com',
  );

  // Helper to sync with backend
  Future<Map<String, dynamic>> _syncWithBackend(
    User user,
    String loginMethod,
  ) async {
    try {
      final idToken = await user.getIdToken();

      final response = await http
          .post(
            Uri.parse('$baseUrl/verify_token.php'),
            body: jsonEncode({
              'idToken': idToken,
              'uid': user.uid,
              'email': user.email,
              'loginMethod': loginMethod,
            }),
            headers: {'Content-Type': 'application/json'},
          )
          .timeout(
            const Duration(seconds: 10),
            onTimeout: () {
              throw Exception(
                'Backend request timed out - server may not be accessible from emulator',
              );
            },
          );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        await saveSession(data, idToken!, loginMethod); // Save backend status
        return {'success': true, 'data': data};
      } else {
        return {
          'success': false,
          'message': 'Backend sync failed: ${response.body}',
        };
      }
    } catch (e) {
      return {'success': false, 'message': 'Sync error: $e'};
    }
  }

  Future<Map<String, dynamic>> loginWithEmail(
    String email,
    String password,
  ) async {
    try {
      final UserCredential userCredential = await _auth
          .signInWithEmailAndPassword(email: email, password: password);
      return await _syncWithBackend(userCredential.user!, 'email');
    } on FirebaseAuthException catch (e) {
      return {'success': false, 'message': e.message};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  Future<Map<String, dynamic>> registerWithEmail(
    String email,
    String password,
  ) async {
    try {
      final UserCredential userCredential = await _auth
          .createUserWithEmailAndPassword(email: email, password: password);
      return await _syncWithBackend(userCredential.user!, 'email');
    } on FirebaseAuthException catch (e) {
      return {'success': false, 'message': e.message};
    } catch (e) {
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  Future<Map<String, dynamic>> loginWithGoogle() async {
    try {
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      if (googleUser == null) {
        return {'success': false, 'message': 'Google Sign In cancelled'};
      }

      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;
      final AuthCredential credential = GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken,
        idToken: googleAuth.idToken,
      );

      final UserCredential userCredential = await _auth.signInWithCredential(
        credential,
      );
      return await _syncWithBackend(userCredential.user!, 'google');
    } catch (e) {
      return {'success': false, 'message': 'Google Sign In Error: $e'};
    }
  }

  Future<Map<String, dynamic>> loginWithApple() async {
    try {
      final appleCredential = await SignInWithApple.getAppleIDCredential(
        scopes: [
          AppleIDAuthorizationScopes.email,
          AppleIDAuthorizationScopes.fullName,
        ],
      );

      final oauthCredential = OAuthProvider("apple.com").credential(
        idToken: appleCredential.identityToken,
        accessToken: appleCredential.authorizationCode,
      );

      final UserCredential userCredential = await _auth.signInWithCredential(
        oauthCredential,
      );
      return await _syncWithBackend(userCredential.user!, 'apple');
    } catch (e) {
      return {'success': false, 'message': 'Apple Sign In Error: $e'};
    }
  }

  // Check if user is logged in and has a session
  Future<bool> isLoggedIn() async {
    final user = _auth.currentUser;
    if (user == null) return false;

    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('token');
    return token != null;
  }

  // Get current user status
  Future<String> getUserStatus() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('user_status') ?? 'inactive';
  }

  // Get valid token, refreshing if needed
  Future<String?> getValidToken({bool forceRefresh = false}) async {
    final user = _auth.currentUser;
    if (user == null) return null;

    final token = await user.getIdToken(forceRefresh);
    if (token != null) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('token', token);
    }
    return token;
  }

  // Validate session against both local and Firebase
  Future<bool> validateSession() async {
    final user = _auth.currentUser;
    if (user == null) {
      await logout();
      return false;
    }

    final token = await getValidToken();
    if (token == null) {
      await logout();
      return false;
    }

    return true;
  }

  Future<void> saveSession(
    Map<String, dynamic> data,
    String firebaseToken,
    String loginMethod,
  ) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', firebaseToken);

    // Convert UUID to string and handle nulls
    final userId = data['user']['id'];
    if (userId != null) {
      await prefs.setString('userId', userId.toString());
    }

    await prefs.setString('user_role', data['user']['role'] ?? 'user');
    await prefs.setString('user_status', data['user']['status'] ?? 'inactive');
    await prefs.setString('user_email', data['user']['email'] ?? '');
    await prefs.setString('login_method', loginMethod); // Save login method
  }

  Future<void> logout() async {
    await _auth.signOut();
    await _googleSignIn.signOut();
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
}
