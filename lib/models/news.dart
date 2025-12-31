class News {
  final String id;
  final String? userId;
  final String title;
  final String shortDescription;
  final String details;
  final String link;
  final String imageUrl;
  final DateTime createdAt;

  News({
    required this.id,
    this.userId,
    required this.title,
    required this.shortDescription,
    required this.details,
    required this.link,
    required this.imageUrl,
    required this.createdAt,
  });

  factory News.fromJson(Map<String, dynamic> json) {
    return News(
      id: json['id'] as String,
      userId: json['user_id'] as String?,
      title: json['title'] as String,
      shortDescription: json['short_description'] as String,
      details: json['details'] as String,
      link: json['link'] as String,
      imageUrl: json['image_url'] as String,
      createdAt: DateTime.parse(json['created_at'] as String),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'user_id': userId,
      'title': title,
      'short_description': shortDescription,
      'details': details,
      'link': link,
      'image_url': imageUrl,
      'created_at': createdAt.toIso8601String(),
    };
  }
}
