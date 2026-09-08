<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

start_secure_session();

header('Cache-Control: no-store');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'login':
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            header('Location: ' . BASE_URL . 'login.php?e=Invalid+CSRF+token');
            exit;
        }
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            header('Location: ' . BASE_URL . 'login.php?e=Missing+credentials');
            exit;
        }

        global $pdo;
        $stmt = $pdo->prepare('SELECT user_id, username, email, password, full_name, role FROM users WHERE (username = :u1 OR email = :u2) AND status = "active" LIMIT 1');
        $stmt->execute([':u1' => $username, ':u2' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => (int)$user['user_id'],
                'username' => $user['username'],
                'name' => $user['full_name'],
                'role' => $user['role'],
            ];
            if ($user['role'] === ROLE_ADMIN) {
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
            } elseif ($user['role'] === ROLE_STAFF) {
                header('Location: ' . BASE_URL . 'staff/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'index.php');
            }
            exit;
        }

        // Try drivers table
        $stmt = $pdo->prepare('SELECT driver_id, username, password, full_name FROM drivers WHERE username = :u AND status = "active" LIMIT 1');
        $stmt->execute([':u' => $username]);
        $driver = $stmt->fetch();
        if ($driver && password_verify($password, $driver['password'])) {
            $_SESSION['user'] = [
                'id' => (int)$driver['driver_id'],
                'username' => $driver['username'],
                'name' => $driver['full_name'],
                'role' => ROLE_DRIVER,
            ];
            header('Location: ' . BASE_URL . 'driver/dashboard.php');
            exit;
        }

        header('Location: ' . BASE_URL . 'login.php?e=Invalid+credentials');
        exit;

    case 'logout':
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . 'login.php');
        exit;

    case 'register_driver':
        // Minimal driver registration (username, password, full_name, email, phone, license_number)
        if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
            http_response_code(400);
            echo 'Invalid CSRF token';
            exit;
        }
        $payload = [
            'username' => trim((string)($_POST['username'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'license_number' => trim((string)($_POST['license_number'] ?? '')),
        ];
        foreach ($payload as $k => $v) {
            if ($v === '') {
                http_response_code(422);
                echo 'Missing field: ' . $k;
                exit;
            }
        }

        global $pdo;
        $hash = password_hash($payload['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO drivers (username, password, full_name, email, phone, license_number, address) VALUES (:u, :p, :n, :e, :ph, :ln, :addr)');
        try {
            $stmt->execute([
                ':u' => $payload['username'],
                ':p' => $hash,
                ':n' => $payload['full_name'],
                ':e' => $payload['email'],
                ':ph' => $payload['phone'],
                ':ln' => $payload['license_number'],
                ':addr' => (string)($_POST['address'] ?? ''),
            ]);
        } catch (PDOException $e) {
            http_response_code(400);
            echo 'Username or email already exists';
            exit;
        }
        header('Location: ' . BASE_URL . 'login.php?e=Registration+successful.+Please+login');
        exit;

    default:
        http_response_code(400);
        echo 'Unsupported action';
        exit;
}


