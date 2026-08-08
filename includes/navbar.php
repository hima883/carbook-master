<?php

if(session_status() == PHP_SESSION_NONE){

    session_start();
}


$current_page = basename($_SERVER['PHP_SELF']);

?>
<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
  <div class="container">
    <a class="navbar-brand" href="index.php">Car<span>Book</span></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="oi oi-menu"></span> Menu
    </button>

    <div class="collapse navbar-collapse" id="ftco-nav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>"><a href="index.php" class="nav-link">Home</a></li>
        <li class="nav-item <?php echo ($current_page === 'car.php') ? 'active' : ''; ?>"><a href="tenant/ShowCars.php" class="nav-link">Cars</a></li>
        <li class="nav-item <?php echo ($current_page === 'tenant/TenantProfile.php') ? 'active' : ''; ?>"><a href="tenant/TenantProfile.php" class="nav-link">Profile</a></li>
        <li class="nav-item <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>"><a href="about.php" class="nav-link">About</a></li>
        <li class="nav-item <?php echo ($current_page === 'services.php') ? 'active' : ''; ?>"><a href="services.php" class="nav-link">Services</a></li>
        <li class="nav-item <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>"><a href="contact.php" class="nav-link">Contact</a></li>

      </ul>
    </div>
  </div>
</nav>
