<?php
session_start();
require_once 'db.php';

// --- Require login ---
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$errors  = [];
$success = '';

// --- Fetch current user ---
$stmt = $pdo->prepare('SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// --- Fetch tenant data ---
$tenant = null;

if ($user['role'] === 'renter' || $user['role'] === 'both') {
    $stmt = $pdo->prepare("
        SELECT driving_license, damages_count
        FROM tenants
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
}


// --- Fetch owner data ---
$owner = null;

if ($user['role'] === 'owner' || $user['role'] === 'both') {
    $stmt = $pdo->prepare("
        SELECT balance
        FROM owners
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $owner = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// --- Handle profile update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    // Make sure the email isn't already used by another account
    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $errors[] = 'That email is already in use by another account.';
        }
    }

    if (empty($errors)) {
        $update = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
        $update->execute([$name, $email, $phone, $user_id]);

        $success = 'Your profile has been updated.';

        // Refresh local copy of user data
        $user['name']  = $name;
        $user['email'] = $email;
        $user['phone'] = $phone;
    }
}

// --- Handle driving license update ---
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_license'])
    && ($user['role'] === 'renter' || $user['role'] === 'both')
) {

    $driving_license = trim($_POST['driving_license'] ?? '');

    if ($driving_license === '') {

        $errors[] = 'Driving license number is required.';

    } else {

        // Check if license is already used by another tenant
        $check = $pdo->prepare("
            SELECT user_id
            FROM tenants
            WHERE driving_license = ?
            AND user_id != ?
        ");

        $check->execute([
            $driving_license,
            $user_id
        ]);

        if ($check->fetch()) {

            $errors[] = 'This driving license is already registered.';

        } else {

            $update = $pdo->prepare("
                UPDATE tenants
                SET driving_license = ?
                WHERE user_id = ?
            ");

            $update->execute([
                $driving_license,
                $user_id
            ]);

            $tenant['driving_license'] = $driving_license;

            $success = 'Driving license updated successfully.';
        }
    }
}

// --- Handle password change ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password      = $_POST['new_password'] ?? '';
    $confirm_password  = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current_password, $hash)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match.';
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update   = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $update->execute([$new_hash, $user_id]);
        $success = 'Your password has been changed.';
    }
}

// --- Fetch this user's bookings (with car details) ---
$bookings_stmt = $pdo->prepare(
    'SELECT b.id, b.pickup_datetime, b.return_datetime, b.total_price, b.booking_status,
            c.brand, c.model
     FROM bookings b
     JOIN cars c ON c.id = b.car_id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC
     LIMIT 10'
);
$bookings_stmt->execute([$user_id]);
$bookings = $bookings_stmt->fetchAll();

$role_labels = [
    'renter' => 'Renter',
    'owner'  => 'Car Owner',
    'both'   => 'Renter & Owner',
];

