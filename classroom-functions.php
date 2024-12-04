<?php
/**
 * @author Szlonkai Benedek
 */

include ('classroom-data.php');

//create schoolbook in session if it doesn't exist
if (empty($_SESSION['schoolbook'])){
    $_SESSION['schoolbook'] = generateSchoolBook();
    generateMarks();
}

//save the active class in session
if (isset($_GET['class'])){
    $_SESSION['CurrentClass'] = $_GET['class'];
}

//save data to file
if (isset($_GET["save"])) {
    SaveToFile();
}

if (isset($_GET['reset'])){
    unset($_SESSION['schoolbook']);
    echo "<div class='popup success'>Students successfully reset.</div>";

}

//generate students for classes
function generateStudents($firstnames, $lastnames, $min=10, $max = 15): array
{
    $students = [];
    for ($i = 0; $i < rand($min, $max); $i++){
        $gender = array_rand($firstnames);
        $firstName = $firstnames[$gender][array_rand($firstnames[$gender])];
        $lastName = $lastnames[array_rand($lastnames)];
        $students[] = ["$lastName $firstName" , $gender];
    }
    return $students;
}

//create schoolbook from classes
function generateSchoolBook(): array{
    $classStudents = [];
    foreach (DATA['classes'] as $class) {
        $classStudents[$class] = generateStudents(DATA['firstnames'], DATA['lastnames']);
    }
    return $classStudents;
}

//generate marks for every subject for every student
function generateMarks(): void{
    foreach (DATA['classes'] as $class){
        for ($i = 0; $i < count($_SESSION['schoolbook'][$class]); $i++){
            $avgs = [];
            for ($s = 0; $s < count(DATA['subjects']); $s++){
                $count = rand(0, 5);
                $marks = [];
                $avg = 0;
                for ($k = 0; $k < $count; $k++){
                    $r = rand(1, 5);
                    $marks[] = $r;
                    $avg += $r;
                }
                if(COUNT($marks) > 0){
                    $avg = $avg / count($marks);
                }
                $avgs[DATA['subjects'][$s]] = $avg;
                $_SESSION['schoolbook'][$class][$i][DATA['subjects'][$s]]= $marks;
            }
            $_SESSION['schoolbook'][$class][$i]['avg'] = $avgs;
        }
    }
}

//create folder, filename
function SaveToFile(): void
{
    // Check if it's a query result or a class save
    $isQuery = $_GET['query'] ?? null;
    $class = $_SESSION['CurrentClass'] == 'all' ? 'all' : $_SESSION['CurrentClass'];

    // Create "export" folder if it doesn't exist
    if (!is_dir("export")) {
        mkdir("export");
    }

    // Generate filename based on context
    if ($isQuery) {
        if ($_GET['query'] == 'ranking') {
            $filename = "{$class}_query_{$isQuery}" . date('Y-m-d_Hi') . ".csv";
        }else{
            $filename = "query_{$isQuery}" . date('Y-m-d_Hi') . ".csv";
        }
    } else {
        $filename = ($class == null) ? "all_" . date('Y-m-d_Hi') . ".csv" : $class . "_" . date('Y-m-d_Hi') . ".csv";
    }

    $filePath = "export/$filename";
    $file = fopen($filePath, 'w');

    // Handle error during file opening
    if ($file === false) {
        header("Location: index.php?status=error");
        exit;
    }

    // Write query or class data to the file
    if ($isQuery) {
        SaveQueryData($file, $isQuery);
    } else {
        DataToSave($filename, $class);
    }

    fclose($file);

    // Redirect with success
    header("Location: index.php?status=success&file=$filename");
    exit;
}

