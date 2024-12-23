<?php include 'includes/header.php'; ?>

<?php

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['serial_number']) || empty($_GET['serial_number'])) {
    die("Не указан серийный номер.");
}

$serial_number = $_GET['serial_number'];

// Подключение к БД
$link = mysqli_connect("localhost", "root", "1sWOtKrGAn5I3l1Y", "philips");
if (!$link) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Экранируем serial_number для безопасности
$serial_number_esc = mysqli_real_escape_string($link, $serial_number);

// Получим pdf_file_name по serial_number
$sql_pdf = "
    SELECT sc.pdf_file_name
    FROM equipment e
    JOIN system_codes sc ON sc.system_code_id = e.system_code_id
    WHERE e.serial_number = '$serial_number_esc'
";
$result_pdf = mysqli_query($link, $sql_pdf);

if (!$result_pdf || mysqli_num_rows($result_pdf) == 0) {
    mysqli_close($link);
    die("Техническое обсуживание для данного серийного номера не найдено.");
}

$pdf_file_name = mysqli_fetch_assoc($result_pdf)['pdf_file_name'];

if (empty($pdf_file_name)) {
    echo "<p>Техническое обсуживание для данного серийного номера отсутствует.</p>";
} else {
    // Отображаем ссылку на скачивание PDF (если есть pdf_file_name)
    ?>
    <h1>Технические характеристики для серийного номера: <?php echo htmlspecialchars($serial_number, ENT_QUOTES); ?></h1>
    <form action="download.php" method="get">
        <input type="hidden" name="serial_number" value="<?php echo htmlspecialchars($serial_number, ENT_QUOTES); ?>">
        <button type="submit">Скачать Технические характеристики (PDF)</button>
    </form>
    <?php
}
?>

<hr>
<h2>Дополнительная информация об оборудовании</h2>

<?php
// 1. Основная информация об оборудовании
$sql1 = "SELECT e.serial_number,
            sc.system_code_description AS Equipment_Name,
            us.status_name AS Status,
            ln.account_name AS LPU,
            loc.city,
            loc.street
        FROM equipment e
        JOIN system_codes sc ON sc.system_code_id = e.system_code_id
        JOIN user_statuses us ON us.status_id = e.status_id
        JOIN accounts a ON a.account_id = e.account_id
        JOIN lpu_names ln ON ln.lpu_id = a.lpu_id
        JOIN locations loc ON loc.location_id = a.location_id
        WHERE e.serial_number = '$serial_number_esc'";
