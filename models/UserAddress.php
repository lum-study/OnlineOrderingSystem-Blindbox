<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/UserAddress.php';

class UserAddressModel extends Database {
    public function findByUserId($user_id) {
        $sql = "SELECT * FROM user_addresses WHERE user_id = ? AND is_deleted = 0 ORDER BY is_default DESC, created_date DESC";
        $stmt = $this->query($sql, [$user_id]);
        $addresses = [];
        while ($row = $stmt->fetch()) {
            $addresses[] = new UserAddress(
                $row['address_id'],
                $row['user_id'],
                $row['receiver_name'],
                $row['phone_number'],
                $row['email'],
                $row['address'],
                $row['city'],
                $row['state'],
                $row['postal_code'],
                $row['country'],
                $row['is_default'],
                $row['created_date'],
                $row['updated_date'],
                $row['is_deleted']
            );
        }
        return $addresses;
    }

    public function findByID($address_id) {
        $sql = "SELECT * FROM user_addresses WHERE address_id = ? AND is_deleted = 0 ORDER BY is_default DESC, created_date DESC";
        $stmt = $this->query($sql, [$address_id]);
        $addresses = [];
        while ($row = $stmt->fetch()) {
            $addresses[] = new UserAddress(
                $row['address_id'],
                $row['user_id'],
                $row['receiver_name'],
                $row['phone_number'],
                $row['email'],
                $row['address'],
                $row['city'],
                $row['state'],
                $row['postal_code'],
                $row['country'],
                $row['is_default'],
                $row['created_date'],
                $row['updated_date'],
                $row['is_deleted']
            );
        }
        return $addresses[0];
    }

    public function create($address_id, $user_id, $receiver_name, $phone_number, $email, $address, $city, $state, $postal_code, $country, $is_default) {
        if ($is_default) {
            $this->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?", [$user_id]);
        }
        $sql = "INSERT INTO user_addresses (address_id, user_id, receiver_name, phone_number, email, address, city, state, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->query($sql, [$address_id, $user_id, $receiver_name, $phone_number, $email, $address, $city, $state, $postal_code, $country, $is_default]);
    }

    public function delete($address_id) {
        $sql = "UPDATE user_addresses SET is_deleted = 1 WHERE address_id = ?";
        $this->query($sql, [$address_id]);
    }

    public function setDeleted($user_id, $is_deleted)
    {
        $sql = "UPDATE user_addresses SET is_deleted = ? WHERE user_id = ?";
        $this->query($sql, [$is_deleted, $user_id]);
    }
}
