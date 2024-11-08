<?php
require_once 'dbconnect.php';

session_start();

$pdo = connect();

spl_autoload_register(function ($class) {
    require_once(__DIR__ . '/classes/' . $class . '.php');
});


$token = filter_input(INPUT_POST, 'token');
$login_pass  = Sanitize::h(filter_input(INPUT_POST, 'login_pass'));

// トークンが一致しない場合はindex.phpへ戻す
if(!Token::validateOneTimeToken(filter_input(INPUT_POST,'token')))  {
    $_SESSION['err'] = 'トークンが一致しません';
    Location::l('login.php');
}

// 再度生成トークンを生成
$token = Token::generateOneTimeToken();

// passwordが一致しなければlogin.phpへ戻す
if($login_pass !== '*****') {
    $_SESSION['err'] = 'passwordが一致しません';
    Location::l('login.php');
}

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
$sqls = new Functions($pdo);
$lists = $sqls->lists();

// memberの削除処理
if(filter_input(INPUT_POST, 'delete_btn')) {
    $hdn = filter_input(INPUT_POST, 'name');
    try {
        $sql = 'DELETE FROM selects WHERE hdn = ? AND password = ?';
        $arr = [];
        $arr[] = $hdn;
        $arr[] = filter_input(INPUT_POST, 'delete');
        $stmt = $pdo->prepare($sql);
        $stmt->execute($arr);
    } catch (\Exception $e) {
        exit('接続に失敗しました');
    }
}

// 全削除
if(filter_input(INPUT_POST, 'all_clear')) {
    try {
        $sql = 'DELETE FROM selects WHERE 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } catch (\Exception $e) {
        exit('接続に失敗しました');
    }
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
    <link rel="icon" href="inyou.ico">
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
        <div class="all_clear_div">すべての登録者を削除</div>
        <form  class="all_clear_form" method="POST">
            <input type="hidden" name="token" value="<?= $token ?>">
            <input type="hidden" name="login_pass" value="<?= $login_pass ?>">
            <input type="hidden" name="all_clear" value="1">
        </form>
        <?php foreach($names as $name) : ?>
        <form class="delete_form" method="POST">
            [名前] : <span><?php echo $name['hdn'] ?></span><br>
            [IP＿] : <span><?php echo $name['ip'] ?></span><br>

            <input type="hidden" name="name" value="<?php echo $name['hdn'] ?>">
            <input type="hidden" name="delete" value="<?php echo $name['password'] ?>">
            <input type="hidden" name="token" value="<?= $token ?>">
            <input type="hidden" name="login_pass" value="<?= $login_pass ?>">
            <input type="submit" name="delete_btn" value="削除">
        </form>
        <?php endforeach ?>
    </div>

    <script>
        const allclear = document.querySelector('.all_clear_div');
        allclear.addEventListener('click', () => {
            if(confirm('すべての登録者情報を削除しますか？')) {
                document.querySelector('.all_clear_form').submit();
            }
        });
    </script>
</body>
</html>