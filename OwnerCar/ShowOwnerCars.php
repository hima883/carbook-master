<?php

require_once 'config/db.php';

$stmt = "select * from cars where owner_id = ?";

$conn->prepare($stmt);
$conn->execute_query($stmt, /* [$_GET['owner_id']] */);
