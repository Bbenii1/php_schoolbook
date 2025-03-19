<?php
session_start();
require_once "functions.php";

if (isset($_GET['edit'])) {
    $subject_id = intval($_GET['edit']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_name = $_POST['name'] ?? '';

        if (!empty($new_name)) {
            var_dump($new_name);
            runQuery("UPDATE subjects SET subject = '$new_name' WHERE subjectID = '$subject_id'");
            header("Location: admin.php?subjects");
            exit();
        }
    }

    $subject = execSQL("SELECT * FROM subjects WHERE subjectID = {$subject_id}") ?? null;
    if (!$subject) {
        die("Subject not found.");
    }
}

if (isset($_GET['delete'])) {
$subject_id = intval($_GET['delete']);

runQuery("DELETE FROM subjects WHERE subjectID = {$subject_id}");

header("Location: admin.php?subjects");
exit;
}

if (isset($_GET['add'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_name = $_POST['name'] ?? '';

        if (!empty($new_name)) {
            runQuery("INSERT INTO subjects (subject) VALUES ('$new_name')");
            header("Location: admin.php?subjects");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zsírkréta</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="script.js"></script>
</head>
<body>
<nav>
    <a href="index.php" class="homeBtn"><i class='bx bxs-home-alt-2'></i></a>

    <!--Dropdown menu-->
    <div class="dropdown">
        <button class="dropbtn" onclick="menuToggle()">
            <a class="burger-menu">
                <div class="line-one"></div>
                <div class="line-two"></div>
                <div class="line-three"></div>
            </a>
        </button>
        <div class="dropdown-content">
            <a href="?createDB">Create database</a>
            <a href="?uploadDB">Upload tables</a>
            <a href="?reset">Reset students</a>
            <a href="admin.php">admin</a>
        </div>
    </div>
</nav>

<h2><?= isset($_GET['edit']) ? "Edit Subject" : "Add Subject" ?></h2>
<form method="post">
    <label for="name">Subject Name:</label>
    <input type="text" id="name" name="name" value="<?= isset($subject) ? htmlspecialchars($subject[0][1]) : '' ?>" required>
    <button type="submit">Save</button>
</form>
<a href="admin.php?subjects">Back</a>

</body>
</html>


