<?php
require_once 'functions.php';

if($_SERVER["REQUEST_METHOD"]==="GET") $token = generateOneTimeToken();

if($_SERVER["REQUEST_METHOD"]==="POST") {
    $_SESSION['err'] = [];

    // バリデーション
    if(!validateOneTimeToken($_POST['token'])) exit('validation error');
    // $token = generateOneTimeToken();

    // 削除処理(実行後は「GET」で更新するので これより下の処理は実行されない)
    if(filter_input(INPUT_POST,'delete_btn')) delete_f();
    
    $_SESSION['hdn']  = h((mb_substr(filter_input(INPUT_POST, 'hdn'),0,15)));
    $_SESSION['pass'] = h((mb_substr(filter_input(INPUT_POST, 'password'),0,15)));

    if(!$_SESSION['hdn']) {
        $_SESSION['err'][] = '名前を入力して下さい';
    } else {
        if(inspection_name($_SESSION['hdn'], $_SESSION['pass'])) $_SESSION['err'][] = 'その名前はすでに登録済みです';
    }

    if(!preg_match("/\A[a-z\d]{1,20}+\z/i", $_SESSION['pass'])) $_SESSION['err'][] = '削除キーを正しく入力して下さい';

    $_SESSION['school'] = filter_input(INPUT_POST, 'school',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
    if (!isset($_SESSION['school']) || count($_SESSION['school']) !== 8) {
        $_SESSION['err'][] = '８校選んで check を入れて下さい';
    } else {
        if(inspection_8($_SESSION['school'])) $_SESSION['err'][] = 'その8校はすでに登録済みです';
    }

    // バリデーションのエラーが０だったら
    if(count($_SESSION['err']) === 0) {
        insert_f(); // 登録処理
        unset($_SESSION['hdn']);
        unset($_SESSION['pass']);
        unset($_SESSION['school']);
    }
    
    location_f('./');
}

$err = $_SESSION['err'] ?? [];
$hdn = $_SESSION['hdn'] ?? null;
$pass = $_SESSION['pass'] ?? null;
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

$lists = lists();
$selects = selects();

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
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="../inyou.ico">
    <title>野球●場</title>
</head>
<body>

<div class="container">
    <header>
        <h1><a href="">野 球 <i class="fa-solid fa-baseball"></i> 場</a></h1>  
        <ul>
            <li><a href="../"><i class="fa-solid fa-arrow-up-right-from-square"></i> Home</a></li>
            <li><a href="login.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> 管理画面</a></li>
            <li><a href="rank.html"><i class="fa-solid fa-arrow-up-right-from-square"></i> 過去の成績</a></li>
        </ul>
    </header> 

    <div class="result">
        <?php foreach($err as $er): ?>
            <div><?= $er ?></div>
        <?php endforeach ?>
        <div><?= $delete_err ?></div>
        <div><?= $delete_success ?></div>
        <div><?= $insert ?></div>
    </div>
    
    <form class="insert_form <?= $start ?>" method="post">
        <input type="text" name="hdn" value="<?= $hdn ?>" placeholder="名前(15文字以内)"><br>
        <input type="text" name="password" value="<?= $pass ?>" placeholder="削除キー(半角英数15字以内)"><br>
        <button>8 校 選 ん で 登 録 </button><br>
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
                時点で双方に勝ち★を加点して<br>
                勝者には更に勝ち★を追加です
            </div>
        </div>

        <?php foreach($lists as $list): ?>
            <?php $lists_arr[$list['high_school']] = $list['win']; ?>
            <?php if(in_array($list["high_school"], $school)): ?>
                <label>
                    <input type="checkbox" name="school[]" value="<?= $list["high_school"] ?>"  checked>
                    <?= $list["high_school"] ?>:<?= $list["odds"] ?></label>
                </label><br>
            <?php else: ?>
                <label>
                    <input type="checkbox" name="school[]" value="<?= $list["high_school"] ?>">
                    <?= $list["high_school"] ?>:<?= $list["odds"] ?></label>
                </label><br>
            <?php endif ?>
        <?php endforeach ?>
    </form>

    <main>
    <?php foreach($selects as $select): ?>
        <section>
            <div><?= $select['rank'] ?></div>
            <div>★<?= $select['sum'] ?></div>
            <div><?= $select['hdn'] ?></div>
            <div><?= $select['created'] ?></div>
            <div class="inputs">
                <div class="<?= $lists_arr[$select['inputs1']] == '0' ? 'lose' : '' ?>"><?= $select['inputs1'] ?></div>
                <div class="<?= $lists_arr[$select['inputs2']] == '0' ? 'lose' : '' ?>"><?= $select['inputs2'] ?></div>
                <div class="<?= $lists_arr[$select['inputs3']] == '0' ? 'lose' : '' ?>"><?= $select['inputs3'] ?></div>
                <div class="<?= $lists_arr[$select['inputs4']] == '0' ? 'lose' : '' ?>"><?= $select['inputs4'] ?></div>
                <div class="<?= $lists_arr[$select['inputs5']] == '0' ? 'lose' : '' ?>"><?= $select['inputs5'] ?></div>
                <div class="<?= $lists_arr[$select['inputs6']] == '0' ? 'lose' : '' ?>"><?= $select['inputs6'] ?></div>
                <div class="<?= $lists_arr[$select['inputs7']] == '0' ? 'lose' : '' ?>"><?= $select['inputs7'] ?></div>
                <div class="<?= $lists_arr[$select['inputs8']] == '0' ? 'lose' : '' ?>"><?= $select['inputs8'] ?></div>
            </div>

            <form class="delete_form <?= $start ?>" method="post">
                <input type="text" name="delete" placeholder="削除キー">
                <button><i class="fa-solid fa-trash"></i></button>
                <input type="hidden" name="name" value="<?= $select['hdn'] ?>">
                <input type="hidden" name="token" value="<?= $token ?>">
                <input type="hidden" name="delete_btn" value="delete">
            </form>
        </section>
    <?php endforeach ?>
    </main>
</div>

</body>
</html>