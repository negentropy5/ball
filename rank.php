<?php
require_once 'dbconnect.php';
$pdo = connect();

$state = true;

if($_SERVER["REQUEST_METHOD"]==="POST"){
    $year = explode(',', $_POST["year"]);
    $sql = "SELECT hdn, star FROM rank WHERE year = ? AND season = ? ORDER BY star DESC";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int)$year[0],$year[1]]);
        $res = $stmt->fetchAll();
    
        header('Content-Type: application/json');
        echo json_encode($res);
        exit;
    } catch(\Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(false);
        exit;
    }
}

function initial_f() {
    global $pdo;
    global $state;
    $sql = "SELECT hdn, star FROM rank WHERE (SELECT MAX(CONCAT(year,season)) FROM rank) = CONCAT(year,season)";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();   
    } catch(\Exception $e) {
        $state = false;
    }
}

function select_f() {
    global $pdo;
    global $state;
    try {
        $stmt = $pdo->query("SELECT hdn, year, season, COUNT(id) AS num, TRUNCATE(AVG(star),2) AS avg FROM rank GROUP BY year, season ORDER BY year DESC, season DESC");
        return $stmt->fetchAll();
    } catch(\Exception $e) {
        $state = false;
    }
}

header('Content-Type: application/json');
echo json_encode([select_f(), initial_f(), $state]);
exit;