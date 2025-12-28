<?php
require_once __DIR__ . "/../models/Wishlist.php";
require_once __DIR__ . "/../models/Blindbox.php";
class WishlistController
{
    private $wishlistModel;
    private $blindboxModel;

    public function __construct()
    {
        $this->wishlistModel = new Wishlist();
        $this->blindboxModel = new Blindbox();
    }

    public function add()
    {
        header('Content-Type: application/json');

        try {
            $userID = $_SESSION['user_id'] ?? null;
            $blindboxID = $_POST['blindbox_id'] ?? null;

            if (empty($userID)) {
                throw new Exception("User ID is required.");
            }

            if (empty($blindboxID)) {
                throw new Exception("Blindbox ID is required.");
            }

            $blindbox = $this->blindboxModel->getBlindboxById($blindboxID);
            if (empty($blindbox)) {
                throw new Exception("Blindbox does not exist.");
            }

            if($this->wishlistModel->exists($userID, $blindboxID)) {
                throw new Exception("Item already in wishlist.");
            }

            $this->wishlistModel->addWishlistItem($userID, $blindboxID);

            echo json_encode([
                "success" => true,
                "message" => "Added to wishlist successfully.",
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage(),
            ]);
        }
    }

    public function remove()
    {
        header('Content-Type: application/json');

        try {
            $userID = $_SESSION['user_id'] ?? null;
            $blindboxID = $_POST['blindbox_id'] ?? null;

            if (empty($userID)) {
                throw new Exception("User ID is required.");
            }

            if (empty($blindboxID)) {
                throw new Exception("Blindbox ID is required.");
            }

            $wishlistItems = $this->wishlistModel->getWishlistByUserAndBlindbox($userID, $blindboxID);
            if (empty($wishlistItems)) {
                throw new Exception("Wishlist item not found.");
            }
            
            $this->wishlistModel->removeWishlistItem($wishlistItems->getWishlistID());

            echo json_encode([
                "success" => true,
                "message" => "Item removed from wishlist successfully.",
            ]);
        } catch (Exception $e) {
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage(),
            ]);
        }
    }
}