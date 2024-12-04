<?php
/**
 * @author Szlonkai Benedek
 */

require_once "classroom-functions.php";

function getData(){
    return DATA;
}

if (isset($_POST['reset_session'])) {
    session_unset();
    $_SESSION['schoolbook'] = generateSchoolBook();
}