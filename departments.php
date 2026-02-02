<link rel="stylesheet" href="style.css">

<?php
include 'db.php';
mysqli_report(MYSQLI_REPORT_OFF);

// Add department
if (isset($_POST['action']) && $_POST['action'] === 'add_department') {
    $dept_name = trim($_POST['name']);

    // Validation: prevent empty or spaces-only names
    if ($dept_name === '') {
        $message = "<div class='message error'>⚠ Department name cannot be empty or spaces only.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO Department (name) VALUES (?)");
        if ($stmt && $stmt->bind_param("s", $dept_name) && $stmt->execute()) {
            $message = "<div class='message success'>✅ Department added successfully.</div>";
        } else {
            $message = "<div class='message error'>⚠ Failed to add department.</div>";
        }
        $stmt->close();
    }
}

// Edit department (name + head)
if (isset($_POST['action']) && $_POST['action'] === 'edit_department') {
    $dept_id = (int)$_POST['dept_id'];
    $dept_name = trim($_POST['name']);
    $head_emp_id = (int)$_POST['head_emp_id'];

    if ($head_emp_id === 0) {
        // If "None" is selected, set head_emp_id = NULL
        $stmt = $conn->prepare("UPDATE Department SET name=?, head_emp_id=NULL WHERE dept_id=?");
        if ($stmt && $stmt->bind_param("si", $dept_name, $dept_id) && $stmt->execute()) {
            $message = "<div class='message success'>✅ Department updated successfully (Head removed).</div>";
        } else {
            $message = "<div class='message error'>⚠️ Failed to update department.</div>";
        }
        $stmt->close();
    } else {
        // Check if selected head belongs to same department
        $check = $conn->prepare("SELECT COUNT(*) FROM Employee WHERE emp_id=? AND dept_id=?");
        $check->bind_param("ii", $head_emp_id, $dept_id);
        $check->execute();
        $check->bind_result($valid);
        $check->fetch();
        $check->close();

        if ($valid > 0) {
            $stmt = $conn->prepare("UPDATE Department SET name=?, head_emp_id=? WHERE dept_id=?");
            if ($stmt && $stmt->bind_param("sii", $dept_name, $head_emp_id, $dept_id) && $stmt->execute()) {
                $message = "<div class='message success'>✅ Department updated successfully.</div>";
            } else {
                $message = "<div class='message error'>⚠️ Failed to update department.</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='message error'>⚠️ Invalid: Head must be an employee in the same department.</div>";
        }
    }
}


// Delete department
if (isset($_POST['action']) && $_POST['action'] === 'delete_department') {
    $dept_id = (int)$_POST['dept_id'];

    $stmt = $conn->prepare("DELETE FROM Department WHERE dept_id=?");
    if ($stmt && $stmt->bind_param("i", $dept_id) && $stmt->execute()) {
        $message = "<div class='message success'>✅ Department deleted successfully.</div>";
    } else {
        $message = "<div class='message error'>⚠️ Failed to delete department.</div>";
    }
    $stmt->close();
}

// Fetch departments with supervisor count
$departments = $conn->query("
    SELECT d.dept_id, d.name, d.head_emp_id,
           COUNT(e.emp_id) AS supervisor_count
    FROM Department d
    LEFT JOIN Employee e ON e.dept_id = d.dept_id AND e.is_supervisor = 1
    GROUP BY d.dept_id, d.name, d.head_emp_id
    ORDER BY d.dept_id
");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Manage Departments</title>
</head>
<body>
  <h2>Manage Departments</h2>
  <a href="index.php">Back to Dashboard</a>

  <?php if (isset($message)) echo $message; ?>

  <h3>Add New Department</h3>
  <form method="post">
    <input type="hidden" name="action" value="add_department">
    <label>Department Name:</label>
    <input type="text" name="name" required>
    <button type="submit">Add</button>
  </form>

  <h3>Department List</h3>
  <table>
    <tr><th>ID</th><th>Name</th><th>Supervisors Count</th><th>Status</th><th>Head (ID)</th><th>Actions</th></tr>
    <?php while($d = $departments->fetch_assoc()): ?>
      <tr>
        <td><?php echo $d['dept_id']; ?></td>
        <td><?php echo $d['name']; ?></td>
        <td><?php echo $d['supervisor_count']; ?></td>
        <td>
          <?php 
            if ($d['supervisor_count'] == 0) {
                echo "<span class='message error'>Invalid ❌</span>";
            } else {
                echo "<span class='message success'>Valid ✅</span>";
            }
          ?>
        </td>
        <td><?php echo $d['head_emp_id'] ? $d['head_emp_id'] : "None"; ?></td>
        <td>
          <!-- Edit -->
          <form method="post" class="edit-form">
            <input type="hidden" name="action" value="edit_department">
            <input type="hidden" name="dept_id" value="<?php echo $d['dept_id']; ?>">

            <label>Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($d['name']); ?>" required>

            <label>Head (Employee ID):</label>
            <select name="head_emp_id">
              <option value="0">-- None --</option>
              <?php
              $emps = $conn->query("SELECT emp_id FROM Employee WHERE dept_id=".$d['dept_id']);
              while($e = $emps->fetch_assoc()):
              ?>
                <option value="<?php echo $e['emp_id']; ?>" <?php if($d['head_emp_id']==$e['emp_id']) echo 'selected'; ?>>
                  <?php echo $e['emp_id']; ?>
                </option>
              <?php endwhile; ?>
            </select>

            <button type="submit">Edit</button>
          </form>

          <!-- Delete -->
          <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this department?');">
            <input type="hidden" name="action" value="delete_department">
            <input type="hidden" name="dept_id" value="<?php echo $d['dept_id']; ?>">
            <button type="submit">Delete</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
  </table>
</body>
</html>
