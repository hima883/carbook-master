<?php
require_once __DIR__ . '/config/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

$error_msg = get_flash_message('error');
$success_msg = get_flash_message('success');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = login_user($conn, $email, $password);
    if ($result['success']) {
        $_SESSION['flash_success'] = "مرحباً بعودتك، " . htmlspecialchars($_SESSION['user_name']) . "!";
        
        $redirect = $_GET['redirect'] ?? 'index.php';
        header("Location: " . $redirect);
        exit();
    } else {
        $error_msg = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Login - Carbook Car Rental</title>
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
      .auth-card {
        background: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      }
      .auth-card h2 {
        font-weight: 700;
        color: #000;
        margin-bottom: 25px;
      }
      .form-group label {
        font-weight: 600;
        color: #333;
        font-size: 14px;
        margin-bottom: 5px;
      }
      .form-control {
      height: 48px !important;
      border-radius: 6px;
      border: 2px solid #1089ff !important;

      background-color: #f2f8ff !important;

    /* الكلام اللي المستخدم بيكتبه */
      color: #000000 !important;

      font-size: 15px;
      transition: all 0.3s ease;
  }

  .form-control::placeholder {
      color: #7a9bbd !important;
      opacity: 1;
  }

  .form-control:focus {
      color: #000000 !important;
      background-color: #ffffff !important;
      border-color: #0069d9 !important;
      box-shadow: 0 0 0 3px rgba(16, 137, 255, 0.15) !important;
      outline: none;
  }

  .form-control:focus {
      border-color: #0069d9 !important;
      background-color: #ffffff !important;

      box-shadow: 0 0 0 3px rgba(16, 137, 255, 0.15) !important;
      outline: none;
  }
      .btn-auth {
        height: 50px;
        font-weight: 600;
        letter-spacing: 0.5px;
        border-radius: 6px;
        background: #1089ff;
        border: 1px solid #1089ff;
        color: #fff;
        width: 100%;
        transition: all 0.3s ease;
      }
      .btn-auth:hover {
        background: #0069d9;
        border-color: #0062cc;
      }
      
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
	          <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
            <li class="nav-item active"><a href="login.php" class="nav-link">Login</a></li>
	          <li class="nav-item"><a href="register.php" class="nav-link">Register</a></li>
	        </ul>
	      </div>
	    </div>
	  </nav>
    <!-- END nav -->
    
    <section class="hero-wrap hero-wrap-2 js-fullheight" style="background-image: url('images/bg_3.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text js-fullheight align-items-end justify-content-start">
          <div class="col-md-9 ftco-animate pb-5">
          	<p class="breadcrumbs"><span class="mr-2"><a href="index.php">Home <i class="ion-ios-arrow-forward"></i></a></span> <span>Login <i class="ion-ios-arrow-forward"></i></span></p>
            <h1 class="mb-3 bread">Log In To Your Account</h1>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section bg-light">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-7 col-lg-5">
            <div class="auth-card">
              <h2 class="text-center">Welcome Back</h2>

              <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <i class="ion-ios-close-circle mr-2"></i> <?php echo htmlspecialchars($error_msg); ?>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>

              <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <i class="ion-ios-checkmark-circle mr-2"></i> <?php echo htmlspecialchars($success_msg); ?>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>

              <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST" class="request-form">
                <div class="form-group">
                  <label for="email">Email Address / البريد الإلكتروني *</label>
                  <input type="email" name="email" id="email" class="form-control" placeholder="user@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <div class="form-group">
                  <label for="password">Password / كلمة المرور *</label>
                  <input type="password" name="password" id="password" class="form-control" placeholder="******" required>
                </div>

                <div class="form-group mt-4">
                  <button type="submit" class="btn btn-auth py-3 px-4">Log In</button>
                </div>

                <div class="text-center mt-3">
                  <p>Don't have an account? <a href="register.php" style="color: #1089ff; font-weight: 600;">Register Here</a></p>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer class="ftco-footer ftco-bg-dark ftco-section">
      <div class="container">
        <div class="row">
          <div class="col-md-12 text-center">
            <p>Copyright &copy;<script>document.write(new Date().getFullYear());</script> All rights reserved | Carbook Rental Platform</p>
          </div>
        </div>
      </div>
    </footer>
    
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
    <script src="js/main.js"></script>
  </body>
</html>
