<?php
require_once __DIR__ . "/Database.php";

class IDGenerator
{
    /**
     * Generate next custom ID.
     *
     * @param string $table      Table name
     * @param string $prefix     Prefix like "I"
     * @param int    $length     Numeric length (2 = 01, 10 = 000001)
     * @param string $idField    Table primary key field name
     *
     * @return string            Generated ID (I01, I02, ...)
     */

    public static function generate(string $table, string $prefix, int $length, string $idField): string
    {
        try {
            $sql = "
                SELECT $idField
                FROM $table
                WHERE $idField LIKE :prefix
                ORDER BY LENGTH($idField) DESC, $idField DESC
                LIMIT 1
            ";

            $stmt = Database::query($sql, [
                ":prefix" => $prefix . "%"
            ]);

            $lastId = $stmt->fetchColumn();
            $nextNumber = 1;

            if ($lastId) {
                $numeric = substr($lastId, strlen($prefix));
                $nextNumber = intval($numeric) + 1;
            }

            $number = str_pad($nextNumber, $length, "0", STR_PAD_LEFT);

            return $prefix . $number;
        } catch (Exception $e) {
            throw new Exception("Error generating ID: " . $e->getMessage());
        }
    }

    public static function userID(): string
    {
        return self::generate("user_data", "UD", 4, "user_id");
    }

    public static function userLoginID(): string
    {
        return self::generate("user_logins", "UL", 4, "login_id");
    }

    public static function staffID(): string
    {
        return self::generate("staff_data", "SD", 4, "staff_id");
    }

    public static function staffLoginID(): string
    {
        return self::generate("staff_logins", "SL", 4, "login_id");
    }

    public static function verificationTokenID(): string
    {
        return self::generate("verification_tokens", "VT", 4, "token_id");
    }

    public static function passwordResetID(): string
    {
        return self::verificationTokenID();
    }

    public static function userAddressID(): string
    {
        return self::generate("user_addresses", "UA", 4, "address_id");
    }

    public static function orderID(): string
    {
        return self::generate("orders", "OR", 4, "order_id");
    }

    public static function paymentID(): string
    {
        return self::generate("payments", "PM", 4, "payment_id");
    }

    public static function refundID(): string
    {
        return self::generate("refunds", "RF", 4, "refund_id");
    }

    public static function categoryID(): string
    {
        return self::generate("categories", "CT", 4, "category_id");
    }

    public static function blindboxID(): string
    {
        return self::generate("blindbox", "BB", 4, "blindbox_id");
    }

    public static function blindboxImageID(): string
    {
        return self::generate("blindbox_images", "BI", 4, "image_id");
    }

    public static function productID(): string
    {
        return self::generate("products", "PR", 4, "product_id");
    }

    public static function productImageID(): string
    {
        return self::generate("product_images", "PI", 4, "image_id");
    }

    public static function cartID(): string
    {
        return self::generate("carts", "CA", 4, "cart_id");
    }

    public static function cartItemID(): string
    {
        return self::generate("cart_items", "CI", 4, "cart_item_id");
    }

    public static function orderItemID(): string
    {
        return self::generate("order_items", "OI", 4, "order_item_id");
    }

    public static function blindboxReviewID(): string
    {
        return self::generate("blindbox_reviews", "BR", 4, "review_id");
    }

    public static function reviewImageID(): string
    {
        return self::generate("review_images", "RI", 4, "image_id");
    }

    public static function activityLogID(): string
    {
        return self::generate("activity_logs", "AL", 4, "activity_id");
    }

    public static function passwordHistoryID(): string
    {
        return self::generate("password_history", "PH", 4, "history_id");
    }

    public static function wishlistID(): string
    {
        return self::generate("wishlist", "WL", 4, "wishlist_id");
    }
}
