<?php
/**
 * @author Szlonkai Benedek
 */

include('data.php');
include 'database/db_config.php';

//save the active class in session
if (isset($_GET['class'])){
    $_SESSION['CurrentClass'] = $_GET['class'];
}

if (!isset($_SESSION['schoolbook'])) {
    generateSchoolBook();
}

// set up database
if (isset($_GET['createDB'])){
    CreateDatabase();
}

// upload database
if (isset($_GET['uploadDB'])){
    DatabaseUpload();
}

//reset students
if (isset($_GET['reset'])) {
    unset($_SESSION['schoolbook']);

    CreateDatabase();
    generateSchoolBook();

    $_SESSION['popup_message'] = "<div class='popup success'>Students successfully reset.</div>";
    header("Location: ?");
    exit;
}

//display popup message stored in session, then delete
if (isset($_SESSION['popup_message'])) {
    echo $_SESSION['popup_message'];
    unset($_SESSION['popup_message']);
}

// Generate students for each class
function generateStudents($firstnames, $lastnames, $min=10, $max = 15): array
{
    $students = [];

    for ($i = 0; $i < rand($min, $max); $i++){
        $gender = array_rand($firstnames);
        $firstName = $firstnames[$gender][array_rand($firstnames[$gender])];
        $lastName = $lastnames[array_rand($lastnames)];
        $students[] = [$lastName, $firstName, ($gender === 'men') ? 'M' : 'W'];
    }
    return $students;
}

// Generate marks for every subject for every student
function generateMarks(): void
{
    for ($schoolYear = 2022; $schoolYear < 2025; $schoolYear++) {
        foreach (DATA['classes'] as $class){
            for ($i = 0; $i < count($_SESSION['schoolbook'][$schoolYear][$class]); $i++){
                $avgs = [];

                for ($s = 0; $s < count(DATA['subjects']); $s++){
                    $count = rand(3, 5);
                    $marks = [];
                    $avg = 0;

                    $startDate = strtotime($schoolYear . "-01-01");
                    $endDate = strtotime($schoolYear + 1 . "-01-01");

                    for ($k = 0; $k < $count; $k++) {
                        $r = rand(1, 5);
                        $randomTimestamp = rand($startDate, $endDate);
                        $randomDate = date("Y-m-d", $randomTimestamp);

                        $marks[$randomDate] = $r;
                        $avg += $r;
                    }

                    if(COUNT($marks) > 0){
                        $avg = $avg / count($marks);
                    }

                    $avgs[DATA['subjects'][$s]] = $avg;
                    $_SESSION['schoolbook'][$schoolYear][$class][$i][DATA['subjects'][$s]]= $marks;
                }
                $_SESSION['schoolbook'][$schoolYear][$class][$i]['avg'] = $avgs;
            }
        }
    }
}

//Create schoolbook from classes
function generateSchoolBook()
{
    $classStudents = [];

    for ($schoolYear = 2022; $schoolYear < 2025; $schoolYear++) {
        foreach (DATA['classes'] as $class) {
            $classStudents[$schoolYear][$class] = generateStudents(DATA['firstnames'], DATA['lastnames']);
        }
    }

    $_SESSION['schoolbook'] = $classStudents;
    generateMarks();
}

// Create filename, and call saving functions
function SaveToFile(): void
{
    // Check if it's a query result or a class
    $isQuery = $_GET['query'] ?? null;
    $class = $_SESSION['CurrentClass'] == 'all' ? null : $_SESSION['CurrentClass'];

    // Create "export" folder if it doesn't exist
    if (!is_dir("export")) {
        mkdir("export");
    }

    // Generate filename based on context
    if ($isQuery) {
        if ($_GET['query'] == 'Ranking') {
            $filename = "{$class}Query{$isQuery}_" . date('Y-m-d_Hi') . ".csv";

            if ($_SESSION['CurrentClass'] == 'all') {
                $filename = "AllQuery{$isQuery}_" . date('Y-m-d_Hi') . ".csv";
            }

        }else{
            $filename = "Query{$isQuery}_" . date('Y-m-d_Hi') . ".csv";
        }
    } else {
        $filename = ($class == null) ? "All_" . date('Y-m-d_Hi') . ".csv" : $class . "_" . date('Y-m-d_Hi') . ".csv";
    }

    // Write query or class data to the file
    if ($isQuery) {
        SaveQueryData($filename, $isQuery);
    } else {
        SaveClasses($filename, $class);
    }

    // Redirect with success
    header("Location: index.php?status=success&file=$filename");
    exit;
}