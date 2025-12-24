import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:intl/intl.dart';

class PromotionsScreen extends StatefulWidget {
  const PromotionsScreen({super.key});

  @override
  State<PromotionsScreen> createState() => _PromotionsScreenState();
}

class _PromotionsScreenState extends State<PromotionsScreen> {
  List<dynamic> _promotions = [];
  bool _isLoading = true;
  String _errorMessage = '';
  late PageController _pageController;
  int _currentPage = 0;

  // For infinite scroll
  static const int _virtualPageCount = 10000;
  int get _realPageCount => _promotions.length;

  @override
  void initState() {
    super.initState();
    _fetchPromotions();
  }

  void _initializePageController() {
    // Start at middle of virtual pages
    final initialPage = (_virtualPageCount / 2).floor();
    _pageController = PageController(initialPage: initialPage);
    setState(() {
      _currentPage = 0;
    });
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  Future<void> _fetchPromotions() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    try {
      final response = await http.get(
        Uri.parse('http://10.0.2.2:8000/api/get_promotions.php'),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        setState(() {
          _promotions = data['data'] ?? [];
          _isLoading = false;
        });
        if (_promotions.isNotEmpty) {
          _initializePageController();
        }
      } else {
        setState(() {
          _errorMessage = 'Failed to load promotions';
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

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final date = DateTime.parse(dateStr);
      return DateFormat('MMM d, yyyy').format(date);
    } catch (e) {
      return dateStr;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0EEE9), // Cloud Dancer background
      body: SafeArea(
        child: _isLoading
            ? const Center(
                child: CircularProgressIndicator(
                  color: Color(0xFF2C3E50),
                ), // Deep Slate
              )
            : _errorMessage.isNotEmpty
            ? Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      _errorMessage,
                      style: const TextStyle(color: Colors.red),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: _fetchPromotions,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF2C3E50), // Deep Slate
                        foregroundColor: Colors.white,
                      ),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              )
            : _promotions.isEmpty
            ? const Center(child: Text('No promotions available'))
            : Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Promotions',
                          style: Theme.of(context).textTheme.headlineMedium,
                        ),
                        // Refresh button
                        Container(
                          decoration: BoxDecoration(
                            color: const Color(
                              0xFF2C3E50,
                            ).withOpacity(0.1), // Deep Slate
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: IconButton(
                            icon: const Icon(
                              Icons.refresh,
                              size: 20,
                              color: Color(0xFF2C3E50), // Deep Slate
                            ),
                            padding: const EdgeInsets.all(8),
                            constraints: const BoxConstraints(),
                            onPressed: _fetchPromotions,
                            tooltip: 'Refresh',
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Card with PageView
                    Expanded(
                      child: Center(
                        child: SizedBox(
                          height: 600,
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
                      ),
                    ),

                    // Navigation Controls (Bottom Row)
                    const SizedBox(height: 32),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        // Left Arrow
                        Container(
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.04),
                                blurRadius: 24,
                                offset: const Offset(0, 8),
                              ),
                            ],
                          ),
                          child: IconButton(
                            icon: const Icon(
                              Icons.arrow_back_ios_new,
                              color: Color(0xFFA39382), // Warm Taupe
                              size: 20,
                            ),
                            onPressed: () {
                              _pageController.previousPage(
                                duration: const Duration(milliseconds: 300),
                                curve: Curves.easeInOut,
                              );
                            },
                            tooltip: 'Previous',
                          ),
                        ),
                        const SizedBox(width: 24),

                        // Dot Indicators
                        Row(
                          mainAxisSize: MainAxisSize.min,
                          children: List.generate(_promotions.length, (index) {
                            final isActive = index == _currentPage;
                            return AnimatedContainer(
                              duration: const Duration(milliseconds: 300),
                              margin: const EdgeInsets.symmetric(horizontal: 4),
                              width: isActive ? 32 : 8,
                              height: 8,
                              decoration: BoxDecoration(
                                color: isActive
                                    ? const Color(0xFF8A9A5B) // Soft Sage
                                    : const Color(0xFFD1D5DB),
                                borderRadius: BorderRadius.circular(4),
                              ),
                            );
                          }),
                        ),

                        const SizedBox(width: 24),
                        // Right Arrow
                        Container(
                          decoration: BoxDecoration(
                            color: const Color(0xFF8A9A5B), // Soft Sage
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(
                                  0xFF8A9A5B,
                                ).withOpacity(0.3), // Soft Sage
                                blurRadius: 16,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: IconButton(
                            icon: const Icon(
                              Icons.arrow_forward_ios,
                              color: Colors.white,
                              size: 20,
                            ),
                            onPressed: () {
                              _pageController.nextPage(
                                duration: const Duration(milliseconds: 300),
                                curve: Curves.easeInOut,
                              );
                            },
                            tooltip: 'Next',
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 100), // Add padding for floating nav
                  ],
                ),
              ),
      ),
    );
  }

  Widget _buildPromotionCard(dynamic promo) {
    final user = promo['user'] ?? {};
    final email = user['email'] ?? 'Unknown User';
    final profileImageUrl = user['profile_image_url'];
    final date = _formatDate(promo['created_at']);

    return GestureDetector(
      onTap: () => _showFullscreenImage(context, promo['image_url']),
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 8),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.04),
              blurRadius: 24,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: Column(
            children: [
              // Top Half: Image with Gradient Overlay and Author Info
              Expanded(
                flex: 5,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    // Background Image
                    Image.network(
                      promo['image_url'],
                      fit: BoxFit.cover,
                      errorBuilder: (context, error, stackTrace) {
                        return Container(
                          color: Colors.grey[300],
                          child: Center(
                            child: Icon(
                              Icons.broken_image,
                              size: 80,
                              color: Colors.grey[500],
                            ),
                          ),
                        );
                      },
                    ),

                    // Gradient Overlay (bottom to transparent)
                    Positioned.fill(
                      child: Container(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.bottomCenter,
                            end: Alignment.topCenter,
                            colors: [Colors.black54, Colors.transparent],
                          ),
                        ),
                      ),
                    ),

                    // Author Info (Bottom Left)
                    Positioned(
                      left: 24,
                      bottom: 24,
                      child: Row(
                        children: [
                          // Avatar
                          CircleAvatar(
                            radius: 24,
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
                                      fontSize: 16,
                                    ),
                                  )
                                : null,
                          ),
                          const SizedBox(width: 12),
                          // Name
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                email.split('@')[0],
                                style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                  color: Colors.white,
                                ),
                              ),
                              Text(
                                date,
                                style: const TextStyle(
                                  color: Colors.white70,
                                  fontSize: 12,
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

              // Bottom Half: Content
              Expanded(
                flex: 2,
                child: Container(
                  width: double.infinity,
                  color: Colors.white,
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Title
                      Text(
                        promo['title']?.isEmpty ?? true
                            ? 'Untitled Post'
                            : promo['title'],
                        style: Theme.of(context).textTheme.headlineSmall,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 8),

                      // Description
                      Expanded(
                        child: Text(
                          promo['description'] ?? '',
                          style: Theme.of(context).textTheme.bodyMedium,
                          maxLines: 4,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showFullscreenImage(BuildContext context, String imageUrl) {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => Scaffold(
          backgroundColor: Colors.black,
          appBar: AppBar(
            backgroundColor: Colors.transparent,
            elevation: 0,
            leading: IconButton(
              icon: const Icon(Icons.close, color: Colors.white),
              onPressed: () => Navigator.pop(context),
            ),
          ),
          body: Center(
            child: InteractiveViewer(
              minScale: 0.5,
              maxScale: 4.0,
              child: Image.network(
                imageUrl,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) {
                  return const Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.broken_image, size: 80, color: Colors.grey),
                        SizedBox(height: 16),
                        Text(
                          'Image not available',
                          style: TextStyle(color: Colors.white),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ),
          ),
        ),
      ),
    );
  }
}
