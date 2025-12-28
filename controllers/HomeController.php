<?php
require_once __DIR__ . "/../models/Blindbox.php";
require_once __DIR__ . "/../models/BlindboxImage.php";
require_once __DIR__ . "/../models/Category.php";


class HomeController
{
    /**
     * Home page controller action
     * @param array $params Route parameters (not used for home, but kept for consistency)
     */
    private $blindboxModel;
    private $blindboxImageModel;
    private $categoryModel;

    public function __construct()
    {
        $this->blindboxModel = new Blindbox();
        $this->blindboxImageModel = new BlindboxImage();
        $this->categoryModel = new Category();
    }
    
    public function index(array $params = []): void
    {
        $newArrival = $this->blindboxModel->getNewArrival();
        $images = [];
        $categoryName = [];
        foreach ($newArrival as $item) {
            $images[$item->getBlindboxId()] = $this->blindboxImageModel->getImagesByBlindboxId($item->getBlindboxId());
            $categoryName[$item->getBlindboxId()] = $this->categoryModel->getCategoryById($item->getCategoryId())->getCategoryName();
        }
        
        $topSellingProduct = $this->blindboxModel->getTopSellingProduct();
        foreach ($topSellingProduct as $item) {
            $images[$item->getBlindboxId()] = $this->blindboxImageModel->getImagesByBlindboxId($item->getBlindboxId());
            $categoryName[$item->getBlindboxId()] = $this->categoryModel->getCategoryById($item->getCategoryId())->getCategoryName();
        }

        require __DIR__ . '/../views/pages/home.php';
    }
}
