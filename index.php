<?php
/**
 * @author Szlonkai Benedek
 */

session_start();

require_once "functions.php";

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

        /*make redirect links*/
        function buildQuery($params) {
            if (!isset($_GET['query'])) return '?' . http_build_query(array_merge($_GET, $params));
            else return '?' . http_build_query(array_merge($params));
        }

        function buildQueryString($params) {
            return '?' . http_build_query(array_merge($_GET, $params));
        }

        $dbempty = execSQL("SELECT * FROM classes;");
        if(empty(execSQL("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'schoolbook' AND TABLE_ROWS = 0;")) && empty($dbempty)) : ?>

        <a href="?createDB" >Create database</a>

        <?php elseif (connect() && empty($dbempty)) : ?>
            <a href="?uploadDB" >Upload tables</a>
        <?php else : ?>

        <!-- Year Select -->
        <select id="year-select" name="year" onchange="window.location.href=this.value;">
            <?php $years = execSQL("SELECT DISTINCT schoolYear FROM classes"); ?>
            <?php if (!empty($years) && !isset($_GET['year'])) : ?>
                <option value="<?= buildQuery(['year' => '']) ?>" <?= empty($currentYear) ? 'selected' : '' ?>>Year</option>
            <?php endif; ?>

            <?php if (!empty($years)) : foreach($years as $year): ?>
                <option value="<?= buildQuery(['year' => $year[0]]) ?>" <?= ($currentYear == $year[0]) ? 'selected' : '' ?>><?= $year[0] ?></option>
            <?php  endforeach; else :?>
                <option value="?" selected="selected">DB error</option>
            <?php endif; ?>
        </select>

        <!-- Class Select -->
        <?php if (isset($_GET['year']) && !isset($_GET['query'])): ?>
        <select id="class-select" name="class" onchange="window.location.href=this.value;">
            <?php $year = $_GET['year']; $classes = execSQL("SELECT class FROM classes WHERE schoolYear = '$year' "); ?>

            <?php if(!isset($_GET['class']) && !empty($classes)): ?>
                <option value="" selected="selected"><?= "Select class"?></option>
            <?php elseif (!isset($_GET['class']) && empty($classes)):;?>
                <option value="" selected="selected"><?= "No classes"?></option>
            <?php endif; ?>

            <?php foreach ($classes as $class):?>
                <option value="<?= buildQuery(['class' => $class]) ?>" <?= ($currentClass === $class) ? 'selected' : '' ?>><?php echo $class[0] ?></option>
            <?php endforeach; ?>

            <?php if ( !empty($classes)): ?>
                <option value="<?= buildQuery(['class' => 'all']) ?>" <?= ($currentClass === 'all') ? 'selected' : '' ?>>All class</option>
            <?php endif; ?>
        </select>
        <?php endif; ?>

        <!-- query, save, reset button -->
        <form method="GET">
            <a href="?query"><button type="button">Queries</button></a>
        </form>
        <?php endif; ?>

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
                <a href="?createDB">Create database</a>
                <a href="?uploadDB">Upload tables</a>
                <a href="?reset">Reset students</a>
            </div>
        </div>
    </nav>

    <div>
        <?php
        /*home page*/
        if ((isset($_GET['reset']) || !isset($_GET['class']) || $_GET['class'] == '') && !isset($_GET['query'])){
            echo "<h1>Zsírkréta</h1>";
        }

        /*normal class selection*/
        if (!isset($_GET['query']) && isset($_GET['class']) && isset($_GET['year'])){
            $class = $_GET['class'];
            $year = $_GET['year'];
            if ($class){
                ClassQuery($year, $class);
            } else {
                $_SESSION['popup_message'] = "<div class='popup error'>Hibás osztály választás.</div>";
                header("Location:?");
            }
        }

        /*queries*/
        if (isset($_GET['query']) && !isset($_GET['reset'])) {
            echo "<div class='querymenu'>
                      <a href='?query=Ranking'><button>Ranking</button></a>
                      <a href='?query=Top10'><button>Top 10</button></a>
                      <a href='?query=HallOfFame'><button>Hall of Fame</button></a>
                  </div>";

            switch ($_GET['query']) {
                case 'SubjectAverages':
                    echo "<br> <h2>Class averages by subject and overall.</h2>";
                    getClassAverages();
                    break;

                case 'Ranking':
                    echo "<div class='queryButton'>";
                    ?>
                    <!--ranking year selection-->
                    <select id="year-select" name="year" onchange="window.location.href=this.value;">
                        <?php $year = $_GET['year']; $years = execSQL("SELECT DISTINCT schoolYear FROM classes"); ?>

                        <?php if(!isset($_GET['year'])): ?>
                            <option value="" selected="selected"><?= "Select year"?></option>
                        <?php endif;?>

                        <?php foreach ($years as $year):?>
                            <option value="<?= buildQueryString(['year' => $year]) ?>" <?= ($currentYear === $year) ? 'selected' : '' ?>>
                                <?php echo $year[0] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!--ranking class selection-->
                    <?php if(isset($_GET['year']) && isset($_GET['query']) && $_GET['query'] == 'Ranking'): ?>
                        <select id="class-select" name="class" onchange="window.location.href=this.value;">
                            <?php $year = $_GET['year'][0]; $classes = execSQL("SELECT class FROM classes WHERE schoolYear = '$year'");?>

                            <?php if(!isset($_GET['class'])): ?>
                                <option value="" selected="selected"><?= "-"?></option>
                            <?php endif;?>

                            <?php foreach ($classes as $key => $class):?>
                                <option value="<?= buildQueryString(['class' => $class]) ?>" <?= ($currentClass === $class) ? 'selected' : '' ?>>
                                    <?php echo $classes[$key][0]?>
                                </option>
                            <?php endforeach; ?>

                            <option value="<?= buildQueryString(['class' => 'all']) ?>" <?= ($currentClass === 'all') ? 'selected' : '' ?>>All class</option>
                        </select>
                    <?php endif; ?>

                    <?php
                    echo "</div>";

                    /*get and display ranking*/
                    if (isset($_GET['class']) && isset($_GET['year'])) getRanking($_GET['year'], $_GET['class']);
                    break;

                case 'Top10':
                    echo "<div class='queryButton'>"; ?>
                    <!--top10 query year selection-->
                    <select id="year-select" name="year" onchange="window.location.href=this.value;">
                        <?php $year = $_GET['year']; $years = execSQL("SELECT DISTINCT schoolYear FROM classes"); ?>

                        <?php if(!isset($_GET['year'])): ?>
                            <option value="" selected="selected"><?= "Select year"?></option>
                        <?php endif;?>

                        <?php foreach ($years as $year):?>
                            <option value="<?= buildQueryString(['year' => $year]) ?>" <?= ($currentYear === $year) ? 'selected' : '' ?>>
                                <?php echo $year[0] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php
                    echo "</div>";

                    if (isset($_GET['year'])) getTop10($_GET['year']);

                    break;

                case 'HallOfFame':
                    getHallOfFame();
                    break;
            };
        }?>
    </div>
</body>
</html>