<?php
class Token
{
    // ワンタイムトークンの生成
    public static function generateOneTimeToken() {
        $token = bin2hex(random_bytes(16));
        $_SESSION['token'] = $token; 
        return $token;
    }

    // ワンタイムトークンの検証
    public static function validateOneTimeToken($token) {
        if (isset($_SESSION['token']) && $_SESSION['token'] === $token) {
            unset($_SESSION['token']); // ★バリデーションが通ったらトークンのセッションを削除
            return true;
        }

        return false;
    }
}

/*
index.php

if($_SERVER["REQUEST_METHOD"]==="GET") {
    $token = Token::generateOneTimeToken(); // ★Getで渡ってきた時にトークを生成
}

if($_SERVER["REQUEST_METHOD"]==="POST") {
    if(!Token::validateOneTimeToken(filter_input(INPUT_POST,'token'))) {
        exit('validation error');
    }

    Location::l(''); // ←★バリデーションが通ったらGetでindex.phpへ
}
*/