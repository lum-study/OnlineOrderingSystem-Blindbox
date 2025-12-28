<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Cart.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class Cart extends Database
{
    public function getCart($user_id)
    {
        try {
            $sql = "SELECT * from carts WHERE user_id = ?";
            $rows = $this->query($sql, [$user_id])->fetchAll();

            $carts = [];
            foreach ($rows as $row) {
                $carts[] = new CartEntity($row);
            }
            return $carts;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart.");
        }
    }

    public function addCart($user_id)
    {
        try {
            //Use IDgenerator
            $cart_id = IDGenerator::cartID();
            $sql = "INSERT INTO carts(cart_id, user_id) VALUES (?, ?)";
            $this->query($sql, [$cart_id, $user_id]);
            return $cart_id;
        } catch (Exception $e) {
            error_log("Error adding cart: " . $e->getMessage());
            throw new Exception("Failed to create cart.");
        }
    }

    public function getOrCreateCartID($user_id)
    {
        try {
            $cart = $this->getCart($user_id);
            
            // Check if cart exists and is not empty
            if (!empty($cart) && isset($cart[0])) {
                $cart_id = $cart[0]->getCartId();
                if (!empty($cart_id)) {
                    return $cart_id;
                }
            }

            // If no cart exists, create one
            $cart_id = $this->addCart($user_id);

            return $cart_id;
        } catch (Exception $e) {
            error_log("Error in getOrCreateCartID: " . $e->getMessage());
            throw new Exception("Failed to get or create cart.");
        }
    }
}
