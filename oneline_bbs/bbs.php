<?php

// データベースに接続・データベースを選択 (PHP7では、mysql関数が使えないので、PDOを使用する)
try {
    $dbh = new PDO(
        'mysql:host=localhost;dbname=oneline_bbs;charset=utf8',
        'root',
        '',
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        )
    );

    $validationErrors = array();
    // POSTなら保存処理実行
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // 名前が正しく入力されているかチェック
        $name = null;
        if (!isset($_POST['name']) || !strlen($_POST['name'])) {
            $validationErrors['name'] = '名前を入力してください';
        } elseif (strlen($_POST['name']) > 40) {
            $validationErrors['name'] = '名前は40文字以内で入力してください';
        } else {
            $name = $_POST['name'];
        }
    }

    // ひとことが正しく入力されているかチェック
    $comment = null;
    if (!isset($_POST['comment']) || !strlen($_POST['comment'])) {
        $validationErrors['comment'] = 'ひとことを入力してください';
    } elseif (strlen($_POST['comment']) > 200) {
        $validationErrors['comment'] = 'ひとことは200文字以内で入力してください';
    } else {
        $comment = $_POST['comment'];
    }

    // エラーがなければ保存
    if (count($validationErrors) === 0) {
        // 保存するためのSQL文を作成
        $statement = $dbh->prepare("INSERT INTO post (name, comment, created_at) VALUES (:name, :comment, :created_at)");
        $statement->bindValue(':name', $name, PDO::PARAM_STR);
        $statement->bindValue(':comment', $comment, PDO::PARAM_STR);
        $statement->bindValue(':created_at', date('Y-m-d H:i:s'), PDO::PARAM_STR);

        // 保存する
        $statement->execute();

        $statement = null;

        header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    }

} catch (PDOException $e) {
    echo $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>ひとこと掲示板</title>
</head>
<body>
    <h1>ひとこと掲示板</h1>

    <form action="bbs.php" method="post">
        <?php if (count($validationErrors)): ?>
        <ul class="error_list">
            <?php foreach ($validationErrors as $error): ?>
                <li>
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        名前： <input type="text" name="name"><br>
        ひとこと： <input type="text" name="comment" size="60"><br>
        <input type="submit" name="submit" value="送信">
    </form>

    <?php
    // 投稿された内容を取得するSQLを作成して、結果を取得
    $selectSql = 'SELECT * FROM `post` ORDER BY `created_at` DESC';
    $selectStatement = $dbh->query($selectSql);
    ?>

    <?php if ($selectStatement !== false && $selectStatement->rowCount()): ?>
    <ul>
        <?php while ($post = $selectStatement->fetch(PDO::FETCH_ASSOC)): ?>
        <li>
            <?php echo htmlspecialchars($post['name'], ENT_QUOTES, 'UTF-8'); ?>:
            <?php echo htmlspecialchars($post['comment'], ENT_QUOTES, 'UTF-8'); ?>
            - <?php echo htmlspecialchars($post['created_at'], ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endwhile; ?>
    </ul>
    <?php endif; ?>

    <?php
    // 取得結果を解放して、接続を閉じる
    $selectStatement = null;
    $dbh = null;

    ?>

</body>
</html>