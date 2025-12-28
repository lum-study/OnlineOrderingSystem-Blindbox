<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Wishlist.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class Wishlist extends Database
{
    // Get all wishlist items for a user
    public function getWishlistByUser($user_id)
    {
        try {
            $sql = "SELECT * FROM wishlist WHERE user_id = ?";
            $rows = $this->query($sql, [$user_id])->fetchAll();

            $wishlistItems = [];
            foreach ($rows as $row) {
                $wishlistItems[] = new WishlistEntity($row);
            }
            return $wishlistItems;
        } catch (Exception $e) {
            error_log("Failed to retrieve wishlist: " . $e->getMessage());
            throw new Exception("Failed to retrieve wishlist items.");
        }
    }

    public function getWishlistByUserAndBlindbox($user_id, $blindbox_id)
    {
        try {
            $sql = "SELECT * FROM wishlist WHERE user_id = ? AND blindbox_id = ?";
            $rows = $this->query($sql, [$user_id, $blindbox_id])->fetchAll();

            if (!$rows) {
                throw new Exception("Wishlist item not found.");
            }
            $wishlistItems = [];
            foreach ($rows as $row) {
                $wishlistItems[] = new WishlistEntity($row);
            }
            return $wishlistItems[0];
        } catch (Exception $e) {
            error_log("Failed to retrieve wishlist: " . $e->getMessage());
            throw new Exception("Failed to retrieve wishlist items.");
        }
    }

    // Add item to wishlist
    public function addWishlistItem($user_id, $blindbox_id)
    {
        try {
            $wishlist_id = IDGenerator::wishlistID();

            $sql = "INSERT INTO wishlist(wishlist_id, user_id, blindbox_id) VALUES (?, ?, ?)";
            $this->query($sql, [$wishlist_id, $user_id, $blindbox_id]);

            return $wishlist_id;
        } catch (Exception $e) {
            error_log("Error adding wishlist item: " . $e->getMessage());
            throw new Exception("Failed to add item to wishlist.");
        }
    }

    // Remove item from wishlist
    public function removeWishlistItem($wishlist_id)
    {
        try {
            $sql = "DELETE FROM wishlist WHERE wishlist_id = ?";
            $this->query($sql, [$wishlist_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error removing wishlist item: " . $e->getMessage());
            throw new Exception("Failed to remove wishlist item.");
        }
    }

    // Optional: check if item already exists in wishlist
    public function exists($user_id, $blindbox_id)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = ? AND blindbox_id = ?";
            $row = $this->query($sql, [$user_id, $blindbox_id])->fetch();
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error checking wishlist existence: " . $e->getMessage());
            return false;
        }
    }

    public function getWishlistItemDetail($user_id)
    {
        try {
            $sql = "SELECT 
                        w.wishlist_id,
                        b.blindbox_id,
                        b.product_name,
                        b.price,
                        i.image_url
                    FROM wishlist w
                    INNER JOIN blindbox b 
                        ON w.blindbox_id = b.blindbox_id
                    LEFT JOIN blindbox_images i 
                        ON b.blindbox_id = i.blindbox_id
                        AND i.is_deleted = 0
                    WHERE 
                        b.is_deleted = 0
                        AND w.user_id = ?";
            $row = $this->query($sql, [$user_id])->fetchAll();
            return $row;
        } catch (Exception $e) {
            error_log("Failed to retrieve wishlist item details");
            throw new Exception($e->getMessage());
        }
    }
}
