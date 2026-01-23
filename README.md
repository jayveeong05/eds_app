# EDS App - E-Document Solutions

A mobile application for managing e-documents, promotions, and invoices with Firebase authentication and AWS S3 integration.

---

## 🎨 Brand Colors

- **Primary:** Royal Blue `#141478` (Deep Royal Blue)
- **Secondary:** Royal Blue `#141478` (Unified with Primary)
- **Background:** Cloud Dancer `#F0EEE9` (Warm Off-White)
- **Error:** Red `#DE1F26` (EDS Red)
- **Text:** Dark Charcoal `#333333`

---

## ✨ Features

### 🔐 Authentication
- **Email/Password** authentication with Firebase
- **Google Sign-In** integration
- **Apple Sign-In** integration
- **Complete Profile** - Unified registration/profile completion screen
- **Login method tracking** - Displays authentication provider
- **QR Code activation** - New users display QR for admin approval
- **Account status management** - Active/Inactive states
- **Smart registration flow** - Auto-navigate new third-party users to profile completion

### 👤 Profile Management
- **Profile picture upload** - S3 integration with presigned URLs
- **Camera & gallery support** - Choose photo source
- **Change password** - Email users only (conditional display)
- **Firebase password update** - Direct reauthentication
- **Login method display** - Shows email/google/apple with icons
- **Profile information editing** - Name and email management

### 📱 Dashboard
- **Latest news feed** - Displays recent company news and announcements
- **Latest promotions feed** - Displays 3 most recent promotions  
- **Polished card design** - Light blue tint with enhanced elevation
- **Vertical scrollable cards** - Image, user, timestamp, description
- **Pull-to-refresh** - Swipe down to reload
- **Relative timestamps** - "5m ago", "2h ago", "3d ago"
- **User avatars** - Profile pictures or initials
- **Branded AppBar** - EDS royal blue, left-aligned title
- **Tap to view details** - Open news/promotions in detail screen

### 🖥️ Admin Panel
- **Web-based management** - Full CRUD for users and content
- **Dashboard stats** - Real-time metrics (active users, promotions)
- **QR Code Scanner** - Instantly activate users via webcam
- **Promotion Management** - Upload images (S3) and manage campaigns
- **User Management** - Activate/deactivate, role assignment, soft delete
- **Secure Access** - Firebase Auth + Session management

### 🎯 Promotions
- **Two-tone card header** - EDS royal blue with white text
- **Circular navigation** - Swipe through promotions with branded arrows
- **Refresh button** - Manual reload of promotion data
- **Optimized card layout** - 75% width with proper spacing
- **Fullscreen image viewer** - Tap to zoom
- **Pinch-to-zoom** - InteractiveViewer support
- **User information** - Creator profile and timestamp
- **Description display** - Multi-line text with overflow
- **Branded theme** - EDS blue AppBar, icons, and background
- **Light blue background** - Cohesive color scheme

### 📄 Invoices
- **User Assignment** - Admin assigns machine codes to specific users
- **Secure Access** - Users only see invoices for their assigned machine codes
- **Bulk Upload System** - Admin panel for uploading multiple PDFs with assignment
- **Client-side Validation** - Validates `CODE-MONTH.pdf` format before upload
- **Replacement Strategy** - New uploads replace old data (max 12 months per machine)
- **Filename Parsing** - Automatic extraction of machine code and month from filename
- **Two-level Navigation** - Machine codes → Monthly invoices
- **In-app PDF Viewer** - Opens PDFs directly in app with zoom and scroll
- **Real-time Feedback** - Shows valid/invalid files with detailed error messages
- **Progress Tracking** - Upload progress bar with file count
- **S3 Storage** - Original filenames preserved in cloud storage

### 📚 Knowledge Base
- **Document Library** - Centralized PDF documentation storage
- **AI Chatbot Assistant** - Integrated chat for querying the knowledge base
- **Smart Context** - Chatbot answers based on uploaded documents (RAG)
- **Chat History** - Persisted chat sessions per user
- **Search Functionality** - Search documents by title or subtitle
- **Admin Upload Portal** - Web interface for uploading PDFs with metadata
- **In-app PDF Viewer** - Seamless PDF viewing with zoom and text selection
- **Metadata Management** - Title and subtitle for each document
- **S3 Integration** - Secure file storage with presigned URL access
- **Pull-to-refresh** - Update document list

