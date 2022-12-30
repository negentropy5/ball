<?php
require_once 'dbconnect.php';
session_start();
$pdo = connect();

// login画面い戻る関数
function return_f() {
    $host = $_SERVER['HTTP_HOST'];
    $url = rtrim(dirname($_SERVER['PHP_SELF']), '/');
    header("Location: //$host$url/login.php");
    exit;
}

// バリデーション
$token = filter_input(INPUT_POST, 'token');
if (empty($_SESSION['token']) || $_SESSION['token'] !== $token) return_f();
$_SESSION['token'] = bin2hex(random_bytes(32));//token再生成

// パスワード確認
$pass = filter_input(INPUT_POST, 'pass');
if($pass !== 'password') {
    $_SESSION['err'] = 'passwordが一致しません';
    return_f();
}

// 編集ボタンが押された時の処理
if(filter_input(INPUT_POST, 'edit')) {
    $high_school = filter_input(INPUT_POST, 'high_school',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    $odds = filter_input(INPUT_POST, 'odds',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    $ratio = filter_input(INPUT_POST, 'ratio',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    $score = filter_input(INPUT_POST, 'score',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    $win = filter_input(INPUT_POST, 'win',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    $high_schools = array_filter($high_school); // 空の配列を削除
    
    $pdo->beginTransaction(); //トランザクション
    
    // 一旦、高校名一覧を全部削除
    try {
        $pdo->query('DELETE FROM lists WHERE 1');
    } catch(\Exception $e){
        exit('接続に失敗しました');
    }

    try {
        $sql = "INSERT INTO lists (high_school, odds, ratio, score, win) VALUES (?,?,?,?,?)";
        for($i = 0; $i < count($high_schools); $i++) {
            $arr   = [];
            $arr[] = $high_school[$i];
            $arr[] = $odds[$i];
            $arr[] = $ratio[$i];
            $arr[] = $score[$i];
            $arr[] = $win[$i];
            $stmt  = $pdo->prepare($sql);
            $stmt->execute($arr);
        }
        $pdo->commit();   //トランザクション
    } catch(\Exception $e){
        $pdo->rollBack(); //トランザクション
        exit('接続に失敗しました');
    } 
}

// 高校名一覧を表示する
try {
    $sql = "SELECT high_school, odds, ratio, score, win FROM lists";
    $stmt = $pdo->query($sql);
    $lists = $stmt->fetchAll();
} catch (\Exception $e) {
    exit('接続に失敗しました');
}

// memberの削除処理
try {
    $sql = 'DELETE FROM selects WHERE hdn = ? AND password = ?';
    $arr = [];
    $arr[] = filter_input(INPUT_POST, 'hdn');
    $arr[] = filter_input(INPUT_POST, 'password');
    $stmt = $pdo->prepare($sql);
    $stmt->execute($arr);
} catch (\Exception $e) {
    exit('接続に失敗しました');
}

// 登録者一覧を表示
try {
    $stmt = $pdo->query('SELECT hdn, password, ip FROM selects ORDER BY id DESC');
    $names = $stmt->fetchAll();
} catch (\Exception $e) {
    exit('接続に失敗しました');
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet">
    <title>register</title>
    <style>
        *{margin: 0; font-size: 16px; text-decoration: none;}
        form {width: 100%; max-width: 450px; margin: 20px auto;}
        .bottom_form {
            margin: 5px auto;
            border: 1px solid #555;
            font-size: 14px;
            position: relative;
            border-radius: 3px;
        }
        section {display: flex; text-align: center;}
        section > input {
            box-sizing: border-box;
            border: 0.5px solid #aaa;
            width: 0; text-align: center;
            padding: 2px 0;
        }
        section * {flex: 1;}
        section > :nth-child(2) {flex: 3;}
        section > :nth-child(3) {flex: 3;}
        input[type="submit"] {margin: 10px 0;width: 100%;}
        section:nth-of-type(2) input[name="ratio[]"] {color: red;}
        button {position: absolute; top: 0; right: 0;}
    </style>
</head>
<body>

<form method="POST">
    <div style="margin: 20px 0; text-align: center">
        <a href="index.php">HOME画面</a> /
        <a href="login.php">ログイン画面</a>
    </div>
    <section>
        <span></span>
        <span class="com1 com">高校名</span>
        <span class="com1 com">オッズ</span>
        <span class="com2 com">勝率  </span>
        <span class="com2 com">勝数  </span>
        <span class="com2 com">勝負  </span>
    </section>
    <?php for($i = 0; $i < 50; $i++): ?>
    <section>
        <span><?php echo $i + 1 ?></span>
        <input type="text" name="high_school[]" value="<?php echo $lists[$i]['high_school'] ?? null ?>">
        <input type="text" name="odds[]" value="<?php echo $lists[$i]['odds'] ?? null ?>">
        <input type="text" name="ratio[]" value="<?php echo $lists[$i]['ratio'] ?? null ?>">
        <input type="text" name="score[]" value="<?php echo $lists[$i]['score'] ?? null ?>">
        <input type="text" name="win[]" value="<?php echo $lists[$i]['win'] ?? null ?>">
    </section>
    <?php endfor ?>
    <input type="hidden" name="pass" value="<?php echo $pass ?>">
    <input type="hidden" name="token" value="<?php echo $_SESSION['token'] ?>">
    <input type="submit" name="edit" value="編集する">
</form>

<!-- 登録者一覧 -->
<div style="text-align: center;">登録者一覧</div>
<?php foreach($names as $name) : ?>
<form  class="bottom_form" method="POST">
    [名前] : <span><?php echo $name['hdn'] ?></span><br>
    [IP＿] : <span><?php echo $name['ip'] ?></span><br>
    [削除] : <span><?php echo $name['password'] ?></span><br>

    <input type="hidden" name="hdn" value="<?php echo $name['hdn'] ?>">
    <input type="hidden" name="password" value="<?php echo $name['password'] ?>">
    <input type="hidden" name="pass" value="<?php echo $pass ?>">
    <input type="hidden" name="token" value="<?php echo $_SESSION['token'] ?>">
    <button style="color: #4285f4;"><i class="fa-solid fa-trash"></i></button>
</form>
<?php endforeach ?>

</body>
</html>