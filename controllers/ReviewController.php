<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/IDGenerator.php';
require_once __DIR__ . '/../entity/Review.php';
require_once __DIR__ . '/../entity/ReviewImage.php';

class ReviewController
{
    public static function createReview($orderId, $orderItemId, $userId, $blindboxId, $rating, $comment, $images = [])
    {
        try {
            // Handle multiple reviews
            if (isset($_POST['order_item_ids'])) {
                $orderItemIds = json_decode($_POST['order_item_ids'] ?? '[]', true);
                $blindboxIds = json_decode($_POST['blindbox_ids'] ?? '[]', true);
                $ratings = json_decode($_POST['ratings'] ?? '[]', true);
                $comments = json_decode($_POST['comments'] ?? '[]', true);
                
                if (empty($orderItemIds) || count($orderItemIds) !== count($ratings)) {
                    return ['success' => false, 'message' => 'Invalid review data'];
                }
                
                // Verify order
                $order = Database::fetch("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND status = 'completed'", [$orderId, $userId]);
                if (!$order) {
                    return ['success' => false, 'message' => 'Order not found or not completed'];
                }
                
                $uploadDir = __DIR__ . '/../assets/images/uploads/reviews/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Insert all reviews
                foreach ($orderItemIds as $index => $itemId) {
                    // Check if already reviewed
                    $existing = Database::fetch("SELECT * FROM blindbox_reviews WHERE order_item_id = ? AND is_deleted = 0", [$itemId]);
                    if ($existing) continue;
                    
                    $reviewId = IDGenerator::blindboxReviewID();
                    $itemComment = $comments[$index] ?? '';
                    
                    $result = Database::query(
                        "INSERT INTO blindbox_reviews (review_id, order_item_id, rating, comment, review_date, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)",
                        [$reviewId, $itemId, $ratings[$index], $itemComment]
                    );
                    
                    // Upload images for this specific item
                    $imageKey = 'images-' . $itemId;
                    if (isset($_FILES[$imageKey]) && !empty($_FILES[$imageKey]['tmp_name'][0])) {
                        foreach ($_FILES[$imageKey]['tmp_name'] as $key => $tmpName) {
                            if ($_FILES[$imageKey]['error'][$key] === UPLOAD_ERR_OK) {
                                $ext = pathinfo($_FILES[$imageKey]['name'][$key], PATHINFO_EXTENSION);
                                $filename = $reviewId . '_' . time() . '_' . $key . '.' . $ext;
                                $filepath = $uploadDir . $filename;
                                
                                if (move_uploaded_file($tmpName, $filepath)) {
                                    $imageId = IDGenerator::reviewImageID();
                                    Database::query(
                                        "INSERT INTO review_images (image_id, review_id, image_url, sequence, upload_date, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)",
                                        [$imageId, $reviewId, $filename, $key + 1]
                                    );
                                }
                            }
                        }
                    }
                }
                
                return ['success' => true, 'message' => 'Reviews submitted successfully'];
            }
            
            // Single review (legacy)
            $order = Database::fetch("SELECT * FROM orders WHERE order_id = ? AND user_id = ? AND status = 'completed'", [$orderId, $userId]);
            if (!$order) {
                return ['success' => false, 'message' => 'Order not found or not completed'];
            }

            $existing = Database::fetch("SELECT * FROM blindbox_reviews WHERE order_item_id = ? AND is_deleted = 0", [$orderItemId]);
            if ($existing) {
                return ['success' => false, 'message' => 'Item already reviewed'];
            }

            $reviewId = IDGenerator::blindboxReviewID();
            $result = Database::query(
                "INSERT INTO blindbox_reviews (review_id, order_item_id, rating, comment, review_date, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)",
                [$reviewId, $orderItemId, $rating, $comment]
            );
            
            if ($result->rowCount() === 0) {
                throw new Exception('Failed to insert review into database');
            }

            if (!empty($images['tmp_name']) && !empty($images['tmp_name'][0])) {
                $uploadDir = __DIR__ . '/../assets/images/uploads/reviews/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($images['tmp_name'] as $key => $tmpName) {
                    if ($images['error'][$key] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($images['name'][$key], PATHINFO_EXTENSION);
                        $filename = $reviewId . '_' . time() . '_' . $key . '.' . $ext;
                        $filepath = $uploadDir . $filename;

                        if (move_uploaded_file($tmpName, $filepath)) {
                            $imageId = IDGenerator::reviewImageID();
                            Database::query(
                                "INSERT INTO review_images (image_id, review_id, image_url, sequence, upload_date, is_deleted) VALUES (?, ?, ?, ?, NOW(), 0)",
                                [$imageId, $reviewId, $filename, $key + 1]
                            );
                        }
                    }
                }
            }

            return ['success' => true, 'message' => 'Review submitted successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function getOrderItemsForReview($orderId, $userId)
    {
        return Database::fetchAll(
            "SELECT oi.*, b.product_name, 
                    (SELECT review_id FROM blindbox_reviews WHERE order_item_id = oi.order_item_id AND is_deleted = 0) as review_id
             FROM order_items oi 
             JOIN blindbox b ON oi.blindbox_id = b.blindbox_id 
             JOIN orders o ON oi.order_id = o.order_id
             WHERE oi.order_id = ? AND o.user_id = ? AND o.status = 'completed'",
            [$orderId, $userId]
        );
    }

    public static function hasOrderBeenReviewed($orderId)
    {
        $result = Database::fetch(
            "SELECT COUNT(*) as review_count 
             FROM blindbox_reviews r
             JOIN order_items oi ON r.order_item_id = oi.order_item_id
             WHERE oi.order_id = ? AND r.is_deleted = 0",
            [$orderId]
        );
        return $result && $result['review_count'] > 0;
    }

    public static function getOrderReviews($orderId)
    {
        $reviews = Database::fetchAll(
            "SELECT r.*, oi.blindbox_id, b.product_name, u.fullname, u.profile_photo
             FROM blindbox_reviews r
             JOIN order_items oi ON r.order_item_id = oi.order_item_id
             JOIN blindbox b ON oi.blindbox_id = b.blindbox_id
             JOIN orders o ON oi.order_id = o.order_id
             JOIN user_data u ON o.user_id = u.user_id
             WHERE oi.order_id = ? AND r.is_deleted = 0
             ORDER BY r.review_date DESC",
            [$orderId]
        );

        foreach ($reviews as &$review) {
            $review['images'] = Database::fetchAll(
                "SELECT * FROM review_images WHERE review_id = ? AND is_deleted = 0 ORDER BY sequence",
                [$review['review_id']]
            );
        }

        return $reviews;
    }
}
