<?php
session_start();
require_once 'dbconnect.php';
require_once 'functions.php';
createToken();

if(isset(lists()[0]['ratio']) && lists()[0]['ratio']) {
    $_SESSION['None'  ] = 'none';
    $_SESSION['delete'] = 'none';
}

$err = [];

if($_SERVER["REQUEST_METHOD"] === "POST") {
    validateToken();
    
    if(filter_input(INPUT_POST, 'send')) {
        $_SESSION['delete'] = 'flex';
        $_SESSION['None' ]  = 'none';
        // 削除キー正しければ 登録を削除した後 GETで更新される
        // 削除項目が０だった場合は エラー表示
        $err[] = delete_f(); 
    } else {
        $ip = gethostbyaddr($_SERVER["REMOTE_ADDR"]); 
        $password = h(filter_input(INPUT_POST, 'password'));
        
        if(!$hdn = h(mb_substr(mbtrim(filter_input(INPUT_POST, 'hdn')), 0, 15))) {
            $err[] = '名前を入力して下さい';
        } else if(inspection_name($hdn, $password)) {
            $err[] = inspection_name($hdn, $password);
        }
        
        if(!preg_match("/\A[a-z\d]{1,20}+\z/i", $password)) $err[] = '削除キーを正しく入力して下さい';
        
        $school = filter_input(INPUT_POST, 'school',FILTER_DEFAULT,FILTER_REQUIRE_ARRAY);
        if (!isset($school) || count($school) !== 8) {
            $err[] = '８校選んで check を入れて下さい';
        } else {
            if(inspection_8($school)) $err[] = inspection_8($school);
        }
    }

    // バリデーションのエラーが０だったら
    if(count($err) === 0) {
        $_SESSION['None'] = 'none';
        // 登録処理
        insert_f($hdn, $password, $ip); 
        // 上記にエラーがなければ GET処理で更新 lists(); selects();
    }
}
// ↓GET処理
// lists();
// selects();
// ↓win=0の時にpushされる配列
$arr = [];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet">
    <title>baseball</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@300&display=swap');
        * {
            margin:0; padding:0;
            font-size:18px; font-weight:normal;
            font-family:'Noto Sans JP',sans-serif;
            text-decoration:none;
            box-sizing:border-box;
            list-style:none;
            color:#333;
        }
        input[type="text"], button {
            height: 30px; line-height: 30px;
            border: .5px solid #777;
            padding: 0 5px;
            vertical-align: bottom;
        }
        button {
            background: radial-gradient(#fff, #aaa);
            font-weight: bold;
            font-size: 20px;
            cursor: pointer;
            text-shadow: 1px 1px rgba(0, 0, 0, 0.3);
        }
        header {margin-bottom: 20px;}
        .title {
            display: block;
            font-size: 30px;
            text-shadow: 1px 1px rgba(0, 0, 0, 0.3);
            margin-bottom: 10px;
        }
        .header_a > a {
            display: inline-block;
            font-size: 16px;
            transform: skewX(-15deg);
        }
        body {
            width: 100%; max-width: 400px;
            margin: 50px auto;
            text-align: center;
        }
        .list_form, section {
            border: 1px solid #777;
            margin-bottom: 10px;
            padding: 5px;
        }
        .list_form > input[type="text"],.list_form > button {
            margin-bottom: 5px;
            width: 100%;
        }
        .send {display: flex;}
        .send > input[type="text"] {
            width: 0;
            flex: 1;
        }
        section {position: relative;}
        .schools {
            display: inline-block;
            width: 48%;
        }
        section > :nth-child(2), section > :nth-child(12)  {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            text-shadow: .5px .5px .5px rgba(0, 0, 0, 0.3);
        }
        section > :nth-child(3) {
            font-size: 16px;
            opacity: .5;
            transform: skewX(-15deg);
        }
        .key {position: absolute;}
        .uchikeshi {
            text-decoration: line-through;
            color: #aaa;
        }
    </style>
</head>

<body>

<header>
    <a class="title" href="">野 球 <i class="fa-solid fa-baseball"></i> 場</a>
    <div class="header_a">
        <a href="login.php"><i class="fa-solid fa-arrow-up-right-from-square"></i> 管理者編集＿</a> /
        <a href="http://negentropy.html.xdomain.jp/grades"><i class="fa-solid fa-arrow-up-right-from-square"></i> 過去の成績</a> 
        <br>
        <a href="../mail"><i class="fa-solid fa-arrow-up-right-from-square"></i> お問い合わせ</a> /
        <a href="http://negentropy.php.xdomain.jp/bbs"><i class="fa-solid fa-arrow-up-right-from-square"></i> 大島掲示板</a>
    </div>
</header>  

<!-- エラー表示 -->
<?php foreach($err as $e) { ?>
    <div style="text-align:left; padding:2px 5px; color:#4285f4;"><?= $e ?></div>
<?php } ?>

<form style="display:<?= $_SESSION['None'] ?? '' ?>;" class="list_form"  method="post">
    <input type="text" name="hdn" value="<?= $hdn ?? '' ?>" placeholder="名前">
    <input type="text" name="password" value="<?= $password ?? '' ?>" placeholder="削除キー(半角英数20文字以内)">
    <button>
        <i class="fa-solid fa-arrow-up-right-from-square"></i> 8 校 選 ん で 登 録
    </button>
    <div style="margin: 10px 0; font-size: 16px; color: #000">
    好きな高校を８校選んで下さい<br>
    総勝ち☆数で争います＿＿＿＿<br>
    ①～③のオッズがあります＿＿<br>
    強い高校は1勝しても①(＋☆１)<br>
    弱い高校は1勝すると③(＋☆３)<br>
    他は1勝すると②(＋☆２)です＿<br>
    延長１２回終了後タイブレーク<br>
    突入の時点で双方に勝ち☆加点<br>
    試合後勝者は更に＋勝ち☆追加
    </div>
    <?php foreach(lists() as $list) : ?>
        <?php if($list['win'] == 0) $arr[] = $list['high_school'] ?>
        
        <?php if(in_array($list['high_school'], $school ?? [])): ?>
        <label style="display: block;">
            <input type="checkbox" name="school[]" value="<?= $list['high_school'] ?>" <?= 'checked' ?>>
            <?= $list['mix']; ?>
        </label>
        <?php else: ?>
        <label style="display: block;">
            <input type="checkbox" name="school[]" value="<?= $list['high_school'] ?>">
            <?= $list['mix']; ?>
        </label>
        <?php endif ?>
    <?php endforeach ?>
    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
</form>

<?php foreach(selects() as $key => $select) : ?>
<section>
    <div class="key"><?= $key + 1 ?></div>
  
    <?php foreach($select as $i => $s) : ?>
        <?php if($i === 'hdn' || $i === 'created' || $i === 'sum'): ?> 
            <div><?= $s ?></div>
        <?php else : ?>
            <?php if(in_array($s, $arr)): ?>
                <span class="schools uchikeshi"><?= $s ?></span>
            <?php else : ?>
                <span class="schools"><?= $s ?></span>
            <?php endif ?>
        <?php endif ?> 
    <?php endforeach ?> 

    <form style="display:<?= $_SESSION['delete'] ?? $_SESSION['None'] ?? '' ?>;" class="send" method="post">
        <input type="text" name="action" placeholder="削除キー">
        <input type="hidden" name="action_n" value="<?= $select['hdn'] ?>">
        <button class="del"><i class="fa-solid fa-trash"></i></button>
        <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
        <input type="hidden" name="send" value="del">
    </form>
</section>
<?php endforeach ?>

<?php $_SESSION['None'  ] = '' ?>
<?php $_SESSION['delete'] = '' ?>

</body>
</html>