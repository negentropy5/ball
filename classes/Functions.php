<?php
class Functions
{
    private $pdo;

    function __construct($pdo)
    {
      $this->pdo = $pdo;
    }

    function lists() {
        try {
            $stmt = $this->pdo->query("select * from lists");
            return $stmt->fetchAll();
        } catch(\Exception $e) {
            echo 'エラーが発生しました: ' . $e->getMessage();
        }
    }

    function selects() {
    // SQL コマンド実行順番
    // FROM, WHERE, GROUP BY, HAVING, SELECT, ORDER BY
        try {
            $stmt = $this->pdo->query(
                "SELECT
                SUM(ratio * score) AS sum,
                RANK() OVER (ORDER BY sum DESC) AS rank, 
                hdn,
                CONCAT(created,'(',SUBSTRING(ip, 1, 7),')') AS created,
                inputs1,inputs2,inputs3,inputs4,inputs5,inputs6,inputs7,inputs8
                FROM lists JOIN selects
                ON high_school IN (inputs1,inputs2,inputs3,inputs4,inputs5,inputs6,inputs7,inputs8)
                GROUP BY selects.id
                ORDER BY rank, selects.id"
            );
            return $stmt->fetchAll();
        } catch(\Exception $e) {
            echo 'エラーが発生しました: ' . $e->getMessage();
        }
    }

    function inspection_name() {
        $sql = 'SELECT COUNT(id) AS num FROM selects WHERE hdn = ? AND password != ?';
        $arr = [];
        $arr[] = $_SESSION['hdn'];
        $arr[] = $_SESSION['pass'];
        try {
          $stmt = $this->pdo->prepare($sql);
          $stmt->execute($arr);
          return $stmt->fetch()['num'];
        } catch(\Exception $e) {
          exit('inspection_name接続に失敗しました');
        }
    }
    
    function inspection_8() {
        $sql = 'SELECT COUNT(id) AS num FROM selects WHERE inputs1 = ? AND inputs2 = ? AND inputs3 = ? AND inputs4 = ? AND inputs5 = ? AND inputs6 = ? AND inputs7 = ? AND inputs8 = ?';
        try {
          $stmt = $this->pdo->prepare($sql);
          $stmt->execute($_SESSION['school']); // schoolの配列をそのまま渡す
          return $stmt->fetch()['num'];
        } catch(\Exception $e) {
          exit('inspectionに失敗しました');
        }
    }

    function delete_f() {
        $delete_key = filter_input(INPUT_POST, 'delete');
        $hdn = filter_input(INPUT_POST, 'name');
        $sql = 'DELETE FROM selects WHERE hdn = ? AND password = ?';
        $arr = [];
        $arr[] = $hdn;
        $arr[] = $delete_key ;
        try {
          $stmt = $this->pdo->prepare($sql);
          $stmt->execute($arr);
          
          $count = $stmt->rowCount();
          if((int)$count === 0) {
            $_SESSION['delete_err'] = '削除キーが一致しません';
          } else {
              $_SESSION['delete_success'] = $hdn . 'さんの登録を削除しました';
          }
          Location::l('./'); // Getでindex.phpへ戻る
        } catch (\Exception $e) {
          exit('デリート接続に失敗しました');
        }
    }
    
    // 登録処理
    function insert_f() {
        $ip = gethostbyaddr($_SERVER["REMOTE_ADDR"]);
    
        $sql   = 'DELETE FROM selects WHERE ip = ? || (hdn = ? AND password = ?)';
        $arr   = [];
        $arr[] = $ip;
        $arr[] = $_SESSION['hdn'];
        $arr[] = $_SESSION['pass'];
        
        $this->pdo->beginTransaction(); //トランザクション★
        try {
          $stmt = $this->pdo->prepare($sql);
          $stmt->execute($arr);
        } catch (\Exception $e) {
          exit('デリート接続に失敗しました');
        }
      
        // インサート処理
        $sql = 'INSERT INTO selects
        (hdn, password, ip, inputs1, inputs2, inputs3, inputs4, inputs5, inputs6, inputs7, inputs8)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)';
      
        $arr   = [];
        $arr[] = $_SESSION['hdn'];
        $arr[] = $_SESSION['pass'];
        $arr[] = $ip;
        foreach($_SESSION['school'] as $school) {
          $arr[]  = $school;
        }
      
        try {
          $stmt = $this->pdo->prepare($sql);
          $stmt->execute($arr);
          $this->pdo->commit();         //トランザクション★
          $_SESSION['insert'] = $_SESSION['hdn'] . 'さんを登録しました';
        } catch (\Exception $e) {
          $this->pdo->rollBack();       //トランザクション★
          exit('インサート接続に失敗しました');
        }
    }
}