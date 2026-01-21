import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';
import '../models/printer_recommendation.dart';
import '../config/environment.dart';

class PrinterChatService extends ChangeNotifier {
  // Use centralized environment configuration
  static String get baseUrl => Environment.apiUrl;

  List<Map<String, dynamic>> _chatHistory = [];
  bool _isLoading = false;
  String? _error;
  List<PrinterRecommendation>? _recommendations;

  List<Map<String, dynamic>> get chatHistory => _chatHistory;
  bool get isLoading => _isLoading;
  String? get error => _error;
  List<PrinterRecommendation>? get recommendations => _recommendations;
  bool get hasRecommendations =>
      _recommendations != null && _recommendations!.isNotEmpty;

  // Secure storage instance
  static const _storage = FlutterSecureStorage();
  static const _deviceIdKey = 'printer_device_id';

  /// Get unique device identifier using secure UUID-based approach
  /// This persists across app reinstalls and is privacy-compliant
  Future<String> _getDeviceId() async {
    try {
      // Try to retrieve existing device ID
      String? deviceId = await _storage.read(key: _deviceIdKey);

      // If no device ID exists, generate a new one
      if (deviceId == null || deviceId.isEmpty) {
        deviceId = const Uuid().v4();
        await _storage.write(key: _deviceIdKey, value: deviceId);
        debugPrint('Generated new device ID: ${deviceId.substring(0, 8)}...');
      }

      return deviceId;
    } catch (e) {
      debugPrint('Error accessing secure storage: $e');
      // Fallback to timestamp-based ID if secure storage fails
      return 'fallback_device_${DateTime.now().millisecondsSinceEpoch}';
    }
  }

  /// Send message to printer chat
  Future<void> sendMessage(String message) async {
    if (message.trim().isEmpty) return;

    _error = null;
    _isLoading = true;
    notifyListeners();

    try {
      final deviceId = await _getDeviceId();

      // Add user message to history
      _chatHistory.add({
        'role': 'user',
        'content': message,
        'timestamp': DateTime.now(),
      });
      notifyListeners();

      // Build API history format (only role and content, no timestamp)
      final historyForApi = _chatHistory
          .where(
            (msg) => msg['role'] != 'error' && msg['recommendations'] == null,
          )
          .map((msg) => {'role': msg['role'], 'content': msg['content']})
          .toList();

      final response = await http.post(
        Uri.parse('$baseUrl/printer_chat.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'device_id': deviceId,
          'message': message,
          'history': historyForApi.length > 1
              ? historyForApi.sublist(
                  0,
                  historyForApi.length - 1,
                ) // Exclude current message
              : [],
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        if (data['success'] != true) {
          throw Exception(data['message'] ?? 'Request failed');
        }

        final chatResponse = PrinterChatResponse.fromJson(data);

        if (chatResponse.isRecommendation) {
          // Store recommendations
          _recommendations = chatResponse.recommendations;

          // Add recommendation to chat history
          _chatHistory.add({
            'role': 'assistant',
            'content': 'Here are my top recommendations for you:',
            'timestamp': DateTime.now(),
            'recommendations': chatResponse.recommendations,
          });
        } else {
          // Add question to chat history
          _chatHistory.add({
            'role': 'assistant',
            'content': chatResponse.message ?? '',
            'timestamp': DateTime.now(),
          });
        }

        notifyListeners();
      } else {
        throw Exception('Server error: ${response.statusCode}');
      }
    } catch (e) {
      _error = e.toString();
      _chatHistory.add({
        'role': 'error',
        'content': 'Sorry, I encountered an error. Please try again.',
        'timestamp': DateTime.now(),
      });
      notifyListeners();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Clear chat and start new conversation
  void clearChat() {
    _chatHistory.clear();
    _recommendations = null;
    _error = null;
    notifyListeners();
  }

  /// Reset error state
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
