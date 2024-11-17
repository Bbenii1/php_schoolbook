<?php
/**
 * @author Szlonkai Benedek
 */
require_once ("classroom-data.php");
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
        <!-- save, reset button -->
        <form method="GET">
            <button type="submit" name="save" >Save</button>
            <button class="reset" type="submit" name="reset">Reset students</button>
        </form>
        
    </nav>
    <div>
        <?php
        if (isset($_GET['reset']) or (isset($_GET['class']) ? $_GET['class'] === '' : '')){
            echo "<h1>Zsírkréta</h1><br><h2>Válasszon osztályt!</h2>";
        }elseif (!isset($_GET['class'])){
            echo "<h1>Zsírkréta</h1><br><h2>Válasszon osztályt!</h2>";
        }
        if (isset($_GET['class'])) {
            $class = $_GET['class'];
            if ($class === 'all') {
                foreach (DATA['classes'] as $class){
                    echo "<table><th>$class</th>";
                    foreach(DATA['subjects'] as $subject){
                        echo "<td>". $subject ."</td>";
                    }
                    $i = 0;
                    foreach ($classStudents[$class] as $student) {
                        echo "<tr><td>" . $student[0] ."<span>". (($student[1] === 'women') ? 'W' : 'M'). "</span></td>";

                        foreach (DATA['subjects'] as $subject){
                            if (isset($student[$subject])){
                                echo "<td>" . join(', ', $student[$subject])."</td>";
                            }
                        }
                        echo "</tr>";
                        $i++;
                    }
                    echo "</tr></table>";
                }
            } elseif (array_key_exists($class, $_SESSION['schoolbook'])) {
                echo "<table><th>$class</th>";
                foreach(DATA['subjects'] as $subject){
                    echo "<td>". $subject ."</td>";
                }

                $i = 0;
                foreach ($classStudents[$class] as $student) {
                    echo "<tr><td>" . $student[0] ."<span>". (($student[1] === 'women') ? 'W' : 'M'). "</span></td>";

                    foreach (DATA['subjects'] as $subject){
                        if (isset($student[$subject])){
                            echo "<td>" . join(', ', $student[$subject])."</td>";
                        }
                    }
                    echo "</tr>";
                    $i++;
                }
                echo "</tr></table>";
            } else {
                echo "<div class='popup error'>Hibás osztály választás.</div>";
            }
        }
        ?>
        </table>
    </div>
    
    <?php
        //check for 'status' parameter in the URL
        if (isset($_GET['status'])) {
            $status = $_GET['status'];

            //display different popup messages based on save status
            if ($status === 'success' && isset($_GET['file'])) {
                $fileName = htmlspecialchars($_GET['file']);
                echo "<div class='popup success'><strong>$fileName</strong> was successfully saved.</div>";
            } elseif ($status === 'error') {
                echo "<div class='popup error'>An error occurred while saving the file. Please try again.</div>";
            }
        }
    ?>

    <footer>Created by Szlonkai Benedek</footer>
</body>
</html>