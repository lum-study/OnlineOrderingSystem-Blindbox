<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/CartItems.php';
require_once __DIR__ . '/../models/Blindbox.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/UserAddress.php';
require_once __DIR__ . '/../models/UserData.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class CheckoutController
{
    private $cartItemsModel;
    private $orderModel;
    private $orderItemModel;
    private $blindboxModel;
    private $addressModel;
    private $userDataModel;

    private const TAX_RATE = 0.06;
    private const SHIPPING_COST_HOME = 5.00;
    private const SHIPPING_COST_PICKUP = 0.00;
    private const STORE_LOCATIONS = [
        [
            'id' => 1,
            'title' => 'Taman Usahawan, Kepong',
            'address' => 'No. 2, Jalan Metro Perdana Barat 3, Taman Usahawan Kepong, Kepong Utara, 52100 Kuala Lumpur.',
            'hours' => '10:00 AM - 8:00 PM',
            'phone' => '+603-1234-5678',
            'distance' => '2.5 km away'
        ],
        [
            'id' => 2,
            'title' => 'Mid Valley Megamall',
            'address' => 'Lingkaran Syed Putra, Mid Valley City, 59200 Kuala Lumpur.',
            'hours' => '10:00 AM - 10:00 PM',
            'phone' => '+603-2345-6789',
            'distance' => '5.2 km away'
        ],
        [
            'id' => 3,
            'title' => 'Pavilion Kuala Lumpur',
            'address' => '168, Jalan Bukit Bintang, Bukit Bintang, 55100 Kuala Lumpur.',
            'hours' => '10:00 AM - 10:00 PM',
            'phone' => '+603-3456-7890',
            'distance' => '8.7 km away'
        ],
        [
            'id' => 4,
            'title' => '1 Utama Shopping Centre',
            'address' => '1, Lebuh Bandar Utama, Bandar Utama, 47800 Petaling Jaya, Selangor.',
            'hours' => '10:00 AM - 10:00 PM',
            'phone' => '+603-4567-8901',
            'distance' => '12.3 km away'
        ]
    ];

    public function __construct()
    {
        $this->cartItemsModel = new CartItems();
        $this->addressModel = new UserAddressModel();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->blindboxModel = new Blindbox();
        $this->userDataModel = new UserData();
    }

    /**
     * Display checkout page
     */
    public function view()
    {
        require_once __DIR__ . '/../includes/auth_required.php';

        $cartData = $_POST['cart_data'] ?? '';

        // Validate cart data
        if (empty($cartData)) {
            throw new Exception('No cart data provided. Please add items to your cart first.');
        }

        $cartData = json_decode($cartData, true);
        $dbCartItem = $this->cartItemsModel->getAllCartItemsByUserID($_SESSION['user_id']);
        $userCartItemIds = array_map(function ($item) {
            return $item->getCartItemID();
        }, $dbCartItem);

        $totalAmount = 0;
        foreach ($cartData as $item) {
            $id = $item['id'];

            if (!in_array($id, $userCartItemIds)) {
                $_SESSION['error'] = 'Invalid cart item detected. Please try again.';
                header('Location: /online_shopping_system/cart');
                return;
            }

            if ($item['qty'] <= 0) {
                $_SESSION['error'] = 'Invalid item quantity.';
                header('Location: /online_shopping_system/cart');
                return;
            }

            $totalAmount += $item['qty'] * $item['price'];
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid cart data format. Please try again.');
        }

        if (empty($cartData)) {
            throw new Exception('Your cart is empty. Please add items to checkout.');
        }

        $_SESSION['cart_data'] = $cartData;

        // Get user addresses
        $userAddresses = $this->addressModel->findByUserId($_SESSION['user_id']);

        // Find default address
        $defaultAddress ??= $userAddresses[0] ?? null;
        foreach ($userAddresses as $addr) {
            if ($addr->getIsDefault() === true) {
                $defaultAddress = $addr;
                break;
            }
        }

        // Prepare data for view
        $storeLocations = self::STORE_LOCATIONS;
        require __DIR__ . '/../includes/header.php';
        require __DIR__ . '/../views/pages/member/cart/checkout.php';
        require __DIR__ . '/../includes/footer.php';
    }

    /**
     * Process checkout and create order
     */
    public function process()
    {
        header('Content-Type: application/json');

        try {
            // 1. Validate and get input
            $input = $this->getAndValidateInput();

            // 2. Validate session and cart
            $cartSession = $this->validateCartSession();

            // 3. Calculate order totals and validate stock
            $orderData = $this->prepareOrderData($cartSession, $input);

            // 4. Get shipping address
            $shippingAddress = $this->getShippingAddress($input);

            // 5. Create order (Status defaults to 'pending' or 'to_pay')
            $orderId = $this->createOrder($orderData, $shippingAddress, $input['payment_method']);

            // 6. Create order items
            $this->createOrderItems($orderId, $orderData['items']);

            // 7. Remove cart item
            $this->clearUserCart();
            $this->clearCartSession();

            // 8. Return success immediately with Order ID
            echo json_encode([
                'success' => true,
                'order_id' => $orderId,
                'total_amount' => $orderData['total_amount'],
                'payment_method' => $input['payment_method'],
                'message' => 'Order created successfully'
            ]);
        } catch (ValidationException $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'field' => $e->getField() ?? null
            ]);
        } catch (Exception $e) {
            error_log('Checkout Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function sendConfirmationEmail()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $orderID = $input['order_id'];
        $shippingID = $this->orderModel->getOrderById($orderID)['shipping_id'];

        $userData = $this->userDataModel->findById($_SESSION['user_id']);

        $shippingAddress = !empty($shippingID) ? $this->addressModel->findByID($shippingID) : null;
        $email = ($shippingAddress && !empty($shippingAddress->getEmail()))
            ? $shippingAddress->getEmail()
            : $userData->getEmail();

        $order = $this->orderModel->getOrderById($orderID);
        $orderItems = $this->orderItemModel->getByOrderId($orderID);
        list($date, $time) = explode(' ', $order['created_date']);

        $qrCodePath = null;
        
        try {
            // Generate QR code for pickup orders (when shipping_id is empty)
            if (empty($shippingID) && !empty($order['shipping_address'])) {
                // Remove "PICKUP: " prefix from shipping address
                $pickupAddress = $order['shipping_address'];
                if (stripos($pickupAddress, 'PICKUP: ') === 0) {
                    $pickupAddress = substr($pickupAddress, 8);
                }
                
                // Create Google Maps link
                $googleMapsLink = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($pickupAddress);
                
                // Generate QR code and save to assets
                $qrCodeFileName = 'qr_order_' . $orderID . '_' . time() . '.png';
                $qrCodePath = __DIR__ . '/../assets/images/uploads/' . $qrCodeFileName;
                
                $options = new QROptions([
                    'outputType' => QRCode::OUTPUT_IMAGE_PNG,
                    'eccLevel' => QRCode::ECC_L,
                    'scale' => 10,
                ]);
                
                $qrcode = new QRCode($options);
                $qrcode->render($googleMapsLink, $qrCodePath);
            }
            
            Email::sendOrderConfirmation($email, $orderItems, $order, $userData, !empty($shippingID), $date, $time, $qrCodePath);
            
            // Delete QR code after sending email
            if ($qrCodePath && file_exists($qrCodePath)) {
                unlink($qrCodePath);
            }
            
            echo json_encode([
                "success" => true
            ]);
        } catch (Exception $e) {
            // Clean up QR code if email sending failed
            if ($qrCodePath && file_exists($qrCodePath)) {
                unlink($qrCodePath);
            }
            
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get and validate input data
     */
    private function getAndValidateInput(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input)) {
            throw new ValidationException('Invalid input data received. Please refresh and try again.');
        }

        $requiredFields = ['payment_method', 'delivery_method'];
        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || empty($input[$field])) {
                throw new ValidationException("Please select a $field to proceed.", $field);
            }
        }

        // Validate payment method
        $validPaymentMethods = ['card', 'cash'];
        if (!in_array($input['payment_method'], $validPaymentMethods)) {
            throw new ValidationException('Invalid payment method selected.', 'payment_method');
        }

        // Validate delivery method
        $validDeliveryMethods = ['home', 'pickup'];
        if (!in_array($input['delivery_method'], $validDeliveryMethods)) {
            throw new ValidationException('Invalid delivery method selected.', 'delivery_method');
        }

        return $input;
    }

    /**
     * Validate cart session data
     */
    private function validateCartSession(): array
    {
        if (!isset($_SESSION['cart_data']) || empty($_SESSION['cart_data'])) {
            throw new ValidationException('Your cart is empty or the session has expired. Please add items to your cart.');
        }

        return $_SESSION['cart_data'];
    }

    /**
     * Prepare order data with calculations and stock validation
     */
    private function prepareOrderData(array $cartSession, array $input): array
    {
        $subTotal = 0;
        $items = [];

        foreach ($cartSession as $item) {
            // Validate item structure
            if (!isset($item['blindboxID']) || !isset($item['qty'])) {
                throw new ValidationException('Invalid item format in cart.');
            }

            $product = $this->blindboxModel->getBlindboxById($item['blindboxID']);

            if (!$product) {
                throw new ValidationException(
                    "Product not found. It may have been removed from our store.",
                    'product_' . $item['blindboxID']
                );
            }

            $qty = (int) $item['qty'];
            $stockQty = $product->getStockQuantity();

            // Validate quantity
            if ($qty < 1) {
                throw new ValidationException(
                    "Invalid quantity for product: " . $product->getProductName(),
                    'quantity_' . $item['blindboxID']
                );
            }

            // Check stock availability
            if ($qty > $stockQty) {
                throw new ValidationException(
                    "Insufficient stock for '{$product->getProductName()}'. " .
                    "Available: {$stockQty}, Requested: {$qty}",
                    'stock_' . $item['blindboxID']
                );
            }

            $price = $product->getPrice();
            $lineTotal = $price * $qty;
            $subTotal += $lineTotal;

            // Prepare item for order
            $items[] = [
                'blindbox_id' => $product->getBlindboxId(),
                'product_name' => $product->getProductName(),
                'quantity' => $qty,
                'price' => $price,
                'subtotal' => $lineTotal,
                'tax_amount' => $price * self::TAX_RATE,
            ];
        }

        // Calculate shipping cost
        $shippingCost = $input['delivery_method'] === 'home'
            ? self::SHIPPING_COST_HOME
            : self::SHIPPING_COST_PICKUP;

        // Calculate taxes and totals
        $taxAmount = $subTotal * self::TAX_RATE;
        $totalAmount = $subTotal + $shippingCost + $taxAmount;

        return [
            'sub_total' => $subTotal,
            'shipping_cost' => $shippingCost,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'items' => $items,
            'shipping_id' => $input['delivery_method'] === 'home' ? $input['shipping_id'] : null,
        ];
    }

    /**
     * Get shipping address based on delivery method
     */
    private function getShippingAddress(array $input): string
    {
        if ($input['delivery_method'] === 'home') {
            // Home delivery - use user address
            $addresses = $this->addressModel->findByUserId($_SESSION['user_id']);
            $selectedAddress = null;

            foreach ($addresses as $address) {
                if ($address->getAddressId() == $input['shipping_id']) {
                    $selectedAddress = $address;
                    break;
                }
            }

            if (!$selectedAddress) {
                throw new ValidationException('Selected shipping address not found.', 'shipping_address');
            }

            return $selectedAddress->getAddress() . ", " .
                $selectedAddress->getCity() . ", " .
                $selectedAddress->getPostalCode();
        } else {
            // Store pickup
            $storeIndex = (int) $input['shipping_id'];

            $store = null;
            foreach (self::STORE_LOCATIONS as $location) {
                if ($location['id'] == $storeIndex) {
                    $store = $location;
                    break;
                }
            }

            if (!$store) {
                throw new ValidationException('Selected store location not found.', 'store_location');
            }

            return "PICKUP: " . $store['address'];
        }
    }

    /**
     * Create order in database
     */
    private function createOrder(array $orderData, string $shippingAddress, string $paymentMethod): string
    {
        try {
            $orderId = IDGenerator::orderID();

            $result = $this->orderModel->create([
                'order_id' => $orderId,
                'user_id' => $_SESSION['user_id'],
                'sub_total_amount' => $orderData['sub_total'],
                'total_amount' => $orderData['total_amount'],
                'tax_amount' => $orderData['tax_amount'],
                'status' => 'to_pay',
                'shipping_address' => $shippingAddress,
                'payment_method' => $paymentMethod,
                'shipping_id' => $orderData['shipping_id'],
            ]);

            if (!$result) {
                throw new Exception('Failed to create order in database.');
            }

            return $orderId;

        } catch (Exception $e) {
            throw new Exception('Failed to create order. Please try again.');
        }
    }

    /**
     * Create order items in database
     */
    private function createOrderItems(string $orderId, array $items): void
    {
        try {
            foreach ($items as $item) {
                $result = $this->orderItemModel->create([
                    'order_item_id' => IDGenerator::orderItemID(),
                    'order_id' => $orderId,
                    'blindbox_id' => $item['blindbox_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                    'tax_amount' => $item['tax_amount'],
                ]);

                if (!$result) {
                    throw new Exception("Failed to add item '{$item['product_name']}' to order.");
                }

                // Update stock quantity
                $blindbox = $this->blindboxModel->getBlindboxById($item["blindbox_id"]);
                $this->blindboxModel->updateBlindbox(
                    $blindbox->getBlindboxId(),
                    $blindbox->getCategoryId(),
                    $blindbox->getProductName(),
                    $blindbox->getDescription(),
                    $blindbox->getPrice(),
                    $blindbox->getStockQuantity() - $item["quantity"],
                    $blindbox->getStatus(),
                );
            }
        } catch (Exception $e) {
            // If we fail to add order items, we should rollback the order
            $this->orderModel->delete($orderId);
            throw new Exception('Failed to process order items: ' . $e->getMessage());
        }
    }

    /**
     * Clear user's cart after successful payment
     */
    private function clearUserCart(): void
    {
        try {
            if (!$_SESSION['user_id']) {
                throw new Exception('User not authenticated.');
            }

            $cartItems = $_SESSION['cart_data'];

            foreach ($cartItems as $item) {
                $result = $this->cartItemsModel->removeCartItem($item['id']);
                if (!$result) {
                    error_log("Failed to remove cart item: {$item['id']}");
                }
            }

        } catch (Exception $e) {
            error_log('Error clearing user cart: ' . $e->getMessage());
        }
    }

    /**
     * Clear cart data from session
     */
    private function clearCartSession(): void
    {
        if (isset($_SESSION['cart_data'])) {
            unset($_SESSION['cart_data']);
        }
    }
}

/**
 * Custom exception class for validation errors
 */
class ValidationException extends Exception
{
    private $field;

    public function __construct($message, $field = null, $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->field = $field;
    }

    public function getField()
    {
        return $this->field;
    }
}