<link rel="stylesheet" href="style.css">

<?php
include 'db.php';
// Import CSV
if (isset($_POST['action']) && $_POST['action'] === 'import') {
  if (is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
    $file = fopen($_FILES['csv_file']['tmp_name'], "r");
    // Skip header row if exists
    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
      $email = $data[0];
      $date = $data[1];
      if (!empty($email) && !empty($date)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO Email_List (email, date_subscribed) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $date);
        $stmt->execute();
        $stmt->close();
      }
    }
    fclose($file);
    echo "<div class='msg'>CSV imported successfully!</div>";
  } else {
    echo "<div class='msg'>Error: No file uploaded.</div>";
  }
}


// Add email
if (isset($_POST['action']) && $_POST['action'] === 'add') {
  $email = $_POST['email'];
  $date = $_POST['date_subscribed'];
  $stmt = $conn->prepare("INSERT INTO Email_List (email, date_subscribed) VALUES (?, ?)");
  $stmt->bind_param("ss", $email, $date);
  $stmt->execute();
  $stmt->close();
}

// Edit email
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
  $email_id = (int)$_POST['email_id'];
  $email = $_POST['email'];
  $date = $_POST['date_subscribed'];
  $stmt = $conn->prepare("UPDATE Email_List SET email=?, date_subscribed=? WHERE email_id=?");
  $stmt->bind_param("ssi", $email, $date, $email_id);
  $stmt->execute();
  $stmt->close();
}

// Delete email
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
  $email_id = (int)$_POST['email_id'];
  $conn->query("DELETE FROM Email_List WHERE email_id=".$email_id);
}

// Fetch emails
$emails = $conn->query("SELECT * FROM Email_List ORDER BY email_id");
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Manage Email List</title></head>
<body>
<h2>Manage Email List</h2>
<a href="index.php">Back to Dashboard</a>

<h3>Import Subscribers from CSV</h3>
<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="action" value="import">
  <input type="file" name="csv_file" accept=".csv" required>
  <button type="submit">Import</button>
</form>

<h3>Add Subscriber</h3>
<form method="post">
  <input type="hidden" name="action" value="add">
  Email: <input type="email" name="email" required>
  Date: <input type="date" name="date_subscribed" required>
  <button type="submit">Add</button>
</form>

<h3>Export All Subscribers</h3>
<form method="post" action="export_csv.php">
  <button type="submit">Download CSV</button>
</form>

<h3>Subscribers</h3>
<table border="1">
<tr><th>ID</th><th>Email</th><th>Date Subscribed</th><th>Actions</th></tr>
<?php while($s = $emails->fetch_assoc()): ?>
<tr>
  <td><?php echo $s['email_id']; ?></td>
  <td><?php echo $s['email']; ?></td>
  <td><?php echo $s['date_subscribed']; ?></td>
  <td>

    <!-- Edit -->
    <form method="post" stle="display:inline;">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="email_id" value="<?php echo $s['email_id']; ?>">
      Email: <input type="email" name="email" value="<?php echo $s['email']; ?>">
      Date: <input type="date" name="date_subscribed" value="<?php echo $s['date_subscribed']; ?>">
      <button type="submit">Edit</button>
    </form>
    <!-- Delete -->
    <form method="post" style="display:inline;" onsubmit="return confirmDelete();">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="email_id" value="<?php echo $s['email_id']; ?>">
      <button type="submit">Delete</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>
</table>
<script src="script.js"></script>
</body>
</html>
