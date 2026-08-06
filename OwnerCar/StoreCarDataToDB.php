<?php

require_once '../mysql/db_connect.php';
require_once '../config/auth.php';

require_login('../login.php');


// ========================================
// Get Logged-in User
// ========================================

$user_id = $_SESSION['user_id'];


// ========================================
// Make Sure User Is An Owner
// ========================================

$check_owner = $conn->prepare("
    SELECT user_id
    FROM owners
    WHERE user_id = ?
");

$check_owner->bind_param("i", $user_id);
$check_owner->execute();

$owner_result = $check_owner->get_result();

if ($owner_result->num_rows === 0) {
    die("You must be an owner to add a car.");
}

$check_owner->close();

$owner_id = $user_id;


// ========================================
// Make Sure Request Is POST
// ========================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}


// ========================================
// Get Form Data
// ========================================

$brand        = trim($_POST['car_brand'] ?? '');
$model        = trim($_POST['car_model'] ?? '');
$plate_number = trim($_POST['car_plate_number'] ?? '');
$year         = (int)($_POST['car_year'] ?? 0);
$color        = trim($_POST['car_color'] ?? '');
$location     = trim($_POST['car_location'] ?? '');
$mileage      = (int)($_POST['car_mileage'] ?? 0);
$price        = (float)($_POST['car_price_per_day'] ?? 0);
$fuel_type    = $_POST['car_fuel_type'] ?? '';
$transmission = $_POST['car_transmission'] ?? '';
$seats        = (int)($_POST['car_seats'] ?? 0);
$description  = trim($_POST['car_description'] ?? '');


// ========================================
// Basic Validation
// ========================================

if (
    $brand === '' ||
    $model === '' ||
    $plate_number === '' ||
    $year <= 0 ||
    $color === '' ||
    $location === '' ||
    $mileage < 0 ||
    $price <= 0 ||
    $fuel_type === '' ||
    $transmission === '' ||
    $seats <= 0
) {
    die("Please fill in all required car information.");
}


// ========================================
// Validate Fuel Type
// ========================================

$allowed_fuel_types = [
    'petrol',
    'diesel',
    'electric'
];

if (!in_array($fuel_type, $allowed_fuel_types, true)) {
    die("Invalid fuel type.");
}


// ========================================
// Validate Transmission
// ========================================

$allowed_transmissions = [
    'automatic',
    'manual'
];

if (!in_array($transmission, $allowed_transmissions, true)) {
    die("Invalid transmission type.");
}


// ========================================
// Check Plate Number
// ========================================

$check_plate = "
    SELECT id
    FROM cars
    WHERE plate_number = ?
    LIMIT 1
";

$check = $conn->execute_query($check_plate, [
    $plate_number
]);

if ($check->num_rows > 0) {
    die("A car with this plate number already exists.");
}


// ========================================
// Check Image
// ========================================

if (
    !isset($_FILES['car_image']) ||
    $_FILES['car_image']['error'] !== UPLOAD_ERR_OK
) {
    die("Please upload a valid car image.");
}


// ========================================
// Prepare Image
// ========================================

$originalImageName = basename($_FILES['car_image']['name']);
$imageTmp = $_FILES['car_image']['tmp_name'];

$extension = strtolower(pathinfo(
    $originalImageName,
    PATHINFO_EXTENSION
));

$allowed_extensions = [
    'jpg',
    'jpeg',
    'png',
    'webp'
];

if (!in_array($extension, $allowed_extensions, true)) {
    die("Only JPG, JPEG, PNG and WEBP images are allowed.");
}


// Create unique image name

$imageName =
    time() .
    "_" .
    bin2hex(random_bytes(4)) .
    "." .
    $extension;


// ========================================
// Image Paths
// ========================================

// Physical path used to save/delete the file
$uploadDirectory = __DIR__ . '/../images/CarOwner/';

if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

$physicalPath = $uploadDirectory . $imageName;


// Path stored in database
$imagePath = "../images/CarOwner/" . $imageName;


// ========================================
// Upload Image
// ========================================

if (!move_uploaded_file($imageTmp, $physicalPath)) {
    die("Failed to upload car image.");
}


// ========================================
// Insert Car
// ========================================

try {

    $query = "
        INSERT INTO cars
        (
            owner_id,
            brand,
            model,
            plate_number,
            year,
            color,
            location,
            mileage,
            price_per_day,
            fuel_type,
            transmission,
            seats,
            image,
            description
        )

        VALUES
        (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";


    $conn->execute_query($query, [

        $owner_id,

        $brand,
        $model,
        $plate_number,
        $year,
        $color,
        $location,
        $mileage,
        $price,
        $fuel_type,
        $transmission,
        $seats,
        $imagePath,
        $description

    ]);

} catch (Exception $e) {

    // If DB insert fails, remove uploaded image
    if (file_exists($physicalPath)) {
        unlink($physicalPath);
    }

    die("Failed to add car: " . $e->getMessage());
}


// ========================================
// Redirect
// ========================================

header("Location: ShowOwnerCars.php");
exit;