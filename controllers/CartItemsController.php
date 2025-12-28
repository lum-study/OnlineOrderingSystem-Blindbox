<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/CartItems.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Blindbox.php';

class CartItemsController
{
    private $cartItemsModel;
    private $cartModel;
    private $blindboxModel;

    public function __construct()
    {
        $this->cartItemsModel = new CartItems();
        $this->cartModel = new Cart();
        $this->blindboxModel = new Blindbox();
    }

    public function addCartItem()
    {
        // Set error handling first
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        // Set JSON header immediately
        header('Content-Type: application/json');

        // Catch any fatal errors
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                if (!headers_sent()) {
                    header('Content-Type: application/json');
                }
                echo json_encode(['success' => false, 'error' => 'Server error: ' . $error['message']]);
            }
        });

        try {
            if (!Auth::check()) {
                echo json_encode(['success' => false, 'error' => 'Authentication required. Please login.']);
                exit;
            }

            $user_id = $_SESSION['user_id'] ?? null;
            $blindboxID = $_POST['blindbox_id'] ?? null;
            $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;

            if (empty($user_id)) {
                throw new Exception("User ID is required.");
            }

            if (empty($blindboxID)) {
                throw new Exception("Blind Box ID is required.");
            }

            if (empty($quantity) || $quantity < 1) {
                throw new Exception("Valid quantity is required.");
            }

            $cart_id = $this->cartModel->getOrCreateCartID($user_id);
            if (empty($cart_id)) {
                throw new Exception("Unable to create cart.");
            }

            $product = $this->blindboxModel->getBlindboxById($blindboxID);
            if (!$product) {
                throw new Exception("Product not found.");
            }

            if ($quantity > $product->getStockQuantity()) {
                throw new Exception("Insufficient stock. Only " . $product->getStockQuantity() . " available.");
            }

            $cartItem = $this->cartItemsModel->getCartItemByBlindBoxID($blindboxID, $cart_id);
            if ($cartItem) {
                $this->cartItemsModel->updateCartItemQuantity($cartItem->getCartItemId(), $quantity);
            } else {
                $this->cartItemsModel->addCartItem($cart_id, $blindboxID, $quantity);
            }

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            error_log("CartItemsController::addCartItem - " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        } catch (Throwable $e) {
            error_log("CartItemsController::addCartItem - Fatal: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'A fatal error occurred: ' . $e->getMessage()]);
            exit;
        }
    }



    public function getAllCartItemsByCartID($cart_id)
    {
        if (empty($cart_id)) {
            throw new Exception("Cart ID is required.");
        }

        try {
            return $this->cartItemsModel->getAllCartItemsByCartID($cart_id);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCartItemByID($cart_item_id)
    {
        if (empty($cart_item_id)) {
            throw new Exception("Cart Item ID is required.");
        }

        try {
            $result = $this->cartItemsModel->getCartItemByID($cart_item_id);
            if ($result) {
                return $result[0];
            }
            return null;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function updateCartItemQuantity()
    {
        header('Content-Type: application/json');

        try {
            if (!Auth::check()) {
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }

            $cart_item = $_POST['cart_item'] ?? null;
            $quantity = $_POST['cart_item_quantity'] ?? null;
            $cart_item_id = $cart_item['id'] ?? null;

            if (empty($cart_item_id)) {
                throw new Exception("Cart Item ID is required.");
            }

            $product = $this->blindboxModel->getBlindboxById($cart_item['blindboxID']);
            if ($quantity < 1 || $product->getStockQuantity() < $quantity) {
                throw new Exception("Invalid quantity.");
            }

            $this->cartItemsModel->updateCartItemQuantity($cart_item_id, $quantity);

            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function removeCartItem()
    {
        header('Content-Type: application/json');

        try {
            if (!Auth::check()) {
                echo json_encode(['success' => false, 'error' => 'Authentication required']);
                exit;
            }

            $cart_item_id = $_POST['cart_item_id'] ?? null;

            if (empty($cart_item_id)) {
                throw new Exception("Cart Item ID is required.");
            }

            $this->cartItemsModel->removeCartItem($cart_item_id);

            // return success as JSON
            echo json_encode(['success' => true]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function getCartItemsDetails($user_id)
    {
        if (empty($user_id)) {
            throw new Exception("User ID is required.");
        }

        try {
            $rows = $this->cartItemsModel->getCartItemsDetails($user_id);
            $cartItems = [];
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
            return $cartItems;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function getCartCount()
    {
        header('Content-Type: application/json');

        try {
            if (!Auth::check()) {
                echo json_encode(['success' => true, 'count' => 0]);
                exit;
            }

            $user_id = $_SESSION['user_id'] ?? null;

            if (empty($user_id)) {
                throw new Exception("User ID is required.");
            }

            $count = 0;

            if ($user_id) {
                $count = $this->cartItemsModel->getCartItemsCount($user_id);
            }

            echo json_encode(['success' => true, 'count' => $count]);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'count' => 0, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
