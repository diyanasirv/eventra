<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $action = $_POST['action'];

    if ($action === 'register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $class = trim($_POST['class']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $user_id = generateUserId();

        // Check if email exists
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['message'] = 'Email already registered!';
            header('Location: index.php');
            exit();
        }

        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (user_id, name, email, password, phone, class) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $user_id, $name, $email, $password, $phone, $class);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Registration successful! Your User ID: ' . $user_id;
            header('Location: index.php');
        } else {
            $_SESSION['message'] = 'Registration failed!';
            header('Location: index.php');
        }
    }
    
    elseif ($action === 'login') {
        $identifier = trim($_POST['identifier']);
        $password = $_POST['password'];

        // Check by user_id or email
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? OR email = ?");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            }
        }
        
        $_SESSION['message'] = 'Invalid credentials!';
        header('Location: index.php');
    }
    
    elseif ($action === 'logout') {
        session_start(); // Ensure session is started
        session_destroy(); // Destroy all session data
        session_write_close(); // Properly close the session
        header('Location: index.php'); // Redirect to index page
        exit();
    }

    if (isset($conn)) {
        $conn->close();
    }
}

// Handle GET logout request
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_start(); // Ensure session is started
    session_destroy(); // Destroy all session data
    session_write_close(); // Properly close the session
    header('Location: index.php'); // Redirect to index page
    exit();
}
?>
