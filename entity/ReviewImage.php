<?php
class ReviewImage
{
    private $image_id;
    private $review_id;
    private $image_path;
    private $uploaded_at;

    public function getImageId() { return $this->image_id; }
    public function setImageId($image_id) { $this->image_id = $image_id; }

    public function getReviewId() { return $this->review_id; }
    public function setReviewId($review_id) { $this->review_id = $review_id; }

    public function getImagePath() { return $this->image_path; }
    public function setImagePath($image_path) { $this->image_path = $image_path; }

    public function getUploadedAt() { return $this->uploaded_at; }
    public function setUploadedAt($uploaded_at) { $this->uploaded_at = $uploaded_at; }
}
