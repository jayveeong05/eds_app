import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter_markdown/flutter_markdown.dart';
import '../models/chat_message.dart';
import '../screens/pdf_viewer_screen.dart';

class ChatMessageBubble extends StatelessWidget {
  final ChatMessage message;
  final VoidCallback onFavorite;
  final VoidCallback onCopy;

  const ChatMessageBubble({
    super.key,
    required this.message,
    required this.onFavorite,
    required this.onCopy,
  });

  @override
  Widget build(BuildContext context) {
    final isUser = message.isUserMessage;
    final theme = Theme.of(context);

    // Parse message to extract text and sources
    final parsedMessage = _parseMessageWithSources(message.messageText);

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
        constraints: BoxConstraints(
          maxWidth: MediaQuery.of(context).size.width * 0.75,
        ),
        child: Column(
          crossAxisAlignment: isUser
              ? CrossAxisAlignment.end
              : CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: BoxDecoration(
                color: isUser
                    ? Colors.lightBlue[100] // User message light blue
                    : theme.colorScheme.surface,
                borderRadius: BorderRadius.circular(16).copyWith(
                  bottomRight: isUser ? const Radius.circular(4) : null,
                  bottomLeft: !isUser ? const Radius.circular(4) : null,
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Main message text with markdown support for bot messages
                  if (isUser)
                    Text(
                      parsedMessage['text']!,
                      style: theme.textTheme.bodyLarge?.copyWith(
                        color: Colors.black87,
                        fontSize: 15,
                      ),
                    )
                  else
                    MarkdownBody(
                      data: parsedMessage['text']!,
                      selectable: true,
                      styleSheet: MarkdownStyleSheet(
                        p: theme.textTheme.bodyLarge?.copyWith(fontSize: 15),
                        strong: theme.textTheme.bodyLarge?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                        em: theme.textTheme.bodyLarge?.copyWith(
                          fontStyle: FontStyle.italic,
                        ),
                        listBullet: TextStyle(
                          color: theme.colorScheme.secondary,
                        ),
                        code: TextStyle(
                          backgroundColor:
                              theme.colorScheme.surfaceContainerHighest,
                          fontFamily: 'monospace',
                          fontSize: 14,
                          color: theme.colorScheme.primary,
                        ),
                        h1: theme.textTheme.displaySmall?.copyWith(
                          fontSize: 20,
                        ),
                        h2: theme.textTheme.titleLarge?.copyWith(fontSize: 18),
                        h3: theme.textTheme.titleMedium?.copyWith(fontSize: 16),
                        blockquote: theme.textTheme.bodyMedium?.copyWith(
                          color: theme.colorScheme.onSurface.withOpacity(0.6),
                          fontStyle: FontStyle.italic,
                        ),
                        blockquoteDecoration: BoxDecoration(
                          color: theme.colorScheme.surface,
                          border: Border(
                            left: BorderSide(
                              color: theme.colorScheme.secondary,
                              width: 4,
                            ),
                          ),
                        ),
                      ),
                    ),

                  // Source citations
                  if (parsedMessage['sources'].isNotEmpty && !isUser) ...[
                    const SizedBox(height: 12),
                    Divider(height: 1, color: Colors.grey[200]),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: (parsedMessage['sources'] as List)
                          .map((source) => _buildSourceChip(context, source))
                          .toList()
                          .cast<Widget>(),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 4),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  _formatTimestamp(message.createdAt),
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurface.withOpacity(0.6),
                  ),
                ),
                if (!isUser) ...[
                  const SizedBox(width: 8),
                  InkWell(
                    onTap: onFavorite,
                    borderRadius: BorderRadius.circular(12),
                    child: Padding(
                      padding: const EdgeInsets.all(4),
                      child: Icon(
                        message.isFavorite ? Icons.star : Icons.star_border,
                        size: 16,
                        color: message.isFavorite
                            ? Colors.amber
                            : theme.colorScheme.onSurface.withOpacity(0.4),
                      ),
                    ),
                  ),
                  InkWell(
                    onTap: onCopy,
                    borderRadius: BorderRadius.circular(12),
                    child: Padding(
                      padding: const EdgeInsets.all(4),
                      child: Icon(
                        Icons.copy,
                        size: 16,
                        color: theme.colorScheme.onSurface.withOpacity(0.4),
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ],
        ),
      ),
    );
  }

  /// Parse message to extract main text and source citations
  Map<String, dynamic> _parseMessageWithSources(String messageText) {
    final sources = <String>[];
    String cleanedText = messageText;

    // Pattern: [SOURCE: filename.pdf] or [ SOURCE: filename.pdf ]
    final sourcePattern = RegExp(r'\[\s*SOURCE:\s*([^\]]+?)\s*\]');
    final matches = sourcePattern.allMatches(messageText);

    for (final match in matches) {
      final filename = match.group(1)?.trim();
      if (filename != null && filename.isNotEmpty) {
        // Debug: print extracted filename
        debugPrint('Extracted source filename: $filename');
        sources.add(filename);
      }
    }

    // Remove source citations from main text
    cleanedText = messageText.replaceAll(sourcePattern, '').trim();

    return {'text': cleanedText, 'sources': sources};
  }

  /// Build a clickable source chip
  Widget _buildSourceChip(BuildContext context, String filename) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: () => _openPDF(context, filename),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: theme.colorScheme.secondary.withOpacity(0.05),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: theme.colorScheme.secondary.withOpacity(0.2),
            width: 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.picture_as_pdf,
              size: 14,
              color: theme.colorScheme.secondary,
            ),
            const SizedBox(width: 6),
            Flexible(
              child: Text(
                _truncateFilename(filename),
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: theme.colorScheme.onSurface,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            const SizedBox(width: 4),
            Icon(
              Icons.open_in_new,
              size: 12,
              color: theme.colorScheme.secondary,
            ),
          ],
        ),
      ),
    );
  }

  /// Truncate long filenames for display
  String _truncateFilename(String filename) {
    if (filename.length <= 30) return filename;
    final parts = filename.split('.');
    if (parts.length > 1) {
      final name = parts.sublist(0, parts.length - 1).join('.');
      final ext = parts.last;
      if (name.length > 25) {
        return '${name.substring(0, 25)}...$ext';
      }
    }
    return filename;
  }

  /// Open PDF from S3
  Future<void> _openPDF(BuildContext context, String filename) async {
    try {
      // Show loading indicator
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => Center(
          child: CircularProgressIndicator(
            color: Theme.of(context).colorScheme.primary,
          ),
        ),
      );

      // Get presigned URL from backend
      final s3Key = 'knowledge_base/$filename';
      debugPrint('Opening PDF - Filename: $filename');
      debugPrint('Opening PDF - S3 Key: $s3Key');

      final response = await http.post(
        Uri.parse('http://10.0.2.2:8000/api/get_presigned_url.php'),
        body: {'s3_key': s3Key},
      );

      // Close loading dialog
      if (context.mounted) {
        Navigator.pop(context);
      }

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] && context.mounted) {
          // Open PDF viewer
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) =>
                  PdfViewerScreen(pdfUrl: data['url'], title: filename),
            ),
          );
        } else {
          if (context.mounted) {
            ScaffoldMessenger.of(
              context,
            ).showSnackBar(const SnackBar(content: Text('Failed to load PDF')));
          }
        }
      }
    } catch (e) {
      // Close loading dialog if still open
      if (context.mounted) {
        Navigator.pop(context);
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error: ${e.toString()}')));
      }
    }
  }

  String _formatTimestamp(DateTime timestamp) {
    final now = DateTime.now();
    final difference = now.difference(timestamp);

    if (difference.inMinutes < 1) {
      return 'Just now';
    } else if (difference.inHours < 1) {
      return '${difference.inMinutes}m ago';
    } else if (difference.inDays < 1) {
      return '${difference.inHours}h ago';
    } else {
      return '${timestamp.hour.toString().padLeft(2, '0')}:${timestamp.minute.toString().padLeft(2, '0')}';
    }
  }
}
