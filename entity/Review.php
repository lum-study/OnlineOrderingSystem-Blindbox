<?php
class Review
{
    private $review_id;
    private $order_item_id;
    private $user_id;
    private $blindbox_id;
    private $rating;
    private $comment;
    private $created_date;

    public function getReviewId() { return $this->review_id; }
    public function setReviewId($review_id) { $this->review_id = $review_id; }

    public function getOrderItemId() { return $this->order_item_id; }
    public function setOrderItemId($order_item_id) { $this->order_item_id = $order_item_id; }

    public function getUserId() { return $this->user_id; }
    public function setUserId($user_id) { $this->user_id = $user_id; }

    public function getBlindboxId() { return $this->blindbox_id; }
    public function setBlindboxId($blindbox_id) { $this->blindbox_id = $blindbox_id; }

    public function getRating() { return $this->rating; }
    public function setRating($rating) { $this->rating = $rating; }

    public function getComment() { return $this->comment; }
    public function setComment($comment) { $this->comment = $comment; }

    public function getCreatedDate() { return $this->created_date; }
    public function setCreatedDate($created_date) { $this->created_date = $created_date; }
}
