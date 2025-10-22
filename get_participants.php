
<?php
<?php
require_once 'config.php';

requireLogin();

// Start output buffering to capture any stray output
if (ob_get_level() === 0) ob_start();

// Helper to send JSON and discard any stray output (warnings/whitespace)
function send_json($payload, $context = array()) {
    // Capture any buffered output
    $buf = '';
    while (ob_get_level() > 0) {
        $buf .= ob_get_contents();
        ob_end_clean();
    }

    // If there was any stray output or a PHP error, log it for debugging
    $lastErr = error_get_last();
    if (!empty($buf) || $lastErr) {
        $logFile = __DIR__ . DIRECTORY_SEPARATOR . 'debug_participants.log';
        $log = "---- " . date('Y-m-d H:i:s') . " ----\n";
        if (isset($context['event_id'])) $log .= "event_id: " . $context['event_id'] . "\n";
        if (isset($context['user_id'])) $log .= "session_user: " . $context['user_id'] . "\n";
        if (isset($context['created_by'])) $log .= "created_by(db): " . $context['created_by'] . "\n";
        if ($lastErr) $log .= "php_error: " . print_r($lastErr, true) . "\n";
        if (!empty($buf)) $log .= "buffered_output:\n" . $buf . "\n";
        $log .= "-------------------------\n\n";
        @file_put_contents($logFile, $log, FILE_APPEND);
    }

    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

if (!isset($_GET['event_id'])) {
    send_json(array('error' => 'Invalid request'));
}

$conn = getDBConnection();
$event_id = (int)$_GET['event_id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Fetch event and check permission
$evt_stmt = $conn->prepare("SELECT created_by FROM events WHERE event_id = ?");
$evt_stmt->bind_param("i", $event_id);
$evt_stmt->execute();
$evt_res = $evt_stmt->get_result();

if (!$evt_res || $evt_res->num_rows === 0) {
    send_json(array('error' => 'Event not found'));
}

$event_data = $evt_res->fetch_assoc();

if ($event_data['created_by'] !== $user_id && !isAdmin()) {
    send_json(array('error' => 'Not authorized'));
}

$part_stmt = $conn->prepare("SELECT r.reg_id, r.status, u.user_id, u.name, u.email, u.phone, u.class
    FROM registrations r
    JOIN users u ON r.user_id = u.user_id
    WHERE r.event_id = ?
    ORDER BY r.registered_at ASC");
$part_stmt->bind_param("i", $event_id);
$part_stmt->execute();
$participants = $part_stmt->get_result();

$data = array(
    'total' => 0,
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0,
    'participants' => array()
);

if ($participants) {
    while ($p = $participants->fetch_assoc()) {
        $data['total']++;
        $status = isset($p['status']) ? $p['status'] : 'pending';
        if (!isset($data[$status])) $data[$status] = 0;
        $data[$status]++;
        $data['participants'][] = $p;
    }
}

send_json($data);
?>
                    ORDER BY r.registered_at ASC");
                $part_stmt->bind_param("i", $event_id);
                $part_stmt->execute();
                $participants = $part_stmt->get_result();

                $data = array(
                    'total' => 0,
                    'approved' => 0,
                    'pending' => 0,
                    'rejected' => 0,
                    'participants' => array()
                );

                if ($participants) {
                    while ($p = $participants->fetch_assoc()) {
                        $data['total']++;
                        $status = isset($p['status']) ? $p['status'] : 'pending';
                        if (!isset($data[$status])) $data[$status] = 0;
                        $data[$status]++;
                        $data['participants'][] = $p;
                    }
                }

                send_json($data);
                ?>