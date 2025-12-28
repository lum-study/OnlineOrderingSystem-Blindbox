<?php

class WishlistEntity
{
    private string $wishlist_id;
    private string $user_id;
    private string $blindbox_id;
    private string $created_date;

    public function __construct($data)
    {
        $this->wishlist_id = $data['wishlist_id'] ?? 0;
        $this->user_id = $data['user_id'] ?? 0;
        $this->blindbox_id = $data['blindbox_id'] ?? 0;
        $this->created_date = $data['created_date'] ?? date('Y-m-d H:i:s');
    }

    // Getters
    public function getWishlistID()
    {
        return $this->wishlist_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getBlindBoxID()
    {
        return $this->blindbox_id;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    // Setters
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function setBlindBoxID($blindbox_id)
    {
        $this->blindbox_id = $blindbox_id;
    }

    public function setCreatedDate($created_date)
    {
        $this->created_date = $created_date;
    }
}
