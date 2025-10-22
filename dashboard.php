<!--
===========================================
FILE 5: dashboard.php (User Dashboard) - FIXED
===========================================
-->
<?php
require_once 'config.php';
requireLogin();

$conn = getDBConnection();
if (!$conn) {
    die("Database connection failed");
}
$user_id = $_SESSION['user_id'];

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = trim($_POST['venue']);
    $category = trim($_POST['category']);
    $max_participants = (int)$_POST['max_participants'];

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, venue, category, max_participants, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    // types: title(s), description(s), event_date(s), event_time(s), venue(s), category(s), max_participants(i), created_by(s)
    $stmt->bind_param("ssssssis", $title, $description, $event_date, $event_time, $venue, $category, $max_participants, $user_id);
    
    if ($stmt->execute()) {
        $message = "Event created successfully! Waiting for admin approval.";
    } else {
        $error = "Failed to create event!";
    }
}

// Handle event registration
if (isset($_GET['register'])) {
    $event_id = (int)$_GET['register'];
    
    // First check if the event exists and is approved
    $event_check = $conn->prepare("SELECT status FROM events WHERE event_id = ?");
    $event_check->bind_param("i", $event_id);
    $event_check->execute();
    $event_result = $event_check->get_result();
    
    if ($event_result->num_rows === 0) {
        $error = "Event not found!";
    } else {
        $event_data = $event_result->fetch_assoc();
        if ($event_data['status'] !== 'approved') {
            $error = "This event is not available for registration.";
        } else {
            // Check if already registered
            $check_stmt = $conn->prepare("SELECT status FROM registrations WHERE event_id = ? AND user_id = ?");
            $check_stmt->bind_param("is", $event_id, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $reg_status = $result->fetch_assoc()['status'];
                if ($reg_status === 'approved') {
                    $error = "You are already registered and approved for this event!";
                } else if ($reg_status === 'pending') {
                    $error = "Your registration is pending approval.";
                } else {
                    $error = "Your previous registration was rejected.";
                }
            } else {
                // Check if event has space available
                $space_check = $conn->prepare("SELECT e.max_participants, COUNT(r.reg_id) as current_registrations 
                    FROM events e 
                    LEFT JOIN registrations r ON e.event_id = r.event_id 
                    WHERE e.event_id = ? 
                    GROUP BY e.event_id, e.max_participants");
                $space_check->bind_param("i", $event_id);
                $space_check->execute();
                $event_data = $space_check->get_result()->fetch_assoc();
                
                if (!$event_data) {
                    $error = "Event not found!";
                } else if ($event_data['current_registrations'] >= $event_data['max_participants']) {
                    $error = "Sorry, this event is already full!";
                } else {
                    // Proceed with registration
                    $stmt = $conn->prepare("INSERT INTO registrations (event_id, user_id) VALUES (?, ?)");
                    $stmt->bind_param("is", $event_id, $user_id);
                    
                    if ($stmt->execute()) {
                        $message = "Registration successful! Waiting for approval.";
                    } else {
                        $error = "Registration failed! Please try again.";
                    }
                }
            }
        }
    }
}

// Handle approve/reject participant (for event creators)
if (isset($_GET['action']) && isset($_GET['reg_id'])) {
    $action = $_GET['action'];
    $reg_id = (int)$_GET['reg_id'];
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // Check if user is the event creator
    $check_stmt = $conn->prepare("SELECT e.created_by FROM registrations r JOIN events e ON r.event_id = e.event_id WHERE r.reg_id = ?");
    $check_stmt->bind_param("i", $reg_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $event_data = $result->fetch_assoc();
        if ($event_data['created_by'] === $user_id) {
            $update_stmt = $conn->prepare("UPDATE registrations SET status = ? WHERE reg_id = ?");
            $update_stmt->bind_param("si", $status, $reg_id);
            $update_stmt->execute();
            $message = "Participant " . $status . " successfully!";
        }
    }
}

// Get approved events
$approved_events = $conn->query("SELECT * FROM events WHERE status = 'approved' AND event_date >= CURDATE() ORDER BY event_date ASC");

// Get past events
$past_events = $conn->query("SELECT * FROM events WHERE status = 'approved' AND event_date < CURDATE() ORDER BY event_date DESC LIMIT 5");

// Get user's created events
$my_events = $conn->prepare("SELECT * FROM events WHERE created_by = ? ORDER BY created_at DESC");
$my_events->bind_param("s", $user_id);
$my_events->execute();
$my_events_result = $my_events->get_result();

// Get user's registrations
$my_registrations = $conn->prepare("SELECT r.*, e.title, e.event_date, e.event_time, e.venue FROM registrations r JOIN events e ON r.event_id = e.event_id WHERE r.user_id = ? ORDER BY r.registered_at DESC");
$my_registrations->bind_param("s", $user_id);
$my_registrations->execute();
$my_registrations_result = $my_registrations->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Eventra</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Alert auto-hide animation */
        .auto-hide {
            transition: opacity 0.3s ease-out;
        }

        /* White text for all tab headings */
        #events-tab h2,
        #create-tab h2,
        #myevents-tab h2,
        #myregistrations-tab h2 {
            color: white;
            margin-bottom: 1rem;
        }
        
        /* Custom Modal Styles */
        .confirm-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .confirm-modal.show {
            display: flex;
        }
        
        .confirm-modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .confirm-modal h3 {
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .confirm-modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }
        
        .confirm-modal-buttons .btn {
            min-width: 100px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">🎓 Eventra</div>
        <div class="nav-links">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?> (<?php echo $user_id; ?>)</span>
            <a href="auth.php?action=logout" class="btn btn-danger">Logout</a>
        </div>
    </nav>

    <div class="container">
        <?php if(isset($message)): ?>
            <div class="alert alert-success auto-hide"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error auto-hide"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="dashboard-tabs">
            <button class="tab-btn active" onclick="showTab('events', this)">Browse Events</button>
            <button class="tab-btn" onclick="showTab('create', this)">Create Event</button>
            <button class="tab-btn" onclick="showTab('myevents', this)">My Events</button>
            <button class="tab-btn" onclick="showTab('myregistrations', this)">My Registrations</button>
        </div>

        <!-- Browse Events Tab -->
        <div class="tab-content active" id="events-tab">
            <h2>Upcoming Events</h2>
            <div class="events-grid">
                <?php if($approved_events && $approved_events->num_rows > 0): ?>
                    <?php while($event = $approved_events->fetch_assoc()): ?>
                        <div class="event-card">
                            <div class="event-header">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span class="badge badge-success">Approved</span>
                            </div>
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>
                            <div class="event-details">
                                <div class="detail-item">
                                    <strong>📅 Date:</strong> <?php echo formatDate($event['event_date']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>🕐 Time:</strong> <?php echo formatTime($event['event_time']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>📍 Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>🏷️ Category:</strong> <?php echo htmlspecialchars($event['category']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>👥 Max Participants:</strong> <?php echo $event['max_participants']; ?>
                                </div>
                            </div>
                            <a href="#" class="btn btn-primary btn-block" onclick="showRegistrationModal('?register=<?php echo $event['event_id']; ?>')">Register</a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">No upcoming events available</p>
                <?php endif; ?>
            </div>

            <h2 style="margin-top: 2rem;">Past Events</h2>
            <div class="events-grid">
                <?php if($past_events && $past_events->num_rows > 0): ?>
                    <?php while($event = $past_events->fetch_assoc()): ?>
                        <div class="event-card past-event">
                            <div class="event-header">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span class="badge badge-secondary">Completed</span>
                            </div>
                            <div class="event-details">
                                <div class="detail-item">
                                    <strong>📅 Date:</strong> <?php echo formatDate($event['event_date']); ?>
                                </div>
                                <div class="detail-item">
                                    <strong>📍 Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">No past events</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Create Event Tab -->
        <div class="tab-content" id="create-tab">
            <h2>Create New Event</h2>
            <form method="POST" class="event-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Event Title *</label>
                        <input type="text" name="title" required>
                    </div>
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <option value="Sports">Sports</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Academic">Academic</option>
                            <option value="Technical">Technical</option>
                            <option value="Social">Social</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="description" rows="4" required></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Event Time *</label>
                        <input type="time" name="event_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Venue *</label>
                        <input type="text" name="venue" required>
                    </div>
                    <div class="form-group">
                        <label>Max Participants *</label>
                        <input type="number" name="max_participants" min="1" required>
                    </div>
                </div>
                <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
                <p class="form-note">* Your event will be visible after admin approval</p>
            </form>
        </div>

        <!-- My Events Tab -->
        <div class="tab-content" id="myevents-tab">
            <h2>Events Created by Me</h2>
            <div class="events-list">
                <?php if($my_events_result && $my_events_result->num_rows > 0): ?>
                    <?php while($event = $my_events_result->fetch_assoc()): ?>
                        <div class="event-item">
                            <div class="event-item-header">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span class="badge badge-<?php echo $event['status']; ?>">
                                    <?php echo ucfirst($event['status']); ?>
                                </span>
                            </div>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                            <div class="event-meta">
                                <span>📅 <?php echo formatDate($event['event_date']); ?></span>
                                <span>🕐 <?php echo formatTime($event['event_time']); ?></span>
                                <span>📍 <?php echo htmlspecialchars($event['venue']); ?></span>
                            </div>
                            <?php if($event['status'] === 'approved'): ?>
                                <button class="btn btn-outline" onclick="viewParticipants(<?php echo $event['event_id']; ?>)">
                                    View Participants
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">You haven't created any events yet</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Registrations Tab -->
        <div class="tab-content" id="myregistrations-tab">
            <h2>My Event Registrations</h2>
            <div class="events-list">
                <?php if($my_registrations_result && $my_registrations_result->num_rows > 0): ?>
                    <?php while($reg = $my_registrations_result->fetch_assoc()): ?>
                        <div class="event-item">
                            <div class="event-item-header">
                                <h3><?php echo htmlspecialchars($reg['title']); ?></h3>
                                <span class="badge badge-<?php echo $reg['status']; ?>">
                                    <?php echo ucfirst($reg['status']); ?>
                                </span>
                            </div>
                            <div class="event-meta">
                                <span>📅 <?php echo formatDate($reg['event_date']); ?></span>
                                <span>🕐 <?php echo formatTime($reg['event_time']); ?></span>
                                <span>📍 <?php echo htmlspecialchars($reg['venue']); ?></span>
                                <span>✅ Registered on: <?php echo formatDate($reg['registered_at']); ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-data">You haven't registered for any events yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Participants Modal -->
    <div class="modal" id="participantsModal">
        <div class="modal-content">
            <span class="close" onclick="closeParticipantsModal()">&times;</span>
            <h2>Event Participants</h2>
            <div id="participantsList"></div>
        </div>
    </div>

    <!-- Registration Confirmation Modal -->
    <div class="confirm-modal" id="registrationModal">
        <div class="confirm-modal-content">
            <h3>Confirm Registration</h3>
            <p>Are you sure you want to register for this event?</p>
            <div class="confirm-modal-buttons">
                <button class="btn btn-primary" onclick="confirmRegistration()">Confirm</button>
                <button class="btn btn-danger" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

<script>
    // Client-side error reporter (development aid)
    (function(){
        // ... (Error reporter code remains the same) ...
        function reportError(payload){
            try{
                navigator.sendBeacon('log_client_error.php', JSON.stringify(payload));
            }catch(e){
                // fallback
                fetch('log_client_error.php', {method:'POST', body: JSON.stringify(payload), headers:{'Content-Type':'application/json'}}).catch(()=>{});
            }
        }

        window.addEventListener('error', function(e){
            const payload = {message: e.message, filename: e.filename, lineno: e.lineno, colno: e.colno, stack: e.error && e.error.stack};
            console.error('Captured error:', payload);
            reportError(payload);
            // show visible banner
            let b = document.getElementById('debugBanner');
            if(!b){ b = document.createElement('div'); b.id='debugBanner'; b.style.position='fixed'; b.style.bottom='10px'; b.style.right='10px'; b.style.background='#fee2e2'; b.style.color='#7f1d1d'; b.style.padding='10px'; b.style.border='1px solid #fecaca'; b.style.zIndex=9999; document.body.appendChild(b); }
            b.textContent = payload.message + ' at ' + payload.filename + ':' + payload.lineno;
        });

        window.addEventListener('unhandledrejection', function(e){
            const payload = {message: e.reason && e.reason.message ? e.reason.message : String(e.reason), stack: e.reason && e.reason.stack};
            console.error('Captured rejection:', payload);
            reportError(payload);
        });
    })();

    // Auto-hide alerts after 2 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.auto-hide');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300); // Wait for fade out animation
            }, 2000);
        });
    });

    let pendingRegistrationUrl = '';

    // 1. Tab Switching Function
    function showTab(tab, btn) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        if (btn) btn.classList.add('active');
        document.getElementById(tab + '-tab').classList.add('active');
    }

    // 2. View Participants Function (Async)
    function viewParticipants(eventId) {
        fetch('get_participants.php?event_id=' + encodeURIComponent(eventId))
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // Show raw server response for debugging
                    console.error('Failed to parse JSON from get_participants.php:', text);
                    document.getElementById('participantsList').innerHTML = '<div class="participants-error"><p style="color: #dc2626; font-weight:600;">Server returned invalid JSON. See console for details.</p><pre style="white-space:pre-wrap; max-height:300px; overflow:auto; background:#f8f8f8; padding:8px; border-radius:4px;">' +
                        text.replace(/</g, '&lt;') + '</pre></div>';
                    document.getElementById('participantsModal').style.display = 'flex';
                    return;
                }

                // If the endpoint returned an error object, show it in the modal
                if (data.error) {
                    document.getElementById('participantsList').innerHTML = '<div class="participants-error"><p style="color: #dc2626; font-weight:600;">' +
                        (data.error || 'Unable to load participants') + '</p></div>';
                    document.getElementById('participantsModal').style.display = 'flex';
                    return;
                }

                let html = '<div class="participants-summary">';
                html += '<p><strong>Total Participants:</strong> ' + (data.total || 0) + '</p>';
                html += '<p><strong>Approved:</strong> ' + (data.approved || 0) + '</p>';
                html += '<p><strong>Pending:</strong> ' + (data.pending || 0) + '</p>';
                html += '</div>';

                if (!data.participants || data.participants.length === 0) {
                    html += '<p class="no-data">No participants found for this event.</p>';
                    document.getElementById('participantsList').innerHTML = html;
                    document.getElementById('participantsModal').style.display = 'flex';
                    return;
                }

                html += '<div class="participants-table">';
                html += '<table class="data-table"><thead><tr><th>Name</th><th>User ID</th><th>Class</th><th>Status</th><th>Action</th></tr></thead><tbody>';
                data.participants.forEach(p => {
                    html += '<tr>';
                    html += '<td>' + (p.name || '') + '</td>';
                    html += '<td>' + (p.user_id || '') + '</td>';
                    html += '<td>' + (p.class || '') + '</td>';
                    html += '<td><span class="badge badge-' + (p.status || 'pending') + '">' + (p.status || '') + '</span></td>';
                    html += '<td>';
                    if (p.status === 'pending') {
                        html += '<a href="?action=approve&reg_id=' + p.reg_id + '" class="btn btn-sm btn-success">Approve</a> ';
                        html += '<a href="?action=reject&reg_id=' + p.reg_id + '" class="btn btn-sm btn-danger">Reject</a>';
                    } else {
                        html += '<span style="color: #6b7280;">-</span>';
                    }
                    html += '</td></tr>';
                });

                html += '</tbody></table></div>';
                document.getElementById('participantsList').innerHTML = html;
                document.getElementById('participantsModal').style.display = 'flex';
            })
            .catch(error => {
                document.getElementById('participantsList').innerHTML = '<p style="color: #dc2626;">Error loading participants. See console for details.</p>';
                document.getElementById('participantsModal').style.display = 'flex';
                console.error('Error:', error);
            });
    } // <<<< The missing closing brace for viewParticipants!

    // 3. Modal Control Functions (Now correctly in the global scope)
    function closeParticipantsModal() {
        document.getElementById('participantsModal').style.display = 'none';
    }

    function showRegistrationModal(url) {
        pendingRegistrationUrl = url;
        document.getElementById('registrationModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('registrationModal').classList.remove('show');
        pendingRegistrationUrl = '';
    }

    function confirmRegistration() {
        if (pendingRegistrationUrl) {
            window.location.href = pendingRegistrationUrl;
        }
    }
        
        
</script>
</body>
</html>