<?php
require_once 'functions.php';

$token = filter_input(INPUT_POST,'token');
$login_pass = filter_input(INPUT_POST,'login_pass');
// トークンの検証
if(!validateOneTimeToken($token)) location_f('login.php');
// ワスワードの検証
if($login_pass !== '1') {
    $_SESSION['err'] = 'passwordが一致しません';
    location_f('login.php');
}
// 入室が成功してトークン再生成
$token = generateOneTimeToken();


if(filter_input(INPUT_POST, 'edit')) {
    // filter_input(INPUT_POST, 'high_school',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    $count = count(array_filter($_POST['high_school'])); // レコード数

    $pdo->beginTransaction(); //トランザクション
    
    // 一旦、高校名一覧を全部削除
    try {
        $pdo->query('DELETE FROM lists WHERE 1');
    } catch(\Exception $e){
        exit('接続に失敗しました');
    }

    try {
        $sql = "INSERT INTO lists (high_school,odds,ratio,score,win) VALUES (?,?,?,?,?)";
        for($i = 0; $i < $count; $i++) {
            $arr   = [];
            $arr[] = $_POST['high_school'][$i];
            $arr[] = $_POST['odds'][$i];
            $arr[] = $_POST['ratio'][$i];
            $arr[] = $_POST['score'][$i];
            $arr[] = $_POST['win'][$i];
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css">
    <title>register</title>
</head>
<body>
    <div class="container">
        <nav><a href="./">野球●場</a></nav>
        <form method="post">
            <table>
                <thead>
                    <tr><th></th><th>高校名</th><th>オッズ</th><th>勝率</th><th>勝数</th><th>勝敗</th></tr>
                </thead>
                <tbody>
                    <?php for($i = 0; $i < 50; $i++): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><input type="text" name="high_school[]" value="<?= $lists[$i]['high_school'] ?? '' ?>"></td>
                        <td><input type="text" name="odds[]" value="<?= $lists[$i]['odds'] ?? '' ?>"></td>
                        <td><input type="text" name="ratio[]" value="<?= $lists[$i]['ratio'] ?? '' ?>"></td>
                        <td><input type="text" name="score[]" value="<?= $lists[$i]['score'] ?? '' ?>"></td>
                        <td><input type="text" name="win[]" value="<?= $lists[$i]['win'] ?? '' ?>"></td>
                    </tr>
                    <?php endfor ?>
                </tbody>
            </table>
            <input type="submit" name="edit" value="編集">
            <input type="hidden" name="token" value="<?= $token ?>">
            <input type="hidden" name="login_pass" value="<?= $login_pass ?>">
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
            <input type="hidden" name="token" value="<?= $token ?>">
            <input type="hidden" name="login_pass" value="<?= $login_pass ?>">
            <button style="color: #4285f4;"><i class="fa-solid fa-trash"></i></button>
        </form>
        <?php endforeach ?>
    </div>
</body>
</html>