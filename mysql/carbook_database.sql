
CREATE DATABASE IF NOT EXISTS carbook_database
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE carbook_database;


CREATE TABLE owners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NOT NULL UNIQUE,
    balance         DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    CONSTRAINT chk_owner_balance CHECK (balance >= 0)
) ENGINE=InnoDB;


CREATE TABLE tenants (
    driving_license VARCHAR(50) PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20) NOT NULL UNIQUE,
    damages_count    INT UNSIGNED NOT NULL DEFAULT 0  -- number of damages
) ENGINE=InnoDB;


CREATE TABLE cars (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id            INT UNSIGNED NOT NULL,
    make                VARCHAR(80) NOT NULL,
    model               VARCHAR(80) NOT NULL,
    model_year          SMALLINT UNSIGNED NOT NULL,
    plate_number        VARCHAR(30) NOT NULL UNIQUE,  -- رقم العربية أو نمر العربية
    color               VARCHAR(40) NOT NULL,
    daily_rent          DECIMAL(12,2) NOT NULL,
    status              ENUM('available','rented' , 'unavailable') NOT NULL DEFAULT 'available', 
    `image` varchar(255) NOT NULL,

    CONSTRAINT chk_car_daily_rent CHECK (daily_rent > 0),
    CONSTRAINT fk_cars_owner FOREIGN KEY (owner_id)
        REFERENCES owners(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


CREATE TABLE bookings (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_license          VARCHAR(50) NOT NULL,
    car_id                  INT UNSIGNED NOT NULL,
    pickup_datetime         DATETIME NOT NULL,
    return_datetime         DATETIME NOT NULL,
    daily_rent              DECIMAL(12,2) NOT NULL,
    total_price             DECIMAL(12,2) NOT NULL,
    booking_status          ENUM('pending','completed','approved' ,'cancelled') NOT NULL DEFAULT 'pending',
 
    CONSTRAINT chk_booking_dates CHECK (return_datetime > pickup_datetime),
    CONSTRAINT chk_booking_amounts CHECK (daily_rent > 0 AND total_price >= 0),
    CONSTRAINT fk_bookings_tenant FOREIGN KEY (tenant_license)
        REFERENCES tenants(driving_license)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_car FOREIGN KEY (car_id)
        REFERENCES cars(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_bookings_tenant (tenant_license),
    INDEX idx_bookings_car_dates (car_id, pickup_datetime, return_datetime)
) ENGINE=InnoDB;


CREATE TABLE payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id          INT UNSIGNED NOT NULL UNIQUE,
    amount              DECIMAL(12,2) NOT NULL,
    payment_status      ENUM('pending','paid') NOT NULL DEFAULT 'pending',
 
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

