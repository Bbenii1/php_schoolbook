<?php
/**
 * @author Szlonkai Benedek
 */
require_once ("classroom-helper.php");

if (empty($_SESSION['schoolbook'])){
    $_SESSION['schoolbook'] = generateSchoolBook();
    generateMarks();
}

$classStudents = $_SESSION['schoolbook'];
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zsírkréta</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav>
        <a href="?class=all"><button>All class</button></a>
        <?php foreach (DATA['classes'] as $class): ?>
            <a href="?class=<?= $class ?>"><button class="osztaly"><?= $class ?></button></a>
        <?php endforeach; ?>
        <!-- query, save, reset button -->
        <form method="GET" class="inline-form">
            <a href="?query"><button type="button">Query</button></a>
            <input type="hidden" name="query" value="<?= isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '' ?>">

            <button type="submit" name="save">Save</button>

            <button type="submit" name="reset">Reset students</button>
        </form>
    </nav>
    <div>
        <?php
        if ((isset($_GET['reset']) || !isset($_GET['class']) || $_GET['class'] == '') && !isset($_GET['query'])){
            echo "<h1>Zsírkréta</h1><br>
                  <h2>Válasszon osztályt!</h2>";
        }
        if (!isset($_GET['query'])){
            if (isset($_GET['class'])) {
                $class = $_GET['class'];
                if ($class == 'all') {
                    foreach (DATA['classes'] as $class){
                        displayInTable($class, $classStudents[$class]);
                    }
                } elseif (array_key_exists($class, $_SESSION['schoolbook'])) {
                    displayInTable($class, $classStudents[$class]);
                } else {
                    echo "<div class='popup error'>Hibás osztály választás.</div>";
                }
            }
        }

        if (isset($_GET['query'])) {
            echo "<div class='querymenu'>
                      <a href='?query=subjectAverages'><button>Subject averages</button></a>
                      <a href='?query=ranking'><button>Ranking</button></a>
                      <a href='?query=bestnworst'><button>Best and worst</button></a>
                  </div>";

            if ($_GET['query'] == "subjectAverages") {
                echo "<br> <h2>Class averages by subject and overall.</h2>";
                $temp = subjectAverages();
                displayAvgQuery($temp[0], $temp[1], $temp[2]);

            }
            else if ($_GET['query'] == "ranking") {
                echo "<div class='queryButton'>";
                foreach (DATA['classes'] as $class) {
                    echo "<a href=?query=ranking&class=". $class . "><button class='$class osztaly'>" . $class . "</button></a>";
                }
                echo "<a href=?query=ranking&class=all><button class='all osztaly'>all</button></a>";
                echo "</div> <br> <h2>Rank students by their average in class or overall.</h2>";

                if (isset($_GET['class'])) {
                    $class = $_GET['class'] ?? 'all';
                    $rankings = ($_GET['class'] == 'all') ? rankStudents(true): rankStudents();
                    displaySubjectRankings($rankings, $class);
                }

            }
            else if ($_GET['query'] == "bestnworst") {
                echo "<br> <h2>The best and the weakest class by subject and overall.</h2>";
                $ranking = subjectAverages();
                displayBestAndWorst($ranking);
            }
        }

        ?>
    </div>
    
    <?php
        //check for 'status' parameter in the URL
        if (isset($_GET['status'])) {
            $status = $_GET['status'];

            //display different popup messages based on save status
            if ($status === 'success' && isset($_GET['file'])) {
                $fileName = htmlspecialchars($_GET['file']);
                echo "<div class='popup'><strong>$fileName</strong> was successfully saved.</div>";
            } elseif ($status === 'error') {
                echo "<div class='popup error'>An error occurred while saving the file. Please try again.</div>";
            }
        }
    ?>

    <!--<footer>Created by Szlonkai Benedek</footer>-->
</body>
</html>