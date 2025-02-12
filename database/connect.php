<?php
function connect(): mysqli
{
    $host = "localhost";
    $username = "root";
    $password = "admin";
    $dbname = "schoolbook";

    return new mysqli($host, $username, $password, $dbname);
}

function execSQL($sql) {
    $mysqli = connect();

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

function execAssocSQL($query) {
    $mysqli = connect();

    $result = $mysqli->query($query);

    if (!$result) {
        return false;
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $mysqli->close();

    return $data;
}
