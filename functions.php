<?php

function createToken() {
  if(!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
  }
}

function validateToken() {
  if (
    empty($_SESSION['token']) ||
    $_SESSION['token'] !== filter_input(INPUT_POST, 'token')
  ) {
    exit('Invalid post request');
  }
}

function h($str) {
  return htmlspecialchars($str,ENT_QUOTES,'UTF-8');
}

function mbtrim($str) {
  return preg_replace("/(^\s+)|(\s+$)/u", "", $str);
}

function inspection_name($hdn, $password) {
  $sql = 'SELECT COUNT(id) AS num FROM selects WHERE hdn = ? AND password != ?';
  $arr = [];
  $arr[] = $hdn;
  $arr[] = $password;
  try {
    $stmt = connect()->prepare($sql);
    $stmt->execute($arr);
    if($stmt->fetch()['num']) {
      return "$hdn の名前はすでに登録済み";
    }
  } catch(\Exception $e) {
    exit('inspectionNに失敗しました');
  }
}

function inspection_8($inputs) {
  $sql = 'SELECT COUNT(id) AS num FROM selects WHERE inputs1 = ? AND inputs2 = ? AND inputs3 = ? AND inputs4 = ? AND inputs5 = ? AND inputs6 = ? AND inputs7 = ? AND inputs8 = ?';
  $arr = [];
  foreach($inputs as $input) {
    $arr[] = $input;
  }
  try {
    $stmt = connect()->prepare($sql);
    $stmt->execute($arr);
    if($stmt->fetch()['num']) {
        return "その８校はすでに選ばれています";
    }
  } catch(\Exception $e) {
    exit('inspectionに失敗しました');
  }
}

// 登録処理
function insert_f($hdn, $password, $ip) {
  $sql   = 'DELETE FROM selects WHERE ip = ? || (hdn = ? AND password = ?)';
  $arr   = [];
  $arr[] = $ip;
  $arr[] = $hdn;
  $arr[] = $password;
  
  $pdo = connect();
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
  $arr[] = $hdn;
  $arr[] = $password;
  $arr[] = $ip;
  foreach($_POST['school'] as $school) {
    $arr[]  = $school;
  }

  try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($arr);
    $pdo->commit();         //トランザクション★

    $host = $_SERVER['HTTP_HOST'];
    $url = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    header("Location: //$host$url/index.php");
    exit;
  } catch (\Exception $e) {
    $pdo->rollBack();       //トランザクション★
    exit('インサート接続に失敗しました');
  }
}

function delete_f() {
  $k = filter_input(INPUT_POST, 'action');
  $n = filter_input(INPUT_POST, 'action_n');
  $sql = 'DELETE FROM selects WHERE hdn = ? AND password = ?';
  $arr = [];
  $arr[] = $n;
  $arr[] = $k;
  try {
    $stmt = connect()->prepare($sql);
    $stmt->execute($arr);
    
    $count = $stmt->rowCount();
    if((int)$count === 0) {
      return '削除キーが一致しません';
    }

    $host = $_SERVER['HTTP_HOST'];
    $url = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    header("Location: //$host$url/index.php");
    exit;
  } catch (\Exception $e) {
    exit('デリート接続に失敗しました');
  }
}

function lists() {
  try {
    return connect()->query("SELECT CONCAT(high_school, ' : ', odds) AS mix, high_school, win, ratio FROM lists")->fetchAll();
  } catch(Exception $e) {
    exit('selectに失敗しました');
  }
}

function selects() {
  try {
    $stm = connect()->query(
      "SELECT hdn, CONCAT(created, ' ( ', SUBSTRING(ip, 1, 7), '...', ' )') AS created, inputs1, inputs2, inputs3, inputs4, inputs5, inputs6, inputs7, inputs8, CONCAT('☆', SUM(ratio * score)) AS sum
      FROM selects JOIN lists
      ON high_school IN (inputs1, inputs2, inputs3, inputs4, inputs5, inputs6, inputs7, inputs8) GROUP BY hdn ORDER BY SUM(ratio * score) DESC, selects.id DESC"
    );
    return $stm->fetchAll();
  } catch(\Exception $e) {
    exit('セレクト接続に失敗しました');
  }
}