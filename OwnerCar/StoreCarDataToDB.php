<?php 
// Code for storing car data in the database

require_once '../mysql/db_connect.php' ; 

session_start();

if(!isset($_SESSION['owner_id'])){

    header("Location: auth/OwnerLogin.php");
    exit();

}

$owner_id = $_SESSION['owner_id'];

//  بيانات الصورة من الفورم
$imageName = str_replace(" ", "_", $_FILES['car_image']['name']);
$imageTmp = $_FILES['car_image']['tmp_name'];
// $imageSize = $_FILES['car_image']['size'];
// $imageType = $_FILES['car_image']['type'];
// $imageError = $_FILES['car_image']['error'];

//  تحديد مكان التخزين للصورة على السيرفر Path -> URL
$target = "../images/CarOwner/" . basename($imageName);

//  نقل الصورة من tmp إلى فولدر السيرفر
if (move_uploaded_file($imageTmp, $target)) 
   {   
       // إدخال بيانات العربية ومسار الصورة فى قاعدة البيانات

       // check if the car already exists in the database for the same owner
       $stmt = "SELECT * FROM cars WHERE owner_id = ? AND plate_number = ?";
       $check = $conn->execute_query($stmt,[

    $owner_id,

    $_POST['car_plate_number']

]);

       if ($check->num_rows > 0)
          {
             echo "Car with this plate number already exists for your account.";
          } 
          else 
          {
             $query = 'INSERT INTO cars (owner_id , make, model, model_year, color, plate_number, daily_rent, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'; 
             
             $conn->execute_query($query,[

                 $owner_id,
             
                 $_POST['car_make'],
             
                 $_POST['car_model'],
             
                 $_POST['car_model_year'],
             
                 $_POST['car_color'],
             
                 $_POST['car_plate_number'],
             
                 $_POST['car_daily_rent'],
             
                 $target
             
             ]);
          }
   
       echo "تم رفع وتخزين بيانات العربية بنجاح";
       header("Location: ../OwnerCar/ShowOwnerCars.php") ; 
    }
 else 
    {
       echo "فشل فى رفع وتخزين بيانات العربية";
    }






