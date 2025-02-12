<?php
include("connect.php");

function displayInTable($class, $classStudents, $grades, $studentAverages, $subjectAverages): void
{
    echo "<table class='classTable'><tr><th>$class</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }

    echo "<td>Average</td></tr>";

    //Display student grades and their averages
    foreach ($classStudents as $student) {
        echo "<tr><td>" . $student[1] . " " . $student[2] . "<span>" . ($student[3]) . "</span></td>";
        foreach (DATA['subjects'] as $subject) {
            $gradeFound = false;
            foreach ($grades as $grade) {
                if ($grade[0] == $student[0] && $grade[1] == $subject) {
                    echo "<td>" . $grade[2] . "</td>";
                    $gradeFound = true;
                    break;
                }
            }
            if (!$gradeFound) {
                echo "<td>-</td>";
            }
        }

        //Display student average
        $studentAvg = "-";
        foreach ($studentAverages as $avg) {
            if ($avg[0] == $student[0]) {
                $studentAvg = number_format($avg[1], 2);
                break;
            }
        }
        echo "<td>" . $studentAvg . "</td></tr>";
    }

    //Display subject averages in a separate row
    echo "<tr><td></td>";
    foreach (DATA['subjects'] as $subject) {
        $subjectAvg = "-";
        foreach ($subjectAverages as $avg) {
            if ($avg[0] == $subject) {
                $subjectAvg = number_format($avg[1], 2);
                break;
            }
        }
        echo "<td>" . $subjectAvg . "</td>";
    }
    echo "<td>-</td></tr>";

    echo "</table>";
}

function ClassQuery($class) {
    $sql = "SELECT s.studentID, s.firstName AS firstName, s.lastName AS lastName, s.gender AS gender, c.class AS class
            FROM students s
            JOIN classes c ON c.classID = s.classID
            WHERE c.class LIKE '$class';";

    $sql2 = "SELECT
                s.studentID,
                sub.subject,
                GROUP_CONCAT(g.grade SEPARATOR ', ') AS grades
             FROM grades g
             JOIN subjects sub ON sub.subjectID = g.subjectID
             JOIN students s ON s.studentID = g.studentID
             JOIN classes c ON c.classID = s.classID
             WHERE c.class LIKE '$class'
             GROUP BY s.studentID, sub.subject;";

    $sql_student_avg = "
        SELECT 
            s.studentID,
            AVG(g.grade) AS student_avg
        FROM grades g
        JOIN students s ON s.studentID = g.studentID
        JOIN classes c ON c.classID = s.classID
        WHERE c.class LIKE '$class'
        GROUP BY s.studentID;
    ";

    $sql_subject_avg = "
        SELECT 
            sub.subject,
            AVG(g.grade) AS subject_avg
        FROM grades g
        JOIN subjects sub ON sub.subjectID = g.subjectID
        JOIN students s ON s.studentID = g.studentID
        JOIN classes c ON c.classID = s.classID
        WHERE c.class LIKE '$class'
        GROUP BY sub.subject;
    ";

    $data1 = execSQL($sql);
    $data2 = execSQL($sql2);
    $studentAverages = execSQL($sql_student_avg);
    $subjectAverages = execSQL($sql_subject_avg);

    if ($data1 === false || $data2 === false || $studentAverages === false || $subjectAverages === false) {
        header("Location: ?");
    } else {
        displayInTable($class, $data1, $data2, $studentAverages, $subjectAverages);
    }
}


function getClassAverages() {
    //osztályonkénti átlag minden tantárgyhoz
    $sql = "SELECT c.class, sub.subject, ROUND(AVG(g.grade), 2) AS subjectAverage
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            JOIN students s ON s.studentID = g.studentID
            JOIN classes c ON c.classID = s.classID
            GROUP BY c.class, sub.subject;";

    //osztályonkénti átlag
    $sql2 = "SELECT c.class, ROUND(AVG(g.grade), 2) AS overallAverage
             FROM grades g
             JOIN students s ON s.studentID = g.studentID
             JOIN classes c ON c.classID = s.classID
             GROUP BY c.class;";

    //tantárgyankénti átlag
    $sql3 = "SELECT 
                sub.subject AS subject,
                ROUND(AVG(g.grade), 2) AS subjectAverage
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            GROUP BY sub.subject;";

    if (execAssocSQL($sql) === false || execAssocSQL($sql2) === false || execAssocSQL($sql3) === false){
        header("Location: ?");
    } else {
        displayClassAverages(execAssocSQL($sql), execAssocSQL($sql2), execAssocSQL($sql3));
    }
}

function displayClassAverages($classSubjectAverages, $overallAverage, $subjectAverages): void
{
    echo "<table class='classTable'>";
    echo "<tr><th>Class</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }

    echo "<td>Average</td></tr>";

    foreach (DATA['classes'] as $class) {
        echo "<tr><td>$class</td>";
        foreach ($classSubjectAverages as $subjectAvg) {

            foreach (DATA['subjects'] as $subject){
                if ($subjectAvg['class'] == $class && $subjectAvg['subject'] == $subject){
                    echo "<td>" . $subjectAvg['subjectAverage'] . "</td>";
                }
            }
        }
        foreach ($overallAverage as $avg) {
            if ($avg['class'] == $class){
                echo "<td>" . $avg['overallAverage'] . "</td>";
            }
        }
        echo "</tr>";
    }

    echo "<tr><td></td>";
    foreach (DATA['subjects'] as $subject) {
        foreach ($subjectAverages as $avg) {
            if ($avg['subject'] == $subject){
                echo "<td>" . $avg['subjectAverage'] . "</td>";
            }
        }
    }
    echo "<td></td></tr></table>";
}

function getRanking(){
    if ($_GET['class'] == 'all'){
        $class = "%";
    } else  $class = $_GET["class"];
    $sql = "SELECT CONCAT(s.firstName,' ', s.lastName) AS name, sub.subject, ROUND(AVG(g.grade), 2) AS average
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            JOIN students s ON s.studentID = g.studentID
            JOIN classes c ON c.classID = s.classID
            WHERE c.class LIKE '$class'
            GROUP BY s.studentID, sub.subject
            ORDER BY 2, 3 DESC;";
    displayRankings(execAssocSQL($sql));
}

function displayRankings($list) {
    echo "<table class='rankingTable'>";
    echo "<tr><th>Rank</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<th>" . $subject . "</th>";
    }
    echo "</tr>";

    $rankings = [];
    foreach ($list as $record) {
        $rankings[$record['subject']][] = $record;
    }

    $maxRows = max(array_map('count', $rankings));
    for ($i = 0; $i < $maxRows; $i++) {
        echo "<tr><td>" . ($i + 1) . ".</td>";
        foreach (DATA['subjects'] as $subject) {
            if (isset($rankings[$subject][$i])) {
                $record = $rankings[$subject][$i];
                echo "<td>" . $record['name'] . " (" . $record['average'] . ")</td>";
            } else {
                echo "<td>-</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
}