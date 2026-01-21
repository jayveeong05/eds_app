import 'dart:convert';
import 'package:firebase_auth/firebase_auth.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart';
import '../config/environment.dart';

class AuthService {
  final FirebaseAuth _auth = FirebaseAuth.instance;
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
    serverClientId:
        '937666435898-iluqu91pjhkkhi1pimouvq7j8un180vi.apps.googleusercontent.com',
  );

  // Get current user
  User? get currentUser => _auth.currentUser;

  // Helper to sync with backend
  Future<Map<String, dynamic>> _syncWithBackend(
    User user,
    String loginMethod,
  ) async {
    try {
      debugPrint('🔄 [SYNC] Starting backend sync - Method: $loginMethod');

      final idToken = await user.getIdToken();
      debugPrint('🔄 [SYNC] Got ID token: ${idToken?.substring(0, 20)}...');

      if (idToken == null) {
        debugPrint('🔄 [SYNC] ❌ No ID token available');
        return {
          'success': false,
          'message': 'Failed to get authentication token',
        };
      }

      debugPrint(
        '🔄 [SYNC] Sending request to: ${Environment.apiUrl}/verify_token.php',
      );
      final response = await http.post(
        Uri.parse('${Environment.apiUrl}/verify_token.php'),
        body: jsonEncode({'idToken': idToken, 'signInMethod': loginMethod}),
        headers: {'Content-Type': 'application/json'},
      );

      debugPrint('🔄 [SYNC] Response status: ${response.statusCode}');
      debugPrint('🔄 [SYNC] Response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        debugPrint('🔄 [SYNC] Decoded data: $data');
        debugPrint('🔄 [SYNC] Data type: ${data.runtimeType}');
        debugPrint('🔄 [SYNC] Success value: ${data['success']}');
        debugPrint('🔄 [SYNC] Success type: ${data['success'].runtimeType}');

        if (data['success'] == true) {
          debugPrint('🔄 [SYNC] ✅ Backend verification successful');

          // Store login method in SharedPreferences
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('login_method', loginMethod);
          await saveSession(data, idToken, loginMethod); // Save backend status
          return {'success': true, 'data': data};
        } else {
          debugPrint(
            '🔄 [SYNC] ❌ Backend verification failed: ${data['message']}',
          );
          return {
            'success': false,
            'message': data['message'] ?? 'Verification failed',
          };
        }
      } else {
        debugPrint('🔄 [SYNC] ❌ Server error: ${response.statusCode}');
        return {'success': false, 'message': 'Server error'};
      }
    } catch (e, stackTrace) {
      debugPrint('🔄 [SYNC] ❌ EXCEPTION: $e');
      debugPrint('🔄 [SYNC] Stack trace: $stackTrace');
      return {'success': false, 'message': 'Error: $e'};
    }
  }

  Future<Map<String, dynamic>> loginWithEmail(
    String email,
    String password,
  ) async {
    try {
      debugPrint('📧 [EMAIL_LOGIN] Attempting login: $email');

      final UserCredential userCredential = await _auth
          .signInWithEmailAndPassword(email: email, password: password);

      debugPrint(
        '📧 [EMAIL_LOGIN] Firebase auth successful, syncing with backend...',
      );
      return await _syncWithBackend(userCredential.user!, 'email');
    } on FirebaseAuthException catch (e) {
      debugPrint('📧 [EMAIL_LOGIN] ❌ Firebase error: ${e.code} - ${e.message}');
      return {'success': false, 'message': e.message};
    } catch (e) {
      debugPrint('📧 [EMAIL_LOGIN] ❌ Exception: $e');
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
      // Sign in with Google
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      if (googleUser == null) {
        return {'success': false, 'message': 'Google Sign In cancelled'};
      }

      // Get authentication credentials
      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;

      // Check if we have valid tokens
      if (googleAuth.accessToken == null || googleAuth.idToken == null) {
        return {
          'success': false,
          'message': 'Failed to get Google credentials',
        };
      }

      // Create Firebase credential
      final AuthCredential credential = GoogleAuthProvider.credential(
        accessToken: googleAuth.accessToken!,
        idToken: googleAuth.idToken!,
      );

      // Sign in to Firebase
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
