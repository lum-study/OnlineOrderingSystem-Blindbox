-- Host: localhost:3306    
-- Server: MySQL 8.4.4
-- username: root
-- password: root
-- Database: online_shopping_db

DROP DATABASE IF EXISTS online_shopping_db;
CREATE DATABASE online_shopping_db;

USE online_shopping_db;

CREATE TABLE user_data (
    user_id VARCHAR(255) PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50),
    birth_date DATE,
    gender VARCHAR(50),
    profile_photo VARCHAR(255),
    is_blocked TINYINT(1) DEFAULT 0,
    preferences JSON,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_email_active (email, is_deleted)
);

CREATE TABLE user_logins (
    login_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    username_changed_at TIMESTAMP NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    remember_token VARCHAR(255),
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_username_active (username, is_deleted),
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE
);

CREATE TABLE staff_data (
    staff_id VARCHAR(255) PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    contact_number VARCHAR(50),
    birth_date DATE,
    gender VARCHAR(50),
    profile_photo VARCHAR(255),
    is_blocked TINYINT(1) DEFAULT 0,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_email_active (email, is_deleted)
);

CREATE TABLE staff_logins (
    login_id VARCHAR(255) PRIMARY KEY,
    staff_id VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    failed_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_login TIMESTAMP NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    UNIQUE KEY unique_staff_id_active (staff_id, is_deleted),
    UNIQUE KEY unique_username_active (username, is_deleted),
    FOREIGN KEY (staff_id) REFERENCES staff_data(staff_id) ON DELETE CASCADE
);

CREATE TABLE verification_tokens (
    token_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255),
    staff_id VARCHAR(255),
    type ENUM('password_reset', 'email_verification') NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff_data(staff_id) ON DELETE CASCADE,
    INDEX idx_token_type (token, type)
);

CREATE TABLE user_addresses (
    address_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    receiver_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL,
    is_default TINYINT(1) DEFAULT 0,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE
);

CREATE TABLE orders (
    order_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    sub_total_amount DECIMAL(25,2) NOT NULL,
    total_amount DECIMAL(25,2) NOT NULL,
    tax_amount DECIMAL(25,2) NOT NULL,
    status ENUM('to_pay', 'pending','shipped','completed','cancelled') DEFAULT 'pending',
    shipping_address TEXT,
    shipping_id VARCHAR(255),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE
);

