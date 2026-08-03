<?php
require_once __DIR__ . '/../config/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
$logged_user = get_logged_in_user();
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
        <li class="nav-item <?php echo ($current_page === 'about.php') ? 'active' : ''; ?>"><a href="about.php" class="nav-link">About</a></li>
        <li class="nav-item <?php echo ($current_page === 'services.php') ? 'active' : ''; ?>"><a href="services.php" class="nav-link">Services</a></li>
        <li class="nav-item <?php echo ($current_page === 'pricing.php') ? 'active' : ''; ?>"><a href="pricing.php" class="nav-link">Pricing</a></li>
        <li class="nav-item <?php echo ($current_page === 'car.php') ? 'active' : ''; ?>"><a href="car.php" class="nav-link">Cars</a></li>
        <li class="nav-item <?php echo ($current_page === 'blog.php') ? 'active' : ''; ?>"><a href="blog.php" class="nav-link">Blog</a></li>
        <li class="nav-item <?php echo ($current_page === 'contact.php') ? 'active' : ''; ?>"><a href="contact.php" class="nav-link">Contact</a></li>

        <?php if ($logged_user): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle font-weight-bold text-warning" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="ion-ios-person"></i> <?php echo htmlspecialchars($logged_user['name']); ?> (<?php echo ucfirst($logged_user['role']); ?>)
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <a class="dropdown-item" href="logout.php"><i class="ion-ios-log-out"></i> Logout / تسجيل الخروج</a>
            </div>
          </li>
        <?php else: ?>
          <li class="nav-item <?php echo ($current_page === 'login.php') ? 'active' : ''; ?>"><a href="login.php" class="nav-link">Login</a></li>
          <li class="nav-item <?php echo ($current_page === 'register.php' || $current_page === 'regester.php') ? 'active' : ''; ?>"><a href="register.php" class="nav-link">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
