<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/UserData.php';
require_once __DIR__ . '/../models/UserLogin.php';
require_once __DIR__ . '/../models/StaffData.php';
require_once __DIR__ . '/../models/StaffLogin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/UserAddress.php';

class ProfileController
{
    public static function view()
    {
        if (!Auth::check()) {
            header('Location: ' . BASE_URL . 'views/errors/401.php');
            exit;
        }

        // Member-only session namespace (avoid cross-role bleed)
        $userId = Session::getMember('user_id');

        if ($userId) {
            $userDataModel = new UserData();
            return $userDataModel->getProfileWithLogin($userId);
        }

        // No valid member session found
        header('Location: ' . BASE_URL . 'views/errors/401.php');
        exit;
    }
    public static function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            // Member-only
            $userId = Session::getMember('user_id');

            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            $fullname = Security::sanitize($_POST['fullname'] ?? '');
            $contactPrefix = Security::sanitize($_POST['contact_number_prefix'] ?? '+60');
            $contactNumber = Security::sanitize($_POST['contact_number'] ?? '');
            $contact = !empty($contactNumber) ? $contactPrefix . '-' . $contactNumber : null;
            $birthDate = $_POST['birth_date'] ?? '';
            $gender = $_POST['gender'] ?? '';

            $fullnameTrimmed = trim($fullname);
            if ($fullnameTrimmed === '') {
                echo json_encode(['success' => false, 'message' => 'Full name is required']);
                exit;
            }
            if (mb_strlen($fullnameTrimmed) < 3) {
                echo json_encode(['success' => false, 'message' => 'Full name must be at least 3 characters long']);
                exit;
            }
            if (mb_strlen($fullnameTrimmed) > 100) {
                echo json_encode(['success' => false, 'message' => 'Full name must not exceed 100 characters']);
                exit;
            }
            // Letters, spaces, hyphens ONLY (no apostrophes / symbols)
            if (!preg_match("/^[a-zA-Z\\s\\-]+$/", $fullnameTrimmed)) {
                echo json_encode(['success' => false, 'message' => 'Full name can only contain letters, spaces, and hyphens']);
                exit;
            }

            // Contact number: optional, but if provided must be 7-15 digits 
            if ($contactNumber !== '' && !preg_match('/^[0-9]{7,15}$/', $contactNumber)) {
                echo json_encode(['success' => false, 'message' => 'Contact number must be between 7 and 15 digits']);
                exit;
            }

            // Birth date: optional, but if provided must not be in future and must be between 13-120 years ago 
            if (!empty($birthDate)) {
                $ts = strtotime($birthDate);
                if ($ts === false) {
                    echo json_encode(['success' => false, 'message' => 'Please enter a valid birth date']);
                    exit;
                }

                // Compare dates only
                $birthDateYmd = date('Y-m-d', $ts);
                $todayYmd = date('Y-m-d');
                if ($birthDateYmd > $todayYmd) {
                    echo json_encode(['success' => false, 'message' => 'Birth date cannot be in the future']);
                    exit;
                }

                $minDate = date('Y-m-d', strtotime('-120 years'));
                $maxDate = date('Y-m-d', strtotime('-13 years'));
                if ($birthDateYmd < $minDate) {
                    echo json_encode(['success' => false, 'message' => 'Birth date cannot be more than 120 years ago']);
                    exit;
                }
                if ($birthDateYmd > $maxDate) {
                    echo json_encode(['success' => false, 'message' => 'You must be at least 13 years old']);
                    exit;
                }
            }

