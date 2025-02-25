<?php

include("db_queries.php");

//create database
function CreateDatabase() : void
{
    $mysqli = connect();

    $mysqli->query("DROP DATABASE IF EXISTS schoolbook");

    $mysqli->query("CREATE DATABASE schoolbook DEFAULT CHARACTER SET utf8 COLLATE utf8_hungarian_ci");

    $mysqli -> query("USE schoolbook");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS students (
                                studentID INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
                                firstName VARCHAR(30) NOT NULL,
                                lastName VARCHAR(30) NOT NULL,
                                gender VARCHAR(30) NOT NULL,
                                classID INT NOT NULL)");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS classes (
                                classID INT NOT NULL PRIMARY KEY AUTO_INCREMENT, 
                                class VARCHAR(30) NOT NULL,
                                schoolYear year NOT NULL)");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS grades (
                                studentID INT NOT NULL, 
                                subjectID INT NOT NULL,
                                grade INT NOT NULL,
                                date DATE NOT NULL)");

    $mysqli -> query("CREATE TABLE IF NOT EXISTS subjects(
                                subjectID INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                subject varchar(30) NOT NULL )");

    $mysqli -> close();

    $_SESSION['popup_message'] = "<div class='popup'>Database created!</div>";
    header("Location: ?");
}

//upload database with data from session
function DatabaseUpload() : void
{
    $mysqli = connect();

    if (!empty(execSQL("SELECT * FROM classes"))) {
        $mysqli->close();
        $_SESSION['popup_message'] = "<div class='popup error'>Already uploaded!</div>";
        header("Location: ?");
        exit;
    }

    for ($schoolyear = 2022; $schoolyear < 2025; $schoolyear++) {
        foreach (DATA['classes'] as $class) {
            $mysqli->query("INSERT INTO classes (class, schoolYear) VALUES ('$class', '$schoolyear')");
        }
    }

    foreach (DATA['subjects'] as $subject) {
        $mysqli->query("INSERT INTO subjects (subject) VALUES ('$subject')");
    }

    $result = $mysqli->query("SELECT classID, class, schoolYear FROM classes;");
    $classIDs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $classIDs[$row['schoolYear']][$row['class']] = $row['classID'];

        }
    }

    for ($schoolyear = 2022; $schoolyear < 2025; $schoolyear++) {
        foreach ($_SESSION['schoolbook'][$schoolyear] as $key => $record) {
            /*var_dump($key, $record, $classIDs[$schoolyear][$key]);*/
            foreach ($record as $value) {
                $mysqli->query("INSERT INTO students (firstName, lastName, gender, classID) VALUES ('{$value[0]}', '{$value[1]}', '{$value[2]}', {$classIDs[$schoolyear][$key]})");
            }
        }
    }

    $result = $mysqli->query("SELECT subject, schoolbook.subjects.subjectID FROM subjects;");
    $subjectIDs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $subjectIDs[$row['subject']] = $row['subjectID'];
        }
    }

    $result = $mysqli->query("SELECT studentID, firstName, lastName, classID FROM students;");
    $students = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            /*var_dump($row);*/
            $key = $row['firstName'] . $row['lastName'] . $row['studentID'];
            $students[$key] = $row['studentID'];
        }
    }

    $index = 1;
    for ($schoolYear = 2022; $schoolYear < 2025; $schoolYear++) {
        foreach ($_SESSION['schoolbook'][$schoolYear] as $class => $record) {

            $subjects = DATA['subjects'];

            foreach ($record as $student) {
                $name = $student[0] . $student[1] . $index;
                $index++;
                foreach ($subjects as $subject) {
                    if (!isset($student[$subject])) {
                        continue;
                    }

                    foreach ($student[$subject] as $date => $grade) {

                        $studentID = $mysqli->real_escape_string($students[$name]);
                        $subjectID = $mysqli->real_escape_string($subjectIDs[$subject]);
                        $gradeValue = $mysqli->real_escape_string($grade);
                        $gradeDate = $mysqli->real_escape_string($date);

                        $query = "INSERT INTO grades (studentID, subjectID, grade, date)
                              VALUES ('$studentID', '$subjectID', '$gradeValue', '$gradeDate')";
                        $mysqli->query($query);

                        if ($mysqli->error) {
                            echo "Error: " . $mysqli->error . "<br>";
                        }
                    }
                }
            }
        }
    }

    $mysqli->close();
    $_SESSION['popup_message'] = "<div class='popup'>Database uploaded!</div>";
    header("Location: ?");
    exit;
}