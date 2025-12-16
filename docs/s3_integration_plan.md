# AWS S3 Integration Plan for EDS App

## 📋 Current Status

**What's Already Built:**
- ✅ S3 configuration file (`backend/config/s3_config.php`) - needs credentials
- ✅ Custom S3 upload library (`backend/lib/SimpleS3.php`) - AWS Signature V4
- ✅ Upload API endpoint (`backend/api/upload.php`) - ready to use
- ❌ Missing: AWS credentials and bucket setup
- ❌ Missing: Flutter integration for file uploads

**Supported Upload Types:**
- Promotions images
- Invoice PDFs
- User profile avatars

---

## 🎯 Implementation Objectives

1. **Set up AWS S3 bucket** with proper permissions
2. **Configure AWS credentials** in backend
3. **Test backend upload functionality** 
4. **Build Flutter upload service** for mobile/web
5. **Integrate uploads** into Profile, Promotions, and Invoices screens
6. **Add image optimization** (compression, resizing)
7. **Implement security** (file validation, size limits)

---

## 📊 Implementation Phases

### Phase 1: AWS S3 Setup ⚙️

**Duration:** 30 minutes

#### Step 1.1: Create AWS Account & S3 Bucket

1. **Sign up for AWS** (if not already): https://aws.amazon.com/
2. **Create S3 Bucket:**
   ```
   - Bucket Name: eds-app-storage (or your preferred name)
   - Region: ap-southeast-1 (Singapore) or us-east-1 (US East)
   - Block all public access: UNCHECK (we need public read for images)
   - Bucket Versioning: Disabled (optional)
   - Encryption: AES-256 (optional but recommended)
   ```

#### Step 1.2: Configure Bucket Policy