            // Gender: allow '', 'male', 'female', 'other'
            $allowedGenders = ['', 'male', 'female', 'other'];
            if (!in_array($gender, $allowedGenders, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid gender selection']);
                exit;
            }

            // Keep Validator for backwards compatibility, but the checks above are authoritative
            $validator = new Validator();
            if (!$validator->validate(['fullname' => $fullnameTrimmed], ['fullname' => 'required|min:3'])) {
                echo json_encode(['success' => false, 'message' => implode(', ', $validator->errors())]);
                exit;
            }

            // Update member profile
            $userDataModel = new UserData();
            $currentUser = $userDataModel->findById($userId);
            $email = $currentUser->getEmail(); // Always keep original email for members

            $userDataModel->update($userId, $fullnameTrimmed, $email, $contact, $birthDate, $gender);
            self::logActivity($userId, null, 'profile_update', 'Profile updated');

            // Update member session
            Session::setMember('fullname', $fullnameTrimmed);

            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            exit;
        }
    }
    public static function changeUsername()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            // Member-only
            $userId = Session::getMember('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'Only members can change username']);
                exit;
            }

            $newUsername = Security::sanitize($_POST['new_username'] ?? '');

            if (strlen($newUsername) < 3 || strlen($newUsername) > 50) {
                echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters']);
                exit;
            }

            $userLoginModel = new UserLogin();

            // Get current username
            $currentUsername = $userLoginModel->getUsername($userId);
            if (!$currentUsername) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            // Check if new username is the same as current username (case-insensitive)
            if (strtolower($newUsername) === strtolower($currentUsername)) {
                echo json_encode(['success' => false, 'message' => 'New username must be different from current username']);
                exit;
            }

            // Check if new username already exists in database (excluding current user, case-insensitive)
            if ($userLoginModel->usernameExistsCaseInsensitive($newUsername, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Username is already taken']);
                exit;
            }

            $usernameChangedAt = $userLoginModel->getUsernameChangedAt($userId);
            if ($usernameChangedAt) {
                $lastChange = strtotime($usernameChangedAt);
                $daysSinceChange = (time() - $lastChange) / (60 * 60 * 24);
                if ($daysSinceChange < 30) {
                    $daysLeft = ceil(30 - $daysSinceChange);
                    echo json_encode(['success' => false, 'message' => "You can change username again in $daysLeft days"]);
                    exit;
                }
            }

            $userLoginModel->updateUsername($userId, $newUsername);
            // Update session username so UI reflects change immediately
            Session::setMember('username', $newUsername);
            Session::set('username', $newUsername);
            self::logActivity($userId, null, 'username_change', "Username changed to $newUsername");

            echo json_encode(['success' => true, 'message' => 'Username changed successfully', 'username' => $newUsername]);
            exit;
        }
    }
    public static function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            // Member-only
            $userId = Session::getMember('user_id');

            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            $oldPassword = $_POST['old_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            if ($newPassword !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
                exit;
            }

            if (!Validator::passwordStrength($newPassword)) {
                echo json_encode(['success' => false, 'message' => 'Password must be 8+ chars with uppercase, lowercase, number, and special character']);
                exit;
            }

            // Update member password only
            $userLoginModel = new UserLogin();
            $currentPassword = $userLoginModel->getPassword($userId);
            if (!Security::verifyPassword($oldPassword, $currentPassword)) {
                echo json_encode(['success' => false, 'message' => 'Old password is incorrect']);
                exit;
            }
            $userLoginModel->updatePassword($userId, Security::hashPassword($newPassword));
            self::logActivity($userId, null, 'password_change', 'Password changed');

            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            exit;
        }
    }

    /**
     * Update user's theme preference (persist immediately)
     * POST /profile/update-theme
     * Body: JSON { theme: 'light' | 'dark' } or form-encoded
     */
    public static function updateTheme()
    {
        header('Content-Type: application/json');

        // Accept JSON or form body
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) $data = $_POST;

        // CSRF may come as header or body        
        $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $data['csrf_token'] ?? '';
        if (!Security::verifyCSRF($csrf)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        $userId = Session::getMember('user_id');
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
            exit;
        }

        $theme = $data['theme'] ?? null;
        if (!in_array($theme, ['light', 'dark'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid theme']);
            exit;
        }

        $userDataModel = new UserData();
        try {
            $userDataModel->updatePreference($userId, 'theme', $theme);
            // update session copy so server-side session reflects it
            Session::setMember('theme', $theme);
            echo json_encode(['success' => true, 'message' => 'Theme saved']);
            exit;
        } catch (Exception $e) {
            error_log('Failed to save theme: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to save theme']);
            exit;
        }
    }

    public static function uploadPhoto()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');            // CSRF validation
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            // Member-only
            $userId = Session::getMember('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            // Check if file was uploaded
            if (!isset($_FILES['profile_photo'])) {
                echo json_encode(['success' => false, 'message' => 'No file uploaded']);
                exit;
            }

            $file = $_FILES['profile_photo'];

            // Check upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                ];
                $message = $errorMessages[$file['error']] ?? 'Unknown upload error';
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }

            // Validate file size (must be greater than 0 and less than 2MB)
            $maxSize = 2 * 1024 * 1024; // 2MB in bytes
            if ($file['size'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Uploaded file is empty (0 bytes)']);
                exit;
            }
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB']);
                exit;
            }

            // Get original filename and sanitize
            $originalFileName = basename($file['name']);
            if (empty($originalFileName)) {
                echo json_encode(['success' => false, 'message' => 'Invalid filename']);
                exit;
            }
            $lowerFileName = strtolower($originalFileName);

            // Define allowed extensions and MIME types
            $allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];

            // Validate file extension
            $hasValidExtension = false;
            foreach ($allowedExtensions as $allowedExt) {
                if (substr($lowerFileName, -strlen($allowedExt)) === $allowedExt) {
                    $hasValidExtension = true;
                    break;
                }
            }
            if (!$hasValidExtension) {
                echo json_encode(['success' => false, 'message' => 'Unsupported file extension. Only JPG, JPEG, PNG, GIF are allowed']);
                exit;
            }

            // Validate MIME type (from upload)
            $uploadedMimeType = $file['type'];
            if (!in_array($uploadedMimeType, $allowedMimeTypes)) {
                echo json_encode(['success' => false, 'message' => 'Unsupported MIME type: ' . htmlspecialchars($uploadedMimeType) . '. Only image/jpeg, image/png, image/gif are allowed']);
                exit;
            }

            // Additional validation: Check actual file MIME type using finfo
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedMimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($detectedMimeType, $allowedMimeTypes)) {
                    echo json_encode(['success' => false, 'message' => 'File content does not match allowed image types. Detected type: ' . htmlspecialchars($detectedMimeType)]);
                    exit;
                }
            }

            // Additional validation: Check if file is actually an image using getimagesize
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                echo json_encode(['success' => false, 'message' => 'File is not a valid image']);
                exit;
            }

            // Validate image dimensions (optional: prevent extremely large images)
            $maxWidth = 5000;
            $maxHeight = 5000;
            if ($imageInfo[0] > $maxWidth || $imageInfo[1] > $maxHeight) {
                echo json_encode(['success' => false, 'message' => "Image dimensions too large. Maximum: {$maxWidth}x{$maxHeight}px"]);
                exit;
            }

            // Get file extension from original filename
            $ext = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
            if (empty($ext) || !in_array('.' . $ext, $allowedExtensions)) {
                $ext = 'jpg'; // Default fallback
            }
            // Ensure upload directory exists
            $uploadDir = __DIR__ . '/../assets/images/uploads/profile/';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
                    exit;
                }
            }

            // ------------------------------------------------------------------
            // Reuse existing filename if present in DB; otherwise create once.
            // Required format: <id>_<random number>.<ext>
            // ------------------------------------------------------------------
            $userDataModel = new UserData();
            $userData = $userDataModel->findById($userId);

            $existingFilename = null;
            if ($userData && $userData->getProfilePhoto()) {
                $existingFilename = basename($userData->getProfilePhoto());
            }

            // Normalize extension for new uploads
            $normalizedExt = ($ext === 'jpeg') ? 'jpg' : $ext;

            if ($existingFilename) {
                // Keep the same stored name, but ensure it still matches this user and allowed extensions
                $existingExt = strtolower(pathinfo($existingFilename, PATHINFO_EXTENSION));
                $existingExt = ($existingExt === 'jpeg') ? 'jpg' : $existingExt;

                $allowedExtNoDot = ['jpg', 'png', 'gif'];
                $isValidExisting = in_array($existingExt, $allowedExtNoDot, true) && str_starts_with($existingFilename, $userId . '_');

                // Also ensure no path traversal / weird names
                $isSafeName = (basename($existingFilename) === $existingFilename);

                if ($isValidExisting && $isSafeName) {
                    $filename = $existingFilename;
                } else {
                    // Fallback: create a new compliant filename if the stored one is unexpected
                    $filename = $userId . '_' . time() . '.' . $normalizedExt;
                    $userDataModel->updateProfilePhoto($userId, $filename);
                }
            } else {
                // First-time upload: create and store the name
                $filename = $userId . '_' . time() . '.' . $normalizedExt;
                $userDataModel->updateProfilePhoto($userId, $filename);
            }

            $uploadPath = $uploadDir . $filename;

            // Move uploaded file (overwrite if exists)
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
                exit;
            }

            // Verify the file was actually written and has content
            if (!file_exists($uploadPath) || filesize($uploadPath) <= 0) {
                @unlink($uploadPath);
                echo json_encode(['success' => false, 'message' => 'Failed to verify uploaded file']);
                exit;
            }

            // Log activity
            self::logActivity($userId, null, 'photo_upload', 'Profile photo uploaded');

            echo json_encode(['success' => true, 'message' => 'Photo uploaded successfully', 'filename' => $filename]);
            exit;
        }
    }

    public static function getAddresses()
    {
        $userId = Session::getMember('user_id');
        if (!$userId) return [];
        $model = new UserAddressModel();
        return $model->findByUserId($userId);
    }

    public static function addAddress()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $userId = Session::getMember('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            $receiverName = htmlspecialchars_decode(Security::sanitize($_POST['receiver_name'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $phonePrefix = Security::sanitize($_POST['phone_number_prefix'] ?? '+60');
            $phoneNumber = Security::sanitize($_POST['phone_number'] ?? '');
            $email = htmlspecialchars_decode(Security::sanitize($_POST['email'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $address = htmlspecialchars_decode(Security::sanitize($_POST['addr_address'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $city = htmlspecialchars_decode(Security::sanitize($_POST['addr_city'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $state = htmlspecialchars_decode(Security::sanitize($_POST['addr_state'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $postalCode = htmlspecialchars_decode(Security::sanitize($_POST['addr_postal_code'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $country = Security::sanitize($_POST['addr_country'] ?? '');
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            // Validation
            // Email is now required and must be a valid format
            if (empty($receiverName) || empty($phoneNumber) || empty($email) || empty($address) || empty($city) || empty($state) || empty($postalCode) || empty($country)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            if (strlen($receiverName) < 3 || strlen($receiverName) > 255) {
                echo json_encode(['success' => false, 'message' => 'Receiver name must be 3-255 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z\s\-]+$/', $receiverName)) {
                echo json_encode(['success' => false, 'message' => 'Receiver name can only contain letters, spaces, and hyphens']);
                exit;
            }
            if (!preg_match('/^[0-9]{7,15}$/', $phoneNumber)) {
                echo json_encode(['success' => false, 'message' => 'Phone number must be 7-15 digits']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            if (strlen($email) > 255) {
                echo json_encode(['success' => false, 'message' => 'Email must not exceed 255 characters']);
                exit;
            }
            if (strlen($address) < 10 || strlen($address) > 255) {
                echo json_encode(['success' => false, 'message' => 'Address must be 10-255 characters']);
                exit;
            }
            if (strlen($city) < 2 || strlen($city) > 100) {
                echo json_encode(['success' => false, 'message' => 'City must be 2-100 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z\s]+$/', $city)) {
                echo json_encode(['success' => false, 'message' => 'City can only contain letters and spaces']);
                exit;
            }
            if (strlen($state) < 2 || strlen($state) > 100) {
                echo json_encode(['success' => false, 'message' => 'State must be 2-100 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z\s]+$/', $state)) {
                echo json_encode(['success' => false, 'message' => 'State can only contain letters and spaces']);
                exit;
            }
            if (strlen($postalCode) < 3 || strlen($postalCode) > 20) {
                echo json_encode(['success' => false, 'message' => 'Postal code must be 3-20 digits']);
                exit;
            }
            if (!preg_match('/^[0-9]+$/', $postalCode)) {
                echo json_encode(['success' => false, 'message' => 'Postal code can only contain digits']);
                exit;
            }
            $allowedCountries = ['MY', 'SG', 'ID', 'TH'];
            if (!in_array($country, $allowedCountries)) {
                echo json_encode(['success' => false, 'message' => 'Invalid country selected']);
                exit;
            }

            $phone = $phonePrefix . '-' . $phoneNumber;
            $addressId = IDGenerator::userAddressID();
            $model = new UserAddressModel();
            $model->create($addressId, $userId, $receiverName, $phone, $email, $address, $city, $state, $postalCode, $country, $isDefault);
            self::logActivity($userId, null, 'address_add', 'Address added');

            echo json_encode([
                'success' => true,
                'message' => 'Address added successfully',
                'address' => [
                    'id' => $addressId,
                    'receiver_name' => $receiverName,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postalCode,
                    'country' => $country,
                    'is_default' => $isDefault
                ]
            ]);
            exit;
        }
    }

    public static function deleteAddress()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $addressId = $_POST['address_id'] ?? '';
            $userId = Session::getMember('user_id');

            $model = new UserAddressModel();
            $model->delete($addressId);
            self::logActivity($userId, null, 'address_delete', 'Address deleted');

            echo json_encode(['success' => true, 'message' => 'Address deleted successfully']);
            exit;
        }
    }

    public static function editAddress()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $userId = Session::getMember('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            $addressId = Security::sanitize($_POST['address_id'] ?? '');
            $receiverName = htmlspecialchars_decode(Security::sanitize($_POST['receiver_name'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $phonePrefix = Security::sanitize($_POST['phone_number_prefix'] ?? '+60');
            $phoneNumber = Security::sanitize($_POST['phone_number'] ?? '');
            $email = htmlspecialchars_decode(Security::sanitize($_POST['email'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $address = htmlspecialchars_decode(Security::sanitize($_POST['addr_address'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $city = htmlspecialchars_decode(Security::sanitize($_POST['addr_city'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $state = htmlspecialchars_decode(Security::sanitize($_POST['addr_state'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $postalCode = htmlspecialchars_decode(Security::sanitize($_POST['addr_postal_code'] ?? ''), ENT_QUOTES | ENT_HTML5);
            $country = Security::sanitize($_POST['addr_country'] ?? '');
            $isDefault = isset($_POST['is_default']) ? 1 : 0;

            // ------------------------------------------------------------------
            // Validation (mirror addAddress rules for consistency)
            // Email is now required and must be a valid format
            // ------------------------------------------------------------------
            if (empty($addressId)) {
                echo json_encode(['success' => false, 'message' => 'Invalid address ID']);
                exit;
            }

            if (empty($receiverName) || empty($phoneNumber) || empty($email) || empty($address) || empty($city) || empty($state) || empty($postalCode) || empty($country)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }
            if (strlen($receiverName) < 3 || strlen($receiverName) > 255) {
                echo json_encode(['success' => false, 'message' => 'Receiver name must be 3-255 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z\s\-]+$/', $receiverName)) {
                echo json_encode(['success' => false, 'message' => 'Receiver name can only contain letters, spaces, and hyphens']);
                exit;
            }
            if (!preg_match('/^[0-9]{7,15}$/', $phoneNumber)) {
                echo json_encode(['success' => false, 'message' => 'Phone number must be 7-15 digits']);
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                exit;
            }
            if (strlen($email) > 255) {
                echo json_encode(['success' => false, 'message' => 'Email must not exceed 255 characters']);
                exit;
            }
            if (strlen($address) < 10 || strlen($address) > 255) {
                echo json_encode(['success' => false, 'message' => 'Address must be 10-255 characters']);
                exit;
            }
            if (strlen($city) < 2 || strlen($city) > 100) {
                echo json_encode(['success' => false, 'message' => 'City must be 2-100 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z\s]+$/', $city)) {
                echo json_encode(['success' => false, 'message' => 'City can only contain letters and spaces']);
                exit;
            }
            if (strlen($state) < 2 || strlen($state) > 100) {
                echo json_encode(['success' => false, 'message' => 'State must be 2-100 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z\s]+$/', $state)) {
                echo json_encode(['success' => false, 'message' => 'State can only contain letters and spaces']);
                exit;
            }
            if (strlen($postalCode) < 3 || strlen($postalCode) > 20) {
                echo json_encode(['success' => false, 'message' => 'Postal code must be 3-20 digits']);
                exit;
            }
            if (!preg_match('/^[0-9]+$/', $postalCode)) {
                echo json_encode(['success' => false, 'message' => 'Postal code can only contain digits']);
                exit;
            }
            $allowedCountries = ['MY', 'SG', 'ID', 'TH'];
            if (!in_array($country, $allowedCountries, true)) {
                echo json_encode(['success' => false, 'message' => 'Invalid country selected']);
                exit;
            }
            $phone = $phonePrefix . '-' . $phoneNumber;
            $model = new UserAddressModel();
            $model->query(
                "UPDATE user_addresses SET receiver_name = ?, phone_number = ?, email = ?, address = ?, city = ?, state = ?, postal_code = ?, country = ?, is_default = ? WHERE address_id = ? AND user_id = ?",
                [$receiverName, $phone, $email, $address, $city, $state, $postalCode, $country, $isDefault, $addressId, $userId]
            );

            if ($isDefault) {
                $model->query("UPDATE user_addresses SET is_default = 0 WHERE address_id != ? AND user_id = ?", [$addressId, $userId]);
            }

            self::logActivity($userId, null, 'address_edit', 'Address updated');

            echo json_encode([
                'success' => true,
                'message' => 'Address updated successfully',
                'address' => [
                    'id' => $addressId,
                    'receiver_name' => $receiverName,
                    'phone' => $phone,
                    'email' => $email,
                    'address' => $address,
                    'city' => $city,
                    'state' => $state,
                    'postal_code' => $postalCode,
                    'country' => $country,
                    'is_default' => $isDefault
                ]
            ]);
            exit;
        }
    }

    public static function setDefaultAddress()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $userId = Session::getMember('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            $addressId = $_POST['address_id'] ?? '';
            $model = new UserAddressModel();
            $model->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$userId]);
            $model->query("UPDATE user_addresses SET is_default = 1 WHERE address_id = ? AND user_id = ?", [$addressId, $userId]);

            self::logActivity($userId, null, 'address_set_default', 'Set default address');

            echo json_encode(['success' => true, 'message' => 'Default address updated']);
            exit;
        }
    }

    public static function deleteAccount()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $userId = Session::getMember('user_id');
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            $password = $_POST['delete_password'] ?? '';
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required']);
                exit;
            }

            $userLoginModel = new UserLogin();
            $currentPassword = $userLoginModel->getPassword($userId);
            if (!Security::verifyPassword($password, $currentPassword)) {
                echo json_encode(['success' => false, 'message' => 'Incorrect password']);
                exit;
            }

            $userDataModel = new UserData();
            $userDataModel->setDeleted($userId, 1);
            $userLoginModel->setDeleted($userId, 1);

            $userAddressesModel = new UserAddressModel();
            $userAddressesModel->setDeleted($userId, 1);


            self::logActivity($userId, null, 'account_delete', 'Account deleted');

            Session::destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Account deleted successfully',
                'redirect' => BASE_URL . 'login'
            ]);
            exit;
        }
    }

    private static function logActivity($userId, $staffId, $action, $description)
    {
        $activityLogModel = new ActivityLog();
        $activityLogModel->create($userId, $staffId, $action, $description, Security::getClientIP(), Security::getUserAgent());
    }
}
