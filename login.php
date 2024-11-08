<?php
session_start();

require_once 'classes/Token.php';

$token = Token::generateOneTimeToken();

$err = $_SESSION['err'] ?? '';
unset($_SESSION['err']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" href="inyou.ico">
    <title>login</title>
</head>
<body>
    <div class="container">
        <header>
            <nav><a href="./"><i class="fa-solid fa-backward"></i> 野球●場</a></nav>
        </header>
    
        <?= $err ?>
        <form action="register.php" method="post">
            <input type="text" name="login_pass" placeholder="password">
            <button>login <i class="fa-solid fa-right-to-bracket"></i></button>
            <input type="hidden" name="token" value="<?= $token ?>">
        </form>
      
        <h1>※管理画面の操作方法</h1>
        <table>
            <thead>
                <tr><th></th><th>高校名</th><th>オッズ</th><th>勝率</th><th>勝数</th><th>勝敗</th></tr>
            </thead>
            <tbody>
                <tr><td>1</td><td>大阪桐蔭①</td><td>AAAAAA</td><td>0</td><td>0</td><td>1</td></tr>
                <tr><td>2</td><td>智辯和歌①</td><td>AAAAAA</td><td>1</td><td>0</td><td>1</td></tr>
                <tr><td>3</td><td>仙台育英②</td><td>BBBBBB</td><td>2</td><td>0</td><td>1</td></tr>
                <tr><td>4</td><td>日本文理②</td><td>BBBBBB</td><td>2</td><td>0</td><td>1</td></tr>
                <tr><td>5</td><td>日大三＿③</td><td>CCCCCC</td><td>3</td><td>0</td><td>1</td></tr>
            </tbody>
        </table>
        <ul>
            <li>1. 一番上の勝率が <span>0</span> の時は登録受付期間内で登録も削除も可で、<span>0</span> 以外の数値を入力すると受け付け期間終了で登録も削除も不可</li>
            <li>2. 勝数←勝ち星数(初期値は0)</li>
            <li>3. 勝敗←負けた高校を0にする(初期値は1)</li>
        </ul>
    </div>
</body>
</html>