### 🖨️ Printer Matcher (AI-Powered Recommendations)
- **Smart Questionnaire** - 6-question guided conversation flow
- **AI-Driven Matching** - DigitalOcean Agent analyzes requirements and recommends top 3 printers
- **Anonymous Tracking** - Device-based tracking (no login required)
- **Suggestion Chips** - Quick-start prompts (e.g., "Small office printer")
- **Match Scores** - Percentage-based compatibility scores
- **Detailed Reasoning** - Bullet-point explanations for each recommendation
- **Product Links** - Direct links to printer product pages
- **Empty State Design** - Welcoming UI with large icon and suggestions
- **Admin Analytics** - View all customer requests with filtering and CSV export
- **S3 Knowledge Base** - Printer specs uploaded to S3 `printers/` folder
- **Template System** - JSON format guide with downloadable template

### 🧭 Navigation
- **5-Tab Bottom Navigation** - Promotions | Invoices | Home | Knowledge Base | Profile
- **Center-Elevated Home Button** - Prominent circular button with gradient background
- **Floating Navigation Bar** - Modern rounded design with smooth animations
- **Tab State Management** - Persistent across sessions
- **Material Design 3** - Modern UI components
- **Consistent AppBars** - All screens use consistent theming
- **Printer Matcher Access** - Accessible from Knowledge Base tab
- **Logout Functionality** - Quick logout from home screen with confirmation dialog

---

## 🛠 Tech Stack

### Frontend (Flutter)
- **Flutter SDK** - Cross-platform mobile framework
- **Firebase Auth** - Authentication provider
- **Firebase Core** - Firebase integration
- **Google Sign-In** - OAuth authentication
- **Sign in with Apple** - iOS authentication
- **HTTP** - API communication
- **Shared Preferences** - Local storage
- **Flutter Secure Storage** - Secure key-value storage
- **QR Flutter** - QR code generation
- **Image Picker** - Photo selection
- **Syncfusion PDF Viewer** - In-app PDF viewing with zoom and text selection
- **URL Launcher** - External link handling
- **Device Info Plus** - Device identification for anonymous tracking
- **UUID** - Unique identifier generation
- **Provider** - State management
- **Share Plus** - Share functionality
- **Flutter Markdown** - Markdown rendering
- **Google Fonts** - Typography (Inter font family)
- **Intl** - Internationalization and date formatting

### Backend (PHP)
- **PHP 8+** - Server-side logic
- **PostgreSQL** - Production database
- **MySQL/MariaDB** - Local development database
- **Firebase Admin SDK** - Token verification
- **SimpleS3 (Custom)** - AWS S3 integration with presigned URLs
- **PDO** - Database abstraction
- **Docker** - Containerization for production deployment
- **Nginx** - Web server and reverse proxy

### Infrastructure
- **AWS S3** - File storage (profile pictures, invoices, promotions)
  - **Security**: S3 keys stored in database, presigned URLs for access
  - **Access**: Temporary signed URLs (1-hour expiry)
  - **Performance**: Direct client access, no proxy overhead
- **Firebase** - Authentication services
- **Docker** - Containerized deployment
- **Nginx** - Production web server
- **PostgreSQL** - Production database
- **Local PHP server** - Development backend (php -S)

---

## 📁 Project Structure

