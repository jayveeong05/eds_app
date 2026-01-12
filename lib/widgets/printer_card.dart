import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../models/printer_recommendation.dart';

class PrinterCard extends StatelessWidget {
  final PrinterRecommendation printer;
  final int rank;

  const PrinterCard({required this.printer, required this.rank, super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      elevation: 2,
      margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 16),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Rank badge and model name
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: _getRankColor(rank, theme),
                  radius: 20,
                  child: Text(
                    '#$rank',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        printer.model,
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      if (printer.score > 0)
                        Text(
                          'Match Score: ${printer.score}%',
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.secondary,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Reason
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: theme.colorScheme.surfaceContainerHighest.withOpacity(
                  0.3,
                ),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                printer.reason,
                style: theme.textTheme.bodyMedium?.copyWith(height: 1.5),
              ),
            ),
            const SizedBox(height: 16),

            // View Product button
            if (printer.productUrl.isNotEmpty)
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () => _launchUrl(printer.productUrl, context),
                  icon: const Icon(Icons.open_in_new),
                  label: const Text('View Product Details'),
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              )
            else
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: null,
                  icon: const Icon(Icons.link_off),
                  label: const Text('Product Link Not Available'),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Color _getRankColor(int rank, ThemeData theme) {
    switch (rank) {
      case 1:
        return Colors.amber.shade700; // Gold
      case 2:
        return Colors.grey.shade600; // Silver
      case 3:
        return Colors.brown.shade400; // Bronze
      default:
        return theme.colorScheme.primary;
    }
  }

  Future<void> _launchUrl(String url, BuildContext context) async {
    // Validate URL
    if (url.isEmpty) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Product link is not available'),
            backgroundColor: Colors.orange,
          ),
        );
      }
      return;
    }

    try {
      final uri = Uri.parse(url);

      // Validate URI has a scheme (http/https)
      if (!uri.hasScheme || (uri.scheme != 'http' && uri.scheme != 'https')) {
        throw Exception('Invalid URL format');
      }

      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (e) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Could not open link: ${e.toString()}'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }
}
