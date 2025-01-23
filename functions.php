<?php
/**
 * @author Szlonkai Benedek
 */

include('data.php');
require_once 'database/db_config.php';

//save the active class in session
if (isset($_GET['class'])){
    $_SESSION['CurrentClass'] = $_GET['class'];
}

//save data to file
if (isset($_GET["save"])) {
    SaveToFile();
}

//reset session
if (isset($_GET['reset'])) {
    unset($_SESSION['schoolbook']);

    //Store the popup message in the session
    $_SESSION['popup_message'] = "<div class='popup success'>Students successfully reset.</div>";
    header("Location: ?");
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
    foreach (DATA['classes'] as $class){
        for ($i = 0; $i < count($_SESSION['schoolbook'][$class]); $i++){
            $avgs = [];

            for ($s = 0; $s < count(DATA['subjects']); $s++){
                $count = rand(0, 5);
                $marks = [];
                $avg = 0;

                $startDate = strtotime("2024-01-01");
                $endDate = strtotime("2025-01-01");

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
                $_SESSION['schoolbook'][$class][$i][DATA['subjects'][$s]]= $marks;
            }
            $_SESSION['schoolbook'][$class][$i]['avg'] = $avgs;
        }
    }
}

//Create schoolbook from classes
function generateSchoolBook()
{
    $classStudents = [];

    foreach (DATA['classes'] as $class) {
        $classStudents[$class] = generateStudents(DATA['firstnames'], DATA['lastnames']);
    }

    $_SESSION['schoolbook'] = $classStudents;
    generateMarks();
}

// Calculate, store data for subject averages query
/*function subjectAverages(): array
{
    //Calculate subject averages for every class
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
}*/

// Rank students by their averages
/*function rankStudents(bool $schoolWide = false): array
{
    $subjectRankings = []; // class => subject => index => name, average
    $overallAverages = []; // name_class => averages

    foreach (DATA['classes'] as $class) {
        foreach (DATA['subjects'] as $subject) {
            foreach ($_SESSION['schoolbook'][$class] as $student) {

                // Generate unique key for each student using their name and class to prevent duplication issues
                $uniqueKey = $student[0] . " " . $student[1] . '_' . $class;

                $subjectAvg = count($student[$subject]) > 0 ? (array_sum($student[$subject]) / count($student[$subject])) : 0;

                // Add subject average to the rankings
                $subjectRankings[$class][$subject][] = [
                    'name' => "$student[0] $student[1]",
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

// Create best and worst classes by their overall average
function calcBestAndWorst(): array
{
    $subjectAverages = subjectAverages()[0];
    $minMaxResults = [];
    $overallAverages = [];

    foreach ($subjectAverages as $class => $subjects) {
        $subjectValues = array_values($subjects);
        $overallAverages[$class] = array_sum($subjectValues) / count($subjectValues);
    }

    $overallMinValue = PHP_INT_MAX;
    $overallMaxValue = PHP_FLOAT_MIN;
    $overallMinClass = '';
    $overallMaxClass = '';

    foreach ($overallAverages as $class => $overallValue) {
        if ($overallValue < $overallMinValue) {
            $overallMinValue = $overallValue;
            $overallMinClass = $class;
        }

        if ($overallValue > $overallMaxValue) {
            $overallMaxValue = $overallValue;
            $overallMaxClass = $class;
        }
    }

    // Loop through subjects to calculate min and max values
    foreach (DATA['subjects'] as $subject) {
        $minValue = PHP_INT_MAX;
        $maxValue = PHP_FLOAT_MIN;
        $minClass = '';
        $maxClass = '';

        foreach ($subjectAverages as $class => $subjects) {
            $value = floatval($subjects[$subject] ?? 0);

            if ($value < $minValue) {
                $minValue = $value;
                $minClass = $class;
            }

            if ($value > $maxValue) {
                $maxValue = $value;
                $maxClass = $class;
            }
        }

        $minMaxResults[$subject] = [
            'minValue' => number_format($minValue, 1),
            'minClass' => $minClass,
            'maxValue' => number_format($maxValue, 1),
            'maxClass' => $maxClass,
        ];
    }

    return [
        'overall' => [$overallMaxClass, number_format($overallMaxValue, 1), $overallMinClass, number_format($overallMinValue, 1)],
        'subjects' => $minMaxResults
    ];
}*/

/*
// Display simple class tables on html page
function displayInTable($class, $classStudents): void
{
    echo "<table class='classTable'><tr><th>$class</th>";

    foreach (DATA['subjects'] as $subject) {
        echo "<td>" . $subject . "</td>";
    }

    echo "<td>Average</td></tr>";

    foreach ($classStudents as $student) {
        echo "<tr><td>" . $student[0] . " " . $student[1]. "<span>" . ($student[2]) . "</span></td>";

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

        echo "<td> " . number_format($avg / $count, 1) . " </td></tr>";
    }
    echo "</table>";
}*/

// Display subject query table
/*function displaySubjectRankings(array $rankings, string $selectedClass = 'all'): void
{
    if ($selectedClass === 'all') {
        // Rank by school-wide or class
        echo "<table class='subjectQueryTable'>

                <tr><th>Rank</th>";
        */

        /*<th>Overall</th>
        var_dump($rankings);
        foreach ($rankings as $subject) {
            if ($subject !== 'overall') {
                //var_dump($subject);
                echo "<td>$subject[0]</td>";
            }
        }*/

        /*
        echo "</tr>";

        $maxRows = max(array_map('count', $rankings));

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
        // Show by class
        if (!isset($rankings[$selectedClass])) {
            $_SESSION['popup_message'] = "<div class='popup error'>Invalid class selected: $selectedClass</div>";
            header("Location: ?");
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
}*/

