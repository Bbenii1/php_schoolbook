<?php
/**
 * @author Szlonkai Benedek
 */

session_start();

require_once "functions.php";

/*$classStudents = $_SESSION['schoolbook'];*/
//var_dump($_SESSION['schoolbook']['11a'][1]);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zsírkréta</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="script.js"></script>
</head>
<body>
    <nav>
        <a href="?" class="homeBtn"><i class="fa fa-home" style="font-size:24px"></i></a>

        <?php
        $currentYear = isset($_GET['year']) ? $_GET['year'] : '';
        $currentClass = isset($_GET['class']) ? $_GET['class'] : '';

        function buildQuery($params) {
            return '?' . http_build_query(array_merge($_GET, $params));
        }
        ?>

        <!-- Year Select -->
        <select id="year-select" name="year" onchange="window.location.href=this.value;">
            <option value="<?= buildQuery(['year' => '']) ?>" <?= empty($currentYear) ? 'selected' : '' ?>>Select year</option>
            <?php for ($schoolYear = 2022; $schoolYear < 2025; $schoolYear++): ?>
                <option value="<?= buildQuery(['year' => $schoolYear]) ?>" <?= ($currentYear == $schoolYear) ? 'selected' : '' ?>>
                    <?= $schoolYear ?>
                </option>
            <?php endfor; ?>
        </select>

        <!-- Class Select -->
        <select id="class-select" name="class" onchange="window.location.href=this.value;">
            <?php $year = $_GET['year']; $classes = execSQL("SELECT DISTINCT class FROM classes WHERE schoolYear = '$year' "); echo $classes; ?>

            <option value="<?= buildQuery(['class' => '']) ?>" <?= empty($currentClass) ? 'selected' : '' ?>>Select year first</option>


            <?php if (isset($_GET['year'])): ?>
                <option value="<?= buildQuery(['class' => '']) ?>" <?= empty($currentClass) ? 'selected' : '' ?>>Select a class</option>
            <?php foreach ($classes as $class): ?>
                <option value="<?= buildQuery(['class' => $class]) ?>" <?= ($currentClass === $class) ? 'selected' : '' ?>>
                    <?= $class ?>
                </option>
            <?php endforeach; ?>
            <option value="<?= buildQuery(['class' => 'all']) ?>" <?= ($currentClass === 'all') ? 'selected' : '' ?>>All class</option> <?php endif; ?>
        </select>

        <!-- query, save, reset button -->
        <form method="GET">
            <a href="?query"><button type="button">Queries</button></a>
            <input type="hidden" name="query" value="<?= isset($_GET['query']) ? htmlspecialchars($_GET['query']) : '' ?>">
            <?php
            if ( isset($_GET['class']) && !empty($_GET['class']) ||
                (isset($_GET['query']) && $_GET['query'] == 'SubjectAverages') ||
                (isset($_GET['query']) && $_GET['query'] == 'BestAndWorst') ||
                (isset($_GET['query']) && !empty($_GET['query'])) && ($_GET['query'] == 'Ranking' && !empty($_GET['class'])))
            {
                echo '<button type="submit" name="save">Save</button>';
            }
            ?>
        </form>

        <!--Dropdown menu-->
        <div class="dropdown">
            <button class="dropbtn" onclick="menuToggle()">
                <a class="burger-menu">
                    <div class="line-one"></div>
                    <div class="line-two"></div>
                    <div class="line-three"></div>
                </a>
            </button>
            <div class="dropdown-content">
                <a href="?createDB">Create Database</a>
                <a href="?uploadDB">Upload Database</a>
                <a href="?reset">Reset students</a>
                <?php
                try {
                    $mysqli = connect();

                    echo "<span style='color: #2b812f'>connectable</span><br>";

                    $sql = "SHOW DATABASES LIKE 'schoolbook'";
                    $result = $mysqli->query($sql);

                    if ($result->num_rows > 0) {
                        echo "<span style='color: #2b812f'>database exists</span><br>";

                        $mysqli->select_db('schoolbook');

                        $tablesQuery = "SELECT TABLE_NAME 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_SCHEMA = 'schoolbook'";
                        $tablesResult = $mysqli->query($tablesQuery);

                        if ($tablesResult->num_rows > 0) {
                            $countQuery = "SELECT COUNT(*) AS rowCount FROM classes";
                            $countResult = $mysqli->query($countQuery)->fetch_assoc()['rowCount'];

                            if ($countResult > 0) {
                                echo "<span style='color: #2b812f'>database uploaded</span><br>";
                            } else {
                                echo "<span style='color: #dc3545'>database is empty</span><br>";
                            }

                        } else {
                            echo "<span style='color: #dc3545'>no tables found</span><br>";
                        }
                    } else {
                        echo "<span style='color: #dc3545'>database doesn't exist</span><br>";
                    }
                } catch (mysqli_sql_exception $e) {
                    echo "<span style='color: #dc3545'>unable to connect to database</span><br>";
                } finally {
                    if (isset($mysqli) && $mysqli->ping()) {
                        $mysqli->close();
                    }
                }
                ?>
            </div>
        </div>
    </nav>

    <div>
        <?php
        if ((isset($_GET['reset']) || !isset($_GET['class']) || $_GET['class'] == '') && !isset($_GET['query'])){
            echo "<h1>Zsírkréta</h1><br>
                  <h2>A kréta... hát, az porzik, és csak táblán jön be!</h2>";
        }

        if (!isset($_GET['query']) && isset($_GET['class']) && isset($_GET['year'])){
            $class = $_GET['class'];
            $year = $_GET['year'];
            if ($class == 'all') {
                foreach (DATA['classes'] as $class){
                    ClassQuery($year, $class);

                }
            } elseif ($class) {
                ClassQuery($year, $class);
            } else {
                /*echo "<div class='popup error'>Hibás osztály választás.</div>";*/
                $_SESSION['popup_message'] = "<div class='popup error'>Hibás osztály választás.</div>";
                header("Location: ?");
            }
        }

        if (isset($_GET['query']) && !isset($_GET['reset'])) {
            echo "<div class='querymenu'>
                      <a href='?query=SubjectAverages'><button>Subject averages</button></a>
                      <a href='?query=Ranking'><button>Ranking</button></a>
                      <a href='?query=BestAndWorst'><button>Best and worst</button></a>
                  </div>";

            if ($_GET['query'] == "SubjectAverages") {
                echo "<br> <h2>Class averages by subject and overall.</h2>";
                /*$temp = subjectAverages();*/
                /*displayAvgQuery($temp[0], $temp[1], $temp[2]);*/
                getClassAverages();
            }
            else if ($_GET['query'] == "Ranking") {
                echo "<div class='queryButton'>";

                foreach (DATA['classes'] as $class) {
                    echo "<a href=?query=Ranking&class=". $class . "><button class='$class osztaly'>" . $class . "</button></a>";
                }

                echo "<a href=?query=Ranking&class=all><button class='all osztaly'>all</button></a>";
                echo "</div> <br> <h2>Rank students by their average in class or overall.</h2>";

                if (isset($_GET['class'])) {

                    getRanking();
                }

            }
            else if ($_GET['query'] == "BestAndWorst") {
                echo "<br> <h2>The best and the weakest class by subject and overall.</h2>";
                $data = calcBestAndWorst();
                displayBestAndWorst($data);
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
                /*echo "<div class='popup'><strong>$fileName</strong> was successfully saved.</div>";*/
                $_SESSION['popup_message'] = "<div class='popup'><strong>$fileName</strong> was successfully saved.</div>";
                header("Location: ?");

            } elseif ($status === 'error') {
                /*echo "<div class='popup error'>An error occurred while saving the file. Please try again.</div>";*/
                $_SESSION['popup_message'] = "<div class='popup error'>An error occurred while saving the file. Please try again.</div>";
                header("Location: ?");
            }
        }
    ?>
</body>
</html>