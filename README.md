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
- **Latest promotions feed** - Displays 3 most recent promotions
- **Polished card design** - Light blue tint with enhanced elevation
- **Vertical scrollable cards** - Image, user, timestamp, description
- **Pull-to-refresh** - Swipe down to reload
- **Relative timestamps** - "5m ago", "2h ago", "3d ago"
- **User avatars** - Profile pictures or initials
- **Branded AppBar** - EDS royal blue, left-aligned title
- **Static cards** - Read-only display

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
- **Bulk Upload System** - Admin panel for uploading multiple PDFs
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
- **EDS Branded Selection** - Soft Sage for active tab
- **Tab State Management** - Persistent across sessions
- **Material Design 3** - Modern UI components
- **Consistent AppBars** - All screens use Slate Gray/Royal Blue with logout option
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
- **QR Flutter** - QR code generation
- **Image Picker** - Photo selection
- **Syncfusion PDF Viewer** - In-app PDF viewing with zoom and text selection
- **URL Launcher** - External link handling
- **Device Info Plus** - Device identification for anonymous tracking

### Backend (PHP)
- **PHP 8+** - Server-side logic
- **PostgreSQL (Neon)** - Production database
- **MySQL/MariaDB** - Local development database
- **Firebase Admin SDK** - Token verification
- **SimpleS3 (Custom)** - AWS S3 integration with presigned URLs
- **PDO** - Database abstraction
- **Railway** - Production deployment platform

### Infrastructure
- **AWS S3** - File storage (profile pictures, invoices, promotions)
  - **Security**: S3 keys stored in database, presigned URLs for access
  - **Access**: Temporary signed URLs (1-hour expiry)
  - **Performance**: Direct client access, no proxy overhead
- **Firebase** - Authentication services
- **Railway** - Production PHP backend hosting
- **Neon** - Production PostgreSQL database
- **Local PHP server** - Development backend

---

## 📁 Project Structure

```
eds_app/
├── lib/
│   ├── screens/
│   │   ├── landing_screen.dart         # Initial auth screen
│   │   ├── login_screen.dart           # Auth screen with registration dialog
│   │   ├── complete_profile_screen.dart # Unified registration/profile completion
│   │   ├── inactive_screen.dart        # QR code for activation
│   │   ├── main_navigation.dart        # 5-tab bottom nav wrapper
│   │   ├── home_screen.dart            # Combined promotions + invoices dashboard
│   │   ├── promotions_screen.dart      # Promotion carousel with refresh
│   │   ├── invoices_screen.dart        # Invoice listing
│   │   ├── code_detail_screen.dart     # Invoice detail with PDF viewer
│   │   ├── knowledge_base_screen.dart  # Document library with search
│   │   ├── knowledge_base_chat_screen.dart # AI Chat interface
│   │   ├── pdf_viewer_screen.dart      # In-app PDF viewer
│   │   └── profile_screen.dart         # User profile & settings
│   ├── theme/
│   │   └── eds_theme.dart              # Centralized theme configuration
│   ├── services/
│   │   ├── auth_service.dart           # Authentication logic
│   │   ├── kb_chat_service.dart        # Chatbot API service
│   │   └── upload_service.dart         # S3 upload (returns S3 keys)
│   └── main.dart                       # App entry point
├── backend/
│   ├── api/
│   │   ├── verify_token.php            # Firebase token verification + new user flag
│   │   ├── get_promotions.php          # Fetch promotions with presigned URLs
│   │   ├── get_profile.php             # User profile data with presigned URLs
│   │   ├── update_profile.php          # Update user info
│   │   ├── upload.php                  # S3 file upload (custom filename for invoices)
│   │   ├── add_promotion.php           # Create promotion
│   │   ├── check_activation.php        # User status check
│   │   ├── get_machine_codes.php       # Get distinct invoice codes
│   │   ├── get_code_invoices.php       # Get invoices for specific code
│   │   ├── send_kb_message.php         # Send message to AI agent
│   │   ├── get_kb_messages.php         # Get chat history
│   │   ├── clear_kb_history.php        # Clear chat history
│   │   └── admin/
│   │       ├── bulk_save_invoices.php  # Parse and save invoice filenames
│   │       ├── get_all_invoices.php    # Admin invoice listing
│   │       └── get_all_promotions.php  # Admin promotion management
│   ├── config/
│   │   ├── database.php                # DB connection (env-aware)
│   │   ├── load_env.php                # Environment variable loader
│   │   └── s3_config.php               # AWS S3 credentials (local only)
│   ├── lib/
│   │   ├── JWTVerifier.php             # Firebase token decoder
│   │   ├── SimpleS3.php                # Custom S3 client with presigned URLs
│   │   ├── InvoiceParser.php           # Filename parser (CODE-MONTH.pdf)
│   │   └── AdminMiddleware.php         # Admin authentication
│   └── admin/
│       ├── index.php                   # Admin login
│       ├── dashboard.php               # Admin stats
│       ├── promotions.php              # Promotion management
│       ├── invoices.php                # Invoice bulk upload
│       └── users.php                   # User management
└── assets/
    └── images/
        └── company_logo.png            # EDS brand logo
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
  user_id UUID REFERENCES users(id) ON DELETE CASCADE,
  month_date DATE NOT NULL,
  pdf_url TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 5. Backend Configuration

#### Local Development
**Database Config (`backend/config/database.php`)**
```php
<?php
// Auto-detects environment (local vs Railway)
class Database {
    private $host = "localhost";
    private $db_name = "eds_db";
    private $username = "root";
    private $password = "your_password";
    // ...
}
```

**S3 Config (`backend/config/s3_config.php`)**
```php
<?php
define('AWS_ACCESS_KEY', 'your-access-key');
define('AWS_SECRET_KEY', 'your-secret-key');
define('AWS_REGION', 'us-east-1');
define('AWS_BUCKET', 'your-bucket-name');
```

#### Production (Railway)
Set environment variables in Railway dashboard:
- `AWS_ACCESS_KEY`
- `AWS_SECRET_KEY`
- `AWS_REGION`
- `AWS_BUCKET`
- `POSTGRES_HOST`
- `POSTGRES_DATABASE`
- `POSTGRES_USER`
- `POSTGRES_PASSWORD`
- `POSTGRES_SSLMODE=require`

### 6. Start Backend Server

**Local Development:**
```bash
cd backend
php -S 0.0.0.0:8000
```

**Production:**
Deploy to Railway - automatically detects and runs PHP server

### 7. Update API Endpoints
In Flutter files, update endpoints:
- **Local development:** `http://10.0.2.2:8000` (Android emulator)
- **Production:** `https://your-railway-app.up.railway.app`

