<?php
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/CartItems.php';

class CartController
{
    private $cartModel;
    private $cartItemsModel;

    public function __construct()
    {
        $this->cartModel = new Cart();
        $this->cartItemsModel = new CartItems();
    }

    public function show()
    {
        require_once __DIR__ . '/../includes/auth_required.php';

        $user_id = $_SESSION['user_id'] ?? null;

        if (empty($user_id)) {
            throw new Exception('User ID is required');
        }

        $this->getOrCreateCart($user_id);

        $cartItems = [];
        try {
            $rows = $this->cartItemsModel->getCartItemsDetails($user_id);
            foreach ($rows as $row) {
                $cartItems[] = [
                    'id' => $row['cart_item_id'],
                    'blindboxID' => $row['blindbox_id'],
                    'name' => $row['product_name'],
                    'price' => $row['product_price'],
                    'stock_qty' => $row['stock_quantity'],
                    'img' => $row['image_url'],
                    'qty' => $row['quantity'],
                    'isWishlist' => $row['is_wishlist'],
                ];
            }
        } catch (Exception $e) {
            error_log('CartController::show error: ' . $e->getMessage());
            $cartItems = [];
        }
        require __DIR__ . '/../includes/header.php';
        require __DIR__ . '/../views/pages/member/cart/view.php';
        require __DIR__ . '/../includes/footer.php';
    }

    public function getOrCreateCart($user_id)
    {
        if (empty($user_id))
            throw new Exception("User ID is required.");

        try {
            $carts = $this->cartModel->getCart($user_id);
            if (!empty($carts)) {
                return $carts[0];
            }

            $this->cartModel->addCart($user_id);

            $carts = $this->cartModel->getCart($user_id);
            if (empty($carts)) {
                throw new Exception("Failed to create cart for user $user_id.");
            }
            return $carts[0];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getOrCreateCartID($user_id)
    {
        if (empty($user_id)) {
            throw new Exception("User ID is required.");
        }
        try {
            return $this->cartModel->getOrCreateCartID($user_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