//save data to file (.csv)
function DataToSave($filename, $class=null): void{
    $filePath = "export/$filename";
    $file = fopen($filePath, 'w'); //open file

    // define and write the header to the CSV file
    $header = ['ID','Name', 'Gender', ...DATA['subjects']];
    fputcsv($file, $header, ';');
    
    foreach ($_SESSION['schoolbook'] as $currentClass => $students) {
        $i = 0;
        if ($class && $currentClass !== $class) {
            continue;  // skip this class if it doesn't match the selected class
        }

        //collect student data to array
        foreach ($students as $student) {
            $grades = [];
            foreach (DATA['subjects'] as $subject) {
                if (isset($student[$subject]) && is_array($student[$subject])) {
                    $grades[] = implode(',', $student[$subject]); //join grades with commas if multiple
                } else {
                    $grades[] = ''; //empty string if no grades
                }
            }

            $studentData = [
                $currentClass . "-$i",                 // Class
                $student[0],                             // Name
                ($student[1] == 'women' ? 'W' : 'M'),    // Gender
                ...$grades                               // Subject grades
            ];

            // write the student data row to the CSV file
            fputcsv($file, $studentData, ';');
            $i++;
        }
    }
    fclose($file); //close file
}

//display class tables on html page
function displayInTable($class, $classStudents): void
{
    echo "<table class='classTable'><th>$class</th>";
    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }
    echo "<td>Average</td>";
    foreach ($classStudents as $student) {
        echo "<tr><td>" . $student[0] . "<span>" . (($student[1] === 'women') ? 'W' : 'M') . "</span></td>";
        foreach (DATA['subjects'] as $subject) {
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
        echo "<td> " . number_format($avg / $count, 1) . " </td>";
        echo "</tr>";
    }
    echo "</tr></table>";
}

//calculate, store averages for subject averages query
function subjectAverages(): array
{
    //calculate subject averages for every class
    $classAverage = [];
    foreach (DATA['classes'] as $class) {
        foreach (DATA['subjects'] as $subject) {
            $tempSum = 0;
            $tempCount = 0;
            foreach ($_SESSION['schoolbook'][$class] as $student) {
                if (count($student[$subject]) != 0) {
                    $tempCount += count($student[$subject]);
                    $tempSum += array_sum($student[$subject]);
                }
            }
            $classAverage[$class][$subject] = number_format($tempSum / $tempCount, 1);
        }
    }

    //calculate class's overall average
    $overallClassAvg = [];
    foreach (DATA['classes'] as $class) {
        $tempSum = 0;
        $tempCount = 0;
        foreach (DATA['subjects'] as $subject) {
            $tempSum += $classAverage[$class][$subject];
            $tempCount++;
        }
        $overallClassAvg[$class] = number_format($tempSum / $tempCount, 1);
    }

    $overallSubjectAvg = [];
    foreach (DATA['subjects'] as $subject) {
        $tempSum = 0;
        $tempCount = 0;
        foreach (DATA['classes'] as $class) {
            $tempSum += $classAverage[$class][$subject];
            $tempCount++;
        }
        $overallSubjectAvg[$subject] = number_format($tempSum / $tempCount, 1);
    }

    return [$classAverage, $overallClassAvg, $overallSubjectAvg];
}

//display table for subject averages query
function displayAvgQuery($classAverage, $overallAvg, $overallSubjectAvg): void
{
    echo "<table class='avgQueryTable'><th>Averages</th>";
    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }
    echo "<td>Overall</td>";

    foreach (DATA['classes'] as $class) {
        echo "<tr><td>" . $class . "</td>";
        foreach (DATA['subjects'] as $subject) {
            echo "<td>" . $classAverage[$class][$subject] . "</td>";
        }
        echo "<td>" . $overallAvg[$class] . "</td></tr>";
    }
    echo "<tr><td></td>";
    foreach ($overallSubjectAvg as $subject) {
        echo "<td>" . $subject . "</td>";
    }
    echo "<td></td></tr></table>";
}

