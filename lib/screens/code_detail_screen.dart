import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:url_launcher/url_launcher.dart';

class CodeDetailScreen extends StatefulWidget {
  final String machineCode;

  const CodeDetailScreen({super.key, required this.machineCode});

  @override
  State<CodeDetailScreen> createState() => _CodeDetailScreenState();
}

class _CodeDetailScreenState extends State<CodeDetailScreen> {
  List<dynamic> _invoices = [];
  bool _isLoading = true;
  String _errorMessage = '';

  @override
  void initState() {
    super.initState();
    _fetchInvoices();
  }

  Future<void> _fetchInvoices() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final response = await http.post(
        Uri.parse('http://10.0.2.2:8000/api/get_code_invoices.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'code': widget.machineCode}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          setState(() {
            _invoices = data['data'];
            _isLoading = false;
          });
        } else {
          setState(() {
            _errorMessage = data['message'] ?? 'Failed to load invoices';
            _isLoading = false;
          });
        }
      } else {
        setState(() {
          _errorMessage = 'Server error: ${response.statusCode}';
          _isLoading = false;
        });
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Error: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _openPdf(String url) async {
    try {
      final uri = Uri.parse(url);
      final bool launched = await launchUrl(
        uri,
        mode: LaunchMode.externalApplication,
      );

      if (!launched && mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Unable to open PDF')));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text('Error opening PDF: $e')));
      }
    }
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final date = DateTime.parse(dateStr);
      return '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.machineCode,
          style: const TextStyle(color: Colors.white),
        ),
        backgroundColor: const Color(0xFF3F51B5), // EDS royal blue
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      backgroundColor: const Color(0xFFF0F3FF), // Light blue-gray background
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF3F51B5)),
            )
          : _errorMessage.isNotEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(
                    Icons.error_outline,
                    size: 64,
                    color: Color(0xFFE53935),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    _errorMessage,
                    style: const TextStyle(color: Color(0xFFE53935)),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: _fetchInvoices,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF3F51B5),
                    ),
                    child: const Text('Retry'),
                  ),
                ],
              ),
            )
          : _invoices.isEmpty
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.inbox_outlined, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 16),
                  Text(
                    'No invoices found for ${widget.machineCode}',
                    style: TextStyle(fontSize: 18, color: Colors.grey[600]),
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            )
          : RefreshIndicator(
              onRefresh: _fetchInvoices,
              color: const Color(0xFF3F51B5),
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: _invoices.length,
                itemBuilder: (context, index) {
                  final invoice = _invoices[index];
                  return Card(
                    margin: const EdgeInsets.only(bottom: 12),
                    color: const Color(0xFFF5F7FF), // Light blue tint
                    elevation: 2,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 8,
                      ),
                      leading: const CircleAvatar(
                        backgroundColor: Color(0xFFE53935), // EDS red
                        child: Icon(Icons.picture_as_pdf, color: Colors.white),
                      ),
                      title: Text(
                        invoice['month'],
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                      subtitle: Text(
                        'Uploaded: ${_formatDate(invoice['created_at'])}',
                        style: const TextStyle(fontSize: 12),
                      ),
                      trailing: IconButton(
                        icon: const Icon(
                          Icons.open_in_new,
                          color: Color(0xFF3F51B5),
                        ),
                        onPressed: () => _openPdf(invoice['pdf_url']),
                      ),
                      onTap: () => _openPdf(invoice['pdf_url']),
                    ),
                  );
                },
              ),
            ),
    );
  }
}
