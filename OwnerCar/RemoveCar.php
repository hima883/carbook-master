<?php

require_once 'config/db.php';

$stmt = "delete from cars where car_id = ?" ;
$conn->prepare($stmt); 
$conn->execute_query($stmt , /* [$_GET['car_id']] */) ; 

