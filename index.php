<?php
require_once 'dbconnect.php';
// セッション
session_start();
// すべてのクラスを読み込む
spl_autoload_register(function ($class) {
    require_once(__DIR__ . '/classes/' . $class . '.php');
});
// PDO
$pdo = connect();
$sqls = new Functions($pdo);

// Getで渡ってきた時にまた"新たに"トークンを生成する
if($_SERVER["REQUEST_METHOD"]==="GET") $token = Token::generateOneTimeToken();

if($_SERVER["REQUEST_METHOD"]==="POST") {
    // バリデーション(falseが返ってきたらerrorを表示して処理をとめる)
    if(!Token::validateOneTimeToken(filter_input(INPUT_POST,'token'))) exit('validation error');

    // エラーを受け取る配列
    $_SESSION['err'] = [];

    // 削除処理(実行後は「GET」で更新するので これより下の処理は実行されない)
    if(filter_input(INPUT_POST,'delete_btn')) $sqls->delete_f();

    // 名前と削除キー
    $_SESSION['hdn']  = Sanitize::h((mb_substr(filter_input(INPUT_POST, 'hdn'),0,15)));
    $_SESSION['pass'] = Sanitize::h((mb_substr(filter_input(INPUT_POST, 'pass'),0,15)));

    // 名前の入力があるかを検証
    if(!$_SESSION['hdn']) {
        $_SESSION['err'][] = '名前を入力して下さい';
    } else {
        if($sqls->inspection_name()) $_SESSION['err'][] = 'その名前はすでに登録済みです';
    }

    // 削除キーが半角英数で渡ってきたかを検証
    if (!preg_match('/^[a-zA-Z0-9]+$/', $_POST['pass'])) {
        $_SESSION['err'][] = "半角英数字のみを入力してください";
    } 

    // 8校を選んでいるかを検証
    $_SESSION['school'] = filter_input(INPUT_POST, 'school', FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    if (!isset($_SESSION['school']) || count($_SESSION['school']) !== 8) {
        $_SESSION['err'][] = '８校選んで check を入れて下さい';
    } else {
        if($sqls->inspection_8()) $_SESSION['err'][] = 'その8校はすでに登録済みです';
    }

    // バリデーションのエラーが０だったら
    if(count($_SESSION['err']) === 0) {
        $sqls->insert_f(); // インサート処理
        unset($_SESSION['hdn']);
        unset($_SESSION['pass']);
        unset($_SESSION['school']);
    }

    Location::l('./'); // Getでindex.phpへ戻る
}

$err    = $_SESSION['err'] ?? [];
$hdn    = $_SESSION['hdn'] ?? null;
$pass   = $_SESSION['pass'] ?? null;
$school = $_SESSION['school'] ?? [];
$delete_err = $_SESSION['delete_err'] ?? null;
$delete_success = $_SESSION['delete_success'] ?? null;
$insert = $_SESSION['insert'] ?? null;

unset($_SESSION['err']);
unset($_SESSION['hdn']);
unset($_SESSION['pass']);
unset($_SESSION['school']);
unset($_SESSION['delete_err']);
unset($_SESSION['delete_success']);
unset($_SESSION['insert']);

// 打消し線の値を参照するための配列
$lists_arr = [];

$sqls = new Functions($pdo);
$lists = $sqls->lists();
$selects = $sqls->selects();

// 受け付け期間の処理
$start = '';
if(isset($lists[0]['ratio']) && $lists[0]['ratio']) $start = 'start';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet">
    <meta name="description" content="野球●場">
    <meta name="keywords" content="野球●場">
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" href="inyou.ico">
    <title>baseball</title>
</head>
<body>

<header>
    <h1>野球●場</h1>
    <a href="login.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> 管理画面</a>
</header>


<div class="err_success_div">
    <!-- エラー処理 -->
    <?php foreach($err as $er): ?>
        <div><?= $er ?></div>
    <?php endforeach ?>
    <div><?= $delete_err ?></div>
    <!-- 送信/削除の成功報告処理 -->
    <div><?= $delete_success ?></div>
    <div><?= $insert ?></div>
</div>

<form class="insert_form  <?= $start ?>" method="post">
    <input  class="insert_btns" type="text" name="hdn" placeholder="名前" value="<?= $hdn ?>"><br>
    <input  class="insert_btns" type="text" name="pass" placeholder="削除キー" value="<?= $pass ?>"><br>
    <button class="insert_btns">8校選んで送信<i class="fa-regular fa-paper-plane"></i></button>
    <input type="hidden" name="token" value="<?= $token ?>">

    <div class="description">
        ルール説明
        <div>
            好きな高校を８校選んで下さい<br>
            総勝ち★数で争います＿＿＿＿
        </div>
        <div>
            ①～③のオッズがあります＿＿<br>
            強い高校は1勝しても①(＋★1)<br>
            弱い高校は1勝すると③(＋★3)<br>
            他は1勝すると②(＋★2)です＿
        </div>
        <div>
            延長でタイブレークに突入した<br>
            場合は双方に勝ち★を加点して<br>
            勝者には更に勝ち★を追加です
        </div>
    </div>

    <?php foreach($lists as $list): ?>
    <?php $lists_arr[$list['high_school']] = $list['win']; ?>
    <label>
        <!--in_array関数で配列$schoolの中に要素があるか否かをcheck -->
        <input type="checkbox" name="school[]" value="<?= $list['high_school'] ?>"
        <?= in_array($list['high_school'], $school) ? 'checked' : '' ?>>
        <?= $list['high_school'] .':'. $list['odds'] ?>
    </label><br>
    <?php endforeach ?>
</form>

<main>
    <?php foreach($selects as $select): ?>
    <section>
        <div>
            <span class="rank"><?= $select['rank'] ?></span>
            <span><?= $select['hdn'] ?></span>
            <span class="sum">★<?= $select['sum'] ?></span>
        </div>
        <div class="created"><?= $select['created'] ?></div>
        <div>
            <span class="<?= $lists_arr[$select['inputs1']]=='0'?'lose':''?>"><?= $select['inputs1'] ?></span>
            <span class="<?= $lists_arr[$select['inputs2']]=='0'?'lose':''?>"><?= $select['inputs2'] ?></span>
            <span class="<?= $lists_arr[$select['inputs3']]=='0'?'lose':''?>"><?= $select['inputs3'] ?></span>
            <span class="<?= $lists_arr[$select['inputs4']]=='0'?'lose':''?>"><?= $select['inputs4'] ?></span>
            <span class="<?= $lists_arr[$select['inputs5']]=='0'?'lose':''?>"><?= $select['inputs5'] ?></span>
            <span class="<?= $lists_arr[$select['inputs6']]=='0'?'lose':''?>"><?= $select['inputs6'] ?></span>
            <span class="<?= $lists_arr[$select['inputs7']]=='0'?'lose':''?>"><?= $select['inputs7'] ?></span>
            <span class="<?= $lists_arr[$select['inputs8']]=='0'?'lose':''?>"><?= $select['inputs8'] ?></span>
        </div>
        <form class="delete_form  <?= $start ?>" method="post">
            <span class="delete_btn"><i class="fa-solid fa-trash"></i></span>
            <input type="hidden" name="delete" placeholder="削除キー">
            <input type="hidden" name="name" value="<?= $select['hdn'] ?>">
            <input type="hidden" name="delete_btn" value="delete">
            <input type="hidden" name="token" value="<?= $token ?>">
        </form>
    </section>
    <?php endforeach ?>
</main>
<script>
    document.querySelectorAll('.delete_btn').forEach((delete_btn, index) => {
        delete_btn.addEventListener('click',() => {
            const res = prompt('削除キーを入力して下さい');
            if(res === '' || res === null) return;
            document.querySelectorAll('input[name="delete"]')[index].value = res;
            document.querySelectorAll('.delete_form')[index].submit();
        });
    });
</script> 
</body>
</html>