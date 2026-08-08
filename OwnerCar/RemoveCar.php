<?php

require_once '../mysql/db_connect.php';

session_start();

if(!isset($_SESSION['owner_id'])){

    header("Location: auth/OwnerLogin.php");
    exit();

}

$owner_id = $_SESSION['owner_id'];

// =======================================
// التأكد من وجود رقم العربية
// =======================================

if (!isset($_GET['car_id'])) {

    die("Car ID Not Found");

}


$car_id = $_GET['car_id'];



// =======================================
// جلب بيانات العربية
// =======================================

$query = "SELECT *
          FROM cars
          WHERE id = ?
          AND owner_id = ?";

$car = $conn->execute_query($query,[

    $car_id,

    $owner_id

]);


if ($car->num_rows == 0) {

    die("Car Not Found.");

}


$carData = $car->fetch_assoc();



// // =======================================
// // التأكد إن العربية ليست محجوزة
// // =======================================

// $bookingQuery = "SELECT *
//                  FROM bookings
//                  WHERE car_id = ?";


// $booking = $conn->execute_query($bookingQuery, [

//     $car_id

// ]);


// if ($booking->num_rows > 0) {

//     echo "

//     <script>

//         alert('You cannot delete this car because it has bookings.');

//         window.location='ShowOwnerCars.php';

//     </script>

//     ";

//     exit();

// }


// =======================================
// Check Active Bookings
// =======================================

$bookingQuery = "

SELECT id

FROM bookings

WHERE car_id = ?

AND booking_status IN ('pending','approved')

";


$booking = $conn->execute_query($bookingQuery, [

    $car_id

]);


// =======================================
// Cannot Delete Active Booking Car
// =======================================

if($booking->num_rows > 0){

    echo "

    <script>

    alert('You cannot delete this car because it has a pending or approved booking.');

    window.location='ShowOwnerCars.php';

    </script>

    ";

    exit();

}


// =======================================
// Delete Old Payments + Bookings
// =======================================

$conn->begin_transaction();

try {


    // =======================================
    // Delete Payments
    // =======================================

    $deletePayments = "

    DELETE payments

    FROM payments

    INNER JOIN bookings

    ON payments.booking_id = bookings.id

    WHERE bookings.car_id = ?

    AND bookings.booking_status IN ('completed','cancelled')

    ";


    $conn->execute_query($deletePayments,[

        $car_id

    ]);


    // =======================================
    // Delete Completed / Cancelled Bookings
    // =======================================

    $deleteBookings = "

    DELETE FROM bookings

    WHERE car_id = ?

    AND booking_status IN ('completed','cancelled')

    ";


    $conn->execute_query($deleteBookings,[

        $car_id

    ]);


    // =======================================
    // Delete Car
    // =======================================

    $deleteCar = "

    DELETE FROM cars

    WHERE id = ?

    AND owner_id = ?

    ";


    $conn->execute_query($deleteCar,[

        $car_id,

        $owner_id

    ]);


    $conn->commit();


}

catch(Exception $e){

    $conn->rollback();

    die("Car deletion failed.");

}


// =======================================
// حذف صورة العربية من السيرفر
// =======================================

if (

    !empty($carData['image'])

    &&

    file_exists($carData['image'])

)

{

    unlink($carData['image']);

}


// =======================================
// حذف العربية من قاعدة البيانات
// =======================================

$deleteQuery = "DELETE FROM cars
                WHERE id = ?
                AND owner_id = ?";


$conn->execute_query($deleteQuery,[

    $car_id,

    $owner_id

]);




// =======================================
// رسالة نجاح ثم الرجوع لصفحة السيارات
// =======================================

echo "

<script>

alert('Car deleted successfully.');

window.location='ShowOwnerCars.php';

</script>

";

exit();

?>