```
eds_app/
├── lib/
│   ├── config/
│   │   ├── environment.dart            # Environment detection (dev/prod URLs)
│   │   └── firebase_options.dart       # Firebase client configuration
│   ├── screens/
│   │   ├── landing_screen.dart         # Initial auth screen
│   │   ├── login_screen.dart           # Auth screen with social login options
│   │   ├── registration_screen.dart    # Registration form
│   │   ├── inactive_screen.dart        # QR code for activation
│   │   ├── main_navigation.dart        # 5-tab bottom nav wrapper
│   │   ├── home_screen.dart            # Dashboard with news + promotions
│   │   ├── news_detail_screen.dart     # News article detail view
│   │   ├── promotions_screen.dart      # Promotion carousel with refresh
│   │   ├── dashboard_screen.dart       # Admin dashboard
│   │   ├── invoices_screen.dart        # Invoice listing
│   │   ├── code_detail_screen.dart     # Invoice detail with PDF viewer
│   │   ├── knowledge_base_screen.dart  # Document library with search
│   │   ├── knowledge_base_chat_screen.dart # AI Chat interface
│   │   ├── chat_history_screen.dart    # Chat history view
│   │   ├── printer_matcher_screen.dart # Printer recommendation AI
│   │   ├── pdf_viewer_screen.dart      # In-app PDF viewer
│   │   ├── profile_screen.dart         # User profile & settings
│   │   └── profile_screen_premium.dart # Premium profile variant
│   ├── models/
│   │   └── printer_recommendation.dart # Printer matcher data models
│   ├── widgets/
│   │   ├── chat_message_bubble.dart    # Chat UI component
│   │   └── printer_card.dart           # Printer recommendation card
│   ├── theme/
│   │   └── eds_theme.dart              # Centralized theme configuration
│   ├── services/
│   │   ├── auth_service.dart           # Authentication logic
│   │   ├── kb_chat_service.dart        # Knowledge base chatbot API
│   │   ├── printer_chat_service.dart   # Printer matcher chatbot API
│   │   ├── news_service.dart           # News/announcements API
│   │   └── upload_service.dart         # S3 upload service
│   └── main.dart                       # App entry point
├── public/
│   ├── index.php                       # Front controller (routes all requests)
│   └── assets/
│       ├── style.css                   # Admin panel styles
│       ├── admin.js                    # Admin panel scripts
│       ├── auth.js                     # Authentication scripts
│       └── images/                     # Public images
├── src/
│   ├── Router.php                      # URL routing system
│   ├── Controllers/
│   │   ├── ApiController.php           # Mobile app API endpoints
│   │   ├── AdminController.php         # Admin panel pages
│   │   └── AdminApiController.php      # Admin API endpoints
│   └── Admin/
│       └── views/                      # Admin panel HTML views
├── api/
│   ├── verify_token.php                # Firebase token verification
│   ├── get_promotions.php              # Fetch promotions with presigned URLs
│   ├── get_news.php                    # Fetch news with presigned URLs
│   ├── get_profile.php                 # User profile data
│   ├── update_profile.php              # Update user info
│   ├── upload.php                      # S3 file upload
│   ├── check_activation.php            # User status check
│   ├── get_machine_codes.php           # Get invoice machine codes
│   ├── get_code_invoices.php           # Get invoices for code
│   ├── get_knowledge_base.php          # Get KB documents
│   ├── send_kb_message.php             # Send KB chat message
│   ├── printer_chat.php                # Printer matcher chat
│   ├── config/
│   │   ├── database.php                # DB connection wrapper
│   │   └── load_env.php                # Environment loader
│   ├── lib/
│   │   ├── JWTVerifier.php             # Firebase token decoder
│   │   ├── SimpleS3.php                # S3 client with presigned URLs
│   │   └── InvoiceParser.php           # Invoice filename parser
│   └── admin/
│       ├── index.php                   # Admin login page
│       ├── dashboard.php               # Dashboard stats page
│       ├── users.php                   # User management page
│       ├── promotions.php              # Promotions management page
│       ├── news.php                    # News management page
│       ├── invoices.php                # Invoice bulk upload page
│       ├── knowledge_base.php          # KB management page
│       ├── printer_requests.php        # Printer matcher analytics
│       ├── scan.php                    # QR scanner for activation
│       ├── get_dashboard_stats.php     # API: Dashboard metrics
│       ├── get_all_users.php           # API: User list
│       ├── bulk_save_invoices.php      # API: Save invoice batch
│       └── upload_printer_kb.php       # API: Upload printer specs
├── config/
│   ├── database.php                    # Database connection (env-based)
│   ├── load_env.php                    # .env file loader
│   ├── ai_config.php                   # AI Agent credentials (gitignored)
│   └── s3_config.php                   # AWS S3 credentials (gitignored)
└── assets/
    └── images/
        ├── eds_logo.jpg                # EDS brand logo (JPG)
        └── eds_logo.png                # EDS brand logo (PNG)
```

