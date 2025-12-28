<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../lib/AdminAuth.php';
require_once __DIR__ . '/../../models/StaffData.php';
require_once __DIR__ . '/../../models/StaffLogin.php';

class AdminProfileController
{
    /**
     * Show admin profile page
     */
    public function show()
    {
        if (!AdminAuth::check()) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        require __DIR__ . '/../../views/pages/admin/profile.php';
    }

    /**
     * View admin profile
     */
    public function view()
    {
        $staffId = Session::getAdmin('staff_id');

        if (!$staffId) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        $staffDataModel = new StaffData();
        $profile = $staffDataModel->getProfileWithLogin($staffId);

        if (!$profile) {
            header('Location: ' . BASE_URL . 'admin/login?error=' . urlencode('Profile not found'));
            exit;
        }

        return $profile;
    }

    /**
     * Update admin profile
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'views/pages/admin/profile.php');
            exit;
        }

        header('Content-Type: application/json');

        // Verify CSRF (match the rest of the app)
        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $fullname = trim($_POST['fullname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $contactNumberPrefix = $_POST['contact_number_prefix'] ?? '+60';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $contact = !empty($contactNumber) ? $contactNumberPrefix . '-' . $contactNumber : null;
        $birthDate = $_POST['birth_date'] ?? null;
        $gender = $_POST['gender'] ?? null;

        // ------------------------------------------------------------------
        // Server-side validation (match validateAdminProfileForm in admin_profile.js)
        // ------------------------------------------------------------------
        if ($fullname === '') {
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            exit;
        }
        if (mb_strlen($fullname) < 3) {
            echo json_encode(['success' => false, 'message' => 'Full name must be at least 3 characters long']);
            exit;
        }
        if (mb_strlen($fullname) > 100) {
            echo json_encode(['success' => false, 'message' => 'Full name must not exceed 100 characters']);
            exit;
        }
        // Letters, spaces, hyphens ONLY (no apostrophes / symbols)
        if (!preg_match("/^[a-zA-Z\\s\\-]+$/", $fullname)) {
            echo json_encode(['success' => false, 'message' => 'Full name can only contain letters, spaces, and hyphens']);
            exit;
        }

        if ($email === '') {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        if (mb_strlen($email) > 100) {
            echo json_encode(['success' => false, 'message' => 'Email must not exceed 100 characters']);
            exit;
        }
        // Align with JS email checks
        if (
            !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) ||
            str_contains($email, '..') ||
            str_starts_with($email, '.') ||
            str_ends_with($email, '.')
        ) {
            echo json_encode(['success' => false, 'message' => 'Email address format is invalid']);
            exit;
        }

        // Contact number: optional, but if provided must be 7-15 digits
        if ($contactNumber !== '' && !preg_match('/^[0-9]{7,15}$/', $contactNumber)) {
            echo json_encode(['success' => false, 'message' => 'Contact number must be between 7 and 15 digits']);
            exit;
        }

        // Birth date: optional, but if provided must be valid and 18-120 years old
        $birthDateNormalized = null;
        if (!empty($birthDate)) {
            $ts = strtotime($birthDate);
            if ($ts === false) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid birth date']);
                exit;
            }

            $birthDateYmd = date('Y-m-d', $ts);
            $todayYmd = date('Y-m-d');
            if ($birthDateYmd > $todayYmd) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be in the future']);
                exit;
            }

            $minDate = date('Y-m-d', strtotime('-120 years'));
            $maxDate = date('Y-m-d', strtotime('-18 years'));
            if ($birthDateYmd < $minDate) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be more than 120 years ago']);
                exit;
            }
            if ($birthDateYmd > $maxDate) {
                echo json_encode(['success' => false, 'message' => 'Staff must be at least 18 years old']);
                exit;
            }

            $birthDateNormalized = $birthDateYmd;
        }

        // Gender: allow null/empty, 'male', 'female', 'other'
        $allowedGenders = [null, '', 'male', 'female', 'other'];
        if (!in_array($gender, $allowedGenders, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid gender selection']);
            exit;
        }

        $fullContactNumber = ($contactNumber !== '') ? ($contactNumberPrefix . '-' . $contactNumber) : '';

        try {
            $staffDataModel = new StaffData();

            // models/StaffData::update() does not return a boolean and only accepts 6 params
            $staffDataModel->update(
                $staffId,
                $fullname,
                $email,
                $contact,
                $birthDateNormalized,
                ($gender === '' ? null : $gender)
            );

            Session::setAdmin('fullname', $fullname);
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Change password
     */
    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'views/pages/admin/profile.php');
            exit;
        }

        header('Content-Type: application/json');

        // Verify CSRF
        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }

        if (strlen($newPassword) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
            exit;
        }

        if ($newPassword === $oldPassword) {
            echo json_encode(['success' => false, 'message' => 'New passwords must not same as the old password']);
            exit;
        }

        try {
            $staffLoginModel = new StaffLogin();
            $currentPassword = $staffLoginModel->getPassword($staffId);

            if (!$currentPassword || !Security::verifyPassword($oldPassword, $currentPassword)) {
                echo json_encode(['success' => false, 'message' => 'Old password is incorrect']);
                exit;
            }

            // Hash new password
            $hashedPassword = Security::hashPassword($newPassword);
            $success = $staffLoginModel->updatePassword($staffId, $hashedPassword);

            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to change password']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Upload profile photo
     */
    public function uploadPhoto()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        header('Content-Type: application/json');

        // Verify CSRF
        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Support BOTH mechanisms:
        // 1) legacy admin flow: data URL in POST field `cropped_image`
        // 2) member-like flow: multipart file `profile_photo`
        $croppedImageData = $_POST['cropped_image'] ?? '';

        try {
            $uploadDir = __DIR__ . '/../../assets/images/uploads/profile/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }

            // Determine existing stored filename (reuse if present)
            $staffDataModel = new StaffData();
            $staffData = $staffDataModel->findById($staffId);
            $existingFilename = null;
            if ($staffData && $staffData->getProfilePhoto()) {
                $existingFilename = basename($staffData->getProfilePhoto());
            }

            $filename = null;

            if (!empty($croppedImageData)) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $croppedImageData));
                if (!$imageData) {
                    echo json_encode(['success' => false, 'message' => 'Invalid image data']);
                    exit;
                }

                // Base64 flow always produces JPEG
                $targetExt = 'jpg';

                if ($existingFilename) {
                    $existingExt = strtolower(pathinfo($existingFilename, PATHINFO_EXTENSION));
                    $existingExt = ($existingExt === 'jpeg') ? 'jpg' : $existingExt;
                    $isValidExisting = in_array($existingExt, ['jpg', 'png', 'gif'], true) && str_starts_with($existingFilename, $staffId . '_');
                    $isSafeName = (basename($existingFilename) === $existingFilename);

                    if ($isValidExisting && $isSafeName) {
                        // Keep existing name even if ext differs; overwrite file at that name
                        $filename = $existingFilename;
                    } else {
                        $filename = $staffId . '_' . time() . '.' . $targetExt;
                        $staffDataModel->updateProfilePhoto($staffId, $filename);
                    }
                } else {
                    $filename = $staffId . '_' . time() . '.' . $targetExt;
                    $staffDataModel->updateProfilePhoto($staffId, $filename);
                }

                $uploadPath = $uploadDir . $filename;

                if (!file_put_contents($uploadPath, $imageData)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to save image']);
                    exit;
                }
            } elseif (isset($_FILES['profile_photo'])) {
                $file = $_FILES['profile_photo'];

                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    echo json_encode(['success' => false, 'message' => 'Upload failed']);
                    exit;
                }

                // Basic server-side validation consistent with member rules
                $maxSize = 2 * 1024 * 1024;
                if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) {
                    echo json_encode(['success' => false, 'message' => 'File size must be between 1 byte and 2MB']);
                    exit;
                }

                $allowedMime = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'] ?? '', $allowedMime, true)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
                    exit;
                }

                $ext = strtolower(pathinfo($file['name'] ?? 'profile.jpg', PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file extension']);
                    exit;
                }

                $normalizedExt = ($ext === 'jpeg' ? 'jpg' : $ext);

                if ($existingFilename) {
                    $existingExt = strtolower(pathinfo($existingFilename, PATHINFO_EXTENSION));
                    $existingExt = ($existingExt === 'jpeg') ? 'jpg' : $existingExt;
                    $isValidExisting = in_array($existingExt, ['jpg', 'png', 'gif'], true) && str_starts_with($existingFilename, $staffId . '_');
                    $isSafeName = (basename($existingFilename) === $existingFilename);

                    if ($isValidExisting && $isSafeName) {
                        $filename = $existingFilename;
                    } else {
                        $filename = $staffId . '_' . time() . '.' . $normalizedExt;
                        $staffDataModel->updateProfilePhoto($staffId, $filename);
                    }
                } else {
                    $filename = $staffId . '_' . time() . '.' . $normalizedExt;
                    $staffDataModel->updateProfilePhoto($staffId, $filename);
                }

                $uploadPath = $uploadDir . $filename;

                if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No image provided']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
