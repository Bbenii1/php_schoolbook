<?php
session_start();
require_once "functions.php";

if (isset($_GET['edit'])) {
    $classID = intval($_GET['edit']);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newYear = $_POST['year'] ?? '';
        $newClass = $_POST['class'] ?? '';

        if (!empty($newYear) && !empty($newClass)) {

            runQuery("UPDATE classes SET schoolYear = '$newYear' WHERE classID = '$classID'");
            runQuery("UPDATE classes SET class = '$newClass' WHERE classID = '$classID'");
            header("Location: admin.php?class");
            exit();
        }
    }

    $class = execSQL("SELECT * FROM classes WHERE classID = {$classID}") ?? null;
    if (!$class) {
        die("Class not found.");
    }
}

if (isset($_GET['delete'])) {
    $classID = intval($_GET['delete']);

    runQuery("DELETE FROM classes WHERE classID = {$classID}");

    header("Location: admin.php?class");
    exit;
}

if (isset($_GET['add'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $newYear = $_POST['year'] ?? '';
        $newClass = $_POST['class'] ?? '';

        if (!empty($newYear) && !empty($newClass)) {
            runQuery("INSERT INTO classes (class, schoolyear) VALUES ('$newClass', '$newYear')");
            header("Location: admin.php?class");
            exit();
        }
    }
}

if (isset($_GET['edit'])) {
    $class = execAssocSQL("SELECT * FROM classes WHERE classID = {$_GET['edit']}") ?? null;
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

<h2><?= isset($_GET['edit']) ? "Edit classes" : "Add class" ?></h2>
<form method="post">
    <label for="name">Subject Name:</label>
    <input type="number" id="year" name="year" value="<?= isset($class) ? htmlspecialchars($class[0]['schoolYear']) : '' ?>" required>
    <input type="text" id="class" name="class" value="<?= isset($class) ? htmlspecialchars($class[0]['class']) : '' ?>" required>
    <button type="submit">Save</button>
</form>
<a href="admin.php?class">Back</a>

</body>
</html>


