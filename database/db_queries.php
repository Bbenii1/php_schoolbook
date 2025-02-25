<?php
include("connect.php");
include("db_display.php");

function ClassQuery($year, $class) : void
{
    if ($class != 'all') $classFilter = "c.class LIKE '%$class[0]%' AND"; else $classFilter = "";

    /*name, class, gender*/
    $studentSQL = "SELECT s.studentID, s.firstName AS firstName, s.lastName AS lastName, s.gender AS gender, c.class AS class
            FROM students s
            JOIN classes c ON c.classID = s.classID
            WHERE $classFilter c.schoolYear = '$year'
            ORDER BY s.firstName, s.lastName DESC;";

    /*grades*/
    $gradeSQL = "SELECT s.studentID, sub.subject, GROUP_CONCAT(g.grade SEPARATOR ', ') AS grades
             FROM grades g
             JOIN subjects sub ON sub.subjectID = g.subjectID
             JOIN students s ON s.studentID = g.studentID
             JOIN classes c ON c.classID = s.classID
             WHERE $classFilter c.schoolYear = '$year'
             GROUP BY s.studentID, sub.subject
             ORDER BY s.firstName, s.lastName DESC;";

    /*student averages*/
    $sqlStudentAvg = "SELECT s.studentID, AVG(g.grade) AS student_avg
                        FROM grades g
                        JOIN students s ON s.studentID = g.studentID
                        JOIN classes c ON c.classID = s.classID
                        WHERE $classFilter c.schoolYear = '$year'
                        GROUP BY s.studentID
                        ORDER BY s.firstName, s.lastName DESC;";

    /*subject averages*/
    $sqlSubjectAvg = "SELECT sub.subject,AVG(g.grade) AS subject_avg
                        FROM grades g
                        JOIN subjects sub ON sub.subjectID = g.subjectID
                        JOIN students s ON s.studentID = g.studentID
                        JOIN classes c ON c.classID = s.classID
                        WHERE $classFilter c.schoolYear = '$year'
                        GROUP BY sub.subject
                        ORDER BY s.firstName, s.lastName DESC;";

    /*class average*/
    $sqlOverallClassAvg = "SELECT ROUND(AVG(student_avg), 2) AS overall_avg
            FROM (
                SELECT 
                    s.studentID,
                    AVG(g.grade) AS student_avg
                FROM grades g
                JOIN students s ON s.studentID = g.studentID
                JOIN classes c ON c.classID = s.classID
                WHERE $classFilter c.schoolYear = '$year'
                GROUP BY s.studentID
            ) AS student_averages;";

    $students = execSQL($studentSQL);
    $grades = execSQL($gradeSQL);
    $studentAverages = execSQL($sqlStudentAvg);
    $subjectAverages = execSQL($sqlSubjectAvg);
    $classAverage = execAssocSQL($sqlOverallClassAvg);

    if ($students === false || $grades === false || $studentAverages === false || $subjectAverages === false) {
        header("Location: ?");
    } else {
        displayInTable($class, $students, $grades, $studentAverages, $subjectAverages, $classAverage[0]);
    }
}

function getClassAverages() : void
{
    /*class averages for every subject*/
    $sql = "SELECT c.class, sub.subject, ROUND(AVG(g.grade), 2) AS subjectAverage
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            JOIN students s ON s.studentID = g.studentID
            JOIN classes c ON c.classID = s.classID
            GROUP BY c.class, sub.subject;";

    /*class averages*/
    $sql2 = "SELECT c.class, ROUND(AVG(g.grade), 2) AS overallAverage
             FROM grades g
             JOIN students s ON s.studentID = g.studentID
             JOIN classes c ON c.classID = s.classID
             GROUP BY c.class;";

    /*subject averages*/
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

function getRanking($year, $class) : void
{
    if ($class != 'all') $classFilter = "c.class LIKE '%$class[0]%' AND"; else $classFilter = "";

    /*students ranked by their averages*/
    $sql = "SELECT CONCAT(s.firstName,' ', s.lastName) AS name, sub.subject, ROUND(AVG(g.grade), 2) AS average
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            JOIN students s ON s.studentID = g.studentID
            JOIN classes c ON c.classID = s.classID
            WHERE $classFilter c.schoolYear LIKE '$year[0]'
            GROUP BY s.studentID, sub.subject
            ORDER BY 2, 3 DESC;";
    displayRankings(execAssocSQL($sql));
}

function getTop10($year) : void
{
    /*top 10 students from a year*/
    $sql = "SELECT CONCAT(s.firstName,' ', s.lastName) AS name, ROUND(AVG(g.grade), 2) AS average
            FROM grades g
            JOIN subjects sub ON sub.subjectID = g.subjectID
            JOIN students s ON s.studentID = g.studentID
            JOIN classes c ON c.classID = s.classID
            WHERE c.schoolYear = '$year[0]'
            GROUP BY s.studentID
            ORDER BY 2 DESC
            LIMIT 10;";
    displayTop10(execAssocSQL($sql));
}

function getHallOfFame() : void
{
    /*best class*/
    $topClassSQL = "SELECT c.schoolYear, c.class, ROUND(AVG(g.grade), 2) AS avg
             FROM grades g
             JOIN students s ON s.studentID = g.studentID
             JOIN classes c ON c.classID = s.classID
             GROUP by c.classID, c.schoolYear
             ORDER BY avg DESC
             LIMIT 1;";

    /*top 10 students overall*/
    $top10StudentSQL = "SELECT c.schoolYear, c.class, CONCAT(s.firstName, ' ', s.lastName) AS name, ROUND(AVG(g.grade), 2) AS avg
             FROM grades g
             JOIN students s ON s.studentID = g.studentID
             JOIN classes c ON c.classID = s.classID
             GROUP by s.studentID, c.schoolYear
             ORDER BY avg DESC
             LIMIT 10;";

    $bestClass = (execAssocSQL($topClassSQL)[0]);
    $top10Student = (execAssocSQL($top10StudentSQL));
    displayHallOfFame($bestClass, $top10Student);
}