<?php
include 'db.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=subscribers.csv');

$output = fopen('php://output', 'w');

// Header row
fputcsv($output, ['email_id', 'email', 'date_subscribed']);

// Fetch all data
$result = $conn->query("SELECT email_id, email, date_subscribed FROM Email_List ORDER BY email_id");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>
