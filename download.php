<?php

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['serial_number']) || empty($_GET['serial_number'])) {
    die("Не указан серийный номер для скачивания файла.");
}

$serial_number = $_GET['serial_number'];

// Подключение к БД
$link = mysqli_connect("localhost", "root", "1sWOtKrGAn5I3l1Y", "philips");

if (!$link) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Получаем pdf_file и pdf_file_name по serial_number
$sql = "
    SELECT sc.pdf_file, sc.pdf_file_name
    FROM equipment e
    JOIN system_codes sc ON sc.system_code_id = e.system_code_id
    WHERE e.serial_number = ?
";

$stmt = $link->prepare($sql);
$stmt->bind_param("s", $serial_number);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $stmt->close();
    mysqli_close($link);
    die("Файл не найден.");
}

$stmt->bind_result($pdf_file, $pdf_file_name);
$stmt->fetch();

if (empty($pdf_file)) {
    $stmt->close();
    mysqli_close($link);
    die("Файл отсутствует.");
}

$stmt->close();
mysqli_close($link);

// Установка заголовков
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"" . basename($pdf_file_name) . ".pdf" ."\"");
header("Content-Length: " . strlen($pdf_file));

// Очистка буфера вывода
ob_clean();
flush();

// Вывод файла
echo $pdf_file;
exit();
