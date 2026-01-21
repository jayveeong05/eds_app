class PrinterRecommendation {
  final String model;
  final int score;
  final String reason;
  final String productUrl;

  PrinterRecommendation({
    required this.model,
    required this.score,
    required this.reason,
    required this.productUrl,
  });

  factory PrinterRecommendation.fromJson(Map<String, dynamic> json) {
    // Parse score - handle both "95%" string and 95 int
    int score = 0;
    if (json['score'] is int) {
      score = json['score'];
    } else if (json['score'] is String) {
      final scoreStr = (json['score'] as String).replaceAll('%', '').trim();
      score = int.tryParse(scoreStr) ?? 0;
    }

    // Parse and validate product URL
    String productUrl = json['product_url'] ?? '';
    // Normalize invalid URLs (like "-", "null", empty) to empty string
    if (productUrl == '-' ||
        productUrl.toLowerCase() == 'null' ||
        productUrl.trim().isEmpty) {
      productUrl = '';
    } else {
      // Validate that the URL has a proper scheme
      try {
        final uri = Uri.parse(productUrl);
        if (!uri.hasScheme || (uri.scheme != 'http' && uri.scheme != 'https')) {
          productUrl = '';
        }
      } catch (e) {
        productUrl = '';
      }
    }

    return PrinterRecommendation(
      model: json['model'] ?? '',
      score: score,
      reason: json['reason'] ?? '',
      productUrl: productUrl,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'model': model,
      'score': score,
      'reason': reason,
      'product_url': productUrl,
    };
  }
}

class PrinterChatResponse {
  final bool success;
  final String responseType; // "question" or "recommendation"
  final String? message; // For questions
  final List<PrinterRecommendation>? recommendations; // For recommendations

  PrinterChatResponse({
    required this.success,
    required this.responseType,
    this.message,
    this.recommendations,
  });

  factory PrinterChatResponse.fromJson(Map<String, dynamic> json) {
    if (json['response_type'] == 'recommendation') {
      final result = json['result'] as Map<String, dynamic>;
      final recs = <PrinterRecommendation>[];

      if (result.containsKey('top1')) {
        recs.add(PrinterRecommendation.fromJson(result['top1']));
      }
      if (result.containsKey('top2')) {
        recs.add(PrinterRecommendation.fromJson(result['top2']));
      }
      if (result.containsKey('top3')) {
        recs.add(PrinterRecommendation.fromJson(result['top3']));
      }

      return PrinterChatResponse(
        success: json['success'] ?? true,
        responseType: 'recommendation',
        recommendations: recs,
      );
    } else {
      return PrinterChatResponse(
        success: json['success'] ?? true,
        responseType: 'question',
        message: json['message'] ?? '',
      );
    }
  }

  bool get isRecommendation => responseType == 'recommendation';
  bool get isQuestion => responseType == 'question';
}
