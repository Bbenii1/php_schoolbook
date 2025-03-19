<?php
/**
 * @author Szlonkai Benedek
 */

session_start();

require_once "functions.php";

?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zsírkréta</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script>
</head>
<body>
<nav>
    <a href="index.php" class="homeBtn"><i class='bx bxs-home-alt-2'></i></a>

    <a href="?subjects">Subjects</a>

    <a href="?class">Classes</a>


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

<?php
if (isset($_GET['subjects'])) {
    $subjects = execSQL("SELECT * FROM subjects");

    echo "<table id='subjectTable'> <tr><td>ID</td><td>Tantárgy</td><td><a class='addbtn' href='edit_subject.php?add'>Hozzáad</a></td></tr>";
    foreach ($subjects as $subject) {
        echo "<tr>
                <td>{$subject[0]}</td>
                <td>{$subject[1]}</td>
                <td>
                    <a href='edit_subject.php?edit={$subject[0]}' class='editBtn'><i class='bx bx-edit-alt'></i></a>
                    <a href='edit_subject.php?delete={$subject[0]}' class='deleteBtn' onclick='return confirm(\"Biztos törölni szeretnéd?\")'><i class='bx bx-trash'></i></a>
                </td>
              </tr>";
    }
    echo "</table>";
    echo "";

} elseif (isset($_GET['class'])) {
    $classes = execAssocSQL("SELECT * FROM classes");
    echo "<table id='classTale'> <tr><td>ID</td><td>Év</td><td>Osztály</td><td><a class='addbtn' href='edit_class.php?add'>Hozzáad</a></td>";
    foreach ($classes as $class) {
        echo "<tr>
                <td>$class[classID]</td>
                <td>$class[schoolYear]</td>
                <td>$class[class]</td>
                <td>
                <a href='edit_class.php?edit={$class['classID']}' class='editBtn'><i class='bx bx-edit-alt'></i></a>
                <a href='edit_class.php?delete={$class['classID']}' class='deleteBtn' onclick='return confirm(\"Biztos törölni szeretnéd?\")'><i class='bx bx-trash'></i></a>
              </tr></td>";
    };
    echo "</table>";
}
?>
</body>
</html>