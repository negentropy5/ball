<?php
require_once 'dbconnect.php';
session_start();
$pdo = connect();

// ワンタイムトークンの生成
function generateOneTimeToken() {
    $token = bin2hex(random_bytes(16));
    $_SESSION['token'] = $token; 
    return $token;
}

// ワンタイムトークンの検証
function validateOneTimeToken($token) {
    if (isset($_SESSION['token']) && $_SESSION['token'] === $token) {
        unset($_SESSION['token']);
        return true;
    }
    return false;
}

// ロケーション
function location_f($str) {
    $host = $_SERVER['HTTP_HOST'];
    $url = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    header("Location: //$host$url/$str");
    exit;
}

// サニタイズ
function h($str) {
    return htmlspecialchars($str,ENT_QUOTES,'UTF-8');
}

// 高校名
function lists() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT high_school,odds,ratio * score AS score,ratio,win FROM lists");
        return $stmt->fetchAll();
    } catch(\Exception $e) {
        exit('lists接続に失敗しました');
    }
}

// 8高校
function selects() {
    global $pdo;
    try {
        $stmt = $pdo->query(
          "SELECT
          SUM(ratio * score) AS sum,
          RANK() OVER (ORDER BY sum DESC) AS rank, 
          hdn,
          CONCAT(created,'(',SUBSTRING(ip, 1, 7),')') AS created,
          inputs1,inputs2,inputs3,inputs4,inputs5,inputs6,inputs7,inputs8
          FROM lists JOIN selects ON high_school IN (inputs1,inputs2,inputs3,inputs4,inputs5,inputs6,inputs7,inputs8)
          GROUP BY selects.id ORDER BY rank, selects.id"
        );
        return $stmt->fetchAll();
    } catch(\Exception $e) {
        exit('selects接続に失敗しました');
    }
}

function inspection_name($hdn, $password) {
    global $pdo;
    $sql = 'SELECT COUNT(id) AS num FROM selects WHERE hdn = ? AND password != ?';
    $arr = [];
    $arr[] = $hdn;
    $arr[] = $password;
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($arr);
      return $stmt->fetch()['num'];
    } catch(\Exception $e) {
      exit('inspection_name接続に失敗しました');
    }
}

function inspection_8($inputs) {
    global $pdo;
    $sql = 'SELECT COUNT(id) AS num FROM selects WHERE inputs1 = ? AND inputs2 = ? AND inputs3 = ? AND inputs4 = ? AND inputs5 = ? AND inputs6 = ? AND inputs7 = ? AND inputs8 = ?';
    $arr = [];
    foreach($inputs as $input) {
      $arr[] = $input;
    }
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($arr);
      return $stmt->fetch()['num'];
    } catch(\Exception $e) {
      exit('inspectionに失敗しました');
    }
}

function delete_f() {
    global $pdo;
    $delete_key = filter_input(INPUT_POST, 'delete');
    $hdn = filter_input(INPUT_POST, 'name');
    $sql = 'DELETE FROM selects WHERE hdn = ? AND password = ?';
    $arr = [];
    $arr[] = $hdn;
    $arr[] = $delete_key ;
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($arr);
      
      $count = $stmt->rowCount();
      if((int)$count === 0) {
        $_SESSION['delete_err'] = '削除キーが一致しません';
      } else {
          $_SESSION['delete_success'] = $hdn . 'さんの登録を削除しました';
      }
      location_f('./');
    } catch (\Exception $e) {
      exit('デリート接続に失敗しました');
    }
}

// 登録処理
function insert_f() {
    global $pdo;

    $ip = gethostbyaddr($_SERVER["REMOTE_ADDR"]);

    $sql   = 'DELETE FROM selects WHERE ip = ? || (hdn = ? AND password = ?)';
    $arr   = [];
    $arr[] = $ip;
    $arr[] = $_SESSION['hdn'];
    $arr[] = $_SESSION['pass'];
    
    $pdo->beginTransaction(); //トランザクション★
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($arr);
    } catch (\Exception $e) {
      exit('デリート接続に失敗しました');
    }
  
    // インサート処理
    $sql = 'INSERT INTO selects
    (hdn, password, ip, inputs1, inputs2, inputs3, inputs4, inputs5, inputs6, inputs7, inputs8)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)';
  
    $arr   = [];
    $arr[] = $_SESSION['hdn'];
    $arr[] = $_SESSION['pass'];
    $arr[] = $ip;
    foreach($_SESSION['school'] as $school) {
      $arr[]  = $school;
    }
  
    try {
      $stmt = $pdo->prepare($sql);
      $stmt->execute($arr);
      $pdo->commit();         //トランザクション★
      $_SESSION['insert'] = $_SESSION['hdn'] . 'さんを登録しました';
    } catch (\Exception $e) {
      $pdo->rollBack();       //トランザクション★
      exit('インサート接続に失敗しました');
    }
}