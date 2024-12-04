<?php

//save the active class in session
if (isset($_GET['class'])){
    $_SESSION['CurrentClass'] = $_GET['class'];
}

//save data to file
if (isset($_GET["save"])) {
    SaveToFile();
}

//reset session
if (isset($_GET['reset'])){
    unset($_SESSION['schoolbook']);
    echo "<div class='popup success'>Students successfully reset.</div>";
}