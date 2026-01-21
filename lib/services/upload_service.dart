import 'dart:io';
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../config/environment.dart';

class UploadService {
  // Use centralized environment configuration
  static String get baseUrl => Environment.baseUrl;

  /// Upload image to S3 via backend API
  /// Returns the S3 URL on success, null on failure
  static Future<String?> uploadImage({
    required File imageFile,
    required String folder,
    Function(double)? onProgress,
  }) async {
    try {
      var uri = Uri.parse('$baseUrl/api/upload.php');
      var request = http.MultipartRequest('POST', uri);

      // Add folder parameter
      request.fields['folder'] = folder;

      // Add file
      var multipartFile = await http.MultipartFile.fromPath(
        'file',
        imageFile.path,
        filename: imageFile.path.split('/').last,
      );
      request.files.add(multipartFile);

      // Send request
      var streamedResponse = await request.send();
      var response = await http.Response.fromStream(streamedResponse);

      print('Upload response status: ${response.statusCode}');
      print('Upload response body: ${response.body}');

      if (response.statusCode == 201) {
        var data = json.decode(response.body);
        String s3Url = data['url'];

        // Extract S3 key from the upload response
        // The backend returns the S3 key (e.g., "avatars/abc123.jpg")
        // which we store in the database. When fetching profiles/promotions,
        // the backend generates presigned URLs for secure access.
        String s3Path;
        if (s3Url.startsWith('http')) {
          Uri uri = Uri.parse(s3Url);
          s3Path = uri.path.substring(1); // Remove leading "/" from URL path
        } else {
          s3Path = s3Url; // It's already the key (e.g. "avatars/abc123.jpg")
        }

        // Return S3 key - backend will generate presigned URLs when needed
        return s3Path;
      } else {
        print('Upload failed: ${response.body}');
        return null;
      }
    } catch (e) {
      print('Upload error: $e');
      return null;
    }
  }

  /// Upload profile picture (uses avatars folder)
  static Future<String?> uploadProfilePicture(File imageFile) async {
    return await uploadImage(imageFile: imageFile, folder: 'avatars');
  }
}
