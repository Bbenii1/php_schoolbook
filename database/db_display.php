<?php
function displayInTable($class, $classStudents, $grades, $studentAverages, $subjectAverages, $classAverage): void
{
    if (is_array($class)) echo "<table class='classTable'><tr><th>$class[0]</th>"; else echo "<table class='classTable'><tr><th>$class</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }
    echo "<td>Average</td></tr>";

    /*display student grades and their averages*/
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

        /*display student average*/
        $studentAvg = "-";
        foreach ($studentAverages as $avg) {
            if ($avg[0] == $student[0]) {
                $studentAvg = number_format($avg[1], 2);
                break;
            }
        }
        echo "<td>" . $studentAvg . "</td></tr>";
    }

    /*display subject averages in a separate row*/
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
    echo "<td>$classAverage[overall_avg]</td></tr></table>";
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

function displayRankings($list) : void
{
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

function displayTop10($list) : void
{
    echo "<table class='top10Table'>";
    echo "<tr><th colspan='2'>" . $_GET['year'][0] . " Top10</th></tr>";
    foreach ($list as $record) {
        echo "<tr><td>" . $record['name'] . "</td><td>" . $record['average'] . "</td></tr>";
    }
    echo "</table>";
}

function displayHallOfFame($topClass, $topStudents) : void
{
    echo "<table class='topClassTable'> <tr><th colspan='3'>Top class</th></tr>
            <tr><td>" . $topClass['schoolYear'] . "</td><td>" . $topClass['class'] . "</td><td>" . $topClass['avg'] . "</td></tr></table>";

    echo "<table class='topStudentsTable'><tr><th colspan='3'>Top students</th>";
    foreach ($topStudents as $student) {
        echo "<tr><td>". $student['name'] ."</td><td>". $student['schoolYear'] . ", ". $student['class'] ."</td><td>". $student['avg'] ."</td></tr>";
    }
    echo "</table>";
}