//rank students by their averages
function rankStudents(bool $schoolWide = false): array
{
    $subjectRankings = []; // class => subject => index => name, average
    $overallAverages = []; // name_class => averages

    foreach (DATA['classes'] as $class) {
        foreach (DATA['subjects'] as $subject) {
            foreach ($_SESSION['schoolbook'][$class] as $student) {
                // Generate unique key for each student using their name and class to prevent duplication issues
                $uniqueKey = $student[0] . '_' . $class;

                $subjectAvg = count($student[$subject]) > 0 ? (array_sum($student[$subject]) / count($student[$subject])) : 0;

                // Add subject average to the rankings
                $subjectRankings[$class][$subject][] = [
                    'name' => $student[0],
                    'class' => $class,
                    'average' => number_format($subjectAvg, 1)
                ];

                // Store overall averages
                $overallAverages[$uniqueKey][] = $subjectAvg;
            }

            // Sort rankings by subject average within the class
            usort($subjectRankings[$class][$subject], fn($a, $b) => $b['average'] <=> $a['average']);
        }
    }

    // Compute overall averages for each unique student
    $finalOverallRankings = [];
    foreach ($overallAverages as $uniqueKey => $averages) {
        // Extract student name and class from the unique key
        [$name, $class] = explode('_', $uniqueKey);

        $finalOverallRankings[] = [
            'name' => $name,
            'class' => $class,
            'average' => number_format(array_sum($averages) / count($averages), 1)
        ];
    }


    // Iskolai szintű rendezés összátlag alapján
    if ($schoolWide) {
        $schoolWideRankings = [];

        foreach (DATA['subjects'] as $subject) {
            $subjectStudents = [];
            foreach (DATA['classes'] as $class) {
                foreach ($subjectRankings[$class][$subject] as $student) {
                    $subjectStudents[] = [
                        'name' => $student['name'],
                        'class' => $student['class'],
                        'average' => $student['average']
                    ];
                }
            }

            // Tantárgyi rangsorolás
            usort($subjectStudents, fn($a, $b) => $b['average'] <=> $a['average']);
            $schoolWideRankings[$subject] = $subjectStudents;
        }

        // Összátlag szerinti rangsor
        usort($finalOverallRankings, fn($a, $b) => $b['average'] <=> $a['average']);
        $schoolWideRankings['overall'] = $finalOverallRankings;

        return $schoolWideRankings;
    }
    return $subjectRankings;
}

function displaySubjectRankings(array $rankings, string $selectedClass = 'all'): void
{
    if ($selectedClass === 'all') {
        // Iskolai szintű vagy összes osztály rangsorolás
        echo "<table class='subjectQueryTable'>";
        echo "<tr><th>Rank</th><th>Overall</th>";

        foreach (array_keys($rankings) as $subject) {
            if ($subject !== 'overall') {
                echo "<td>$subject</td>";
            }
        }
        echo "</tr>";

        $maxRows = max(array_map('count', $rankings));
        //var_dump($rankings);
        for ($i = 0; $i < $maxRows; $i++) {
            echo "<tr><td>" . ($i + 1) . ".</td>";

            if (isset($rankings['overall'][$i])) {
                $student = $rankings['overall'][$i];
                echo "<td>{$student['name']} ({$student['class']}, {$student['average']})</td>";
            } else {
                echo "<td>-</td>";
            }

            foreach ($rankings as $subject => $students) {
                if ($subject === 'overall') {
                    continue;
                }

                if (isset($students[$i])) {
                    $student = $students[$i];
                    echo "<td>{$student['name']} ({$student['class']}, {$student['average']})</td>";
                } else {
                    echo "<td>-</td>";
                }
            }

            echo "</tr>";
        }

    } else {
        // Osztály szintű megjelenítés
        if (!isset($rankings[$selectedClass])) {
            echo "<div class='popup error'>Invalid class selected: $selectedClass</div>";
            return;
        }

        $subjects = $rankings[$selectedClass];
        echo "<table class='subjectQueryTable'><tr><th class='osztaly'>$selectedClass</th>";

        foreach (DATA['subjects'] as $subject) {
            echo "<td>$subject</td>";
        }

        echo "</tr>";

        $maxRows = max(array_map('count', $subjects));

        for ($i = 0; $i < $maxRows; $i++) {
            echo "<tr><td>" . ($i + 1) . ".</td>";

            foreach ($subjects as $students) {
                if (isset($students[$i])) {
                    echo "<td>{$students[$i]['name']} ({$students[$i]['average']})</td>";
                } else {
                    echo "<td>-</td>";
                }
            }

            echo "</tr>";
        }

    }
    echo "</table>";
}

