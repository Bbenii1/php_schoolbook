<?php
/**
 * @author Szlonkai Benedek
 */
include ('classroom-data.php');




if (!isset($_GET["save"]) && isset($_GET['class'])){
    //save current class in session
    $_SESSION['CurrentClass'] = $_GET['class'];
}
elseif (isset($_GET["save"])) {
    //save the data to file
    SaveToFile();
}
if (isset($_GET['reset'])){
    unset($_SESSION['schoolbook']);
    echo "<div class='popup success'>Students successfully reset.</div>";

}
//create schoolbook in session if doesn't exist
if (empty($_SESSION['schoolbook'])){
    $_SESSION['schoolbook'] = generateSchoolBook();
    generateMarks();
}

//generate students for classes
function generateStudents($firstnames, $lastnames, $min=10, $max = 15){
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
function generateSchoolBook(){
    $classStudents = [];
    foreach (DATA['classes'] as $class) {
        $classStudents[$class] = generateStudents(DATA['firstnames'], DATA['lastnames']);
    }
    return $classStudents;
}

//generate marks for every subject for every student
function generateMarks(){
    foreach (DATA['classes'] as $class){
        for ($i = 0; $i < count($_SESSION['schoolbook'][$class]); $i++){
            for ($s = 0; $s < count(DATA['subjects']); $s++){
                $count = rand(0, 5);
                $marks = [];
                for ($k = 0; $k < $count; $k++){
                    $marks[] = rand(1, 5);
                }
                $_SESSION['schoolbook'][$class][$i][DATA['subjects'][$s]]= $marks;
            }
        }
    }
}

//create folder, filename
function SaveToFile() {
    $class = $_SESSION['CurrentClass'];

    // create "export" folder if doesn't exists
    if (!is_dir("export")) {
        mkdir("export");
    }

    // generate the filename
    $filename = "{$class}_" . date('Y-m-d_Hi') . ".csv";
    $filePath = "export/{$filename}";
     
    $file = fopen($filePath, 'w');

    //redirect error
    if ($file === false) {
        header("Location: index.php?class=$class&status=error");
        exit;
    }

    //save file
    if ($file !== false) {

        if ($class == 'all'){
            DataToSave($filename, null);
        }else {
            DataToSave($filename, $class);
        }

    fclose($file);

    // redirect with a success message
    header("Location: index.php?class=$class&status=success&file=$filename");
    exit;
    }
}

//save data to file (.csv)
function DataToSave($filename, $class=null){
    $filePath = "export/{$filename}";
    $file = fopen($filePath, 'w'); //open file

    // define and write the header to the CSV file
    $header = ['ID','Name', 'Gender', 'Math', 'History', 'Biology', 'Chemistry', 'Physics', 'Informatics', 'Alchemy', 'Astrology'];      
    fputcsv($file, $header, ';');
    
    foreach ($_SESSION['schoolbook'] as $currentClass => $students) {
        $i = 0;
        if ($class && $currentClass !== $class) {
            continue;  // skip this class if it doesn't match the selected class
        }

        //collect student data to array
        foreach ($students as $student) {
            $grades = [];
            $subjects = ['math', 'history', 'biology', 'chemistry', 'physics', 'informatics', 'alchemy', 'astrology'];

            foreach ($subjects as $subject) {
                if (isset($student[$subject]) && is_array($student[$subject])) {
                    $grades[] = implode(',', $student[$subject]); //join grades with commas if multiple
                } else {
                    $grades[] = ''; //empty string if no grades
                }
            }

            $studentData = [
                $currentClass . "-{$i}",                 // Class
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