<?php
// =====================================================
// ADMIN LOGIN
// =====================================================

// Load config FIRST.
// This starts the session and defines BASE_URL.
require_once __DIR__ . '/../config/config.php';

// Database connection
require_once __DIR__ . '/../config/database.php';

// Helper functions
require_once __DIR__ . '/../includes/functions.php';


// Already logged in as admin?
if (is_logged_in() && is_admin()) {
    redirect('admin/dashboard.php');
}


$errors = [];

$old = [
    'identifier' => ''
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // =================================================
    // CSRF CHECK
    // =================================================

    if (!verify_csrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }


    // =================================================
    // GET FORM DATA
    // =================================================

    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    $old['identifier'] = $identifier;


    // =================================================
    // VALIDATION
    // =================================================

    if ($identifier === '') {
        $errors[] = 'Please enter your email or username.';
    }

    if ($password === '') {
        $errors[] = 'Please enter your password.';
    }


    // =================================================
    // LOGIN
    // =================================================

    if (empty($errors)) {

        try {

            $stmt = $pdo->prepare(
                'SELECT
                    u.id,
                    u.full_name,
                    u.password_hash,
                    u.is_active,
                    r.name AS role_name
                 FROM users u
                 JOIN roles r
                    ON r.id = u.role_id
                 WHERE u.email = :email
                    OR u.username = :username
                 LIMIT 1'
            );

            // IMPORTANT:
            // Use two different parameter names.
            $stmt->execute([
                ':email'    => $identifier,
                ':username' => $identifier
            ]);

            $user = $stmt->fetch();


            // =================================================
            // CHECK ACCOUNT
            // =================================================

            if (!$user) {

                $errors[] = 'Invalid admin credentials.';

            } elseif (!password_verify(
                $password,
                $user['password_hash']
            )) {

                $errors[] = 'Invalid admin credentials.';

            } elseif (!$user['is_active']) {

                $errors[] =
                    'This admin account has been deactivated.';

            } elseif ($user['role_name'] !== 'admin') {

                $errors[] = 'Invalid admin credentials.';

            } else {

                // =================================================
                // LOGIN SUCCESS
                // =================================================

                session_regenerate_id(true);

                $_SESSION['user_id'] =
                    (int)$user['id'];

                $_SESSION['full_name'] =
                    $user['full_name'];

                $_SESSION['role'] =
                    $user['role_name'];

                redirect('admin/dashboard.php');
            }

        } catch (Throwable $e) {

            // Temporary diagnostic output
            http_response_code(500);

            echo '<!DOCTYPE html>';
            echo '<html>';
            echo '<head>';
            echo '<meta charset="UTF-8">';
            echo '<title>Admin Login Error</title>';

            echo '<style>
                body {
                    font-family: Arial, sans-serif;
                    background: #f5f5f5;
                    padding: 40px;
                }

                .box {
                    max-width: 900px;
                    margin: auto;
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 4px 20px rgba(0,0,0,.1);
                }

                pre {
                    background: #111;
                    color: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    white-space: pre-wrap;
                }
            </style>';

            echo '</head>';
            echo '<body>';

            echo '<div class="box">';
            echo '<h2>Admin Login Error</h2>';

            echo '<pre>';

            echo htmlspecialchars(
                $e->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            );

            echo "\n\nFile: ";

            echo htmlspecialchars(
                $e->getFile(),
                ENT_QUOTES,
                'UTF-8'
            );

            echo "\nLine: ";

            echo (int)$e->getLine();

            echo '</pre>';

            echo '</div>';

            echo '</body>';
            echo '</html>';

            exit;
        }
    }
}


$pageTitle = 'Admin Login';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Admin Login | Custom Case Co.
    </title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link
        href="<?= BASE_URL ?>/assets/css/style.css"
        rel="stylesheet"
    >

    <link
        href="<?= BASE_URL ?>/assets/css/admin.css"
        rel="stylesheet"
    >

</head>

<body class="admin-login-body">

<div
    class="container d-flex align-items-center justify-content-center"
    style="min-height:100vh;"
>

    <div class="col-11 col-sm-8 col-md-5 col-lg-4">

        <div class="text-center mb-4">

            <i
                class="bi bi-shield-lock"
                style="font-size:2.5rem; color:#fff;"
            ></i>

            <h1 class="h4 text-white mt-2 mb-0">
                Custom Case Co.
            </h1>

            <p class="text-white-50 small">
                Admin Panel
            </p>

        </div>


        <div class="cc-card p-4 p-md-5">

            <?php if ($errors): ?>

                <div class="alert alert-danger">

                    <ul class="mb-0 ps-3">

                        <?php foreach ($errors as $err): ?>

                            <li>
                                <?= e($err) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <form
                method="post"
                action="<?= BASE_URL ?>/admin/login.php"
                data-once
                novalidate
            >

                <?= csrf_field() ?>


                <div class="mb-3">

                    <label
                        class="form-label"
                        for="identifier"
                    >
                        Email or Username
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="identifier"
                        name="identifier"
                        value="<?= e($old['identifier']) ?>"
                        required
                        autofocus
                    >

                </div>


                <div class="mb-4">

                    <label
                        class="form-label"
                        for="password"
                    >
                        Password
                    </label>

                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        required
                    >

                </div>


                <button
                    type="submit"
                    class="btn btn-admin-primary w-100 btn-lg"
                >
                    Log In to Admin
                </button>

            </form>

        </div>


        <p class="text-center small text-white-50 mt-3">

            <a
                href="<?= BASE_URL ?>/index.php"
                class="text-white-50"
            >
                ← Back to the main site
            </a>

        </p>

    </div>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>

</html>