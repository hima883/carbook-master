<?php
/**
 * Authentication & Session Management Module
 * Carbook Rental System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Register a new user
 *
 * @param mysqli $conn
 * @param string $name
 * @param string $email
 * @param string $password
 * @param string $phone
 * @param string $role ('renter', 'owner', 'both')
 * @return array
 */
function register_user($conn, $name, $email, $password, $phone = '', $role = 'renter') {
    $name = trim($name);
    $email = trim(strtolower($email));
    $password = trim($password);
    $phone = trim($phone);

    $allowed_roles = ['renter', 'owner', 'both'];

    if (!in_array($role, $allowed_roles, true)) {
        $role = 'renter';
    }

    // Basic Validations
    if (empty($name) || empty($email) || empty($password)) {
        return [
            'success' => false,
            'message' => 'يرجى ملء جميع الحقول المطلوبة.'
        ];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'البريد الإلكتروني غير صالح.'
        ];
    }

    if (strlen($password) < 6) {
        return [
            'success' => false,
            'message' => 'يجب أن تكون كلمة المرور مكونة من 6 خانات على الأقل.'
        ];
    }

    // Check if email already exists
    $check_stmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ? LIMIT 1"
    );

    if (!$check_stmt) {
        return [
            'success' => false,
            'message' => 'خطأ في قاعدة البيانات: ' . $conn->error
        ];
    }

    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $check_stmt->close();

        return [
            'success' => false,
            'message' => 'البريد الإلكتروني مُسجل بالفعل.'
        ];
    }

    $check_stmt->close();

    // Hash Password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Start Transaction
    $conn->begin_transaction();

    try {

        // =====================================
        // 1. Add user to users table
        // =====================================

        $stmt = $conn->prepare(
            "INSERT INTO users
            (name, email, password, phone, role)
            VALUES (?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception($conn->error);
        }

        $stmt->bind_param(
            "sssss",
            $name,
            $email,
            $hashed_password,
            $phone,
            $role
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        // Get new user ID
        $user_id = $stmt->insert_id;

        $stmt->close();


        // =====================================
        // 2. If Owner
        // =====================================

        if ($role === 'owner' || $role === 'both') {

            $owner_stmt = $conn->prepare(
                "INSERT INTO owners (user_id)
                 VALUES (?)"
            );

            if (!$owner_stmt) {
                throw new Exception($conn->error);
            }

            $owner_stmt->bind_param("i", $user_id);

            if (!$owner_stmt->execute()) {
                throw new Exception($owner_stmt->error);
            }

            $owner_stmt->close();
        }


        // =====================================
        // 3. If Tenant
        // =====================================

        if ($role === 'renter' || $role === 'both') {

            $tenant_stmt = $conn->prepare(
                "INSERT INTO tenants (user_id)
                 VALUES (?)"
            );

            if (!$tenant_stmt) {
                throw new Exception($conn->error);
            }

            $tenant_stmt->bind_param("i", $user_id);

            if (!$tenant_stmt->execute()) {
                throw new Exception($tenant_stmt->error);
            }

            $tenant_stmt->close();
        }


        // =====================================
        // Everything successful
        // =====================================

        $conn->commit();

        return [
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح!',
            'user_id' => $user_id
        ];

    } catch (Exception $e) {

        // Something failed -> undo everything
        $conn->rollback();

        return [
            'success' => false,
            'message' => 'تعذر إنشاء الحساب: ' . $e->getMessage()
        ];
    }
}

/**
 * Login user and initiate session
 *
 * @param mysqli $conn
 * @param string $email
 * @param string $password
 * @return array
 */
function login_user($conn, $email, $password) {
    $email = trim(strtolower($email));
    $password = trim($password);

    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'يرجى إدخال البريد الإلكتروني وكلمة المرور.'];
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, phone, role FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return ['success' => false, 'message' => 'خطأ في استعلام قاعدة البيانات.'];
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password hash
        if (password_verify($password, $user['password'])) {
            // Prevent session fixation attack
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_phone'] = $user['phone'];
            $_SESSION['user_role'] = $user['role'];

            return [
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح!',
                'user' => $user
            ];
        } else {
            return ['success' => false, 'message' => 'كلمة المرور غير صحيحة.'];
        }
    } else {
        if ($stmt) {
            $stmt->close();
        }
        return ['success' => false, 'message' => 'البريد الإلكتروني غير مسجل لدى موقع Carbook.'];
    }
}

/**
 * Logout current user and clear session
 */
function logout_user() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

/**
 * Check if a user is currently logged in
 *
 * @return boolean
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get logged in user details array or null
 *
 * @return array|null
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'renter'
    ];
}

/**
 * Middleware: Require user to be logged in to access page
 *
 * @param string $redirect_page
 */
function require_login($redirect_page = 'login.php') {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'يجب عليك تسجيل الدخول أولاً للوصول إلى هذه الصفحة.';
        header("Location: " . $redirect_page);
        exit();
    }
}

/**
 * Helper to display flash messages
 */
function get_flash_message($type = 'error') {
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}
