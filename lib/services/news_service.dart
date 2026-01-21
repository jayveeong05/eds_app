import 'dart:convert';
import 'package:http/http.dart' as http;
import '../config/environment.dart';
import '../models/news.dart';

class NewsService {
  // Use centralized environment configuration
  static String get baseUrl => Environment.apiUrl;

  /// Fetch news from the backend API
  /// Returns a list of News objects ordered by newest first
  static Future<List<News>> fetchNews({int limit = 50}) async {
    try {
      final uri = Uri.parse('$baseUrl/get_news.php?limit=$limit');
      final response = await http.get(uri);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        if (data['success'] == true) {
          final newsData = data['data'] as List;
          return newsData.map((json) => News.fromJson(json)).toList();
        } else {
          throw Exception(data['message'] ?? 'Failed to fetch news');
        }
      } else {
        throw Exception('Server error: ${response.statusCode}');
      }
    } catch (e) {
      throw Exception('Failed to load news: $e');
    }
  }
}