**Option A: Public Read Access** (for promotions/avatars)
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::eds-app-storage/promotions/*"
    },
    {
      "Sid": "PublicReadAvatars",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::eds-app-storage/avatars/*"
    }
  ]
}
```

**Option B: Private with Signed URLs** (more secure, for invoices)
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PrivateAccess",
      "Effect": "Deny",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::eds-app-storage/invoices/*"
    }
  ]
}
```

#### Step 1.3: Enable CORS

Add CORS configuration to allow uploads from your Flutter app:

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedOrigins": ["*"],
    "ExposeHeaders": ["ETag"]
  }
]
```

#### Step 1.4: Create IAM User for Programmatic Access

1. Go to **IAM Console** → Users → Add User
2. Username: `eds-app-uploader`
3. Access type: ✅ **Programmatic access** (Access Key ID + Secret)
4. Attach policy: **AmazonS3FullAccess** (or create custom policy below)

**Custom Policy (More Secure):**
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject"
      ],
      "Resource": "arn:aws:s3:::eds-app-storage/*"
    },
    {
      "Effect": "Allow",
      "Action": "s3:ListBucket",
      "Resource": "arn:aws:s3:::eds-app-storage"
    }
  ]
}
```

5. **Save Access Key ID and Secret Access Key** (you won't see them again!)

---

### Phase 2: Backend Configuration 🔧

**Duration:** 15 minutes

#### Step 2.1: Update S3 Config

Edit `backend/config/s3_config.php`:

```php
<?php
// backend/config/s3_config.php

// AWS Credentials from IAM User
define('AWS_ACCESS_KEY', 'AKIAIOSFODNN7EXAMPLE'); // Replace with your key
define('AWS_SECRET_KEY', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'); // Replace
define('AWS_REGION', 'ap-southeast-1'); // Your bucket region
define('AWS_BUCKET', 'eds-app-storage'); // Your bucket name

// Base URL for public files
define('AWS_S3_BASE_URL', 'https://' . AWS_BUCKET . '.s3.' . AWS_REGION . '.amazonaws.com');

// Optional: CloudFront CDN URL (for better performance)
// define('AWS_S3_BASE_URL', 'https://d111111abcdef8.cloudfront.net');
?>
```

#### Step 2.2: Test Backend Upload

Create a test script to verify S3 upload works:

**File:** `backend/test_s3_upload.php`
```php
<?php
require_once __DIR__ . '/config/s3_config.php';
require_once __DIR__ . '/lib/SimpleS3.php';

// Create a test file
$test_file = tempnam(sys_get_temp_dir(), 'test');
file_put_contents($test_file, 'Hello S3!');

// Upload to S3
$s3 = new SimpleS3(AWS_ACCESS_KEY, AWS_SECRET_KEY, AWS_REGION);
$result = $s3->putObject($test_file, AWS_BUCKET, 'test/hello.txt');

if ($result === true) {
    echo "✅ Upload successful!\n";
    echo "URL: " . AWS_S3_BASE_URL . "/test/hello.txt\n";
} else {
    echo "❌ Upload failed: $result\n";
}

// Cleanup
unlink($test_file);
?>
```

**Test it:**
```bash
php backend/test_s3_upload.php
```

---

### Phase 3: Flutter Upload Service 📱

**Duration:** 2 hours

#### Step 3.1: Add Dependencies

Update `pubspec.yaml`:
```yaml
dependencies:
  http: ^1.2.2
  file_picker: ^8.1.6  # For file selection
  image_picker: ^1.1.2  # For camera/gallery
  path: ^1.9.0
  mime: ^1.0.6  # For MIME type detection
```

Run:
```bash
flutter pub get
```

#### Step 3.2: Create Upload Service

**File:** `lib/services/upload_service.dart`

```dart
import 'dart:io';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:mime/mime.dart';
import 'package:path/path.dart' as path;

class UploadService {
  static const String baseUrl = 'http://your-server.com/backend'; // Update this
  
  /// Upload file to S3 via backend API
  /// [filePath] - Path to file (mobile)
  /// [fileBytes] - File bytes (web)
  /// [fileName] - Original file name
  /// [folder] - 'promotions', 'invoices', or 'avatars'
  /// Returns the S3 URL or null on error
  static Future<String?> uploadFile({
    String? filePath,
    Uint8List? fileBytes,
    required String fileName,
    required String folder,
  }) async {
    try {
      var uri = Uri.parse('$baseUrl/api/upload.php');
      var request = http.MultipartRequest('POST', uri);
      
      // Add folder parameter
      request.fields['folder'] = folder;
      
      // Add file
      if (filePath != null) {
        // Mobile upload
        var file = await http.MultipartFile.fromPath(
          'file',
          filePath,
          filename: fileName,
        );
        request.files.add(file);
      } else if (fileBytes != null) {
        // Web upload
        var mimeType = lookupMimeType(fileName) ?? 'application/octet-stream';
        var file = http.MultipartFile.fromBytes(
          'file',
          fileBytes,
          filename: fileName,
          contentType: http.MediaType.parse(mimeType),
        );
        request.files.add(file);
      } else {
        throw Exception('Either filePath or fileBytes must be provided');
      }
      
      // Send request
      var response = await request.send();
      var responseBody = await response.stream.bytesToString();
      
      if (response.statusCode == 201) {
        var data = json.decode(responseBody);
        return data['url'];
      } else {
        print('Upload failed: $responseBody');
        return null;
      }
    } catch (e) {
      print('Upload error: $e');
      return null;
    }
  }
  
  /// Upload image from ImagePicker
  static Future<String?> uploadImage({
    required File imageFile,
    required String folder,
  }) async {
    final fileName = path.basename(imageFile.path);
    return await uploadFile(
      filePath: imageFile.path,
      fileName: fileName,
      folder: folder,
    );
  }
  
  /// Upload PDF
  static Future<String?> uploadPDF({
    String? filePath,
    Uint8List? fileBytes,
    required String fileName,
  }) async {
    return await uploadFile(
      filePath: filePath,
      fileBytes: fileBytes,
      fileName: fileName,
      folder: 'invoices',
    );
  }
}
```

#### Step 3.3: Create File Picker Helper

**File:** `lib/services/file_service.dart`

```dart
import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:image_picker/image_picker.dart';

class FileService {
  static final ImagePicker _imagePicker = ImagePicker();
  
  /// Pick image from camera or gallery
  static Future<Map<String, dynamic>?> pickImage({
    required ImageSource source,
  }) async {
    try {
      final XFile? pickedFile = await _imagePicker.pickImage(
        source: source,
        maxWidth: 1920,
        maxHeight: 1920,
        imageQuality: 85,
      );
      
      if (pickedFile == null) return null;
      
      if (kIsWeb) {
        // Web: return bytes
        final bytes = await pickedFile.readAsBytes();
        return {
          'bytes': bytes,
          'name': pickedFile.name,
          'path': null,
        };
      } else {
        // Mobile: return file path
        return {
          'bytes': null,
          'name': pickedFile.name,
          'path': pickedFile.path,
        };
      }
    } catch (e) {
      print('Error picking image: $e');
      return null;
    }
  }
  
  /// Pick PDF file
  static Future<Map<String, dynamic>?> pickPDF() async {
    try {
      FilePickerResult? result = await FilePicker.platform.pickFiles(
        type: FileType.custom,
        allowedExtensions: ['pdf'],
      );
      
      if (result == null) return null;
      
      final file = result.files.first;
      
      if (kIsWeb) {
        return {
          'bytes': file.bytes,
          'name': file.name,
          'path': null,
        };
      } else {
        return {
          'bytes': null,
          'name': file.name,
          'path': file.path,
        };
      }
    } catch (e) {
      print('Error picking PDF: $e');
      return null;
    }
  }
}
```

---

### Phase 4: Integration with Screens 📲

**Duration:** 3 hours

#### Integration 1: Profile Picture Upload

Update `lib/screens/profile_screen.dart`:

```dart
import '../services/file_service.dart';
import '../services/upload_service.dart';
import 'package:image_picker/image_picker.dart';

// Add to ProfileScreenState class:

Future<void> _uploadProfileImage() async {
  // Show picker dialog
  showModalBottomSheet(
    context: context,
    builder: (context) => SafeArea(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ListTile(
            leading: Icon(Icons.camera_alt),
            title: Text('Take Photo'),
            onTap: () async {
              Navigator.pop(context);
              await _pickAndUploadImage(ImageSource.camera);
            },
          ),
          ListTile(
            leading: Icon(Icons.photo_library),
            title: Text('Choose from Gallery'),
            onTap: () async {
              Navigator.pop(context);
              await _pickAndUploadImage(ImageSource.gallery);
            },
          ),
        ],
      ),
    ),
  );
}

Future<void> _pickAndUploadImage(ImageSource source) async {
  setState(() => _isLoading = true);
  
  try {
    // Pick image
    final fileData = await FileService.pickImage(source: source);
    if (fileData == null) {
      setState(() => _isLoading = false);
      return;
    }
    
    // Upload to S3
    final url = await UploadService.uploadFile(
      filePath: fileData['path'],
      fileBytes: fileData['bytes'],
      fileName: fileData['name'],
      folder: 'avatars',
    );
    
    if (url == null) {
      _showError('Upload failed');
      setState(() => _isLoading = false);
      return;
    }
    
    // Update profile with new URL
    await _updateProfileImage(url);
    
  } catch (e) {
    _showError('Error: $e');
  }
  
  setState(() => _isLoading = false);
}
```

#### Integration 2: Invoice PDF Upload

Update `lib/screens/invoices_screen.dart`:

```dart
import '../services/file_service.dart';
import '../services/upload_service.dart';

// Add upload button to app bar
AppBar(
  title: Text('Invoices'),
  actions: [
    IconButton(
      icon: Icon(Icons.upload_file),
      onPressed: _uploadInvoice,
    ),
  ],
)

// Add upload method
Future<void> _uploadInvoice() async {
  setState(() => _isUploading = true);
  
  try {
    // Pick PDF
    final fileData = await FileService.pickPDF();
    if (fileData == null) {
      setState(() => _isUploading = false);
      return;
    }
    
    // Upload to S3
    final url = await UploadService.uploadPDF(
      filePath: fileData['path'],
      fileBytes: fileData['bytes'],
      fileName: fileData['name'],
    );
    
    if (url != null) {
      // Success - optionally add invoice record to database
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Invoice uploaded successfully!')),
      );
      _fetchInvoices(); // Refresh list
    } else {
      _showError('Upload failed');
    }
    
  } catch (e) {
    _showError('Error: $e');
  }
  
  setState(() => _isUploading = false);
}
```

#### Integration 3: Promotion Image Upload (Admin Feature)

This would be part of the admin portal, but here's the Flutter equivalent:

```dart
Future<void> _createPromotion() async {
  // Pick image
  final fileData = await FileService.pickImage(source: ImageSource.gallery);
  if (fileData == null) return;
  
  // Upload to S3
  final imageUrl = await UploadService.uploadFile(
    filePath: fileData['path'],
    fileBytes: fileData['bytes'],
    fileName: fileData['name'],
    folder: 'promotions',
  );
  
  if (imageUrl != null) {
    // Save promotion to database
    await _savePromotion(imageUrl);
  }
}
```

---

### Phase 5: Enhancements & Security 🔒

**Duration:** 2 hours

#### Enhancement 1: Image Compression (Mobile)

Add to `pubspec.yaml`:
```yaml
dependencies:
  flutter_image_compress: ^2.3.0
```

Update `FileService`:
```dart
import 'package:flutter_image_compress/flutter_image_compress.dart';

static Future<Map<String, dynamic>?> pickAndCompressImage({
  required ImageSource source,
}) async {
  final pickedFile = await _imagePicker.pickImage(source: source);
  if (pickedFile == null) return null;
  
  // Compress
  final compressedBytes = await FlutterImageCompress.compressWithFile(
    pickedFile.path,
    quality: 85,
    minWidth: 1920,
    minHeight: 1920,
  );
  
  return {
    'bytes': compressedBytes,
    'name': pickedFile.name,
    'path': null,
  };
}
```

#### Enhancement 2: File Validation

Add validation to `backend/api/upload.php`:

```php
// File size validation (max 10MB)
$maxSize = 10 * 1024 * 1024; // 10MB
if ($_FILES['file']['size'] > $maxSize) {
    http_response_code(413);
    echo json_encode(array("message" => "File too large. Max 10MB."));
    exit;
}

// MIME type validation
$allowedTypes = [
    'promotions' => ['image/jpeg', 'image/png', 'image/webp'],
    'invoices' => ['application/pdf'],
    'avatars' => ['image/jpeg', 'image/png', 'image/webp']
];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file_path);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes[$folder])) {
    http_response_code(415);
    echo json_encode(array("message" => "Invalid file type for $folder."));
    exit;
}
```

#### Enhancement 3: Progress Indicator

Add upload progress to Flutter:

```dart
// In UploadService
static Future<String?> uploadFileWithProgress({
  required String filePath,
  required String fileName,
  required String folder,
  required Function(double) onProgress,
}) async {
  // Use Dio package for progress tracking
  // Add dio: ^5.7.0 to pubspec.yaml
  
  final dio = Dio();
  final formData = FormData.fromMap({
    'folder': folder,
    'file': await MultipartFile.fromFile(filePath, filename: fileName),
  });
  
  try {
    final response = await dio.post(
      '$baseUrl/api/upload.php',
      data: formData,
      onSendProgress: (sent, total) {
        onProgress(sent / total);
      },
    );
    
    return response.data['url'];
  } catch (e) {
    return null;
  }
}
```

---

### Phase 6: Testing Checklist ✅

**Duration:** 1 hour

- [ ] Upload profile image from camera (iOS/Android)
- [ ] Upload profile image from gallery (iOS/Android)
- [ ] Upload profile image from web
- [ ] Upload PDF invoice from mobile
- [ ] Upload PDF invoice from web
- [ ] Upload promotion image (admin)
- [ ] Verify images are publicly accessible
- [ ] Verify invoices have correct permissions
- [ ] Test file size validation (upload 11MB file)
- [ ] Test file type validation (upload .txt file)
- [ ] Test with poor network connection
- [ ] Test upload cancellation
- [ ] Verify URLs are saved correctly in database
- [ ] Check S3 bucket organization (folders)
- [ ] Monitor AWS costs in billing dashboard

---

## 📊 Cost Estimation

**AWS S3 Pricing (ap-southeast-1):**

| Service | Usage | Monthly Cost |
|---------|-------|--------------|
| S3 Storage | 5 GB | $0.12 |
| PUT Requests | 1,000 uploads | $0.005 |
| GET Requests | 10,000 downloads | $0.004 |
| Data Transfer Out | 10 GB | $1.20 |
| **Total** | | **~$1.50/month** |

**Free Tier (First 12 months):**
- 5 GB storage
- 20,000 GET requests
- 2,000 PUT requests
- 15 GB data transfer out

**For 100 users:** Estimated $5-15/month
**For 1,000 users:** Estimated $30-50/month

---

## 🚀 Alternative: Cloudinary (Easier Setup)

If AWS S3 seems complex, **Cloudinary** is a great alternative with simpler integration:

**Pros:**
- Free tier: 25 GB storage, 25 GB bandwidth
- Built-in image optimization
- Easy Flutter SDK
- Automatic format conversion (WebP)
- Built-in CDN

**Setup:**
```yaml
# pubspec.yaml
dependencies:
  cloudinary: ^1.1.1
```

```dart
// Flutter code
final cloudinary = Cloudinary.signedConfig(
  apiKey: 'YOUR_API_KEY',
  apiSecret: 'YOUR_API_SECRET',
  cloudName: 'YOUR_CLOUD_NAME',
);

final response = await cloudinary.upload(
  file: imageFile.path,
  folder: 'promotions',
);

final url = response.secureUrl;
```

---

## 💡 My Recommendation

### **Start with AWS S3** (More Control & Cheaper at Scale)

**Why?**
1. You already have the backend code written
2. More cost-effective for large files (PDFs)
3. Better for learning cloud infrastructure
4. More control over bucket policies
5. Can add CloudFront CDN later for better performance

**Timeline:**
- **Day 1**: AWS setup + backend testing (2 hours)
- **Day 2**: Flutter upload service (3 hours)
- **Day 3**: Screen integration (3 hours)
- **Day 4**: Testing + fixes (2 hours)

**Total: ~10 hours over 4 days**

---

## 🎯 Next Steps

Would you like me to:

1. **Guide you through AWS setup** step-by-step
2. **Build the Flutter upload service** first
3. **Test your existing backend** with sample credentials
4. **Consider Cloudinary instead** for simpler setup

Let me know which approach you prefer, or if you already have AWS credentials, I can start implementing right away! 🚀
