<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/CartItems.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class CartItems extends Database
{
    public function addCartItem($cart_id, $blindbox_id, $quantity)
    {
        try {
            $cart_item_id = IDGenerator::cartItemID();

            $sql = "INSERT INTO cart_items(cart_item_id, cart_id, blindbox_id, quantity) 
                VALUES (?, ?, ?, ?)";
            return $this->query($sql, [$cart_item_id, $cart_id, $blindbox_id, $quantity]);
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw new Exception("Failed to add new cart item." . $e->getMessage());
        }
    }

    public function getAllCartItemsByCartID($cart_id)
    {
        try {
            $sql = "SELECT * FROM cart_items WHERE cart_id = ?";
            $rows = $this->query($sql, [$cart_id])->fetchAll();

            $cartItems = [];
            foreach ($rows as $row) {
                $cartItems[] = new CartItemEntity($row);
            }
            return $cartItems;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart items.");
        }
    }

    public function getAllCartItemsByUserID($user_id)
    {
        try {
            $sql = "SELECT ci.* FROM cart_items ci 
                    JOIN carts c ON ci.cart_id = c.cart_id 
                    WHERE c.user_id = ?";
            $rows = $this->query($sql, [$user_id])->fetchAll();

            $cartItems = [];
            foreach ($rows as $row) {
                $cartItems[] = new CartItemEntity($row);
            }
            return $cartItems;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart items.");
        }
    }

    public function getCartItemByID($cart_item_id)
    {
        try {
            $sql = "SELECT * FROM cart_items WHERE cart_item_id = ?";
            $rows = $this->query($sql, [$cart_item_id]);
            foreach ($rows as $row) {
                $cartItems[] = new CartItemEntity($row);
            }
            return $cartItems;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart item.");
        }
    }

    public function getCartItemByBlindBoxID($blindbox_id, $cart_id)
    {
        try {
            $sql = "SELECT * FROM cart_items WHERE blindbox_id = ? AND cart_id = ?";
            $rows = $this->query($sql, [$blindbox_id, $cart_id]);
            foreach ($rows as $row) {
                $cartItems[] = new CartItemEntity($row);
            }
            return $cartItems[0] ?? null;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart item.");
        }
    }

    public function updateCartItemQuantity($cart_item_id, $quantity)
    {
        try {
            $sql = "UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?";
            return $this->query($sql, [$quantity, $cart_item_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update cart item quantity.");
        }
    }

    public function removeCartItem($cart_item_id)
    {
        try {
            $sql = "DELETE FROM cart_items WHERE cart_item_id = ?";
            return $this->query($sql, [$cart_item_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to remove cart items.");
        }
    }

    public function getCartItemsDetails($user_id)
    {
        try {
            $sql = "SELECT
                ci.cart_item_id AS cart_item_id,
                b.blindbox_id AS blindbox_id,
                b.product_name AS product_name, 
                b.price AS product_price,
                b.stock_quantity AS stock_quantity, 
                bi.image_url AS image_url,
                ci.quantity AS quantity,
                CASE WHEN wl.wishlist_id IS NULL THEN 0 ELSE 1 END AS is_wishlist
            FROM user_data ud
            JOIN carts c ON c.user_id = ud.user_id
            JOIN cart_items ci ON ci.cart_id = c.cart_id
            JOIN blindbox b ON b.blindbox_id = ci.blindbox_id
            LEFT JOIN (
                SELECT blindbox_id, MIN(image_url) AS image_url
                FROM blindbox_images
                GROUP BY blindbox_id
            ) bi ON bi.blindbox_id = b.blindbox_id
            LEFT JOIN (
                SELECT user_id, blindbox_id, MIN(wishlist_id) AS wishlist_id
                FROM wishlist
                GROUP BY user_id, blindbox_id
            ) wl ON wl.user_id = ud.user_id AND wl.blindbox_id = b.blindbox_id
            WHERE ud.user_id = ?";

            return $this->query($sql, [$user_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart item details.");
        }
    }

    public function getCartItemsCount($user_id)
    {
        try {
            $sql = "SELECT COUNT(ci.cart_item_id) AS item_count
                FROM carts c
                JOIN cart_items ci ON ci.cart_id = c.cart_id
                WHERE c.user_id = ?";

            $row = $this->query($sql, [$user_id])->fetch(PDO::FETCH_ASSOC);
            return $row['item_count'] ?? 0;

        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart item count.");
        }
    }
}
