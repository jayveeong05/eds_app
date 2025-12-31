import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:intl/intl.dart';
import 'code_detail_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<dynamic> _promotions = [];
  List<String> _machineCodes = [];
  bool _isLoadingPromotions = true;
  bool _isLoadingInvoices = true;
  late PageController _pageController;
  int _currentPage = 0;

  // For infinite scroll
  static const int _virtualPageCount = 10000;
  int get _realPageCount => _promotions.length;

  @override
  void initState() {
    super.initState();
    _fetchPromotions();
    _fetchMachineCodes();
  }

  void _initializePageController() {
    final initialPage = (_virtualPageCount / 2).floor();
    _pageController = PageController(initialPage: initialPage);
    setState(() {
      _currentPage = 0;
    });
  }

  @override
  void dispose() {
    if (_promotions.isNotEmpty) {
      _pageController.dispose();
    }
    super.dispose();
  }

  Future<void> _fetchPromotions() async {
    setState(() {
      _isLoadingPromotions = true;
    });

    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8000/api/get_promotions.php'),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _promotions = data['data'] ?? [];
          _isLoadingPromotions = false;
        });
        if (_promotions.isNotEmpty) {
          _initializePageController();
        }
      } else {
        setState(() {
          _isLoadingPromotions = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoadingPromotions = false;
      });
    }
  }

  Future<void> _fetchMachineCodes() async {
    setState(() {
      _isLoadingInvoices = true;
    });

    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8000/api/get_machine_codes.php'),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success']) {
          setState(() {
            _machineCodes = List<String>.from(data['data']);
            _isLoadingInvoices = false;
          });
        } else {
          setState(() {
            _isLoadingInvoices = false;
          });
        }
      } else {
        setState(() {
          _isLoadingInvoices = false;
        });
      }
    } catch (e) {
      setState(() {
        _isLoadingInvoices = false;
      });
    }
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final date = DateTime.parse(dateStr);
      return DateFormat('MMM d, yyyy').format(date);
    } catch (e) {
      return dateStr;
    }
  }

  void _navigateToCodeDetail(String code) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => CodeDetailScreen(machineCode: code),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      backgroundColor: theme.scaffoldBackgroundColor,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header with EDS Logo
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 8),
              child: Center(
                child: Image.asset(
                  // 'assets/images/eds_logo.jpg',
                  'assets/images/eds_logo.png',
                  height:
                      80, // Increased size, adjusted padding to match box size
                  fit: BoxFit.contain,
                  errorBuilder: (context, error, stackTrace) {
                    return Text(
                      'EDS',
                      style: theme.textTheme.headlineMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: theme.colorScheme.primary,
                      ),
                    );
                  },
                ),
              ),
            ),

            // Promotions Section
            SizedBox(
              height: 380,
              child: _isLoadingPromotions
                  ? Center(
                      child: CircularProgressIndicator(
                        color: theme.colorScheme.primary,
                      ),
                    )
                  : _promotions.isEmpty
                  ? const Center(child: Text('No promotions available'))
                  : Column(
                      children: [
                        // Promotion Cards
                        Expanded(
                          child: PageView.builder(
                            controller: _pageController,
                            onPageChanged: (virtualIndex) {
                              setState(() {
                                _currentPage = virtualIndex % _realPageCount;
                              });
                            },
                            itemCount: _virtualPageCount,
                            itemBuilder: (context, virtualIndex) {
                              final realIndex = virtualIndex % _realPageCount;
                              final promo = _promotions[realIndex];
                              return _buildPromotionCard(promo);
                            },
                          ),
                        ),

                        // Navigation dots
                        const SizedBox(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: List.generate(_promotions.length, (index) {
                            final isActive = index == _currentPage;
                            return AnimatedContainer(
                              duration: const Duration(milliseconds: 300),
                              margin: const EdgeInsets.symmetric(horizontal: 4),
                              width: isActive ? 24 : 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: isActive
                                    ? theme
                                          .colorScheme
                                          .secondary // EDS Red
                                    : Colors.grey[300],
                                borderRadius: BorderRadius.circular(4),
                              ),
                            );
                          }),
                        ),
                      ],
                    ),
            ),

            const SizedBox(height: 24),

            // Latest Invoices Section
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Latest Invoices', style: theme.textTheme.titleLarge),
                  if (_isLoadingInvoices)
                    SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: theme.colorScheme.primary,
                      ),
                    ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // Invoice List
            Expanded(
              child: _machineCodes.isEmpty
                  ? Center(
                      child: Text(
                        'No invoices available',
                        style: TextStyle(color: Colors.grey[600], fontSize: 14),
                      ),
                    )
                  : ListView.builder(
                      padding: const EdgeInsets.only(
                        left: 24,
                        right: 24,
                        bottom: 100,
                      ),
                      itemCount: _machineCodes.length > 5
                          ? 5
                          : _machineCodes.length,
                      itemBuilder: (context, index) {
                        final code = _machineCodes[index];
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.04),
                                blurRadius: 16,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: ListTile(
                            contentPadding: const EdgeInsets.symmetric(
                              horizontal: 20,
                              vertical: 8,
                            ),
                            leading: Container(
                              width: 40,
                              height: 40,
                              decoration: BoxDecoration(
                                color: theme.colorScheme.primary.withOpacity(
                                  0.1,
                                ),
                                shape: BoxShape.circle,
                              ),
                              child: Icon(
                                Icons.receipt_long,
                                color: theme.colorScheme.primary, // Royal Blue
                                size: 20,
                              ),
                            ),
                            title: Text(
                              code,
                              style: theme.textTheme.titleMedium?.copyWith(
                                fontWeight: FontWeight.w600,
                                color: theme.colorScheme.onSurface,
                              ),
                            ),
                            subtitle: Text(
                              'Tap to view',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.onSurface.withOpacity(
                                  0.6,
                                ),
                              ),
                            ),
                            trailing: Icon(
                              Icons.chevron_right,
                              color: theme.colorScheme.onSurface.withOpacity(
                                0.4,
                              ),
                              size: 20,
                            ),
                            onTap: () => _navigateToCodeDetail(code),
                          ),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPromotionCard(dynamic promo) {
    final user = promo['user'] ?? {};
    final email = user['email'] ?? 'Unknown User';
    final profileImageUrl = user['profile_image_url'];
    final date = _formatDate(promo['created_at']);

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 24),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: Stack(
          children: [
            // Background Image
            Image.network(
              promo['image_url'],
              width: double.infinity,
              height: double.infinity,
              fit: BoxFit.cover,
              errorBuilder: (context, error, stackTrace) {
                return Container(
                  color: Colors.grey[300],
                  child: Center(
                    child: Icon(
                      Icons.broken_image,
                      size: 48,
                      color: Colors.grey[500],
                    ),
                  ),
                );
              },
            ),

            // Gradient Overlay
            Positioned.fill(
              child: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.bottomCenter,
                    end: Alignment.topCenter,
                    colors: [Colors.black54, Colors.transparent],
                  ),
                ),
              ),
            ),

            // Content
            Positioned(
              left: 20,
              right: 20,
              bottom: 20,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  if (promo['title'] != null && promo['title'].isNotEmpty)
                    Text(
                      promo['title'],
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  const SizedBox(height: 8),

                  // Author info
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 16,
                        backgroundColor: Colors.white.withOpacity(0.3),
                        backgroundImage:
                            profileImageUrl != null &&
                                profileImageUrl.toString().isNotEmpty
                            ? NetworkImage(profileImageUrl)
                            : null,
                        child:
                            profileImageUrl == null ||
                                profileImageUrl.toString().isEmpty
                            ? Text(
                                email[0].toUpperCase(),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                ),
                              )
                            : null,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              email.split('@')[0],
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                                fontSize: 13,
                                color: Colors.white,
                              ),
                            ),
                            Text(
                              date,
                              style: const TextStyle(
                                color: Colors.white70,
                                fontSize: 11,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
