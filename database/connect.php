<?php
function execSQL($sql) {
    $host = "localhost";
    $username = "root";
    $password = "";
    $dbname = "schoolbook";

    $mysqli = new mysqli($host, $username, $password, $dbname);

    try {
        $data = mysqli_fetch_all($mysqli->query($sql));

        if (empty($data)) {
            $_SESSION['popup_message'] = "<div class='popup error'>Empty database.</div>";
            return false;
        }
        return $data;
    }
    catch (Exception $e) {
        $_SESSION['popup_message'] = $e->getMessage();
        return false;
    }
    finally {
        $mysqli->close();
    }
}