// Display subject averages query table
/*function displayAvgQuery($classAverage, $overallAvg, $overallSubjectAvg): void
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
}*/

// Display best and worst class query table
/*function displayBestAndWorst($array) : void
{
    // Display rows
    $header = ['Overall', ...DATA['subjects']];

    echo "<table class='bestTable'><tr><th>B & W</th>";

    foreach ($header as $subject) {
        echo "<td>$subject</td>";
    }

    echo "</tr><tr><td>Best:</td><td> {$array['overall'][0]} , " . $array['overall'][1] . "</td>";

    foreach ($array['subjects'] as $result) {
        echo "<td> {$result['maxClass']}, {$result['maxValue']} </td>";
    }

    echo "</tr><tr><td>Worst:</td><td> {$array['overall'][2]} , " . $array['overall'][3] . "</td>";

    foreach ($array['subjects'] as $result) {
        echo "<td> {$result['minClass']}, {$result['minValue']} </td>";
    }

    echo "</tr></table>";
}*/

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

// Save data (simple class query) to file (.csv)
function SaveClasses($filename, $class=null): void
{
    $filePath = "export/$filename";
    $file = fopen($filePath, 'w');

    // Define and write the header to the CSV file
    $header = ['ID','Name', 'Gender', ...DATA['subjects']];
    fputcsv($file, $header, ';', '"', '\\');

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
                "$student[0] $student[1]",                             // Name
                ($student[1] == 'women' ? 'W' : 'M'),    // Gender
                ...$grades                               // Subject grades
            ];

            // Write the student data row to the CSV file
            fputcsv($file, $studentData, ';', '"', '\\');
            $i++;
        }
    }
    fclose($file); //close file
}

// Save different data from queries
function SaveQueryData(string $filename, string $query): void
{
    if ($query === 'SubjectAverages') {

        $filePath = "export/$filename";
        $file = fopen($filePath, "w");

        [$classAverages, $overallClassAvg, $overallSubjectAvg] = subjectAverages();

        // Define and write the header to the CSV file
        $header = ['Class', ...DATA['subjects'], 'Overall'];
        fputcsv($file, $header, ';', '"', '\\');

        // Write class averages
        foreach ($classAverages as $class => $subjects) {
            $row = [$class];

            foreach (DATA['subjects'] as $subject) {
                $row[] = $subjects[$subject] ?? '-';
            }

            $row[] = $overallClassAvg[$class] ?? '-';
            fputcsv($file, $row, ';', '"', '\\');
        }

        // Write overall subject averages
        $overallRow = ['Overall'];

        foreach (DATA['subjects'] as $subject) {
            $overallRow[] = $overallSubjectAvg[$subject] ?? '-';
        }

        $overallRow[] = '-';
        fputcsv($file, $overallRow, ';', '"', '\\');
        fclose($file);
    }

    elseif ($query == 'Ranking') {
        $filePath = "export/$filename";
        $file = fopen($filePath, 'w');

        if ($_SESSION['CurrentClass'] === 'all') {
            // School-wide saving
            $rankings = rankStudents(true);

            // Define and write the header to the CSV file
            $header = ['Rank', 'Overall', ...$rankings];
            fputcsv($file, $header, ';', '"', '\\');

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

                fputcsv($file, $row, ';', '"', '\\'); // Write the row to the file
            }
        } else {
            $rankings = rankStudents();
            if (!isset($rankings[$_SESSION['CurrentClass']])) {
                /*echo "<div class='popup error'>Invalid class selected:" . $_SESSION['CurrentClass'] . "</div>";*/
                $_SESSION['popup_message'] = "<div class='popup error'>Invalid class selected:" . $_SESSION['CurrentClass'] . "</div>";
                return;
            }

            // Define and write the header to the CSV file
            $header = ['Rank', ...DATA['subjects']];
            fputcsv($file, $header, ';', '"', '\\');

            $subjects = $rankings[$_SESSION['CurrentClass']];
            $maxRows = max(array_map('count', $subjects));

            for ($i = 0; $i < $maxRows; $i++) {
                $row = [$i + 1];

                foreach ($subjects as $students) {
                    if (isset($students[$i])) {
                        $row[] = "{$students[$i]['name']} ({$students[$i]['average']})";
                    } else {
                        $row[] = '-';
                    }
                }
                fputcsv($file, $row, ';', '"', '\\'); // Write the row to the file
            }
        }
        fclose($file);
    }


    elseif ($query == "BestAndWorst") {
        $filePath = "export/$filename";
        $file = fopen($filePath, "w");

        $data = calcBestAndWorst();

        // Define and write the header to the CSV file
        $header = ['Overall', ...DATA['subjects']];
        fputcsv($file, $header, ';', '"', '\\');

        $row1[] = "{$data['overall'][0]}, {$data['overall'][1]}";
        foreach ($data['subjects'] as $subject) {
            $row1[] = "{$subject['maxClass']}, {$subject['maxValue']}";
        }

        $row2[] = "{$data['overall'][2]}, {$data['overall'][3]}";
        foreach ($data['subjects'] as $subject) {
            $row2[] = "{$subject['minClass']}, {$subject['minValue']}";
        }

        fputcsv($file, $row1, ';', '"', '\\');
        fputcsv($file, $row2, ';', '"', '\\');
        fclose($file);
    }
    else {
        $_SESSION['popup_message'] = "<div class='popup error'>Invalid query selected.</div>";
        header("Location: ?");

    }
}