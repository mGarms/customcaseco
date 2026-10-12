<?php
// =====================================================
// Shared image-upload validation.
// Checks the REAL file content (not just the extension),
// so a renamed .php file can't sneak through as ".jpg".
// =====================================================

/**
 * @param array  $file       one entry from $_FILES
 * @param string $uploadDir  absolute path ending in /
 * @param string $prefix     filename prefix, e.g. 'design_' or 'user_5_'
 * @param int    $maxBytes   max allowed file size
 * @return array ['ok' => bool, 'filename' => string|null, 'error' => string|null]
 */
function handle_image_upload(array $file, string $uploadDir, string $prefix, int $maxBytes = 5242880): array {
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    ];

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'filename' => null, 'error' => null];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'filename' => null, 'error' => 'There was a problem uploading the image.'];
    }
    if ($file['size'] > $maxBytes) {
        $mb = round($maxBytes / 1024 / 1024, 1);
        return ['ok' => false, 'filename' => null, 'error' => "Image must be smaller than {$mb}MB."];
    }

    $mime = mime_content_type($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'filename' => null, 'error' => 'Image must be a JPG or PNG file.'];
    }

    // Second check: confirm PHP's GD library can actually decode this as an image.
    // This rejects crafted files that pass the MIME check but aren't valid images.
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['ok' => false, 'filename' => null, 'error' => 'The uploaded file is not a valid image.'];
    }
    // Reject absurdly large dimensions (basic protection against decompression-bomb style files)
    if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
        return ['ok' => false, 'filename' => null, 'error' => 'Image dimensions are too large.'];
    }

    $filename = $prefix . uniqid() . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['ok' => false, 'filename' => null, 'error' => 'Could not save the uploaded image.'];
    }

    return ['ok' => true, 'filename' => $filename, 'error' => null];
}
