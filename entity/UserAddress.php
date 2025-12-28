<?php

class UserAddress {
    private $address_id;
    private $user_id;
    private $receiver_name;
    private $phone_number;
    private $email;
    private $address;
    private $city;
    private $state;
    private $postal_code;
    private $country;
    private $is_default;
    private $created_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($address_id, $user_id, $receiver_name, $phone_number, $email, $address, $city, $state, $postal_code, $country, $is_default, $created_date, $updated_date, $is_deleted) {
        $this->address_id = $address_id;
        $this->user_id = $user_id;
        $this->receiver_name = $receiver_name;
        $this->phone_number = $phone_number;
        $this->email = $email;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->postal_code = $postal_code;
        $this->country = $country;
        $this->is_default = $is_default;
        $this->created_date = $created_date;
        $this->updated_date = $updated_date;
        $this->is_deleted = $is_deleted;
    }

    public function getAddressId() { return $this->address_id; }
    public function getUserId() { return $this->user_id; }
    public function getReceiverName() { return $this->receiver_name; }
    public function getPhoneNumber() { return $this->phone_number; }
    public function getEmail() { return $this->email; }
    public function getAddress() { return $this->address; }
    public function getCity() { return $this->city; }
    public function getState() { return $this->state; }
    public function getPostalCode() { return $this->postal_code; }
    public function getCountry() { return $this->country; }
    public function getIsDefault() { return $this->is_default; }
    public function getCreatedDate() { return $this->created_date; }
    public function getUpdatedDate() { return $this->updated_date; }
    public function getIsDeleted() { return $this->is_deleted; }
}
