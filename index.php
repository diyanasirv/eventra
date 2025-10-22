<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Event Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="landing-page">
        <nav class="navbar">
            <div class="logo">🎓 Eventra</div>
            <div class="nav-links">
                <button class="btn btn-outline" onclick="showLogin()">Login</button>
                <button class="btn btn-primary" onclick="showRegister()">Register</button>
            </div>
        </nav>

        <section class="hero">
            <h1>Bringing every CUSAT event closer to you.</h1>
            <p>Discover, Create & Register for School Events</p>
            <div class="hero-features">
                <div class="feature">
                    <div class="feature-icon">📅</div>
                    <h3>Browse Events</h3>
                    <p>View upcoming and past events</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">➕</div>
                    <h3>Create Events</h3>
                    <p>Organize your own events</p>
                </div>
                <div class="feature">
                    <div class="feature-icon">✅</div>
                    <h3>Easy Registration</h3>
                    <p>Register for events instantly</p>
                </div>
            </div>
        </section>

        <!-- Login Modal -->
        <div class="modal" id="loginModal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('loginModal')">&times;</span>
                <h2>Login</h2>
                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>User ID or Email</label>
                        <input type="text" name="identifier" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Login</button>
                    <p class="form-footer">
                        Don't have an account? <a href="#" onclick="showRegister()">Register here</a>
                    </p>
                </form>
            </div>
        </div>

        <!-- Register Modal -->
        <div class="modal" id="registerModal">
            <div class="modal-content">
                <span class="close" onclick="closeModal('registerModal')">&times;</span>
                <h2>Register</h2>
                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" pattern="[0-9]{10}" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="class"  required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                    <p class="form-footer">
                        Already have an account? <a href="#" onclick="showLogin()">Login here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showLogin() {
            document.getElementById('registerModal').style.display = 'none';
            document.getElementById('loginModal').style.display = 'flex';
        }

        function showRegister() {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'flex';
        }

        function closeModal(id) {
            document.getElementById(id).style.display = 'none';
        }

        // Show error/success messages
        <?php if(isset($_SESSION['message'])): ?>
            alert('<?php echo $_SESSION['message']; ?>');
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
    </script>
</body>
</html>

