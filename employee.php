<link rel="stylesheet" href="style.css">

<?php
include 'db.php';
mysqli_report(MYSQLI_REPORT_OFF); // disable fatal error exceptions

// Add employee
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $dept_id = (int)$_POST['dept_id'];
    $is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;

    // Validation: prevent empty or spaces-only names
    if ($fname === '' || $lname === '') {
        $message = "<div class='message error'>⚠ First name and Last name cannot be empty or spaces only.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO Employee (fname, lname, email, dept_id, is_supervisor) VALUES (?, ?, ?, ?, ?)");
        if ($stmt && $stmt->bind_param("sssii", $fname, $lname, $email, $dept_id, $is_supervisor) && $stmt->execute()) {
            $message = "<div class='message success'>✅ Employee added successfully.</div>";
        } else {
            $message = "<div class='message error'>⚠ Failed to add employee. Please check your input.</div>";
        }
        $stmt->close();
    }
}



// Delete employee
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $emp_id = (int)$_POST['emp_id'];

    // check if employee is head of any department
    $check = $conn->prepare("SELECT COUNT(*) FROM Department WHERE head_emp_id=?");
    $check->bind_param("i", $emp_id);
    $check->execute();
    $check->bind_result($is_head);
    $check->fetch();
    $check->close();

   if ($is_head > 0) {
    // allow deletion but reset department head
    $conn->query("UPDATE Department SET head_emp_id=NULL WHERE head_emp_id=".$emp_id);
}

// proceed with deletion anyway
$stmt = $conn->prepare("DELETE FROM Employee WHERE emp_id=?");
if ($stmt) {
    $stmt->bind_param("i", $emp_id);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $message = "<div class='message success'>✅ Employee deleted successfully.</div>";
        } else {
            $message = "<div class='message error'>⚠ Employee not found or already deleted.</div>";
        }
    } else {
        $message = "<div class='message error'>⚠ MySQL Error: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

    }


// Edit employee
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $emp_id = (int)$_POST['emp_id'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $dept_id = (int)$_POST['dept_id'];
    $is_supervisor = isset($_POST['is_supervisor']) ? 1 : 0;

    $stmt = $conn->prepare("UPDATE Employee 
                            SET fname=?, lname=?, email=?, dept_id=?, is_supervisor=? 
                            WHERE emp_id=?");
    if ($stmt && $stmt->bind_param("sssiii", $fname, $lname, $email, $dept_id, $is_supervisor, $emp_id) && $stmt->execute()) {
        $message = "<div class='message success'>✅ Employee updated successfully.</div>";
    } else {
        $message = "<div class='message error'>⚠ Failed to update employee. Please check your input.</div>";
    }
    $stmt->close();
}

// Fetch departments
$departments = $conn->query("SELECT dept_id, name FROM Department ORDER BY dept_id");

// Fetch employees with head check
$employee = $conn->query("SELECT e.emp_id, e.fname, e.lname, e.email, e.dept_id, e.is_supervisor, d.name AS dept_name,
                                 CASE WHEN dept.head_emp_id = e.emp_id THEN 'Yes' ELSE 'No' END AS is_head
                          FROM Employee e
                          LEFT JOIN Department d ON d.dept_id = e.dept_id
                          LEFT JOIN Department dept ON dept.head_emp_id = e.emp_id
                          ORDER BY e.emp_id");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Employees</title>
</head>
<body>
  <h2>Manage Employees</h2>
  <a href="index.php">Back to Dashboard</a>

  <!-- Show messages -->
  <?php if (isset($message)) echo $message; ?>

  <h3>Add New Employee</h3>
  <form method="post">
    <input type="hidden" name="action" value="add">

    <label>First Name:</label>
    <input type="text" name="fname" required>

    <label>Last Name:</label>
    <input type="text" name="lname" required>

    <label>Email:</label>
    <input type="email" name="email" required>

    <label>Department:</label>
    <select name="dept_id" required>
      <?php while($d = $departments->fetch_assoc()): ?>
        <option value="<?php echo $d['dept_id']; ?>">
          <?php echo $d['dept_id']." - ".$d['name']; ?>
        </option>
      <?php endwhile; ?>
    </select>

    <label>Supervisor?</label>
    <input type="checkbox" name="is_supervisor">

    <button type="submit">Add</button>
  </form>

  <h3>Employee List</h3>
  <table>
    <tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th><th>Supervisor</th><th>Head?</th><th>Actions</th></tr>
    <?php while($e = $employee->fetch_assoc()): ?>
      <tr>
        <td><?php echo $e['emp_id']; ?></td>
        <td><?php echo $e['fname']." ".$e['lname']; ?></td>
        <td><?php echo $e['email']; ?></td>
        <td><?php echo $e['dept_name']; ?></td>
        <td><?php echo $e['is_supervisor'] ? "Yes" : "No"; ?></td>
        <td><?php echo $e['is_head']; ?></td>
        <td>
          <!-- Edit -->
          <form method="post" class="edit-form">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="emp_id" value="<?php echo $e['emp_id']; ?>">

            <div class="form-group">
              <label>First Name:</label>
              <input type="text" name="fname" value="<?php echo htmlspecialchars($e['fname']); ?>" required>
            </div>

            <div class="form-group">
              <label>Last Name:</label>
              <input type="text" name="lname" value="<?php echo htmlspecialchars($e['lname']); ?>" required>
            </div>

            <div class="form-group">
              <label>Email:</label>
              <input type="email" name="email" value="<?php echo $e['email']; ?>" required>
            </div>

            <div class="form-group">
              <label>Department:</label>
              <select name="dept_id">
                <?php
                $departments->data_seek(0);
                while($d = $departments->fetch_assoc()):
                ?>
                  <option value="<?php echo $d['dept_id']; ?>" <?php if($e['dept_id']==$d['dept_id']) echo 'selected'; ?>>
                    <?php echo $d['dept_id']." - ".$d['name']; ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>

            <div class="form-group">
              <label>Supervisor?</label>
              <input type="checkbox" name="is_supervisor" <?php echo $e['is_supervisor'] ? 'checked' : ''; ?>>
            </div>

            <button type="submit">Edit</button>
          </form>

          <!-- Delete -->
          <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this employee?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="emp_id" value="<?php echo $e['emp_id']; ?>">
            <button type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>