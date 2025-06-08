<?php
include '../config.php';

$id = intval($_GET['id']); // sanitize id

// If form submitted, update the staff record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $work = mysqli_real_escape_string($conn, $_POST['work']);

    $updateSql = "UPDATE staff SET name='$name', work='$work' WHERE id=$id";
    if (mysqli_query($conn, $updateSql)) {
        header("Location: staff.php");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error updating staff: " . mysqli_error($conn) . "</div>";
    }
}

// Fetch staff data to show in the form
$sql = "SELECT * FROM staff WHERE id=$id";
$result = mysqli_query($conn, $sql);
if (mysqli_num_rows($result) !== 1) {
    die("Staff not found.");
}
$staff = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Edit Staff</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.4.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    html, body {
      height: 100%;
      margin: 0;
      background: rgb(241, 241, 246); /* Light background color */
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }

    .edit-form-container {
      background: rgb(177, 210, 249); /* White background for the form */
      border-radius: 20px;
      padding: 40px 30px;
      max-width: 500px;
      width: 100%;
      color: #333; /* Dark text color */
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Enhanced shadow effect */
    }

    h2 {
      text-align: center;
      margin-bottom: 30px;
      font-weight: 700;
      letter-spacing: 1.5px;
      color: #333; /* Dark text color */
    }

    .form-label {
      color: #555; /* Darker label color */
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .form-control {
      background: rgb(236, 236, 239); /* Light gray background */
      border-radius: 15px;
      border: none;
      padding: 12px 20px;
      font-size: 1rem;
      color: #333; /* Dark text color */
      margin-bottom: 1.5rem;
      box-sizing: border-box;
    }

    .form-control:focus {
      outline: none;
      background: #d1e7dd; /* Light green background on focus */
      color: #333; /* Dark text color */
    }

    button.btn-primary {
      background: blue; /* Blue button color */
      border: none;
      border-radius: 15px;
      padding: 12px 28px;
      font-weight: 700;
      font-size: 1rem;
      color: white; /* White text color */
      transition: all 0.3s ease;
      cursor: pointer;
    }

    button.btn-primary:hover {
      background: darkblue; /* Darker blue on hover */
    }

    a.btn-secondary {
      background: red; /* Red button color */
      color: white; 
      border-radius: 15px;
      padding: 12px 28px;
      font-weight: 600;
      font-size: 1rem;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s ease;
      margin-left: 12px;
      cursor: pointer;
    }

    a.btn-secondary:hover {
      background: darkred; /* Darker red on hover */
    }
  </style>
</head>
<body>

<div class="edit-form-container shadow-sm">
  <h2>Edit Staff</h2>
  <form method="post" action="">
    <label for="nameInput" class="form-label">Name</label>
    <input
      type="text"
      class="form-control"
      id="nameInput"
      name="name"
      value="<?php echo htmlspecialchars($staff['name']); ?>"
      required
    />
    <label for="workInput" class="form-label">Work</label>
    <select class="form-control" id="workInput" name="work" required>
      <option value="Manager" <?php echo ($staff['work'] === 'Manager') ? 'selected' : ''; ?>>Manager</option>
      <option value="Cook" <?php echo ($staff['work'] === 'Cook') ? 'selected' : ''; ?>>Cook</option>
      <option value="Helper" <?php echo ($staff['work'] === 'Helper') ? 'selected' : ''; ?>>Helper</option>
      <option value="Cleaner" <?php echo ($staff['work'] === 'Cleaner') ? 'selected' : ''; ?>>Cleaner</option>
      <option value="Waiter" <?php echo ($staff['work'] === 'Waiter') ? 'selected' : ''; ?>>Waiter</option>
    </select>
    <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem;">
      <button type="submit" class="btn btn-primary">Update</button>
      <a href="staff.php" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.4.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
