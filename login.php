<?php 
session_start();
$_SESSION['token'] = bin2hex(random_bytes(32));
$err = $_SESSION['err'] ?? null;
$_SESSION['err'] = '';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://use.fontawesome.com/releases/v6.1.1/css/all.css" rel="stylesheet">
    <title>login</title>
    <style>
        body {margin: 0; background: #555;}
        form {width: 100%; max-width: 400px; margin: 100px auto 0;}
        input[type="text"], button {
            box-sizing: border-box;
            border: none;
            font-size: 16px;
            padding: 0 5px;
            width: 49%;
            margin: 0 0 20px;
            height: 30px;
            line-height: 30px;
            vertical-align: bottom;
            background: #fff;
        }
        a {
            font-weight: bold;
            color: #fff;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <form action="register.php" method="POST">
        <span style="color: #fff"><?php echo $err ?></span><br>
        <input type="text" name="pass">
        <button>ログイン<i class="fa-solid fa-right-to-bracket"></i></button><br>
        <a href="index.php"><i class="fa-solid fa-house"></i> Home</a>
        <input type="hidden" name="token" value="<?php echo $_SESSION['token'] ?>">
    </form>
</body>
</html>