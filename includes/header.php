<?php
require_once dirname(__DIR__) . '/boot.php';
?>


<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Digital Application Specialist</title>
    <link rel="stylesheet" href="http://localhost/philips/assets/css/style.css">
</head>

<body>

<?php if (isset($_SESSION['user_id'])) { ?> 
<header>
    <div class="header-container">
        <a href="index.php" class="logout-button">Главная</a>
        <a href="do_logout.php" class="logout-button">Выход</a>
    </div>
</header>
<?php } ?>

<main class="content">