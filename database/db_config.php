<?php

include("db_queries.php");

// set up database
if (isset($_GET['createDB'])){

    CreateDatabase();

    DatabaseUpload();

    $_SESSION['popup_message'] = "<div class='popup'>Database created!</div>";
    header("Location: ?");
}

//create database
function CreateDatabase(){
    $mysqli = new mysqli("localhost", "root", "");

    $mysqli->query("DROP DATABASE IF EXISTS classroom");

    $mysqli->query("CREATE DATABASE classroom DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");

    $mysqli -> query("USE classroom");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS students (
                                studentID INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
                                firstName VARCHAR(30) NOT NULL,
                                lastName VARCHAR(30) NOT NULL,
                                gender VARCHAR(30) NOT NULL,
                                classID INT NOT NULL)");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS classes (
                                classID INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
                                class VARCHAR(30) NOT NULL)");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS grades (
                                studentID INT NOT NULL, 
                                subjectID INT NOT NULL,
                                grade INT NOT NULL,
                                date DATE NOT NULL)");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS subjects(
                                subjectID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                subject varchar(30) NOT NULL )");

    $mysqli -> close();
}

function DatabaseUpload()
{
    if (empty($_SESSION['schoolbook'])){
        generateSchoolBook();
    }

    $mysqli = new mysqli("localhost", "root", "", "classroom");

    foreach (DATA['classes'] as $class) {
        $mysqli->query("INSERT INTO classes (class) VALUES ('$class')");
    }

    foreach (DATA['subjects'] as $subject) {
        $mysqli->query("INSERT INTO subjects (subject) VALUES ('$subject')");
    }

    $result = $mysqli->query("SELECT classID, class FROM classes;");
    $classIDs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $classIDs[$row['class']] = $row['classID'];
        }
    }

    foreach ($_SESSION['schoolbook'] as $key => $record) {
        foreach ($record as $studentkey => $value) {
            $mysqli->query("INSERT INTO students (firstName, lastName, gender, classID) VALUES ('$value[0]', '$value[1]', '$value[2]', '$classIDs[$key]')");
        }
    }

    $result = $mysqli->query("SELECT subject, classroom.subjects.subjectID FROM subjects;");
    $subjectIDs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjectIDs[$row['subject']] = $row['subjectID'];
        }
    }

    $result = $mysqli->query("SELECT studentID, firstName, lastName FROM students;");
    $students = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $key = $row['firstName'] . ' ' . $row['lastName'];
            $students[$key] = $row['studentID'];
        }
    }

    foreach ($_SESSION['schoolbook'] as $class => $record) {

        $subjects = DATA['subjects'];

        /*$subjects = ['math', 'history', 'biology', 'chemistry', 'physics', 'informatics', 'alchemy', 'astrology'];*/

        foreach ($record as $key => $student) {
            $name = $record[$key][0] . ' ' . $record[$key][1];


            foreach ($subjects as $subject) {
                if (!isset($student[$subject])) {
                    continue;
                }

                foreach ($student[$subject] as $date => $grade) {

                    $studentID = $mysqli->real_escape_string($students[$name]);
                    $subjectID = $mysqli->real_escape_string($subjectIDs[$subject]);
                    $gradeValue = $mysqli->real_escape_string($grade);
                    $gradeDate = $mysqli->real_escape_string($date);

                    $query = "
                    INSERT INTO grades (studentID, subjectID, grade, date) 
                    VALUES ('$studentID', '$subjectID', '$gradeValue', '$gradeDate')
                ";
                    $mysqli->query($query);

                    // Check for errors (optional debugging)
                    if ($mysqli->error) {
                        echo "Error: " . $mysqli->error . "<br>";
                    }
                }
            }
        }
    }
    unset($_SESSION['schoolbook']);
}