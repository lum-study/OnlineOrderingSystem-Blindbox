<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/BlindboxImage.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class BlindboxImage extends Database
{
    public function getImagesByBlindboxId($blindbox_id)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }

        try {
            $sql = "SELECT * FROM blindbox_images WHERE blindbox_id = ? AND is_deleted = 0 ORDER BY upload_date ASC";
            $rows = $this->query($sql, [$blindbox_id])->fetchAll();

            $images = [];
            foreach ($rows as $row) {
                $images[] = new BlindboxImageEntity($row);
            }
            return $images;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve blindbox images.");
        }
    }

    public function getImageById($image_id)
    {
        if (empty($image_id)) {
            throw new Exception("Image ID is required.");
        }

        try {
            $sql = "SELECT * FROM blindbox_images WHERE image_id = ? AND is_deleted = 0";
            $row = $this->query($sql, [$image_id])->fetch();

            if (!$row) {
                throw new Exception("Image not found.");
            }

            return new BlindboxImageEntity($row);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve image.");
        }
    }

    public function addImage($blindbox_id, $image_url)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }
        if (empty($image_url)) {
            throw new Exception("Image URL is required.");
        }

        try {
            $image_id = IDGenerator::blindboxImageID();

            $sql = "INSERT INTO blindbox_images (image_id, blindbox_id, image_url) 
                    VALUES (?, ?, ?)";
            $this->query($sql, [$image_id, $blindbox_id, $image_url]);

            return $image_id;
        } catch (Exception $e) {
            error_log("Error adding blindbox image: " . $e->getMessage());
            throw new Exception("Failed to add image.");
        }
    }

    public function updateImage($image_id, $image_url)
    {
        if (empty($image_id)) {
            throw new Exception("Image ID is required.");
        }
        if (empty($image_url)) {
            throw new Exception("Image URL is required.");
        }

        try {
            $sql = "UPDATE blindbox_images SET image_url = ? WHERE image_id = ?";
            $this->query($sql, [$image_url, $image_id]);
        } catch (Exception $e) {
            error_log("Error updating blindbox image: " . $e->getMessage());
            throw new Exception("Failed to update image.");
        }
    }

    public function deleteImage($image_id)
    {
        if (empty($image_id)) {
            throw new Exception("Image ID is required.");
        }

        try {
            $sql = "UPDATE blindbox_images SET is_deleted = 1 WHERE image_id = ?";
            $this->query($sql, [$image_id]);
        } catch (Exception $e) {
            error_log("Error deleting blindbox image: " . $e->getMessage());
            throw new Exception("Failed to delete image.");
        }
    }
}