CREATE TABLE payments (
    payment_id VARCHAR(255) PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL,
    payment_method ENUM('ewallet', 'card', 'cash', 'others') DEFAULT 'others',
    status ENUM('completed', 'refunded', 'failed') DEFAULT 'completed',
    amount DECIMAL(25,2) NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

CREATE TABLE refunds (
    refund_id VARCHAR(255) PRIMARY KEY,
    payment_id VARCHAR(255) NOT NULL,
    reason TEXT,
    status ENUM('requested', 'approved', 'rejected') DEFAULT 'requested',
    amount DECIMAL(25,2) NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE
);

CREATE TABLE categories (
    category_id VARCHAR(255) PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0
);

CREATE TABLE blindbox (
    blindbox_id VARCHAR(255) PRIMARY KEY,
    category_id VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(25,2) NOT NULL,
    stock_quantity INT NOT NULL,
    status ENUM('active','inactive','out_of_stock') DEFAULT 'active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);

CREATE TABLE blindbox_images (
    image_id VARCHAR(255) PRIMARY KEY,
    blindbox_id VARCHAR(255) NOT NULL,
    image_url TEXT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (blindbox_id) REFERENCES blindbox(blindbox_id) ON DELETE CASCADE
);

CREATE TABLE products (
    product_id VARCHAR(255) PRIMARY KEY,
    blindbox_id VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (blindbox_id) REFERENCES blindbox(blindbox_id) ON DELETE CASCADE
);

CREATE TABLE product_images (
    image_id VARCHAR(255) PRIMARY KEY,
    product_id VARCHAR(255) NOT NULL,
    image_url TEXT,
    is_front TINYINT(1) NOT NULL DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    INDEX idx_product_front (product_id, is_front, is_deleted)
);

CREATE TABLE carts (
    cart_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    cart_item_id VARCHAR(255) PRIMARY KEY,
    cart_id VARCHAR(255) NOT NULL,
    blindbox_id VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (blindbox_id) REFERENCES blindbox(blindbox_id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    order_item_id VARCHAR(255) PRIMARY KEY,
    order_id VARCHAR(255) NOT NULL,
    blindbox_id VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(25,2),
    subtotal DECIMAL(25,2),
    tax_amount DECIMAL(25,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (blindbox_id) REFERENCES blindbox(blindbox_id) ON DELETE CASCADE
);

CREATE TABLE blindbox_reviews (
    review_id VARCHAR(255) PRIMARY KEY,
    order_item_id VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (order_item_id) REFERENCES order_items(order_item_id) ON DELETE CASCADE
);

CREATE TABLE review_images (
    image_id VARCHAR(255) PRIMARY KEY,
    review_id VARCHAR(255) NOT NULL,
    image_url TEXT,
    sequence INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (review_id) REFERENCES blindbox_reviews(review_id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    activity_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255),
    staff_id VARCHAR(255),
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff_data(staff_id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_action (action),
    INDEX idx_created_date (created_date)
);

CREATE TABLE wishlist(
    wishlist_id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(255),
    blindbox_id VARCHAR(255) NOT NULL,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES user_data(user_id) ON DELETE CASCADE,
    FOREIGN KEY (blindbox_id) REFERENCES blindbox(blindbox_id) ON DELETE CASCADE
);

-- Sample data for testing login module
-- Password for all accounts: Password123!
-- ID Format: {PREFIX}{NUMBER} - PREFIX identifies table, NUMBER starts at 0001
-- Table Prefixes: SD=staff_data, SL=staff_logins, UD=user_data, UL=user_logins
-- Auto-increment: 0001-9999, then 10000-99999, etc.

-- Insert sample staff (admin)
INSERT INTO staff_data (staff_id, fullname, email, contact_number, birth_date, gender) VALUES
('SD0001', 'Admin User', 'admin@blindedoos.com', '+60-123456780', '1990-01-01', 'male');

INSERT INTO staff_logins (login_id, staff_id, username, password, position) VALUES
('SL0001', 'SD0001', 'admin', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'admin');

-- Insert sample users (members)
INSERT INTO user_data (user_id, fullname, email, contact_number, birth_date, gender) VALUES
('UD0001', 'John Doe', 'john@example.com', '+60-123456780', '1995-05-15', 'male'),
('UD0002', 'Jane Smith', 'jane@example.com', '+60-123456780', '1998-08-20', 'female');

INSERT INTO user_logins (login_id, user_id, username, password, email_verified) VALUES
('UL0001', 'UD0001', 'johndoe', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0002', 'UD0002', 'janesmith', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1);

-- =============================================
-- INSERTING 20 ADDITIONAL STAFF MEMBERS
-- IDs: SD0002 to SD0021
-- Logins: SL0002 to SL0021
-- =============================================

INSERT INTO staff_data (staff_id, fullname, email, contact_number, birth_date, gender) VALUES
('SD0002', 'Alice Green', 'alice.green@blindedoos.com', '+60-123456781', '1992-03-12', 'female'),
('SD0003', 'Bob White', 'bob.white@blindedoos.com', '+60-123456782', '1988-11-05', 'male'),
('SD0004', 'Charlie Black', 'charlie.black@blindedoos.com', '+60-123456783', '1995-07-22', 'male'),
('SD0005', 'Diana Prince', 'diana.prince@blindedoos.com', '+60-123456784', '1990-09-15', 'female'),
('SD0006', 'Evan Wright', 'evan.wright@blindedoos.com', '+60-123456785', '1993-01-30', 'male'),
('SD0007', 'Fiona Gallagher', 'fiona.g@blindedoos.com', '+60-123456786', '1996-04-18', 'female'),
('SD0008', 'George Miller', 'george.m@blindedoos.com', '+60-123456787', '1985-12-12', 'male'),
('SD0009', 'Hannah Baker', 'hannah.b@blindedoos.com', '+60-123456788', '1998-06-25', 'female'),
('SD0010', 'Ian Somerhalder', 'ian.s@blindedoos.com', '+60-123456789', '1987-02-14', 'male'),
('SD0011', 'Julia Roberts', 'julia.r@blindedoos.com', '+60-123456790', '1991-08-08', 'female'),
('SD0012', 'Kevin Hart', 'kevin.h@blindedoos.com', '+60-123456791', '1989-10-03', 'male'),
('SD0013', 'Liam Neeson', 'liam.n@blindedoos.com', '+60-123456792', '1980-05-20', 'male'),
('SD0014', 'Mia Kunis', 'mia.k@blindedoos.com', '+60-123456793', '1994-11-11', 'female'),
('SD0015', 'Noah Centineo', 'noah.c@blindedoos.com', '+60-123456794', '1997-03-27', 'male'),
('SD0016', 'Olivia Wilde', 'olivia.w@blindedoos.com', '+60-123456795', '1986-07-19', 'female'),
('SD0017', 'Peter Parker', 'peter.p@blindedoos.com', '+60-123456796', '1999-09-01', 'male'),
('SD0018', 'Quinn Fabray', 'quinn.f@blindedoos.com', '+60-123456797', '1992-12-31', 'female'),
('SD0019', 'Ryan Reynolds', 'ryan.r@blindedoos.com', '+60-123456798', '1983-04-14', 'male'),
('SD0020', 'Sarah Connor', 'sarah.c@blindedoos.com', '+60-123456799', '1984-06-06', 'female'),
('SD0021', 'Tom Holland', 'tom.h@blindedoos.com', '+60-123456800', '1996-06-01', 'male');

INSERT INTO staff_logins (login_id, staff_id, username, password, position) VALUES
('SL0002', 'SD0002', 'alicegreen', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'manager'),
('SL0003', 'SD0003', 'bobwhite', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'stock_keeper'),
('SL0004', 'SD0004', 'charlieblack', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'support'),
('SL0005', 'SD0005', 'dianaprince', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'manager'),
('SL0006', 'SD0006', 'evanwright', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'stock_keeper'),
('SL0007', 'SD0007', 'fionag', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'support'),
('SL0008', 'SD0008', 'georgem', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'admin'),
('SL0009', 'SD0009', 'hannahb', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'support'),
('SL0010', 'SD0010', 'ians', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'marketing'),
('SL0011', 'SD0011', 'juliar', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'manager'),
('SL0012', 'SD0012', 'kevinh', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'stock_keeper'),
('SL0013', 'SD0013', 'liamn', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'security'),
('SL0014', 'SD0014', 'miak', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'support'),
('SL0015', 'SD0015', 'noahc', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'marketing'),
('SL0016', 'SD0016', 'oliviaw', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'admin'),
('SL0017', 'SD0017', 'peterp', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'intern'),
('SL0018', 'SD0018', 'quinnf', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'support'),
('SL0019', 'SD0019', 'ryanr', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'manager'),
('SL0020', 'SD0020', 'sarahc', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'stock_keeper'),
('SL0021', 'SD0021', 'tomh', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 'intern');

-- =============================================
-- INSERTING 20 ADDITIONAL USERS (MEMBERS)
-- IDs: UD0003 to UD0022 (UD0001 & UD0002 exist)
-- Logins: UL0003 to UL0022
-- =============================================

INSERT INTO user_data (user_id, fullname, email, contact_number, birth_date, gender) VALUES
('UD0003', 'Michael Brown', 'michael.brown@example.com', '+60-111111111', '1993-02-10', 'male'),
('UD0004', 'Emily Davis', 'emily.davis@example.com', '+60-111111112', '1995-05-15', 'female'),
('UD0005', 'Chris Wilson', 'chris.wilson@example.com', '+60-111111113', '1990-08-20', 'male'),
('UD0006', 'Jessica Taylor', 'jessica.taylor@example.com', '+60-111111114', '1997-11-25', 'female'),
('UD0007', 'David Anderson', 'david.anderson@example.com', '+60-111111115', '1985-01-30', 'male'),
('UD0008', 'Sophia Thomas', 'sophia.thomas@example.com', '+60-111111116', '1999-04-05', 'female'),
('UD0009', 'Daniel Martinez', 'daniel.martinez@example.com', '+60-111111117', '1988-07-10', 'male'),
('UD0010', 'Ashley Jackson', 'ashley.jackson@example.com', '+60-111111118', '1994-09-15', 'female'),
('UD0011', 'James White', 'james.white@example.com', '+60-111111119', '1991-12-20', 'male'),
('UD0012', 'Linda Harris', 'linda.harris@example.com', '+60-111111120', '1986-03-25', 'female'),
('UD0013', 'Robert Martin', 'robert.martin@example.com', '+60-111111121', '1992-06-30', 'male'),
('UD0014', 'Patricia Thompson', 'patricia.thompson@example.com', '+60-111111122', '1996-08-04', 'female'),
('UD0015', 'William Garcia', 'william.garcia@example.com', '+60-111111123', '1989-10-09', 'male'),
('UD0016', 'Elizabeth Robinson', 'elizabeth.robinson@example.com', '+60-111111124', '1998-01-14', 'female'),
('UD0017', 'Joseph Clark', 'joseph.clark@example.com', '+60-111111125', '1987-03-19', 'male'),
('UD0018', 'Karen Rodriguez', 'karen.rodriguez@example.com', '+60-111111126', '1993-05-24', 'female'),
('UD0019', 'Thomas Lewis', 'thomas.lewis@example.com', '+60-111111127', '1990-07-29', 'male'),
('UD0020', 'Nancy Lee', 'nancy.lee@example.com', '+60-111111128', '1995-10-03', 'female'),
('UD0021', 'Charles Walker', 'charles.walker@example.com', '+60-111111129', '1984-12-08', 'male'),
('UD0022', 'Margaret Hall', 'margaret.hall@example.com', '+60-111111130', '1997-02-13', 'female');

INSERT INTO user_logins (login_id, user_id, username, password, email_verified) VALUES
('UL0003', 'UD0003', 'mikebrown', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0004', 'UD0004', 'emilyd', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0005', 'UD0005', 'chrisw', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0006', 'UD0006', 'jessicat', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0007', 'UD0007', 'davida', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0008', 'UD0008', 'sophiat', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0009', 'UD0009', 'danielm', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0010', 'UD0010', 'ashleyj', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0011', 'UD0011', 'jamesw', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0012', 'UD0012', 'lindah', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0013', 'UD0013', 'robertm', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0014', 'UD0014', 'patriciat', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0015', 'UD0015', 'williamg', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0016', 'UD0016', 'elizabethr', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0017', 'UD0017', 'josephc', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0018', 'UD0018', 'karenr', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0019', 'UD0019', 'thomasl', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0020', 'UD0020', 'nancyl', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0021', 'UD0021', 'charlesw', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1),
('UL0022', 'UD0022', 'margareth', '$2y$10$AcwB4LgXQ6GUuqS3DXonPOAEUvwixBTOv8RIcy0nVPZbLgMyfAUqi', 1);

-- Insert sample categories
INSERT INTO categories (category_id, category_name, description) VALUES
('CT0001', 'Spy x Family', 'Japanese anime characters from Spy x Family series.'),
('CT0002', 'THE MONSTERS', 'Cute monster character series.'),
('CT0003', 'TWINKLE TWINKLE', 'Sparkling star-themed collectible series.'),
('CT0004', 'CRYBABY', 'Emotional character series with expressive designs.'),
('CT0005', 'DIMOO', 'Trendy art toy collectible series.'),
('CT0006', 'DISNEY', 'Classic Disney character collection.');

-- Insert sample blindbox products
INSERT INTO blindbox (blindbox_id, category_id, product_name, description, price, stock_quantity, status) VALUES
('BB0001', 'CT0001', 'Spy x Family Anya''s Daily Life Series', 'Collectible figures featuring Anya Forger in various daily life poses. Each blindbox contains one random figure with 6 different variants.', 59.99, 300, 'active'),
('BB0002', 'CT0002', 'THE MONSTERS 1:00 A.M. Series', 'Adorable monster characters with unique personalities. Contains 7 different variants.', 49.99, 250, 'active'),
('BB0003', 'CT0002', 'THE MONSTERS Lazy Yoga Series', 'Second collection of cute monster characters. Contains 11 different variants.', 54.99, 200, 'active'),
('BB0004', 'CT0002', 'THE MONSTERS Almost Hidden Series', 'Extended monster family collection. Contains 13 different variants.', 59.99, 180, 'active'),
('BB0005', 'CT0003', 'Twinkle Twinkle Savor the Moment Series', 'Sparkling star-themed characters. Contains 6 different variants.', 44.99, 220, 'active'),
('BB0006', 'CT0003', 'Twinkle Twinkle Be a Little Star Series', 'Second collection of starry characters. Contains 6 different variants.', 44.99, 210, 'active'),
('BB0007', 'CT0003', 'We are Twinkle Twinkle Series', 'Extended starlight collection. Contains 10 different variants.', 52.99, 0, 'active'),
('BB0008', 'CT0004', 'CRYBABY CRYING TO THE MOON SERIES', 'Expressive emotional characters. Contains 6 different variants.', 46.99, 240, 'active'),
('BB0009', 'CT0004', 'CRYBABY CRYING TO THE MOON-SITTING SERIES', 'Second collection of emotional designs. Contains 7 different variants.', 48.99, 230, 'active'),
('BB0010', 'CT0005', 'DIMOO The Missing Day Series', 'Trendy art toy collection. Contains 7 different variants.', 49.99, 260, 'active'),
('BB0011', 'CT0005', 'DIMOO Jurassic World Series', 'Second collection of DIMOO figures. Contains 7 different variants.', 49.99, 250, 'active'),
('BB0012', 'CT0006', 'Disney Zootopia Series Figures', 'Beloved Disney characters. Contains 13 different variants.', 64.99, 300, 'active'),
('BB0013', 'CT0001', 'SPY × FAMILY Anya''s Daily Life DLC Series', 'Exciting new collectible series. Contains 5 different variants.', 54.99, 2, 'active');

-- Insert blindbox images
INSERT INTO blindbox_images (image_id, blindbox_id, image_url) VALUES
('BI0001', 'BB0001', 'assets/images/uploads/BB/BB0001/blindbox/Anya_blindbox.png'),
('BI0002', 'BB0002', 'assets/images/uploads/BB/BB0002/blindbox/THEMONSTERS_blindbox.png'),
('BI0003', 'BB0003', 'assets/images/uploads/BB/BB0003/blindbox/THEMONSTERS_blindbox.png'),
('BI0004', 'BB0004', 'assets/images/uploads/BB/BB0004/blindbox/THEMONSTERS_blindbox.png'),
('BI0005', 'BB0005', 'assets/images/uploads/BB/BB0005/blindbox/TWINKLETWINKLE_blindbox.png'),
('BI0006', 'BB0006', 'assets/images/uploads/BB/BB0006/blindbox/TWINKLETWINKLE_blindbox.png'),
('BI0007', 'BB0007', 'assets/images/uploads/BB/BB0007/blindbox/TWINKLETWINKLE_blindbox.png'),
('BI0008', 'BB0008', 'assets/images/uploads/BB/BB0008/blindbox/CRYBABY_blindbox.png'),
('BI0009', 'BB0009', 'assets/images/uploads/BB/BB0009/blindbox/CRYBABY_blindbox.png'),
('BI0010', 'BB0010', 'assets/images/uploads/BB/BB0010/blindbox/DIMOO_blindbox.png'),
('BI0011', 'BB0011', 'assets/images/uploads/BB/BB0011/blindbox/DIMOO_blindbox.png'),
('BI0012', 'BB0012', 'assets/images/uploads/BB/BB0012/blindbox/DISNEY_blindbox.png'),
('BI0013', 'BB0013', 'assets/images/uploads/BB/BB0013/blindbox/Spy x Family_blindbox.png');

-- Insert individual products (variants)
INSERT INTO products (product_id, blindbox_id, product_name) VALUES
('PR0001', 'BB0001', 'Anya - First Sight'),
('PR0002', 'BB0001', 'Anya - Happy'),
('PR0003', 'BB0001', 'Anya - Shock'),
('PR0004', 'BB0001', 'Anya - Sleepy'),
('PR0005', 'BB0001', 'Anya - Exercise'),
('PR0006', 'BB0001', 'Anya - Cry'),
-- BB0002 THEMONSTERS Vol.1
('PR0007', 'BB0002', 'THEMONSTERS - Variant 1'),
('PR0008', 'BB0002', 'THEMONSTERS - Variant 2'),
('PR0009', 'BB0002', 'THEMONSTERS - Variant 3'),
('PR0010', 'BB0002', 'THEMONSTERS - Variant 4'),
('PR0011', 'BB0002', 'THEMONSTERS - Variant 5'),
('PR0012', 'BB0002', 'THEMONSTERS - Variant 6'),
('PR0013', 'BB0002', 'THEMONSTERS - Variant 7'),
-- BB0003 THEMONSTERS Vol.2
('PR0014', 'BB0003', 'THEMONSTERS - Variant 1'),
('PR0015', 'BB0003', 'THEMONSTERS - Variant 2'),
('PR0016', 'BB0003', 'THEMONSTERS - Variant 3'),
('PR0017', 'BB0003', 'THEMONSTERS - Variant 4'),
('PR0018', 'BB0003', 'THEMONSTERS - Variant 5'),
('PR0019', 'BB0003', 'THEMONSTERS - Variant 6'),
('PR0020', 'BB0003', 'THEMONSTERS - Variant 7'),
('PR0021', 'BB0003', 'THEMONSTERS - Variant 8'),
('PR0022', 'BB0003', 'THEMONSTERS - Variant 9'),
('PR0023', 'BB0003', 'THEMONSTERS - Variant 10'),
('PR0024', 'BB0003', 'THEMONSTERS - Variant 11'),
-- BB0004 THEMONSTERS Vol.3
('PR0025', 'BB0004', 'THEMONSTERS - Variant 1'),
('PR0026', 'BB0004', 'THEMONSTERS - Variant 2'),
('PR0027', 'BB0004', 'THEMONSTERS - Variant 3'),
('PR0028', 'BB0004', 'THEMONSTERS - Variant 4'),
('PR0029', 'BB0004', 'THEMONSTERS - Variant 5'),
('PR0030', 'BB0004', 'THEMONSTERS - Variant 6'),
('PR0031', 'BB0004', 'THEMONSTERS - Variant 7'),
('PR0032', 'BB0004', 'THEMONSTERS - Variant 8'),
('PR0033', 'BB0004', 'THEMONSTERS - Variant 9'),
('PR0034', 'BB0004', 'THEMONSTERS - Variant 10'),
('PR0035', 'BB0004', 'THEMONSTERS - Variant 11'),
('PR0036', 'BB0004', 'THEMONSTERS - Variant 12'),
('PR0037', 'BB0004', 'THEMONSTERS - Variant 13'),
-- BB0005 TWINKLETWINKLE Vol.1
('PR0038', 'BB0005', 'TWINKLETWINKLE - Variant 1'),
('PR0039', 'BB0005', 'TWINKLETWINKLE - Variant 2'),
('PR0040', 'BB0005', 'TWINKLETWINKLE - Variant 3'),
('PR0041', 'BB0005', 'TWINKLETWINKLE - Variant 4'),
('PR0042', 'BB0005', 'TWINKLETWINKLE - Variant 5'),
('PR0043', 'BB0005', 'TWINKLETWINKLE - Variant 6'),
-- BB0006 TWINKLETWINKLE Vol.2
('PR0044', 'BB0006', 'TWINKLETWINKLE - Variant 1'),
('PR0045', 'BB0006', 'TWINKLETWINKLE - Variant 2'),
('PR0046', 'BB0006', 'TWINKLETWINKLE - Variant 3'),
('PR0047', 'BB0006', 'TWINKLETWINKLE - Variant 4'),
('PR0048', 'BB0006', 'TWINKLETWINKLE - Variant 5'),
('PR0049', 'BB0006', 'TWINKLETWINKLE - Variant 6'),
-- BB0007 TWINKLETWINKLE Vol.3
('PR0050', 'BB0007', 'TWINKLETWINKLE - Variant 1'),
('PR0051', 'BB0007', 'TWINKLETWINKLE - Variant 2'),
('PR0052', 'BB0007', 'TWINKLETWINKLE - Variant 3'),
('PR0053', 'BB0007', 'TWINKLETWINKLE - Variant 4'),
('PR0054', 'BB0007', 'TWINKLETWINKLE - Variant 5'),
('PR0055', 'BB0007', 'TWINKLETWINKLE - Variant 6'),
('PR0056', 'BB0007', 'TWINKLETWINKLE - Variant 7'),
('PR0057', 'BB0007', 'TWINKLETWINKLE - Variant 8'),
('PR0058', 'BB0007', 'TWINKLETWINKLE - Variant 9'),
('PR0059', 'BB0007', 'TWINKLETWINKLE - Variant 10'),
-- BB0008 CRYBABY Vol.1
('PR0060', 'BB0008', 'CRYBABY - Variant 1'),
('PR0061', 'BB0008', 'CRYBABY - Variant 2'),
('PR0062', 'BB0008', 'CRYBABY - Variant 3'),
('PR0063', 'BB0008', 'CRYBABY - Variant 4'),
('PR0064', 'BB0008', 'CRYBABY - Variant 5'),
('PR0065', 'BB0008', 'CRYBABY - Variant 6'),
-- BB0009 CRYBABY Vol.2
('PR0066', 'BB0009', 'CRYBABY - Variant 1'),
('PR0067', 'BB0009', 'CRYBABY - Variant 2'),
('PR0068', 'BB0009', 'CRYBABY - Variant 3'),
('PR0069', 'BB0009', 'CRYBABY - Variant 4'),
('PR0070', 'BB0009', 'CRYBABY - Variant 5'),
('PR0071', 'BB0009', 'CRYBABY - Variant 6'),
('PR0072', 'BB0009', 'CRYBABY - Variant 7'),
-- BB0010 DIMOO Vol.1
('PR0073', 'BB0010', 'DIMOO - Variant 1'),
('PR0074', 'BB0010', 'DIMOO - Variant 2'),
('PR0075', 'BB0010', 'DIMOO - Variant 3'),
('PR0076', 'BB0010', 'DIMOO - Variant 4'),
('PR0077', 'BB0010', 'DIMOO - Variant 5'),
('PR0078', 'BB0010', 'DIMOO - Variant 6'),
('PR0079', 'BB0010', 'DIMOO - Variant 7'),
-- BB0011 DIMOO Vol.2
('PR0080', 'BB0011', 'DIMOO - Variant 1'),
('PR0081', 'BB0011', 'DIMOO - Variant 2'),
('PR0082', 'BB0011', 'DIMOO - Variant 3'),
('PR0083', 'BB0011', 'DIMOO - Variant 4'),
('PR0084', 'BB0011', 'DIMOO - Variant 5'),
('PR0085', 'BB0011', 'DIMOO - Variant 6'),
('PR0086', 'BB0011', 'DIMOO - Variant 7'),
-- BB0012 DISNEY Collection
('PR0087', 'BB0012', 'DISNEY - Variant 1'),
('PR0088', 'BB0012', 'DISNEY - Variant 2'),
('PR0089', 'BB0012', 'DISNEY - Variant 3'),
('PR0090', 'BB0012', 'DISNEY - Variant 4'),
('PR0091', 'BB0012', 'DISNEY - Variant 5'),
('PR0092', 'BB0012', 'DISNEY - Variant 6'),
('PR0093', 'BB0012', 'DISNEY - Variant 7'),
('PR0094', 'BB0012', 'DISNEY - Variant 8'),
('PR0095', 'BB0012', 'DISNEY - Variant 9'),
('PR0096', 'BB0012', 'DISNEY - Variant 10'),
('PR0097', 'BB0012', 'DISNEY - Variant 11'),
('PR0098', 'BB0012', 'DISNEY - Variant 12'),
('PR0099', 'BB0012', 'DISNEY - Variant 13'),
-- BB0013 New Collection
('PR0100', 'BB0013', 'Spy x Family - Variant 1'),
('PR0101', 'BB0013', 'Spy x Family - Variant 2'),
('PR0102', 'BB0013', 'Spy x Family - Variant 3'),
('PR0103', 'BB0013', 'Spy x Family - Variant 4'),
('PR0104', 'BB0013', 'Spy x Family - Variant 5');

-- Insert product images
INSERT INTO product_images (image_id, product_id, image_url, is_front) VALUES
('PI0001', 'PR0001', 'assets/images/uploads/BB/BB0001/product/Anya_First_Sight.png', 1),
('PI0002', 'PR0002', 'assets/images/uploads/BB/BB0001/product/Anya_Happy.png', 0),
('PI0003', 'PR0003', 'assets/images/uploads/BB/BB0001/product/Anya_Shock.png', 0),
('PI0004', 'PR0004', 'assets/images/uploads/BB/BB0001/product/Anya_Sleepy.png', 0),
('PI0005', 'PR0005', 'assets/images/uploads/BB/BB0001/product/Anya_Exercise.png', 0),
('PI0006', 'PR0006', 'assets/images/uploads/BB/BB0001/product/Anya_Cry.png', 0),
-- BB0002 THEMONSTERS Vol.1
('PI0007', 'PR0007', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_1.png', 1),
('PI0008', 'PR0008', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_2.jpg', 0),
('PI0009', 'PR0009', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_3.jpg', 0),
('PI0010', 'PR0010', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_4.jpg', 0),
('PI0011', 'PR0011', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_5.jpg', 0),
('PI0012', 'PR0012', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_6.jpg', 0),
('PI0013', 'PR0013', 'assets/images/uploads/BB/BB0002/product/THEMONSTERS_7.jpg', 0),
-- BB0003 THEMONSTERS Vol.2
('PI0014', 'PR0014', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_1.jpg', 1),
('PI0015', 'PR0015', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_2.jpg', 0),
('PI0016', 'PR0016', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_3.jpg', 0),
('PI0017', 'PR0017', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_4.jpg', 0),
('PI0018', 'PR0018', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_5.jpg', 0),
('PI0019', 'PR0019', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_6.jpg', 0),
('PI0020', 'PR0020', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_7.jpg', 0),
('PI0021', 'PR0021', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_8.jpg', 0),
('PI0022', 'PR0022', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_9.jpg', 0),
('PI0023', 'PR0023', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_10.jpg', 0),
('PI0024', 'PR0024', 'assets/images/uploads/BB/BB0003/product/THEMONSTERS_11.jpg', 0),
-- BB0004 THEMONSTERS Vol.3
('PI0025', 'PR0025', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_1.jpg', 1),
('PI0026', 'PR0026', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_2.jpg', 0),
('PI0027', 'PR0027', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_3.jpg', 0),
('PI0028', 'PR0028', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_4.jpg', 0),
('PI0029', 'PR0029', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_5.jpg', 0),
('PI0030', 'PR0030', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_6.jpg', 0),
('PI0031', 'PR0031', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_7.jpg', 0),
('PI0032', 'PR0032', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_8.jpg', 0),
('PI0033', 'PR0033', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_9.jpg', 0),
('PI0034', 'PR0034', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_10.jpg', 0),
('PI0035', 'PR0035', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_11.jpg', 0),
('PI0036', 'PR0036', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_12.jpg', 0),
('PI0037', 'PR0037', 'assets/images/uploads/BB/BB0004/product/THEMONSTERS_13.jpg', 0),
-- BB0005 TWINKLETWINKLE Vol.1
('PI0038', 'PR0038', 'assets/images/uploads/BB/BB0005/product/TWINKLETWINKLE_1.jpg', 1),
('PI0039', 'PR0039', 'assets/images/uploads/BB/BB0005/product/TWINKLETWINKLE_2.jpg', 0),
('PI0040', 'PR0040', 'assets/images/uploads/BB/BB0005/product/TWINKLETWINKLE_3.jpg', 0),
('PI0041', 'PR0041', 'assets/images/uploads/BB/BB0005/product/TWINKLETWINKLE_4.jpg', 0),
('PI0042', 'PR0042', 'assets/images/uploads/BB/BB0005/product/TWINKLETWINKLE_5.jpg', 0),
('PI0043', 'PR0043', 'assets/images/uploads/BB/BB0005/product/TWINKLETWINKLE_6.jpg', 0),
-- BB0006 TWINKLETWINKLE Vol.2
('PI0044', 'PR0044', 'assets/images/uploads/BB/BB0006/product/TWINKLETWINKLE_1.jpg', 1),
('PI0045', 'PR0045', 'assets/images/uploads/BB/BB0006/product/TWINKLETWINKLE_2.jpg', 0),
('PI0046', 'PR0046', 'assets/images/uploads/BB/BB0006/product/TWINKLETWINKLE_3.jpg', 0),
('PI0047', 'PR0047', 'assets/images/uploads/BB/BB0006/product/TWINKLETWINKLE_4.jpg', 0),
('PI0048', 'PR0048', 'assets/images/uploads/BB/BB0006/product/TWINKLETWINKLE_5.jpg', 0),
('PI0049', 'PR0049', 'assets/images/uploads/BB/BB0006/product/TWINKLETWINKLE_6.jpg', 0),
-- BB0007 TWINKLETWINKLE Vol.3
('PI0050', 'PR0050', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_1.jpg', 1),
('PI0051', 'PR0051', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_2.jpg', 0),
('PI0052', 'PR0052', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_3.jpg', 0),
('PI0053', 'PR0053', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_4.jpg', 0),
('PI0054', 'PR0054', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_5.jpg', 0),
('PI0055', 'PR0055', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_6.jpg', 0),
('PI0056', 'PR0056', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_7.jpg', 0),
('PI0057', 'PR0057', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_8.jpg', 0),
('PI0058', 'PR0058', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_9.jpg', 0),
('PI0059', 'PR0059', 'assets/images/uploads/BB/BB0007/product/TWINKLETWINKLE_10.jpg', 0),
-- BB0008 CRYBABY Vol.1
('PI0060', 'PR0060', 'assets/images/uploads/BB/BB0008/product/CRYBABY_1.jpg', 1),
('PI0061', 'PR0061', 'assets/images/uploads/BB/BB0008/product/CRYBABY_2.jpg', 0),
('PI0062', 'PR0062', 'assets/images/uploads/BB/BB0008/product/CRYBABY_3.jpg', 0),
('PI0063', 'PR0063', 'assets/images/uploads/BB/BB0008/product/CRYBABY_4.jpg', 0),
('PI0064', 'PR0064', 'assets/images/uploads/BB/BB0008/product/CRYBABY_5.jpg', 0),
('PI0065', 'PR0065', 'assets/images/uploads/BB/BB0008/product/CRYBABY_6.jpg', 0),
-- BB0009 CRYBABY Vol.2
('PI0066', 'PR0066', 'assets/images/uploads/BB/BB0009/product/CRYBABY_1.jpg', 1),
('PI0067', 'PR0067', 'assets/images/uploads/BB/BB0009/product/CRYBABY_2.jpg', 0),
('PI0068', 'PR0068', 'assets/images/uploads/BB/BB0009/product/CRYBABY_3.jpg', 0),
('PI0069', 'PR0069', 'assets/images/uploads/BB/BB0009/product/CRYBABY_4.jpg', 0),
('PI0070', 'PR0070', 'assets/images/uploads/BB/BB0009/product/CRYBABY_5.jpg', 0),
('PI0071', 'PR0071', 'assets/images/uploads/BB/BB0009/product/CRYBABY_6.jpg', 0),
('PI0072', 'PR0072', 'assets/images/uploads/BB/BB0009/product/CRYBABY_7.jpg', 0),
-- BB0010 DIMOO Vol.1
('PI0073', 'PR0073', 'assets/images/uploads/BB/BB0010/product/DIMOO_1.jpg', 1),
('PI0074', 'PR0074', 'assets/images/uploads/BB/BB0010/product/DIMOO_2.jpg', 0),
('PI0075', 'PR0075', 'assets/images/uploads/BB/BB0010/product/DIMOO_3.jpg', 0),
('PI0076', 'PR0076', 'assets/images/uploads/BB/BB0010/product/DIMOO_4.jpg', 0),
('PI0077', 'PR0077', 'assets/images/uploads/BB/BB0010/product/DIMOO_5.jpg', 0),
('PI0078', 'PR0078', 'assets/images/uploads/BB/BB0010/product/DIMOO_6.jpg', 0),
('PI0079', 'PR0079', 'assets/images/uploads/BB/BB0010/product/DIMOO_7.jpg', 0),
-- BB0011 DIMOO Vol.2
('PI0080', 'PR0080', 'assets/images/uploads/BB/BB0011/product/DIMOO_1.jpg', 1),
('PI0081', 'PR0081', 'assets/images/uploads/BB/BB0011/product/DIMOO_2.jpg', 0),
('PI0082', 'PR0082', 'assets/images/uploads/BB/BB0011/product/DIMOO_3.jpg', 0),
('PI0083', 'PR0083', 'assets/images/uploads/BB/BB0011/product/DIMOO_4.jpg', 0),
('PI0084', 'PR0084', 'assets/images/uploads/BB/BB0011/product/DIMOO_5.jpg', 0),
('PI0085', 'PR0085', 'assets/images/uploads/BB/BB0011/product/DIMOO_6.jpg', 0),
('PI0086', 'PR0086', 'assets/images/uploads/BB/BB0011/product/DIMOO_7.jpg', 0),
-- BB0012 DISNEY Collection
('PI0087', 'PR0087', 'assets/images/uploads/BB/BB0012/product/DISNEY_1.jpg', 1),
('PI0088', 'PR0088', 'assets/images/uploads/BB/BB0012/product/DISNEY_2.jpg', 0),
('PI0089', 'PR0089', 'assets/images/uploads/BB/BB0012/product/DISNEY_3.jpg', 0),
('PI0090', 'PR0090', 'assets/images/uploads/BB/BB0012/product/DISNEY_4.jpg', 0),
('PI0091', 'PR0091', 'assets/images/uploads/BB/BB0012/product/DISNEY_5.jpg', 0),
('PI0092', 'PR0092', 'assets/images/uploads/BB/BB0012/product/DISNEY_6.jpg', 0),
('PI0093', 'PR0093', 'assets/images/uploads/BB/BB0012/product/DISNEY_7.jpg', 0),
('PI0094', 'PR0094', 'assets/images/uploads/BB/BB0012/product/DISNEY_8.jpg', 0),
('PI0095', 'PR0095', 'assets/images/uploads/BB/BB0012/product/DISNEY_9.jpg', 0),
('PI0096', 'PR0096', 'assets/images/uploads/BB/BB0012/product/DISNEY_10.jpg', 0),
('PI0097', 'PR0097', 'assets/images/uploads/BB/BB0012/product/DISNEY_11.jpg', 0),
('PI0098', 'PR0098', 'assets/images/uploads/BB/BB0012/product/DISNEY_12.jpg', 0),
('PI0099', 'PR0099', 'assets/images/uploads/BB/BB0012/product/DISNEY_13.jpg', 0),
-- BB0013 Spy x Family Collection
('PI0100', 'PR0100', 'assets/images/uploads/BB/BB0013/product/Spy x Family_1.jpg', 1),
('PI0101', 'PR0101', 'assets/images/uploads/BB/BB0013/product/Spy x Family_2.jpg', 0),
('PI0102', 'PR0102', 'assets/images/uploads/BB/BB0013/product/Spy x Family_3.jpg', 0),
('PI0103', 'PR0103', 'assets/images/uploads/BB/BB0013/product/Spy x Family_4.jpg', 0),
('PI0104', 'PR0104', 'assets/images/uploads/BB/BB0013/product/Spy x Family_5.jpg', 0);

INSERT INTO orders (order_id,user_id,sub_total_amount,tax_amount,total_amount,status,shipping_address,shipping_id) VALUES
('OR0001', 'UD0001', 59.99, 3.60, 63.59, 'completed', 'Kuala Lumpur, Malaysia','UA0001'),
('OR0002', 'UD0002', 119.98, 7.20, 127.18, 'shipped', 'Petaling Jaya, Malaysia','UA0001'),
('OR0003', 'UD0001', 179.97, 10.80, 190.77, 'pending', 'Shah Alam, Malaysia','UA0001'),
('OR0004', 'UD0002', 59.99, 3.60, 63.59, 'completed', 'Subang Jaya, Malaysia','UA0001'),
('OR0005', 'UD0001', 239.96, 14.40, 254.36, 'shipped', 'Cyberjaya, Malaysia','UA0001'),
('OR0006', 'UD0002', 119.98, 7.20, 127.18, 'cancelled', 'Kajang, Malaysia','UA0001'),
('OR0007', 'UD0001', 59.99, 3.60, 63.59, 'completed', 'Puchong, Malaysia','UA0001'),
('OR0008', 'UD0002', 179.97, 10.80, 190.77, 'completed', 'Ampang, Malaysia','UA0001'),
('OR0009', 'UD0001', 119.98, 7.20, 127.18, 'shipped', 'Cheras, Malaysia','UA0001'),
('OR0010', 'UD0002', 59.99, 3.60, 63.59, 'completed', 'Setapak, Malaysia','UA0001'),
('OR0011','UD0003', 94.98,  5.70, 100.68,'completed','Shah Alam, Malaysia','UA0001'),
('OR0012','UD0004', 64.99,  3.90,  68.89,'completed','Subang Jaya, Malaysia','UA0001'),
('OR0013','UD0005', 93.98,  5.64,  99.62,'completed','Cyberjaya, Malaysia','UA0001'),
('OR0014','UD0006', 99.98,  6.00, 105.98,'completed','Kajang, Malaysia','UA0001'),
('OR0015','UD0007',154.97,  9.30, 164.27,'completed','Puchong, Malaysia','UA0001'),
('OR0016','UD0008', 97.98,  5.88, 103.86,'completed','Ampang, Malaysia','UA0001'),
('OR0017','UD0009',109.98,  6.60, 116.58,'completed','Cheras, Malaysia','UA0001'),
('OR0018','UD0010',154.97,  9.30, 164.27,'completed','Setapak, Malaysia','UA0001'),
('OR0019','UD0011',102.98,  6.18, 109.16,'completed','Seri Kembangan, Malaysia','UA0001'),
('OR0020','UD0012',134.97,  8.10, 143.07,'completed','Bangi, Malaysia','UA0001'),
('OR0021','UD0013',146.97,  8.82, 155.79,'completed','Kepong, Malaysia','UA0001'),
('OR0022','UD0014',109.98,  6.60, 116.58,'completed','Gombak, Malaysia','UA0001'),
('OR0023','UD0015',158.97,  9.54, 168.51,'completed','Putrajaya, Malaysia','UA0001'),
('OR0024','UD0016',164.97,  9.90, 174.87,'completed','Sungai Buloh, Malaysia','UA0001'),
('OR0025','UD0017', 91.98,  5.52,  97.50,'completed','Rawang, Malaysia','UA0001');

INSERT INTO order_items (order_item_id,order_id,blindbox_id,quantity,price,subtotal,tax_amount) VALUES
('OI0001', 'OR0001', 'BB0001', 1, 59.99, 59.99, 3.60),
('OI0002', 'OR0002', 'BB0001', 2, 59.99, 119.98, 7.20),
('OI0003', 'OR0003', 'BB0001', 3, 59.99, 179.97, 10.80),
('OI0004', 'OR0004', 'BB0001', 1, 59.99, 59.99, 3.60),
('OI0005', 'OR0005', 'BB0001', 4, 59.99, 239.96, 14.40),
('OI0006', 'OR0006', 'BB0001', 2, 59.99, 119.98, 7.20),
('OI0007', 'OR0007', 'BB0001', 1, 59.99, 59.99, 3.60),
('OI0008', 'OR0008', 'BB0001', 3, 59.99, 179.97, 10.80),
('OI0009', 'OR0009', 'BB0001', 2, 59.99, 119.98, 7.20),
('OI0010', 'OR0010', 'BB0001', 1, 59.99, 59.99, 3.60),
('OI0011','OR0011','BB0002',1,49.99, 49.99, 3.00),
('OI0012','OR0011','BB0005',1,44.99, 44.99, 2.70),
('OI0013','OR0012','BB0012',1,64.99, 64.99, 3.90),
('OI0014','OR0013','BB0008',2,46.99, 93.98, 5.64),
('OI0015','OR0014','BB0013',1,54.99, 54.99, 3.30),
('OI0016','OR0014','BB0006',1,44.99, 44.99, 2.70),
('OI0017','OR0015','BB0003',1,54.99, 54.99, 3.30),
('OI0018','OR0015','BB0010',2,49.99, 99.98, 6.00),
('OI0019','OR0016','BB0009',2,48.99, 97.98, 5.88),
('OI0020','OR0017','BB0004',1,59.99, 59.99, 3.60),
('OI0021','OR0017','BB0002',1,49.99, 49.99, 3.00),
('OI0022','OR0018','BB0012',1,64.99, 64.99, 3.90),
('OI0023','OR0018','BB0005',2,44.99, 89.98, 5.40),
('OI0024','OR0019','BB0011',1,49.99, 49.99, 3.00),
('OI0025','OR0019','BB0007',1,52.99, 52.99, 3.18),
('OI0026','OR0020','BB0006',3,44.99,134.97, 8.10),
('OI0027','OR0021','BB0008',1,46.99, 46.99, 2.82),
('OI0028','OR0021','BB0010',1,49.99, 49.99, 3.00),
('OI0029','OR0021','BB0002',1,49.99, 49.99, 3.00),
('OI0030','OR0022','BB0013',2,54.99,109.98, 6.60),
('OI0031','OR0023','BB0003',2,54.99,109.98, 6.60),
('OI0032','OR0023','BB0009',1,48.99, 48.99, 2.94),
('OI0033','OR0024','BB0012',1,64.99, 64.99, 3.90),
('OI0034','OR0024','BB0011',2,49.99, 99.98, 6.00),
('OI0035','OR0025','BB0005',1,44.99, 44.99, 2.70),
('OI0036','OR0025','BB0008',1,46.99, 46.99, 2.82);

INSERT INTO payments (payment_id, order_id, payment_method, status, amount) VALUES
('PM0001','OR0001','card','completed',63.59),
('PM0002','OR0002','card','completed',127.18),
('PM0003','OR0003','card','completed',190.77),
('PM0004','OR0004','cash','completed',63.59),
('PM0005','OR0005','card','completed',254.36),
('PM0006','OR0006','cash','completed',127.18),
('PM0007','OR0007','card','completed',63.59),
('PM0008','OR0008','card','completed',190.77),
('PM0009','OR0009','cash','completed',127.18),
('PM0010','OR0010','card','completed',63.59),
('PM0011','OR0011','card','completed',100.68),
('PM0012','OR0012','cash','completed',68.89),
('PM0013','OR0013','card','completed',99.62),
('PM0014','OR0014','cash','completed',105.98),
('PM0015','OR0015','card','completed',164.27),
('PM0016','OR0016','cash','completed',103.86),
('PM0017','OR0017','card','completed',116.58),
('PM0018','OR0018','card','completed',164.27),
('PM0019','OR0019','cash','completed',109.16),
('PM0020','OR0020','cash','completed',143.07),
('PM0021','OR0021','card','completed',155.79),
('PM0022','OR0022','card','completed',116.58),
('PM0023','OR0023','cash','completed',168.51),
('PM0024','OR0024','card','completed',174.87),
('PM0025','OR0025','cash','completed',97.50);

INSERT INTO blindbox_reviews (review_id, order_item_id, rating, comment)
VALUES
('BR0001','OI0011',5,'Fast delivery and the figure is super cute!'),
('BR0002','OI0013',4,'Nice quality, packaging was good.'),
('BR0003','OI0017',5,'Love this series, will buy again.'),
('BR0004','OI0020',4,'Good value, item looks great.'),
('BR0005','OI0030',5,'Very satisfied, got a rare variant!');