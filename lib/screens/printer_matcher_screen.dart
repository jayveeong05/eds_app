import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/printer_chat_service.dart';
import '../widgets/printer_card.dart';

class PrinterMatcherScreen extends StatefulWidget {
  const PrinterMatcherScreen({super.key});

  @override
  State<PrinterMatcherScreen> createState() => _PrinterMatcherScreenState();
}

class _PrinterMatcherScreenState extends State<PrinterMatcherScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  void _sendMessage() {
    final message = _messageController.text.trim();
    if (message.isEmpty) return;

    final service = Provider.of<PrinterChatService>(context, listen: false);
    service.sendMessage(message);
    _messageController.clear();
    _scrollToBottom();
  }

  Future<void> _showNewChatDialog() async {
    final theme = Theme.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Start New Chat'),
        content: const Text(
          'Are you sure you want to start a new chat? This will clear the current conversation and recommendations.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            style: TextButton.styleFrom(
              foregroundColor: theme.colorScheme.onSurface,
            ),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            style: FilledButton.styleFrom(
              backgroundColor: theme.colorScheme.primary,
              foregroundColor: Colors.white,
            ),
            child: const Text('Start New'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      context.read<PrinterChatService>().clearChat();
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(
          'Printer Recommendations',
          style: theme.textTheme.headlineSmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: theme.colorScheme.onSurface,
          ),
        ),
        backgroundColor: theme.appBarTheme.backgroundColor,
        elevation: 0,
        iconTheme: IconThemeData(color: theme.colorScheme.onSurface),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
        actions: [
          Consumer<PrinterChatService>(
            builder: (context, service, _) {
              if (service.chatHistory.isEmpty) return const SizedBox.shrink();

              return IconButton(
                icon: const Icon(Icons.refresh),
                tooltip: 'Start New Chat',
                onPressed: _showNewChatDialog,
              );
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Messages area
          Expanded(
            child: Consumer<PrinterChatService>(
              builder: (context, service, _) {
                // Empty state
                if (service.chatHistory.isEmpty) {
                  return Center(
                    child: SingleChildScrollView(
                      padding: const EdgeInsets.all(32),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            width: 120,
                            height: 120,
                            decoration: BoxDecoration(
                              color: theme.colorScheme.primary.withOpacity(0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              Icons.print_rounded,
                              size: 60,
                              color: theme.colorScheme.primary,
                            ),
                          ),
                          const SizedBox(height: 24),
                          Text(
                            'Find Your Perfect Printer',
                            style: theme.textTheme.titleLarge?.copyWith(
                              fontWeight: FontWeight.w600,
                              color: theme.colorScheme.onSurface,
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Tell me about your printing needs and I\'ll recommend the best options for you.',
                            style: theme.textTheme.bodyMedium?.copyWith(
                              color: theme.colorScheme.onSurface.withOpacity(
                                0.6,
                              ),
                            ),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 32),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            alignment: WrapAlignment.center,
                            children: [
                              _buildSuggestionChip(
                                'Small office printer',
                                Icons.business,
                              ),
                              _buildSuggestionChip(
                                'High volume printing',
                                Icons.inventory_2,
                              ),
                              _buildSuggestionChip(
                                'Color printing needed',
                                Icons.palette,
                              ),
                              _buildSuggestionChip(
                                'Budget friendly options',
                                Icons.savings,
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                }

                // Chat messages
                return ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(16),
                  itemCount: service.chatHistory.length,
                  itemBuilder: (context, index) {
                    final message = service.chatHistory[index];
                    final isUser = message['role'] == 'user';
                    final recommendations = message['recommendations'];

                    // If this message has recommendations, show them
                    if (recommendations != null) {
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildMessageBubble(
                            message['content'],
                            false,
                            message['timestamp'],
                          ),
                          const SizedBox(height: 16),
                          ...List.generate(
                            recommendations.length,
                            (i) => PrinterCard(
                              printer: recommendations[i],
                              rank: i + 1,
                            ),
                          ),
                        ],
                      );
                    }

                    return Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _buildMessageBubble(
                        message['content'],
                        isUser,
                        message['timestamp'],
                      ),
                    );
                  },
                );
              },
            ),
          ),

          // Loading indicator
          Consumer<PrinterChatService>(
            builder: (context, service, _) {
              if (!service.isLoading) return const SizedBox.shrink();

              return Container(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Text(
                      'Finding the best printers for you...',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.onSurface.withOpacity(0.6),
                      ),
                    ),
                  ],
                ),
              );
            },
          ),

          // Input field
          Container(
            decoration: BoxDecoration(
              color: theme.colorScheme.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.05),
                  blurRadius: 10,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            padding: const EdgeInsets.all(16),
            child: SafeArea(
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _messageController,
                      decoration: InputDecoration(
                        hintText: 'Describe your printing needs...',
                        hintStyle: TextStyle(
                          color: theme.colorScheme.onSurface.withOpacity(0.5),
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(24),
                          borderSide: BorderSide.none,
                        ),
                        filled: true,
                        fillColor: theme.colorScheme.surfaceContainerHighest,
                        contentPadding: const EdgeInsets.symmetric(
                          horizontal: 20,
                          vertical: 12,
                        ),
                      ),
                      maxLines: null,
                      textCapitalization: TextCapitalization.sentences,
                      onSubmitted: (_) => _sendMessage(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Consumer<PrinterChatService>(
                    builder: (context, service, _) {
                      return Container(
                        decoration: BoxDecoration(
                          color: service.isLoading
                              ? theme.colorScheme.surfaceContainerHighest
                              : theme.colorScheme.primary,
                          shape: BoxShape.circle,
                        ),
                        child: IconButton(
                          onPressed: service.isLoading ? null : _sendMessage,
                          icon: const Icon(Icons.send),
                          color: Colors.white,
                          tooltip: 'Send',
                        ),
                      );
                    },
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMessageBubble(String content, bool isUser, DateTime timestamp) {
    final theme = Theme.of(context);

    return Align(
      alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.symmetric(vertical: 4),
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
                    ? Colors.lightBlue[100]
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
              child: Text(
                content,
                style: theme.textTheme.bodyLarge?.copyWith(
                  color: isUser ? Colors.black87 : theme.colorScheme.onSurface,
                  fontSize: 15,
                ),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              _formatTimestamp(timestamp),
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurface.withOpacity(0.6),
              ),
            ),
          ],
        ),
      ),
    );
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

  Widget _buildSuggestionChip(String label, IconData icon) {
    final theme = Theme.of(context);

    return ActionChip(
      avatar: Icon(icon, size: 18),
      label: Text(label),
      backgroundColor: theme.colorScheme.surfaceContainerHighest,
      onPressed: () {
        _messageController.text = label;
        _sendMessage();
      },
    );
  }
}