---

## 🔑 API Endpoints

| Endpoint | Method | Description | Auth |
|----------|--------|-------------|------|
| `/api/verify_token.php` | POST | Verify Firebase token, create/fetch user, return `is_new_user` flag | Token |
| `/api/get_promotions.php?limit=3` | GET | Fetch promotions with presigned URLs | None |
| `/api/get_profile.php` | POST | Get user profile data with presigned URLs | Token |
| `/api/update_profile.php` | POST | Update user name/picture, return presigned URLs | Token |
| `/api/upload.php` | POST | Upload file to S3, return S3 key | Token |
| `/api/add_promotion.php` | POST | Create new promotion | Token |
| `/api/check_activation.php` | POST | Check user activation status | Token |
| `/api/get_knowledge_base.php` | GET | Fetch knowledge base documents with search | None |
| `/api/send_kb_message.php` | POST | Send user message to AI Agent and get response | Token |
| `/api/get_kb_messages.php` | POST | Fetch user's chat history | Token |
| `/api/clear_kb_history.php` | POST | Clear user's chat history | Token |
| `/api/upload_knowledge_base.php` | POST | Upload PDF to knowledge base (admin) | None |
| `/api/delete_knowledge_base.php` | POST | Delete knowledge base item (admin) | None |
| `/api/get_presigned_url.php` | POST | Generate presigned URL for S3 file | None |

**Authentication:** Include `idToken` in request body for protected endpoints.

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
- ✅ **Production Deployment** - Railway integration with environment variables
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

## 🚧 Future Enhancements

### High Priority
- [x] **Admin panel** - Web interface for user/content management (Completed v1.2.0)
- [x] **S3 Presigned URLs** - Secure image access (Completed v1.2.1)
- [x] **Complete Profile** - Registration flow (Completed v1.3.0)
- [ ] **Invoice screen** - Complete invoice listing with filters
- [ ] **Push notifications** - Account approval, new promotions

### Medium Priority
- [ ] **User-not-found dialog** - Fix email login registration prompt
- [ ] **Search functionality** - Search promotions and invoices
- [ ] **Enhanced loading states** - Skeleton loaders with shimmer effect
- [ ] **Dark mode** - Theme switcher
- [ ] **Offline support** - Cache data locally
- [ ] **Analytics** - Track user engagement

### Low Priority
- [ ] **Page transitions** - Smooth screen animations
- [ ] **Empty state illustrations** - Branded graphics
- [ ] **Export invoices** - PDF download
- [ ] **Multi-language** - i18n support

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

### Railway Deployment
If backend fails on Railway:
- **Environment Variables**: Verify all AWS and PostgreSQL vars are set
- **Database Connection**: Check Neon connection string
- **Logs**: Check Railway deployment logs for PHP errors

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
**Backend (Railway):**
1. Connect GitHub repository to Railway
2. Set environment variables
3. Railway auto-deploys on push to main

**Mobile:**
- Android: Build APK and upload to Play Store
- iOS: Build IPA and upload to App Store Connect

---

## 📄 License

Proprietary - E-Document Solutions (EDS)

---

**Last Updated:** January 12, 2026  
**Version:** 1.6.1 - Device ID Security Update
