class ChatMessage {
  final String id;
  final String messageText;
  final bool isUserMessage;
  final bool isFavorite;
  final DateTime createdAt;

  ChatMessage({
    required this.id,
    required this.messageText,
    required this.isUserMessage,
    required this.isFavorite,
    required this.createdAt,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json) {
    return ChatMessage(
      id: json['id'].toString(),
      messageText: json['message_text'],
      isUserMessage: json['is_user_message'],
      isFavorite: json['is_favorite'] ?? false,
      createdAt: DateTime.parse(json['created_at']),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'message_text': messageText,
      'is_user_message': isUserMessage,
      'is_favorite': isFavorite,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
