import 'dart:io';
import 'package:http/http.dart' as http;
import 'dart:convert';

class UploadService {
  // Update this to match your backend URL
  // For Android emulator: http://10.0.2.2:8000
  // For iOS simulator: http://localhost:8000
  // For web: http://localhost:8000
  static const String baseUrl = 'http://10.0.2.2:8000';

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

        // TEMPORARY WORKAROUND: Use backend proxy because bucket doesn't allow public access
        // TODO: Remove this when bucket policy is updated to allow public read for avatars
        String s3Path;
        if (s3Url.startsWith('http')) {
          Uri uri = Uri.parse(s3Url);
          s3Path = uri.path.substring(1); // Remove leading "/" from URL path
        } else {
          s3Path = s3Url; // It's already the key (e.g. "avatars/abc123.jpg")
        }

        // Return proxy URL instead of direct S3 URL
        String proxyUrl = '$baseUrl/api/get_image.php?path=$s3Path';
        return proxyUrl;
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
