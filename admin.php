




<?php
require_once 'config.php';
requireLogin();

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$conn = getDBConnection();

// Handle event approval/rejection
if (isset($_GET['approve_event'])) {
    $event_id = (int)$_GET['approve_event'];
    $conn->query("UPDATE events SET status = 'approved' WHERE event_id = $event_id");
    $message = "Event approved successfully!";
}

if (isset($_GET['reject_event'])) {
    $event_id = (int)$_GET['reject_event'];
    $conn->query("UPDATE events SET status = 'rejected' WHERE event_id = $event_id");
    $message = "Event rejected!";
}

// Get statistics
$total_events = $conn->query("SELECT COUNT(*) as count FROM events")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$pending_events = $conn->query("SELECT COUNT(*) as count FROM events WHERE status = 'pending'")->fetch_assoc()['count'];
$total_registrations = $conn->query("SELECT COUNT(*) as count FROM registrations")->fetch_assoc()['count'];

// Get pending events
$pending_events_list = $conn->query("SELECT e.*, u.name as creator_name FROM events e JOIN users u ON e.created_by = u.user_id WHERE e.status = 'pending' ORDER BY e.created_at DESC");

// Get all events
$all_events = $conn->query("SELECT e.*, u.name as creator_name FROM events e JOIN users u ON e.created_by = u.user_id ORDER BY e.created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - School Events</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 School Events - Admin</div>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (Admin)</span>
            <a href="dashboard.php" class="btn">User View</a>
            <a href="auth.php?action=logout" class="btn btn-danger">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if(isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <h1>Admin Dashboard</h1>
        <div class="stats-grid">
            <div class="stat">
                <h3>Total Events</h3>
                <p><?php echo (int)$total_events; ?></p>
            </div>
            <div class="stat">
                <h3>Total Users</h3>
                <p><?php echo (int)$total_users; ?></p>
            </div>
            <div class="stat">
                <h3>Pending Events</h3>
                <p><?php echo (int)$pending_events; ?></p>
            </div>
            <div class="stat">
                <h3>Total Registrations</h3>
                <p><?php echo (int)$total_registrations; ?></p>
            </div>
        </div>

        <h2 style="margin-top:1.5rem;">Pending Events</h2>
        <?php if($pending_events_list && $pending_events_list->num_rows > 0): ?>
            <div class="events-list">
                <?php while($ev = $pending_events_list->fetch_assoc()): ?>
                    <div class="event-item">
                        <div class="event-item-header">
                            <h3><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <span>by <?php echo htmlspecialchars($ev['creator_name']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($ev['description']); ?></p>
                        <div class="event-meta">
                            <span>📅 <?php echo formatDate($ev['event_date']); ?></span>
                            <span>📍 <?php echo htmlspecialchars($ev['venue']); ?></span>
                        </div>
                        <div style="margin-top:.5rem;">
                            <a href="admin.php?approve_event=<?php echo $ev['event_id']; ?>" class="btn btn-success">Approve</a>
                            <a href="admin.php?reject_event=<?php echo $ev['event_id']; ?>" class="btn btn-danger">Reject</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-data">No pending events</p>
        <?php endif; ?>

        <h2 style="margin-top:1.5rem;">All Events</h2>
        <?php if($all_events && $all_events->num_rows > 0): ?>
            <div class="events-list">
                <?php while($ev = $all_events->fetch_assoc()): ?>
                    <div class="event-item">
                        <div class="event-item-header">
                            <h3><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <span class="badge badge-<?php echo htmlspecialchars($ev['status']); ?>"><?php echo ucfirst($ev['status']); ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($ev['description']); ?></p>
                        <div class="event-meta">
                            <span>📅 <?php echo formatDate($ev['event_date']); ?></span>
                            <span>📍 <?php echo htmlspecialchars($ev['venue']); ?></span>
                            <span>Created by: <?php echo htmlspecialchars($ev['creator_name']); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-data">No events found</p>
        <?php endif; ?>

    </div>
</body>
</html>

<?php
// Close DB connection
if (isset($conn)) {
    $conn->close();
}