$result1 = mysqli_query($link, $sql1);
echo "<h3>1) Основная информация об оборудовании</h3>";
if ($result1 && mysqli_num_rows($result1) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Серийный номер</th><th>Название аппарата</th><th>Статус</th><th>ЛПУ</th><th>Город</th><th>Улица</th></tr>";
    while($row = mysqli_fetch_assoc($result1)) {
        echo "<tr>
                <td>{$row['serial_number']}</td>
                <td>{$row['Equipment_Name']}</td>
                <td>{$row['Status']}</td>
                <td>{$row['LPU']}</td>
                <td>{$row['city']}</td>
                <td>{$row['street']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 2. Возраст оборудования, даты EOL и EOS
$sql2 = "SELECT e.serial_number,
            e.system_age,
            e.equipment_eol,
            e.eos_bu
        FROM equipment e
        WHERE e.serial_number = '$serial_number_esc'";
$result2 = mysqli_query($link, $sql2);
echo "<h3>2) Возраст оборудования, EOL и EOS BU</h3>";
if ($result2 && mysqli_num_rows($result2) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Серийный номер</th><th>Возраст системы</th><th>EOL</th><th>EOS BU</th></tr>";
    while($row = mysqli_fetch_assoc($result2)) {
        echo "<tr>
                <td>{$row['serial_number']}</td>
                <td>{$row['system_age']}</td>
                <td>{$row['equipment_eol']}</td>
                <td>{$row['eos_bu']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 3. Информация о System BU
$sql3 = "SELECT e.serial_number,
            sb.system_bu_name
        FROM equipment e
        JOIN system_codes sc ON sc.system_code_id = e.system_code_id
        JOIN system_bu sb ON sb.system_bu_id = sc.system_bu_id
        WHERE e.serial_number = '$serial_number_esc'";
$result3 = mysqli_query($link, $sql3);
echo "<h3>3) Информация о System BU</h3>";
if ($result3 && mysqli_num_rows($result3) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Серийный номер</th><th>Название System BU</th></tr>";
    while($row = mysqli_fetch_assoc($result3)) {
        echo "<tr>
                <td>{$row['serial_number']}</td>
                <td>{$row['system_bu_name']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 4. ЛПУ и количество оборудования в нём
$sql4 = "SELECT ln.account_name AS LPU,
            COUNT(e2.equipment_id) AS Equipment_Count_In_This_LPU
        FROM equipment e
        JOIN accounts a ON a.account_id = e.account_id
        JOIN lpu_names ln ON ln.lpu_id = a.lpu_id
        JOIN equipment e2 ON e2.account_id = a.account_id
        WHERE e.serial_number = '$serial_number_esc'
        GROUP BY ln.account_name";
$result4 = mysqli_query($link, $sql4);
echo "<h3>4) ЛПУ и количество оборудования в нём</h3>";
if ($result4 && mysqli_num_rows($result4) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>ЛПУ</th><th>Количество оборудования</th></tr>";
    while($row = mysqli_fetch_assoc($result4)) {
        echo "<tr>
                <td>{$row['LPU']}</td>
                <td>{$row['Equipment_Count_In_This_LPU']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 5. Визиты мастеров
$sql5 = "SELECT u.full_name AS MasterName,
            u.email,
            me.visit_date,
            me.visit_count
        FROM master_equipment_visits me
        JOIN users u ON u.user_id = me.master_id
        JOIN equipment e ON e.equipment_id = me.equipment_id
        WHERE e.serial_number = '$serial_number_esc'";
$result5 = mysqli_query($link, $sql5);
echo "<h3>5) Визиты мастеров</h3>";
if ($result5 && mysqli_num_rows($result5) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Имя мастера</th><th>Email</th><th>Дата визита</th><th>Количество визитов</th></tr>";
    while($row = mysqli_fetch_assoc($result5)) {
        echo "<tr>
                <td>{$row['MasterName']}</td>
                <td>{$row['email']}</td>
                <td>{$row['visit_date']}</td>
                <td>{$row['visit_count']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных о визитах.</p>";
}

// 6. Статус и количество "Operational" оборудования в том же городе
$sql6 = "SELECT us.status_name AS Current_Equipment_Status,
            (SELECT COUNT(*)
             FROM equipment eq
             JOIN accounts ac ON ac.account_id = eq.account_id
             JOIN locations lc ON lc.location_id = ac.location_id
             JOIN user_statuses us2 ON us2.status_id = eq.status_id
             WHERE us2.status_name='Operational'
               AND lc.city = (SELECT loc.city 
                              FROM equipment e3
                              JOIN accounts a3 ON a3.account_id = e3.account_id
                              JOIN locations loc ON loc.location_id = a3.location_id
                              WHERE e3.serial_number='$serial_number_esc')
            ) AS Operational_In_Same_City
        FROM equipment e
        JOIN user_statuses us ON us.status_id = e.status_id
        WHERE e.serial_number='$serial_number_esc'";
$result6 = mysqli_query($link, $sql6);
echo "<h3>6) Статус и количество 'Operational' оборудования в том же городе</h3>";
if ($result6 && mysqli_num_rows($result6) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Текущий статус оборудования</th><th>Количество 'Operational' в том же городе</th></tr>";
    while($row = mysqli_fetch_assoc($result6)) {
        echo "<tr>
                <td>{$row['Current_Equipment_Status']}</td>
                <td>{$row['Operational_In_Same_City']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 7. Год достижения EOL и EOS BU
$sql7 = "SELECT e.serial_number,
            YEAR(e.equipment_eol) AS EOL_Year,
            YEAR(e.eos_bu) AS EOS_BU_Year
        FROM equipment e
        WHERE e.serial_number='$serial_number_esc'";
$result7 = mysqli_query($link, $sql7);
echo "<h3>7) Год достижения EOL и EOS BU</h3>";
if ($result7 && mysqli_num_rows($result7) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Серийный номер</th><th>Год EOL</th><th>Год EOS BU</th></tr>";
    while($row = mysqli_fetch_assoc($result7)) {
        echo "<tr>
                <td>{$row['serial_number']}</td>
                <td>{$row['EOL_Year']}</td>
                <td>{$row['EOS_BU_Year']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 8. Системный код и количество оборудования с таким же кодом
$sql8 = "SELECT e.serial_number,
            sc.system_code,
            (SELECT COUNT(*) 
             FROM equipment eq2 
             JOIN system_codes sc2 ON sc2.system_code_id = eq2.system_code_id
             WHERE sc2.system_code = sc.system_code) AS Same_System_Code_Count
        FROM equipment e
        JOIN system_codes sc ON sc.system_code_id = e.system_code_id
        WHERE e.serial_number='$serial_number_esc'";
$result8 = mysqli_query($link, $sql8);
echo "<h3>8) Системный код и количество оборудования с таким же кодом</h3>";
if ($result8 && mysqli_num_rows($result8) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Серийный номер</th><th>Системный код</th><th>Количество оборудования с таким же кодом</th></tr>";
    while($row = mysqli_fetch_assoc($result8)) {
        echo "<tr>
                <td>{$row['serial_number']}</td>
                <td>{$row['system_code']}</td>
                <td>{$row['Same_System_Code_Count']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 9. Проверка наличия более старых систем в том же ЛПУ
$sql9 = "SELECT e.serial_number,
            e.system_age,
            (SELECT COUNT(*) 
             FROM equipment eq2
             WHERE eq2.account_id = e.account_id
               AND eq2.system_age > e.system_age) AS Older_Systems_In_Same_LPU
        FROM equipment e
        WHERE e.serial_number='$serial_number_esc'";
$result9 = mysqli_query($link, $sql9);
echo "<h3>9) Более старые системы в том же ЛПУ</h3>";
if ($result9 && mysqli_num_rows($result9) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Серийный номер</th><th>Возраст системы</th><th>Количество более старых систем в том же ЛПУ</th></tr>";
    while($row = mysqli_fetch_assoc($result9)) {
        echo "<tr>
                <td>{$row['serial_number']}</td>
                <td>{$row['system_age']}</td>
                <td>{$row['Older_Systems_In_Same_LPU']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

// 10. Город и количество уникальных ЛПУ в том же городе
$sql10 = "SELECT loc.city,
            COUNT(DISTINCT ln.account_name) AS Unique_LPUs_In_Same_City
        FROM equipment e
        JOIN accounts a ON a.account_id = e.account_id
        JOIN lpu_names ln ON ln.lpu_id = a.lpu_id
        JOIN locations loc ON loc.location_id = a.location_id
        WHERE e.serial_number='$serial_number_esc'
        GROUP BY loc.city";
$result10 = mysqli_query($link, $sql10);
echo "<h3>10) Город и количество уникальных ЛПУ в том же городе</h3>";
if ($result10 && mysqli_num_rows($result10) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Город</th><th>Количество уникальных ЛПУ в том же городе</th></tr>";
    while($row = mysqli_fetch_assoc($result10)) {
        echo "<tr>
                <td>{$row['city']}</td>
                <td>{$row['Unique_LPUs_In_Same_City']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Нет данных.</p>";
}

mysqli_close($link);
?>

<?php include 'includes/footer.php'; ?>