function displayBestAndWorst($array)
{
    $header = ['Overall', ...DATA['subjects']];
    echo "<table><tr>";
    foreach ($header as $subject) {
        echo "<td>$subject</td>";
    }
    echo "</tr>";

    $subjectAverages = $array[0];

    $minMaxResults = []; // To store results for each subject

// Loop through subjects to calculate min and max values
    foreach (array_keys(reset($subjectAverages)) as $subject) {
        $minValue = PHP_FLOAT_MAX;
        $maxValue = PHP_FLOAT_MIN;
        $minClass = '';
        $maxClass = '';

        foreach ($subjectAverages as $class => $subjects) {
            if (isset($subjects[$subject])) {
                $value = floatval($subjects[$subject]);

                if ($value < $minValue) {
                    $minValue = $value;
                    $minClass = $class;
                }

                if ($value > $maxValue) {
                    $maxValue = $value;
                    $maxClass = $class;
                }
            }
        }

        $minMaxResults[$subject] = [
            'minValue' => $minValue,
            'minClass' => $minClass,
            'maxValue' => $maxValue,
            'maxClass' => $maxClass,
        ];
    }

// Print results
    foreach ($minMaxResults as $subject => $result) {

        echo "<td> {$result['minValue']}, {$result['minClass']} </td>";
    }

    var_dump($minMaxResults);
}
function SaveQueryData($file, string $query): void
{
    switch ($query) {
        case 'subjectAverages':
            [$classAverages, $overallClassAvg, $overallSubjectAvg] = subjectAverages();

            // Write header
            $header = ['Class', ...DATA['subjects'], 'Overall'];
            fputcsv($file, $header, ';');

            // Write class averages
            foreach ($classAverages as $class => $subjects) {
                $row = [$class];
                foreach (DATA['subjects'] as $subject) {
                    $row[] = $subjects[$subject] ?? '-';
                }
                $row[] = $overallClassAvg[$class] ?? '-';
                fputcsv($file, $row, ';');
            }

            // Write overall subject averages
            $overallRow = ['Overall'];
            foreach (DATA['subjects'] as $subject) {
                $overallRow[] = $overallSubjectAvg[$subject] ?? '-';
            }
            $overallRow[] = '-';
            fputcsv($file, $overallRow, ';');
            break;

        case 'ranking':
            if ($_SESSION['CurrentClass'] === 'all') {
                // schoolwide saving
                $rankings = rankStudents(true);
                $header = ['Rank', 'Overall', ...array_keys($rankings)];
                fputcsv($file, $header, ';');

                $maxRows = max(array_map('count', $rankings));
                for ($i = 0; $i < $maxRows; $i++) {
                    $row = [$i + 1]; // Add the rank

                    // Add overall data
                    if (isset($rankings['overall'][$i])) {
                        $student = $rankings['overall'][$i];
                        $row[] = "{$student['name']} ({$student['class']}, {$student['average']})";
                    } else {
                        $row[] = '-';
                    }

                    // Add subject-specific data
                    foreach ($rankings as $subject => $students) {
                        if ($subject === 'overall') {
                            continue;
                        }

                        if (isset($students[$i])) {
                            $student = $students[$i];
                            $row[] = "{$student['name']} ({$student['class']}, {$student['average']})";
                        } else {
                            $row[] = '-';
                        }
                    }

                    fputcsv($file, $row, ';'); // Write the row to the file
                }
            } else {
                // Class-specific saving
                $rankings = rankStudents();
                if (!isset($rankings[$_SESSION['CurrentClass']])) {
                    echo "<div class='popup error'>Invalid class selected:" . $_SESSION['CurrentClass'] . "</div>";
                    return;
                }

                $header = ['Rank', ...DATA['subjects']]; // Add subjects as headers
                fputcsv($file, $header, ';'); // Write the header row

                $subjects = $rankings[$_SESSION['CurrentClass']];
                $maxRows = max(array_map('count', $subjects));

                for ($i = 0; $i < $maxRows; $i++) {
                    $row = [$i + 1]; // Add the rank

                    foreach ($subjects as $students) {
                        if (isset($students[$i])) {
                            $row[] = "{$students[$i]['name']} ({$students[$i]['average']})";
                        } else {
                            $row[] = '-';
                        }
                    }
                    fputcsv($file, $row, ';'); // Write the row to the file
                }
            }
            break;
        case "bestnworst":


        default:
            fwrite($file, "No data for this query.\n");
            break;
    }
}

