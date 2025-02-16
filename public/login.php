<?php include '../bootstrap.php' ?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            background-color: #161616;
            height: 100%;
            margin: 0;
            padding: 0;
            color: lightgray;
            top: 0;
        }

        form {
            margin-top: 10%;
        }

        form input {
            font-size: 1.3vw;
            color: lightgray;
            background-color: #161616;
            display: block;
            padding: 1em 2em;
            width: auto;
            margin: 1em auto;
            box-sizing: content-box;
            border: 1px lightgray solid;
            border-radius: .3em;
        }

        form strong {
            display: block;
            text-align: center;
            width: auto;
            margin: 1em auto;
            color: #f33;
            padding: .2em;
        }
    </style>
</head>

<body>
    <form method="POST">
        <input type="text" name="username" value="<?= $_POST['username'] ?? '' ?>" placeholder="User Name" required minlength="1" maxlength="12">
        <input type="password" name="password" value="<?= $_POST['password'] ?? '' ?>" placeholder="Password" required minlength="8" maxlength="32">
        <input type="submit" value="login">
        <?php if (!empty($_POST)) : ?>
            <strong>User not exist or password error!</strong>
        <?php endif ?>
    </form>
</body>

</html>
