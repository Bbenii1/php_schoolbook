<?php
include("connect.php");

function displayInTable($class, $classStudents, $grades): void
{
    echo "<table class='classTable'><tr><th>$class</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }

    echo "<td>Average</td></tr>";

    foreach ($classStudents as $student) {
        echo "<tr><td>" . $student[1] . " " . $student[2] . "<span>" . ($student[3]) . "</span></td>";
            foreach (DATA['subjects'] as $subject) {
                foreach ($grades as $grade) {
                    if ($grade[0] == $student[0] && $grade[1] == $subject) {

                        echo "<td>" . $grade[2] . "</td>";

                    }
                }
            }
        echo "</tr>";
    }
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

    $data1 = execSQL($sql);
    $data2 = execSQL($sql2);

    if ($data1 === false || $data2 === false) {
        header("Location:?");
    } else {
        displayInTable($class, $data1, $data2);
    }

    /*$data = mysqli_fetch_all($mysqli->query($sql));*/
    /*var_dump(execSQL($sql), execSQL($sql2));*/
}