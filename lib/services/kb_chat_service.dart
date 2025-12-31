import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/chat_message.dart';

class KbChatService extends ChangeNotifier {
  static const String baseUrl = 'http://10.0.2.2:8000/api';

  List<ChatMessage> _messages = [];
  bool _isLoading = false;
  String? _error;

  List<ChatMessage> get messages => _messages;
  bool get isLoading => _isLoading;
  String? get error => _error;

  /// Get user token from SharedPreferences
  Future<String?> _getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  /// Load chat history from server
  Future<void> loadMessages() async {
    try {
      _error = null;
      notifyListeners();

      final token = await _getToken();
      if (token == null) {
        throw Exception('Not authenticated');
      }

      final response = await http.get(
        Uri.parse('$baseUrl/get_kb_messages.php?token=$token&limit=100'),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          _messages = (data['data'] as List)
              .map((json) => ChatMessage.fromJson(json))
              .toList();
          notifyListeners();
        } else {
          throw Exception(data['message'] ?? 'Failed to load messages');
        }
      } else {
        throw Exception('Server error: ${response.statusCode}');
      }
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// Send a message to the AI
  Future<void> sendMessage(String text) async {
    if (text.trim().isEmpty) return;

    _error = null;
    _isLoading = true;
    notifyListeners();

    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Not authenticated');
      }

      final response = await http.post(
        Uri.parse('$baseUrl/send_kb_message.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'token': token, 'message': text.trim()}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          // Add both user and bot messages to local list
          final userMsg = ChatMessage.fromJson(data['user_message']);
          final botMsg = ChatMessage.fromJson(data['bot_message']);

          _messages.add(userMsg);
          _messages.add(botMsg);
          notifyListeners();
        } else {
          throw Exception(data['message'] ?? 'Failed to send message');
        }
      } else {
        throw Exception('Server error: ${response.statusCode}');
      }
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Toggle favorite status for a message
  Future<void> toggleFavorite(ChatMessage message) async {
    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Not authenticated');
      }

      final response = await http.post(
        Uri.parse('$baseUrl/toggle_kb_favorite.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'token': token, 'message_id': message.id}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          // Update local message
          final index = _messages.indexWhere((m) => m.id == message.id);
          if (index != -1) {
            _messages[index] = ChatMessage(
              id: message.id,
              messageText: message.messageText,
              isUserMessage: message.isUserMessage,
              isFavorite: data['is_favorite'],
              createdAt: message.createdAt,
            );
            notifyListeners();
          }
        }
      }
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// Get favorite messages
  Future<List<ChatMessage>> getFavorites() async {
    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Not authenticated');
      }

      final response = await http.get(
        Uri.parse('$baseUrl/get_kb_favorites.php?token=$token'),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          return (data['data'] as List)
              .map((json) => ChatMessage.fromJson(json))
              .toList();
        }
      }
      return [];
    } catch (e) {
      _error = e.toString();
      notifyListeners();
      return [];
    }
  }

  /// Clear all chat history
  Future<void> clearHistory() async {
    try {
      final token = await _getToken();
      if (token == null) {
        throw Exception('Not authenticated');
      }

      final response = await http.post(
        Uri.parse('$baseUrl/clear_kb_history.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'token': token}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          _messages.clear();
          notifyListeners();
        }
      }
    } catch (e) {
      _error = e.toString();
      notifyListeners();
    }
  }

  /// Clear error message
  void clearError() {
    _error = null;
    notifyListeners();
  }
}