$status_badge = [
    'pending'   => 'badge-warning',
    'completed' => 'badge-success',
    'cancelled' => 'badge-danger',
];
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>My Profile - Carbook</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,500,600,700,800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">

    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">

    <link rel="stylesheet" href="css/aos.css">

    <link rel="stylesheet" href="css/ionicons.min.css">

    <link rel="stylesheet" href="css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">

    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/icomoon.css">
    <link rel="stylesheet" href="css/style.css">

    <style>
      .profile-avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: #F96D00;
        color: #fff;
        font-size: 36px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
      }
      .profile-card {
        background: #fff;
        border-radius: 6px;
        box-shadow: 0 3px 20px rgba(0,0,0,.07);
        padding: 30px;
        margin-bottom: 30px;
      }
      .profile-meta-list li {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
      }
      .profile-meta-list li:last-child { border-bottom: none; }
      .profile-meta-list .label { color: #999; }
      .table-bookings th { border-top: none; }
      .badge-warning { background:#F9A825; color:#fff; }
      .badge-success { background:#28a745; color:#fff; }
      .badge-danger { background:#dc3545; color:#fff; }
    </style>
  </head>
  <body>

	  <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
	    <div class="container">
	      <a class="navbar-brand" href="index.php">Car<span>Book</span></a>
	      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
	        <span class="oi oi-menu"></span> Menu
	      </button>

	      <div class="collapse navbar-collapse" id="ftco-nav">
	        <ul class="navbar-nav ml-auto">
	          <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
	          <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
	          <li class="nav-item"><a href="services.php" class="nav-link">Services</a></li>
	          <li class="nav-item"><a href="pricing.php" class="nav-link">Pricing</a></li>
	          <li class="nav-item"><a href="car.php" class="nav-link">Cars</a></li>
	          <li class="nav-item"><a href="blog.php" class="nav-link">Blog</a></li>
	          <li class="nav-item active"><a href="profile.php" class="nav-link">My Profile</a></li>
	          <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
	        </ul>
	      </div>
	    </div>
	  </nav>
    <!-- END nav -->

    <div class="hero-wrap ftco-degree-bg" style="background-image: url('images/bg_1.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text align-items-end">
          <div class="col-md-9 ftco-animate pb-5">
            <p class="breadcrumbs"><span class="mr-2"><a href="index.php">Home <i class="ion-ios-arrow-forward"></i></a></span> <span>My Profile <i class="ion-ios-arrow-forward"></i></span></p>
            <h1 class="mb-3 bread">My Profile</h1>
          </div>
        </div>
      </div>
    </div>
    <!-- END hero -->

    <section class="ftco-section bg-light">
      <div class="container">

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="row">
          <!-- Left: Avatar + summary -->
          <div class="col-md-4">
            <div class="profile-card text-center">
              <div class="profile-avatar">
                <?= htmlspecialchars(strtoupper(substr($user['name'], 0, 1))) ?>
              </div>
              <h3 class="mb-0"><?= htmlspecialchars($user['name']) ?></h3>
              <p class="text-muted mb-3"><?= htmlspecialchars($role_labels[$user['role']] ?? ucfirst($user['role'])) ?></p>

              <ul class="list-unstyled profile-meta-list text-left">
                <li><span class="label">Email</span> <span><?= htmlspecialchars($user['email']) ?></span></li>
                <li><span class="label">Phone</span> <span><?= htmlspecialchars($user['phone'] ?: '—') ?></span></li>
                <li><span class="label">Member since</span> <span><?= htmlspecialchars(date('M d, Y', strtotime($user['created_at']))) ?></span></li>
              </ul>
            </div>
          </div>

          <?php if ($user['role'] === 'owner' || $user['role'] === 'both'): ?>

              <li>
            <span class="label">Balance</span>

            <span>
              $<?= number_format((float)($owner['balance'] ?? 0), 2) ?> EGP
            </span>
            </li>

          <?php endif; ?>

          <!-- Right: Edit forms + bookings -->
          <div class="col-md-8">

            <div class="profile-card">
              <h4 class="mb-4">Edit Profile Information</h4>
              <form method="post" action="profile.php">
                <div class="form-group">
                  <label for="name">Full Name</label>
                  <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                <div class="form-group">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                  <label for="phone">Phone Number</label>
                  <input type="text" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary py-2 px-4">Save Changes</button>
              </form>
            </div>

            <?php if ($user['role'] === 'renter' || $user['role'] === 'both'): ?>

                  <div class="profile-card">

                  <h4 class="mb-4">
                    Driving License
                  </h4>

                  <form method="post" action="profile.php">

                  <div class="form-group">

                  <label for="driving_license">
                    Driving License Number
                  </label>

                  <input
                  type="text"
                  id="driving_license"
                  name="driving_license"
                  class="form-control"
                  value="<?= htmlspecialchars($tenant['driving_license'] ?? '') ?>"
                  placeholder="Enter your driving license number"
                  required
                  >

                  </div>

                  <button
                  type="submit"
                  name="update_license"
                  class="btn btn-primary"
                  style="
                  padding: 12px 25px !important;
                  min-width: 180px;
                  height: auto !important;
                  color: white !important;
                  font-size: 15px !important;
                  font-weight: 600;
                  line-height: 1.5 !important;
                  display: inline-block !important;
                  "
                >
                  Save Driving License
                </button>

                </form>

              </div>

            <?php endif; ?>

            <div class="profile-card">
              <h4 class="mb-4">Change Password</h4>
              <form method="post" action="profile.php">
                <div class="form-group">
                  <label for="current_password">Current Password</label>
                  <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                  <label for="new_password">New Password</label>
                  <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required>
                </div>
                <div class="form-group">
                  <label for="confirm_password">Confirm New Password</label>
                  <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-secondary py-2 px-4">Change Password</button>
              </form>
            </div>
            <a href="logout.php" class="btn btn-danger btn-block mt-4"><span class="icon-sign-out"></span> Logout</a>

            <div class="profile-card">
              <h4 class="mb-4">Recent Bookings</h4>
              <?php if (empty($bookings)): ?>
                <p class="text-muted mb-0">You haven't made any bookings yet.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-bookings">
                    <thead>
                      <tr>
                        <th>Car</th>
                        <th>Pick-up</th>
                        <th>Return</th>
                        <th>Total</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($bookings as $booking): ?>
                        <tr>
                          <td><?= htmlspecialchars(trim($booking['brand'] . ' ' . $booking['model'])) ?></td>
                          <td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($booking['pickup_datetime']))) ?></td>
                          <td><?= htmlspecialchars(date('M d, Y g:i A', strtotime($booking['return_datetime']))) ?></td>
                          <td>$<?= htmlspecialchars(number_format($booking['total_price'], 2)) ?></td>
                          <td>
                            <span class="badge <?= $status_badge[$booking['booking_status']] ?? 'badge-secondary' ?>">
                              <?= htmlspecialchars(ucfirst($booking['booking_status'])) ?>
                            </span>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>
    </section>

    <footer class="ftco-footer ftco-bg-dark ftco-section">
      <div class="container">
        <div class="row mb-5">
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2"><a href="#" class="logo">Car<span>book</span></a></h2>
              <p>Far far away, behind the word mountains, far from the countries Vokalia and Consonantia, there live the blind texts.</p>
              <ul class="ftco-footer-social list-unstyled float-md-left float-lft mt-5">
                <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
                <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
                <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4 ml-md-5">
              <h2 class="ftco-heading-2">Information</h2>
              <ul class="list-unstyled">
                <li><a href="#" class="py-2 d-block">About</a></li>
                <li><a href="#" class="py-2 d-block">Services</a></li>
                <li><a href="#" class="py-2 d-block">Term and Conditions</a></li>
                <li><a href="#" class="py-2 d-block">Best Price Guarantee</a></li>
                <li><a href="#" class="py-2 d-block">Privacy &amp; Cookies Policy</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
             <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Customer Support</h2>
              <ul class="list-unstyled">
                <li><a href="#" class="py-2 d-block">FAQ</a></li>
                <li><a href="#" class="py-2 d-block">Payment Option</a></li>
                <li><a href="#" class="py-2 d-block">Booking Tips</a></li>
                <li><a href="#" class="py-2 d-block">How it works</a></li>
                <li><a href="#" class="py-2 d-block">Contact Us</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
            	<h2 class="ftco-heading-2">Have a Questions?</h2>
            	<div class="block-23 mb-3">
	              <ul>
	                <li><span class="icon icon-map-marker"></span><span class="text">203 Fake St. Mountain View, San Francisco, California, USA</span></li>
	                <li><a href="#"><span class="icon icon-phone"></span><span class="text">+2 392 3929 210</span></a></li>
	                <li><a href="#"><span class="icon icon-envelope"></span><span class="text">info@yourdomain.com</span></a></li>
	              </ul>
	            </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 text-center">
            <p>Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | This template is made with <i class="icon-heart color-danger" aria-hidden="true"></i> by <a href="https://colorlib.com" target="_blank">Colorlib</a></p>
          </div>
        </div>
      </div>
    </footer>

  <div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>

  <script src="js/jquery.min.js"></script>
  <script src="js/jquery-migrate-3.0.1.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.easing.1.3.js"></script>
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.stellar.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/jquery.magnific-popup.min.js"></script>
  <script src="js/aos.js"></script>
  <script src="js/jquery.animateNumber.min.js"></script>
  <script src="js/bootstrap-datepicker.js"></script>
  <script src="js/jquery.timepicker.min.js"></script>
  <script src="js/scrollax.min.js"></script>
  <script src="js/google-map.js"></script>
  <script src="js/main.js"></script>

  </body>
</html>