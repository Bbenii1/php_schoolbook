<?php
function displayInTable($class, $classStudents): void
{
    echo "<table class='classTable'><tr><th>$class</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }

    echo "<td>Average</td></tr>";

    foreach ($classStudents as $student) {
        echo "<tr><td>" . $student[0] . " " . $student[1] . "<span>" . ($student[2]) . "</span></td>";

        /*foreach (DATA['subjects'] as $subject) {
            if (isset($student[$subject])) {
                echo "<td>" . join(', ', $student[$subject]) . "</td>";
            }
        }

        $avg = 0;
        $count = 0;

        foreach (DATA['subjects'] as $subject) {
            $avg += array_sum($student[$subject]);
            $count += count($student[$subject]);
        }

        echo "<td> " . number_format($avg / $count, 1) . " </td></tr>";*/
    }
    echo "</table>";
}

function ClassQuery($class) {
    $mysqli = new mysqli("localhost", "root", "", "classroom");

    $sql = "SELECT s.firstName AS firstName, s.lastName AS lastName, s.gender AS gender, c.class AS class
            FROM students s
            JOIN classes c ON c.classID = s.classID
            WHERE c.class LIKE '$class';";

    $data = mysqli_fetch_all($mysqli->query($sql));
    displayInTable($class, $data);

    $sql = "SELECT s.firstName, s.lastName, sub.subject, g.grade
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            JOIN students s ON s.studentID = g.studentID;";

    $data = mysqli_fetch_all($mysqli->query($sql));
    var_dump($data);

}