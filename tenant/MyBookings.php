<?php

require_once '../mysql/db_connect.php';

session_start();

if(!isset($_SESSION['tenant_license'])){

    header("Location: auth/TenantLogin.php");
    exit();

}

// =======================================
// Tenant License
// =======================================

$tenant_license = $_SESSION['tenant_license'];


// =======================================
// Get Tenant Bookings
// =======================================


$get_bookings = "

SELECT


bookings.id AS booking_id,

bookings.pickup_datetime,

bookings.return_datetime,

bookings.booking_status,


cars.make,

cars.model,

cars.model_year,

cars.color,

cars.image,


owners.name AS owner_name,

owners.phone AS owner_phone,


payments.payment_status,

payments.amount


FROM bookings


INNER JOIN cars

ON bookings.car_id = cars.id


INNER JOIN owners

ON cars.owner_id = owners.id


LEFT JOIN payments

ON bookings.id = payments.booking_id



WHERE bookings.tenant_license = ?


ORDER BY bookings.id DESC


";




$result = $conn->execute_query($get_bookings,[

    $tenant_license

]);



?>



<!DOCTYPE html>

<html>

<head>

<title>My Bookings</title>


<style>


body{

    font-family: Arial, sans-serif;

    background:#f4f6f8;

    padding:30px;

}



.container{

    width:90%;

    margin:auto;

}



.booking-card{

    background:white;

    padding:25px;

    margin-bottom:25px;

    border-radius:12px;

    box-shadow:0 4px 10px rgba(0,0,0,0.1);

    display:flex;

    gap:25px;

    flex-wrap:wrap;

}



.booking-card img{

    width:250px;

    height:170px;

    object-fit:cover;

    border-radius:10px;

}



.details{

    flex:1;

}



.status{

    padding:8px 15px;

    border-radius:20px;

    display:inline-block;

    margin:5px 0;

}



.pending{

    background:#fff3cd;

    color:#856404;

}



.approved{

    background:#d4edda;

    color:#155724;

}



.cancelled{

    background:#f8d7da;

    color:#721c24;

}



.paid{

    background:#d4edda;

    color:#155724;

}



.unpaid{

    background:#fff3cd;

    color:#856404;

}



</style>


</head>



<body>


<div class="container">


<h1>My Bookings</h1>



<?php


if($result->num_rows == 0){

    echo "

    <h2>No bookings found.</h2>

    ";

}



while($booking = $result->fetch_assoc()){


?>



<div class="booking-card">



<?php

$image_path =  $booking['image'];

?>


<img src="<?php echo $image_path; ?>">





<div class="details">



<h2>

<?php echo $booking['make']; ?>

<?php echo $booking['model']; ?>

(<?php echo $booking['model_year']; ?>)

</h2>




<p>

<strong>Color:</strong>

<?php echo $booking['color']; ?>

</p>





<p>

<strong>Owner:</strong>

<?php echo $booking['owner_name']; ?>

</p>




<p>

<strong>Owner Phone:</strong>

<?php echo $booking['owner_phone']; ?>

</p>





<p>

<strong>Pickup:</strong>

<?php echo $booking['pickup_datetime']; ?>

</p>





<p>

<strong>Return:</strong>

<?php echo $booking['return_datetime']; ?>

</p>





<p>

<strong>Booking Status:</strong>

<span class="status <?php echo $booking['booking_status']; ?>">

<?php echo $booking['booking_status']; ?>

</span>

</p>






<p>

<strong>Payment Status:</strong>


<?php


if($booking['payment_status'] == null){


    echo "

    <span class='status unpaid'>

    Not Created Yet

    </span>

    ";


}

else{


    echo "

    <span class='status ".$booking['payment_status']."'>

    ".$booking['payment_status']."

    </span>

    ";


}



?>

</p>






<?php

if($booking['amount'] != null){

?>


<p>

<strong>Amount:</strong>

<?php echo $booking['amount']; ?>

EGP

</p>


<?php

}


?>





</div>



</div>



<?php

}



?>


</div>


</body>


</html>