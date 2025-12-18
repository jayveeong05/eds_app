# EDS App - E-Document Solutions

A mobile application for managing e-documents, promotions, and invoices with Firebase authentication and AWS S3 integration.

---

## 🎨 Brand Colors

- **Primary:** Royal Blue `#3F51B5`
- **Accent:** Red `#E53935`
- **Card Background:** Light Blue Tint `#F5F7FF`
- **Screen Background:** Light Blue-Gray `#F0F3FF`
- **Profile Avatar:** Light EDS Blue `#E8EAF6`

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
- **Invoice listing** (screen exists, ready for enhancement)
- **PDF support** (backend ready)

### 🧭 Navigation
- **Bottom navigation bar** - 4 tabs (Home, Promotions, Invoices, Profile)
- **EDS branded selection** - Royal blue for active tab
- **Tab state management** - Persistent across sessions
- **Material Design 3** - Modern UI components
- **Consistent AppBars** - All screens use EDS royal blue with left-aligned titles

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
│   │   ├── dashboard_screen.dart       # Home with latest promotions
│   │   ├── login_screen.dart           # Auth screen with registration dialog
│   │   ├── complete_profile_screen.dart # Unified registration/profile completion
│   │   ├── inactive_screen.dart        # QR code for activation
│   │   ├── main_navigation.dart        # Bottom nav wrapper
│   │   ├── promotions_screen.dart      # Promotion carousel with refresh
│   │   ├── invoices_screen.dart        # Invoice listing
│   │   └── profile_screen.dart         # User profile & settings
│   ├── services/
│   │   ├── auth_service.dart           # Authentication logic
│   │   └── upload_service.dart         # S3 upload (returns S3 keys)
│   └── main.dart                       # App entry point
├── backend/
│   ├── api/
│   │   ├── verify_token.php            # Firebase token verification + new user flag
│   │   ├── get_promotions.php          # Fetch promotions with presigned URLs
│   │   ├── get_profile.php             # User profile data with presigned URLs
│   │   ├── update_profile.php          # Update user info
│   │   ├── upload.php                  # S3 file upload (returns S3 key)
│   │   ├── add_promotion.php           # Create promotion
│   │   └── check_activation.php        # User status check
│   ├── config/
│   │   ├── database.php                # DB connection (env-aware)
│   │   ├── load_env.php                # Environment variable loader
│   │   └── s3_config.php               # AWS S3 credentials (local only)
│   ├── lib/
│   │   ├── JWTVerifier.php             # Firebase token decoder
│   │   └── SimpleS3.php                # Custom S3 client with presigned URLs
│   └── admin/
│       ├── index.php                   # Admin login
│       ├── dashboard.php               # Admin stats
│       ├── promotions.php              # Promotion management
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

## ✅ Recent Updates (December 2025)

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

**Last Updated:** December 18, 2025  
**Version:** 1.3.0 - Complete Profile & S3 Presigned URLs
