import 'package:flutter/material.dart';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/news.dart';
import '../services/news_service.dart';
import 'code_detail_screen.dart';
import 'news_detail_screen.dart';
import 'printer_matcher_screen.dart';
import '../config/environment.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  List<News> _news = [];
  List<String> _machineCodes = [];
  bool _isLoadingNews = true;
  bool _isLoadingInvoices = true;
  late PageController _pageController;
  int _currentPage = 0;
  Timer? _autoScrollTimer;
  DateTime? _lastManualInteraction;

  // For infinite scroll
  static const int _virtualPageCount = 10000;
  int get _realPageCount => _news.length;

  @override
  void initState() {
    super.initState();
    _fetchNews();
    _fetchMachineCodes();
  }

  void _initializePageController() {
    final initialPage = (_virtualPageCount / 2).floor();
    _pageController = PageController(initialPage: initialPage);
    setState(() {
      _currentPage = 0;
    });
    _startAutoScroll();
  }

  void _startAutoScroll() {
    _autoScrollTimer?.cancel();
    _autoScrollTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      if (_news.isEmpty || !mounted) return;

      // Check if user has manually interacted recently (within last 5 seconds)
      if (_lastManualInteraction == null ||
          DateTime.now().difference(_lastManualInteraction!) >
              const Duration(seconds: 5)) {
        // Auto-scroll to next page
        if (_pageController.hasClients) {
          _pageController.nextPage(
            duration: const Duration(milliseconds: 300),
            curve: Curves.easeInOut,
          );
        }
      }
    });
  }

  @override
  void dispose() {
    _autoScrollTimer?.cancel();
    if (_news.isNotEmpty) {
      _pageController.dispose();
    }
    super.dispose();
  }

  Future<void> _fetchNews() async {
    setState(() {
      _isLoadingNews = true;
    });

    try {
      final newsItems = await NewsService.fetchNews(limit: 20);
      setState(() {
        _news = newsItems;
        _isLoadingNews = false;
      });
      if (_news.isNotEmpty) {
        _initializePageController();
      }
    } catch (e) {
      setState(() {
        _isLoadingNews = false;
      });
    }
  }

  Future<void> _fetchMachineCodes() async {
    setState(() {
      _isLoadingInvoices = true;
    });

    try {
      final response = await http.get(
        Uri.parse('${Environment.apiUrl}/get_machine_codes.php'),
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
        child: SingleChildScrollView(
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

              // News Section
              SizedBox(
                height: 380,
                child: _isLoadingNews
                    ? Center(
                        child: CircularProgressIndicator(
                          color: theme.colorScheme.primary,
                        ),
                      )
                    : _news.isEmpty
                    ? const Center(child: Text('No news available'))
                    : Column(
                        children: [
                          // News Cards
                          Expanded(
                            child: PageView.builder(
                              controller: _pageController,
                              onPageChanged: (virtualIndex) {
                                setState(() {
                                  _lastManualInteraction = DateTime.now();
                                  _currentPage = virtualIndex % _realPageCount;
                                });
                              },
                              itemCount: _virtualPageCount,
                              itemBuilder: (context, virtualIndex) {
                                final realIndex = virtualIndex % _realPageCount;
                                final newsItem = _news[realIndex];
                                return _buildNewsCard(newsItem);
                              },
                            ),
                          ),

                          // Navigation dots
                          const SizedBox(height: 16),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: List.generate(_news.length, (index) {
                              final isActive = index == _currentPage;
                              return AnimatedContainer(
                                duration: const Duration(milliseconds: 300),
                                margin: const EdgeInsets.symmetric(
                                  horizontal: 4,
                                ),
                                width: isActive ? 24 : 8,
                                height: 8,
                                decoration: BoxDecoration(
                                  color: isActive
                                      ? theme
                                            .colorScheme
                                            .primary // EDS Blue
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

              // Printer Matcher Feature Card (NEW)
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: GestureDetector(
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const PrinterMatcherScreen(),
                      ),
                    );
                  },
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                        colors: [
                          theme.colorScheme.primary,
                          theme.colorScheme.primary.withOpacity(0.8),
                        ],
                      ),
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [
                        BoxShadow(
                          color: theme.colorScheme.primary.withOpacity(0.3),
                          blurRadius: 16,
                          offset: const Offset(0, 8),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 56,
                          height: 56,
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: const Icon(
                            Icons.print_rounded,
                            color: Colors.white,
                            size: 28,
                          ),
                        ),
                        const SizedBox(width: 16),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                'Find Your Perfect Printer',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                'Get AI-powered recommendations',
                                style: TextStyle(
                                  color: Colors.white.withOpacity(0.9),
                                  fontSize: 14,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Icon(
                          Icons.arrow_forward_ios,
                          color: Colors.white,
                          size: 20,
                        ),
                      ],
                    ),
                  ),
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
              _machineCodes.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.all(24),
                      child: Center(
                        child: Text(
                          'No invoices available',
                          style: TextStyle(
                            color: Colors.grey[600],
                            fontSize: 14,
                          ),
                        ),
                      ),
                    )
                  : ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
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
                            color: theme.colorScheme.surface,
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
                              vertical: 4,
                            ),
                            leading: Container(
                              width: 36,
                              height: 36,
                              decoration: BoxDecoration(
                                color: theme.colorScheme.primary.withOpacity(
                                  0.1,
                                ),
                                shape: BoxShape.circle,
                              ),
                              child: Icon(
                                Icons.precision_manufacturing,
                                color: theme.colorScheme.primary, // Royal Blue
                                size: 18,
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
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNewsCard(News newsItem) {
    return GestureDetector(
      onTap: () {
        setState(() {
          _lastManualInteraction = DateTime.now();
        });
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => NewsDetailScreen(news: newsItem),
          ),
        );
      },
      child: Container(
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
                newsItem.imageUrl,
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
                    Text(
                      newsItem.title,
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),

                    // Short Description (replacing user info)
                    Text(
                      newsItem.shortDescription,
                      style: const TextStyle(
                        fontSize: 14,
                        color: Colors.white70,
                        height: 1.4,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
