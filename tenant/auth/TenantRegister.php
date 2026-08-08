<?php

session_start();

require_once "../../mysql/db_connect.php";

if (isset($_SESSION['tenant_license'])) {

    header("Location: ../../index.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $driving_license = trim($_POST['driving_license']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone = trim($_POST['phone']);

    if (

        empty($driving_license) ||
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password) ||
        empty($phone)

    ) {

        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Invalid email address.";
    } elseif ($password != $confirm_password) {

        $error = "Passwords do not match.";
    } else {

        $check_email = "

        SELECT driving_license

        FROM tenants

        WHERE email = ?

        LIMIT 1

        ";

        $tenant = $conn->execute_query($check_email, [$email]);

        if ($tenant->num_rows > 0) {

            $error = "This email is already registered.";
        } else {

            $check_license = "

            SELECT driving_license

            FROM tenants

            WHERE driving_license = ?

            LIMIT 1

            ";

            $license = $conn->execute_query($check_license, [

                $driving_license

            ]);

            if ($license->num_rows > 0) {

                $error = "Driving license already exists.";
            } else {

                $insert = "

                INSERT INTO tenants

                (

                    driving_license,
                    name,
                    email,
                    password,
                    phone

                )

                VALUES

                (

                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )

                ";

                $conn->execute_query($insert, [

                    $driving_license,
                    $name,
                    $email,
                    $password,
                    $phone

                ]);

                $get_tenant = "

                SELECT *

                FROM tenants

                WHERE driving_license = ?

                LIMIT 1

                ";

                $tenant = $conn->execute_query($get_tenant, [

                    $driving_license

                ]);

                $row = $tenant->fetch_assoc();

                $_SESSION['tenant_license'] = $row['driving_license'];
                $_SESSION['tenant_name'] = $row['name'];
                $_SESSION['tenant_email'] = $row['email'];
                $_SESSION['tenant_phone'] = $row['phone'];

                header("Location: ../../index.php");

                exit();
            }
        }
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tenant Register</title>

    <style>
        * {

            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;

        }

        body {

            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;

        }

        .register-box {

            width: 450px;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, .15);

        }

        .register-box h2 {

            text-align: center;
            margin-bottom: 25px;
            color: #333;

        }

        .error {

            background: #ffe5e5;
            color: #b30000;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;

        }

        label {

            display: block;
            margin-bottom: 8px;
            margin-top: 15px;
            font-weight: bold;

        }

        input {

            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            outline: none;

        }

        input:focus {

            border-color: #0d6efd;

        }

        button {

            width: 100%;
            margin-top: 25px;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #0d6efd;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: .3s;

        }

        button:hover {

            background: #0b5ed7;

        }

        .login-link {

            text-align: center;
            margin-top: 20px;

        }

        .login-link a {

            color: #0d6efd;
            text-decoration: none;
            font-weight: bold;

        }

        .login-link a:hover {

            text-decoration: underline;

        }
    </style>

</head>

<body>

    <div class="register-box">

        <h2>

            Tenant Register

        </h2>

        <?php

        if (!empty($error)) {

        ?>

            <div class="error">

                <?= $error ?>

            </div>

        <?php

        }

        ?>

        <form method="POST">

            <label>

                Driving License

            </label>

            <input
                type="text"
                name="driving_license"
                value="<?= $_POST['driving_license'] ?? '' ?>"
                required>

            <label>

                Full Name

            </label>

            <input
                type="text"
                name="name"
                value="<?= $_POST['name'] ?? '' ?>"
                required>

            <label>

                Email

            </label>

            <input
                type="email"
                name="email"
                value="<?= $_POST['email'] ?? '' ?>"
                required>

            <label>

                Phone Number

            </label>

            <input
                type="text"
                name="phone"
                value="<?= $_POST['phone'] ?? '' ?>"
                required>

            <label>

                Password

            </label>

            <input
                type="password"
                name="password"
                required>

            <label>

                Confirm Password

            </label>

            <input
                type="password"
                name="confirm_password"
                required>

            <button type="submit">

                Register

            </button>

        </form>

        <div class="login-link">

            Already have an account?

            <a href="TenantLogin.php">

                Login

            </a>

        </div>

    </div>

</body>

</html>