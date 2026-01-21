import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb, kDebugMode, kReleaseMode;

/// Environment configuration for API endpoints
/// Automatically detects platform and build mode to use correct backend URL
class Environment {
  /// Get the base URL for API calls based on platform and build mode
  static String get baseUrl {
    // For web builds, always use production
    if (kIsWeb) {
      return 'https://edsapp.edsoffice.com.my';
    }

    if (kDebugMode) {
      if (Platform.isAndroid) {
        return 'http://10.0.2.2:8000'; // Android emulator localhost
      }
      return 'http://localhost:8000'; // iOS simulator
    }
    return 'https://edsapp.edsoffice.com.my';
  }

  /// Get the full API URL with /api path
  static String get apiUrl => '$baseUrl/api';

  /// Get the admin panel URL
  static String get adminUrl => '$baseUrl/admin';

  /// Check if running in development mode
  static bool get isDevelopment => !kIsWeb && !kReleaseMode;

  /// Check if running on web
  static bool get isWeb => kIsWeb;

  /// Check if running in production
  static bool get isProduction => kReleaseMode;
}