---

## 🚀 Setup Instructions

### Prerequisites
- Flutter SDK 3.0+
- PHP 8.0+
- PostgreSQL (production) or MySQL/MariaDB (development)
- Firebase project
- AWS account (for S3)

### 1. Clone Repository
```bash
git clone <repository-url>
cd eds_app
```

### 2. Flutter Setup
```bash
# Install dependencies
flutter pub get

# Run app (Android emulator)
flutter run
```

### 3. Firebase Configuration
1. Create Firebase project at [console.firebase.google.com](https://console.firebase.google.com)
2. Download `google-services.json` (Android) and `GoogleService-Info.plist` (iOS)
3. Place files in respective platform folders
4. Enable Authentication providers (Email, Google, Apple)

### 4. Database Setup

#### Production (PostgreSQL/Neon)
```sql
-- Users table
CREATE TABLE users (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  firebase_uid VARCHAR(128) UNIQUE NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  name VARCHAR(255),
  profile_image_url TEXT,
  login_method VARCHAR(50) DEFAULT 'email',
  status VARCHAR(50) DEFAULT 'inactive',
  role VARCHAR(50) DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Promotions table
CREATE TABLE promotions (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  image_url TEXT NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Invoices table
CREATE TABLE invoices (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  code VARCHAR(50) NOT NULL,
  month VARCHAR(20) NOT NULL,
  file_url TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User Codes (Machine Code Assignment)
CREATE TABLE user_codes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    code VARCHAR(50) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, code)
);
```

### 5. Backend Configuration

#### Local Development
Create `.env` file in project root:
```env
# Database
DB_HOST=localhost
DB_NAME=eds_db
DB_USER=root
DB_PASS=your_password

# AWS S3
AWS_ACCESS_KEY=your-access-key
AWS_SECRET_KEY=your-secret-key
AWS_REGION=ap-southeast-1
AWS_BUCKET=your-bucket-name

# DigitalOcean AI Agents
DO_AGENT_BASE_URL=your-kb-agent-url
DO_AGENT_API_KEY=your-kb-agent-key
DO_PRINTER_AGENT_URL=your-printer-agent-url
DO_PRINTER_AGENT_KEY=your-printer-agent-key

# Firebase (for password reset functionality)
# Get this from Firebase Console > Project Settings > General > Web API Key
# Or use the web API key from lib/firebase_options.dart
FIREBASE_API_KEY=AIzaSyCeVSNrtNEmclI0jtMEvHWt4QrCdZDDCB0

# Environment
APP_ENV=development
```

**Configuration Files:**
- `config/database.php` - Auto-detects environment (reads from .env or environment variables)
- `config/load_env.php` - Loads .env file for local development
- `config/ai_config.php` - AI agent credentials (gitignored, use .env instead)
- `config/s3_config.php` - S3 credentials (gitignored, use .env instead)

#### Production (Docker + Nginx)
Set environment variables in your hosting server:
```bash
# Database
export POSTGRES_HOST=your-db-host
export POSTGRES_DATABASE=eds_db
export POSTGRES_USER=your-db-user
export POSTGRES_PASSWORD=your-db-password

# AWS S3
export AWS_ACCESS_KEY=your-access-key
export AWS_SECRET_KEY=your-secret-key
export AWS_REGION=ap-southeast-1
export AWS_BUCKET=your-bucket-name

# AI Agents
export DO_AGENT_BASE_URL=your-kb-agent-url
export DO_AGENT_API_KEY=your-kb-agent-key
export DO_PRINTER_AGENT_URL=your-printer-agent-url
export DO_PRINTER_AGENT_KEY=your-printer-agent-key

export APP_ENV=production
```

### 6. Start Backend Server

**Local Development:**
```bash
# Using PHP built-in server
php -S 0.0.0.0:8000 -t public public/router_dev.php
```

**Production (Docker):**
```bash
# Build Docker image
docker build -t eds-app .

# Run container
docker run -d -p 80:80 \
  -e POSTGRES_HOST=your-db-host \
  -e POSTGRES_DATABASE=eds_db \
  -e POSTGRES_USER=your-user \
  -e POSTGRES_PASSWORD=your-password \
  -e AWS_ACCESS_KEY=your-key \
  -e AWS_SECRET_KEY=your-secret \
  --name eds-app eds-app
```

### 7. Update API Endpoints
**Flutter configuration** is handled automatically by `lib/config/environment.dart`:
- **Local development:** `http://localhost:8000` or `http://10.0.2.2:8000` (Android emulator)
- **Production:** `https://edsapp.edsoffice.com.my` (automatically detected in release builds)

---

## 🔑 API Endpoints

### Mobile App Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/verify_token.php` | POST | Verify Firebase token, create/fetch user | Token |
| `/api/check_activation.php` | POST | Check user activation status | Token |
| `/api/get_profile.php` | GET/POST | Get user profile with presigned URLs | Token |
| `/api/update_profile.php` | POST | Update user name/picture | Token |
| `/api/upload.php` | POST | Upload file to S3, return S3 key | Token |
| `/api/get_promotions.php` | GET | Fetch promotions with presigned URLs | None |
| `/api/get_news.php` | GET | Fetch news with presigned URLs | None |
| `/api/get_machine_codes.php` | GET/POST | Get distinct invoice machine codes | Token |
| `/api/get_code_invoices.php` | GET/POST | Get invoices for specific code | Token |
| `/api/get_presigned_url.php` | GET/POST | Generate presigned URL for S3 file | None |

### Knowledge Base Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/get_knowledge_base.php` | GET | Fetch KB documents with search | None |
| `/api/upload_knowledge_base.php` | POST | Upload PDF to KB (admin) | Token |
| `/api/send_kb_message.php` | POST | Send message to KB AI Agent | Token |
| `/api/get_kb_messages.php` | GET | Fetch user's KB chat history | Token |
| `/api/clear_kb_history.php` | POST | Clear user's KB chat history | Token |
| `/api/create_kb_session.php` | POST | Create new KB chat session | Token |
| `/api/get_kb_sessions.php` | GET | Get all KB chat sessions | Token |
| `/api/get_kb_favorites.php` | GET | Get favorited KB documents | Token |
| `/api/toggle_kb_favorite.php` | POST | Toggle KB document favorite | Token |

### Printer Matcher Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/printer_chat.php` | POST | Send message to Printer Matcher AI | None |

### Admin Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/admin/get_dashboard_stats.php` | POST | Dashboard metrics | Admin |
| `/api/admin/get_all_users.php` | POST | List all users | Admin |
| `/api/admin/update_user_status.php` | POST | Activate/deactivate user | Admin |
| `/api/admin/update_user_role.php` | POST | Change user role | Admin |
| `/api/admin/delete_user.php` | POST | Soft delete user | Admin |
| `/api/admin/get_all_promotions.php` | POST | List all promotions | Admin |
| `/api/admin/update_promotion.php` | POST | Update promotion | Admin |
| `/api/admin/delete_promotion.php` | POST | Delete promotion | Admin |
| `/api/admin/get_all_news.php` | POST | List all news | Admin |
| `/api/admin/update_news.php` | POST | Update news | Admin |
| `/api/admin/delete_news.php` | POST | Delete news | Admin |
| `/api/admin/bulk_save_invoices.php` | POST | Bulk save invoice records | Admin |
| `/api/admin/get_printer_requests.php` | POST | Get printer matcher analytics | Admin |
| `/api/admin/upload_printer_kb.php` | POST | Upload printer specs to S3 | Admin |

**Authentication:** 
- **Token**: Include `idToken` (Firebase) in request body
- **Admin**: Requires admin role + valid session

---

## 🔒 Security & Architecture

### S3 Presigned URLs
- **Database Storage**: Only S3 keys stored (e.g., `avatars/abc123.jpg`)
- **Access Method**: Backend generates temporary presigned URLs (1-hour expiry)
- **Security**: S3 bucket has "Block Public Access" enabled
- **Performance**: Direct client-to-S3 downloads, no proxy overhead

### Authentication
- **Firebase token verification** - All protected endpoints
- **JWTVerifier class** - Custom Firebase token decoder
- **Login method tracking** - Prevents unauthorized password changes
- **Password reauthentication** - Required before password change
- **Status-based access** - Inactive users see QR screen
- **Session persistence** - Token and user data saved locally

### User Registration Flow
1. **Email/Password**: Register button → Complete Profile (empty form)
2. **Third-Party**: First Google/Apple sign-in → Auto-detect new user → Complete Profile (pre-filled)
3. **Session Save**: All registration methods save session data (token, userId, status)
4. **Navigation**: New users → Inactive screen with QR code

---

## 📱 Supported Platforms

- ✅ **Android** - Fully tested
- ✅ **iOS** - Ready (Apple Sign-In configured)
- ⏳ **Web** - Needs testing/adjustments

---

## ✅ Recent Updates (January 2026)

### URL Validation Fix (v1.6.2 - January 21, 2026)
- ✅ **Invalid Product URL Handling** - Fixed printer recommendations crashing when product_url is "-" or invalid  
- ✅ **URL Scheme Validation** - Added http/https scheme validation before opening links
- ✅ **User Feedback** - Show "Product Link Not Available" button for invalid URLs instead of error
- ✅ **Backend Restructure** - Migrated from Railway to self-hosted Docker + Nginx deployment
- ✅ **Updated Documentation** - README now reflects current Docker-based deployment strategy

### Printer Matcher AI (v1.6.0)
- ✅ **AI-Powered Printer Recommendations** - Interactive chatbot that guides users through requirements
- ✅ **Anonymous Device Tracking** - Device ID-based tracking without login requirement
- ✅ **6-Question Flow** - Office size, volume, color, paper size, scanning, budget
- ✅ **Top 3 Recommendations** - AI Agent returns best matches with scores and reasoning
- ✅ **Suggestion Chips** - Quick-start prompts for common scenarios
- ✅ **Admin Analytics Dashboard** - View all customer requests with filtering and CSV export
- ✅ **S3 Knowledge Base Upload** - Admin panel for uploading printer specs to S3 `printers/` folder
- ✅ **JSON Template System** - Format guide with downloadable template for easy data entry
- ✅ **Product URL Validation** - Robust null handling and URL validation with error messages
- ✅ **Database Logging** - `customer_requests` table tracks all anonymous interactions

### Security Update (v1.6.1)
- ✅ **Secure Device ID** - Replaced legacy device tracking with secure, privacy-compliant UUID system
- ✅ **Persistence Upgrade** - Device ID now persists across app reinstalls using secure storage
- ✅ **Privacy Compliance** - Removed deprecated Android ID usage in favor of randomly generated UUIDs

## ✅ Recent Updates (December 2025)

### Theme & AI Chat (v1.5.0)
- ✅ **Knowledge Base Chat** - Interactive AI chatbot for querying documentation
- ✅ **Theme Centralization** - Implemented `EDSTheme` for consistent styling
- ✅ **Brand Refresh** - Updated Logo, verified assets, and unified color scheme (Royal Blue dominant)
- ✅ **UI Polish** - Refined icons, PDF viewer contrast, and "Remember Me" visibility
- ✅ **Profile Fixes** - Implemented robust token refreshing to fix session timeouts

### Knowledge Base & PDF Viewing (v1.4.0)
- ✅ **Knowledge Base Feature** - Document library with search and admin upload portal
- ✅ **In-app PDF Viewer** - Syncfusion PDF viewer with zoom and text selection
- ✅ **5-Tab Navigation** - Added Knowledge Base tab with center-elevated home button
- ✅ **Logout Button** - Quick logout from home screen with confirmation
- ✅ **Admin Portal** - Web interface for uploading PDFs with metadata
- ✅ **Unified PDF Viewing** - Both invoices and knowledge base use in-app viewer
- ✅ **Database Schema** - New knowledge_base table with title, subtitle, file_url

### Registration & Onboarding (v1.3.0)
- ✅ **Complete Profile Screen** - Unified registration for email and third-party users
- ✅ **Smart Registration** - Auto-detect new Google/Apple users
- ✅ **Session Persistence** - Fixed QR code and activation issues after registration
- ✅ **Register Button** - Direct registration flow from login
- ✅ **Proper Navigation** - New users go to inactive screen immediately

### S3 & Image Management (v1.2.1)
- ✅ **Presigned URLs** - Replaced proxy pattern with secure AWS Signature V4 URLs
- ✅ **S3 Key Storage** - Database stores only S3 keys, not full URLs
- ✅ **Self-Hosted Deployment** - Migrated to Docker + Nginx for production
- ✅ **Profile Images** - Store as S3 keys, display with presigned URLs
- ✅ **Promotion Images** - Same presigned URL pattern
- ✅ **Upload Service** - Returns S3 keys instead of proxy URLs
- ✅ **Refresh Button** - Added to promotions screen for manual reload

### UI Polish & Branding (v1.2.0)
- ✅ **Consistent EDS branding** - All screens use royal blue (#3F51B5)
- ✅ **Card styling** - Light blue tint across dashboard and promotions
- ✅ **AppBar consistency** - Left-aligned titles, white text on blue
- ✅ **Two-tone design** - Promotions header with blue/white contrast
- ✅ **Enhanced elevation** - Better visual hierarchy
- ✅ **Branded icons** - All interactive elements use EDS colors

---


## 🔧 Troubleshooting

### Registration Issues
If registration doesn't save session properly:
- **Symptom**: QR code missing or incorrect after registration
- **Cause**: Session data not saved to SharedPreferences
- **Fix**: Ensure `complete_profile_screen.dart` calls `_saveSession()` after registration

### S3 Image Issues
If images don't load:
- **Cause**: Presigned URL expiry or missing AWS credentials
- **Fix**: Check Railway environment variables for AWS credentials
- **Note**: Presigned URLs expire after 1 hour

### Android Network Issues
If the app cannot connect to the local backend (`10.0.2.2`):
- **Manifest**: Ensure `AndroidManifest.xml` has:
  ```xml
  <uses-permission android:name="android.permission.INTERNET" />
  <application ... android:usesCleartextTraffic="true">
  ```
- **Emulator**: Ensure the emulator has internet access

### Docker Deployment
If backend fails in Docker:
- **Environment Variables**: Verify all vars are passed to container with `-e` flags
- **Database Connection**: Ensure PostgreSQL is accessible from container
- **Logs**: Check container logs with `docker logs eds-app`
- **Nginx**: Verify nginx configuration in Dockerfile
- **Permissions**: Ensure proper file permissions for uploads and cache

---

## 📝 Development Notes

### Running the App
```bash
# Development mode
flutter run

# Release mode (Android)
flutter build apk --release

# Release mode (iOS)
flutter build ios --release
```

### Hot Reload
- Press `r` in terminal for hot reload
- Press `R` for hot restart

### Clean Build
```bash
flutter clean
flutter pub get
flutter run
```

### Deployment
**Backend (Self-Hosted):**
1. Set up server with Docker and Nginx
2. Configure environment variables on server
3. Build and run Docker container
4. Point domain to server IP
5. Configure Nginx reverse proxy
6. Set up SSL certificate (Let's Encrypt)

**Mobile:**
- Android: Build APK and upload to Play Store
- iOS: Build IPA and upload to App Store Connect

---

## 📄 License

Proprietary - E-Document Solutions (EDS)

---

### Invoice Security Update (v1.6.3 - January 21, 2026)
- ✅ **User-Specific Filtering** - Invoices are now strictly filtered by user assignment
- ✅ **Secure Authorization** - Backend verifies user ownership of machine codes before access
- ✅ **Admin Assignment UI** - New workflow in admin panel to assign uploaded invoices to users
- ✅ **Database Optimization** - New `user_codes` junction table for efficient code-to-user mapping
- ✅ **Mobile App Update** - Home screen and Invoice list now show only assigned machine codes

---
 
**Last Updated:** January 21, 2026  
**Version:** 1.6.3 - User-Specific Invoice Filtering
