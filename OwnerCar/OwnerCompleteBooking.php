<?php

// date_default_timezone_set('Africa/Cairo');

// require_once "../mysql/db_connect.php";

// session_start() ; 

// $owner_id = $_SESSION['owner_id']; 

// if(!isset($_GET['booking_id'])){

//     die("Booking ID is missing.");

// }

// $booking_id = (int)$_GET['booking_id'];

// $get_booking = "

// SELECT

// bookings.id AS booking_id,
// bookings.booking_status,
// bookings.return_datetime,

// cars.id AS car_id,
// cars.owner_id,

// payments.payment_status

// FROM bookings

// INNER JOIN cars

// ON bookings.car_id = cars.id

// INNER JOIN payments

// ON bookings.id = payments.booking_id

// WHERE bookings.id = '$booking_id'

// LIMIT 1

// ";

// $booking = $conn->execute_query($get_booking);

// if($booking->num_rows == 0){

//     die("Booking not found.");

// }

// $row = $booking->fetch_assoc();

// if($row['owner_id'] != $owner_id){

//     die("Access denied.");

// }

// if($row['booking_status'] != "approved"){

//     die("This booking cannot be completed.");

// }

// if($row['payment_status'] != "paid"){

//     die("Payment has not been confirmed yet.");

// }

// if(strtotime(date("Y-m-d H:i:s")) < strtotime($row['return_datetime'])){

//     die("The return date has not arrived yet.");

// }


// $conn->begin_transaction();

// try{

//     $complete_booking = "

//     UPDATE bookings

//     SET booking_status = 'completed'

//     WHERE id = '$booking_id'

//     ";

//     $conn->execute_query($complete_booking);


//     $car_id = $row['car_id'];

//     $update_car = "

//     UPDATE cars

//     SET status = 'available'

//     WHERE id = '$car_id'

//     ";

//     $conn->execute_query($update_car);


//     $conn->commit();

// }

// catch(Exception $e){

//     $conn->rollback();

//     die("Failed to complete booking.");

// }


// header("Location: OwnerBookingRequests.php");

// exit();

?>
























