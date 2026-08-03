-- ============================================================================
-- Car Rental Database - Revised Schema (MySQL 8+)
-- Execute this file in a NEW/EMPTY database, or back up existing data first.
-- All requested edits are labelled with: HINT
-- ============================================================================

CREATE DATABASE IF NOT EXISTS carbook_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE carbook_db;

-- HINT: Drop children first so the script can be run again safely.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS damages;
DROP TABLE IF EXISTS contracts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS cars;
DROP TABLE IF EXISTS tenants;
DROP TABLE IF EXISTS owners;
-- HINT: The old users table is deliberately removed; owners and tenants are now separate.
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- HINT 1 + 3: Replaces the owner rows formerly held in users.
-- balance stores cash rental money credited to this owner's account on the platform.
CREATE TABLE owners (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20),
    balance         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_owner_balance CHECK (balance >= 0)
) ENGINE=InnoDB;

-- HINT 1 + 4: Replaces tenant rows formerly held in users.
-- driving_license is the primary key, so a tenant cannot rent without a license.
CREATE TABLE tenants (
    driving_license VARCHAR(50) PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20),
    date_of_birth   DATE NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- HINT 2: Use the view at the end of this file to show a tenant's damage count
-- before accepting a booking. Do not store a duplicate damages_count column.

-- HINT 1: owner_id now points to owners(id), not users(id).
CREATE TABLE cars (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id            INT UNSIGNED NOT NULL,
    make                VARCHAR(80) NOT NULL,
    model               VARCHAR(80) NOT NULL,
    model_year          SMALLINT UNSIGNED NULL,
    plate_number        VARCHAR(30) NOT NULL UNIQUE,
    color               VARCHAR(40) NULL,
    daily_rent          DECIMAL(12,2) NOT NULL,
    status              ENUM('available','booked','rented','maintenance','inactive') NOT NULL DEFAULT 'available',
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_car_daily_rent CHECK (daily_rent > 0),
    CONSTRAINT fk_cars_owner FOREIGN KEY (owner_id)
        REFERENCES owners(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- HINT 1 + 5: user_id is replaced with tenant_license and points to the license PK.
-- actual_return_datetime and late_fee record delayed return and its calculated charge.
CREATE TABLE bookings (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_license          VARCHAR(50) NOT NULL,
    car_id                  INT UNSIGNED NOT NULL,
    pickup_datetime         DATETIME NOT NULL,
    return_datetime         DATETIME NOT NULL,
    actual_return_datetime  DATETIME NULL,
    daily_rent              DECIMAL(12,2) NOT NULL,
    total_price             DECIMAL(12,2) NOT NULL,
    late_fee                DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    booking_status          ENUM('pending_payment','confirmed','active','completed','cancelled','overdue') NOT NULL DEFAULT 'pending_payment',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_booking_dates CHECK (return_datetime > pickup_datetime),
    CONSTRAINT chk_actual_return CHECK (actual_return_datetime IS NULL OR actual_return_datetime >= pickup_datetime),
    CONSTRAINT chk_booking_amounts CHECK (daily_rent > 0 AND total_price >= 0 AND late_fee >= 0),
    CONSTRAINT fk_bookings_tenant FOREIGN KEY (tenant_license)
        REFERENCES tenants(driving_license)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_car FOREIGN KEY (car_id)
        REFERENCES cars(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_bookings_tenant (tenant_license),
    INDEX idx_bookings_car_dates (car_id, pickup_datetime, return_datetime)
) ENGINE=InnoDB;

-- HINT 5: One contract per booking. It records the agreed daily rent and late penalty,
-- so later changes to car pricing cannot change an existing agreement.
CREATE TABLE contracts (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id               INT UNSIGNED NOT NULL UNIQUE,
    contract_number          VARCHAR(60) NOT NULL UNIQUE,
    issued_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pickup_datetime          DATETIME NOT NULL,
    return_datetime          DATETIME NOT NULL,
    daily_rent               DECIMAL(12,2) NOT NULL,
    late_penalty_per_day     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    terms                    TEXT NOT NULL,
    tenant_signed_at         DATETIME NULL,
    owner_signed_at          DATETIME NULL,
    created_at               TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_contract_dates CHECK (return_datetime > pickup_datetime),
    CONSTRAINT chk_contract_amounts CHECK (daily_rent > 0 AND late_penalty_per_day >= 0),
    CONSTRAINT fk_contracts_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- HINT 3: Cash only. No payment_method and no online transaction_id are needed.
-- A payment must be marked paid before the tenant receives the car.
CREATE TABLE payments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id          INT UNSIGNED NOT NULL UNIQUE,
    amount              DECIMAL(12,2) NOT NULL,
    payment_status      ENUM('pending','paid','refunded') NOT NULL DEFAULT 'pending',
    paid_at             DATETIME NULL,
    received_by_owner_id INT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    CONSTRAINT chk_paid_timestamp CHECK (
        (payment_status = 'paid' AND paid_at IS NOT NULL) OR payment_status <> 'paid'
    ),
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_payments_receiver FOREIGN KEY (received_by_owner_id)
        REFERENCES owners(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- HINT 2: Each row is one incident/damage. Count these records before approving a tenant.
CREATE TABLE damages (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id          INT UNSIGNED NOT NULL,
    tenant_license      VARCHAR(50) NOT NULL,
    car_id              INT UNSIGNED NOT NULL,
    owner_id            INT UNSIGNED NOT NULL,
    description         TEXT NOT NULL,
    repair_cost         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    damage_date         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status              ENUM('reported','confirmed','repaired','charged') NOT NULL DEFAULT 'reported',
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_damage_repair_cost CHECK (repair_cost >= 0),
    CONSTRAINT fk_damages_booking FOREIGN KEY (booking_id)
        REFERENCES bookings(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_damages_tenant FOREIGN KEY (tenant_license)
        REFERENCES tenants(driving_license)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_damages_car FOREIGN KEY (car_id)
        REFERENCES cars(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_damages_owner FOREIGN KEY (owner_id)
        REFERENCES owners(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_damages_tenant (tenant_license)
) ENGINE=InnoDB;

-- HINT 2: Query this view before an owner accepts a new booking.
CREATE OR REPLACE VIEW tenant_damage_history AS
SELECT
    t.driving_license,
    t.name AS tenant_name,
    COUNT(d.id) AS damages_count,
    COALESCE(SUM(d.repair_cost), 0.00) AS total_repair_cost
FROM tenants t
LEFT JOIN damages d ON d.tenant_license = t.driving_license
GROUP BY t.driving_license, t.name;

-- HINT 5: Calculate late fee when the car is returned.
-- Formula: each late day = daily rent + the late penalty agreed in the contract.
-- Example (replace 15 with the booking ID):
-- UPDATE bookings b
-- JOIN contracts c ON c.booking_id = b.id
-- SET b.actual_return_datetime = NOW(),
--     b.late_fee = GREATEST(0, DATEDIFF(NOW(), b.return_datetime))
--                  * (c.daily_rent + c.late_penalty_per_day),
--     b.booking_status = IF(NOW() > b.return_datetime, 'overdue', 'completed')
-- WHERE b.id = 15;

-- HINT 2: Example: view a tenant's prior damage history before renting.
-- SELECT * FROM tenant_damage_history WHERE driving_license = 'LICENSE-NUMBER';

-- HINT 3: After recording a cash payment, credit the related owner's balance in
-- your application transaction (only once per payment) and mark payment_status = 'paid'.
