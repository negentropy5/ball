<?php
define('DB_HOST', '');
define('DB_NAME', '');
define('DB_USER', '');
define('DB_PASS', '');

function connect() {
    $host = DB_HOST;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
  
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
  
    try {
      $pdo = new PDO($dsn, $user, $pass,
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
      ]);
      return $pdo;
    } catch(PDOException $e) {
      echo $e->getMessage() . PHP_EOL;
      exit;
    }
  }
?>