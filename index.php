<?php include 'includes/header.php'; ?>

<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Подключение к БД
$link = mysqli_connect("localhost", "root", "1sWOtKrGAn5I3l1Y", "philips");

if (!$link) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Обработка формы
$selected_code = '';
$equipments = [];

if (isset($_POST['system_code']) && !empty($_POST['system_code'])) {
    $selected_code = mysqli_real_escape_string($link, $_POST['system_code']);

    // Получим system_code_id по выбранному коду
    $code_sql = "SELECT system_code_id FROM system_codes WHERE system_code = '$selected_code'";
    $code_result = mysqli_query($link, $code_sql);

    if ($code_result && mysqli_num_rows($code_result) > 0) {
        $code_row = mysqli_fetch_assoc($code_result);
        $system_code_id = (int)$code_row['system_code_id'];

        // Теперь получим все оборудование с этим system_code_id
        $equip_sql = "SELECT 
                            e.equipment_id,
                            e.serial_number,
                            sc.system_code_description,
                            us.status_name,
                            ln.account_name,
                            loc.city,
                            loc.street,
                            e.system_age,
                            e.equipment_eol,
                            e.eos_bu
                        FROM equipment e
                        JOIN system_codes sc ON sc.system_code_id = e.system_code_id
                        LEFT JOIN user_statuses us ON us.status_id = e.status_id
                        LEFT JOIN accounts a ON a.account_id = e.account_id
                        LEFT JOIN lpu_names ln ON ln.lpu_id = a.lpu_id
                        LEFT JOIN locations loc ON loc.location_id = a.location_id
                        WHERE e.system_code_id = $system_code_id";

        $equip_result = mysqli_query($link, $equip_sql);

        if ($equip_result) {
            $equipments = mysqli_fetch_all($equip_result, MYSQLI_ASSOC);
        } else {
            echo "Ошибка в запросе к таблице equipment: " . mysqli_error($link);
        }
    }
}

// Получим список всех системных кодов для выпадающего списка или автодополнения
$all_codes = [];
$all_codes_sql = "SELECT system_code FROM system_codes ORDER BY system_code";
$all_codes_result = mysqli_query($link, $all_codes_sql);
if ($all_codes_result) {
    $all_codes = mysqli_fetch_all($all_codes_result, MYSQLI_ASSOC);
}
?>
<h1>Поиск медицинской системы</h1>
<form method="POST" action="">
    <label for="system_code">Системный код:</label>
    <input list="codes" name="system_code" id="system_code" value="<?php echo htmlspecialchars($selected_code, ENT_QUOTES); ?>">
    <datalist id="codes">
        <?php foreach ($all_codes as $c) { ?>
            <option value="<?php echo htmlspecialchars($c['system_code'], ENT_QUOTES); ?>">
        <?php } ?>
    </datalist>
    <button type="submit">Поиск</button>
</form>

<?php if (!empty($selected_code)): ?>
    <h2>Результаты поиска для кода: <?php echo htmlspecialchars($selected_code, ENT_QUOTES); ?></h2>
    <?php if (!empty($equipments)) { ?>
        <table border="1" cellpadding="5">
            <tr>
                <th>ID оборудования</th>
                <th>Серийный номер</th>
                <th>Описание</th>
                <th>Статус</th>
                <th>ЛПУ</th>
                <th>Город</th>
                <th>Улица</th>
                <th>Возраст системы</th>
                <th>EOL</th>
                <th>EOS BU</th>
            </tr>
            <?php foreach ($equipments as $eq) { ?>
                <tr>
                    <td><?php echo $eq['equipment_id']; ?></td>
                    <!-- Делаем серийный номер ссылкой на новую страницу -->
                    <td>
                        <a href="pdf_page.php?serial_number=<?php echo urlencode($eq['serial_number']); ?>">
                            <?php echo htmlspecialchars($eq['serial_number'], ENT_QUOTES); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($eq['system_code_description'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($eq['status_name'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($eq['account_name'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($eq['city'], ENT_QUOTES); ?></td>
                    <td><?php echo htmlspecialchars($eq['street'], ENT_QUOTES); ?></td>
                    <td><?php echo (int)$eq['system_age']; ?></td>
                    <td><?php echo $eq['equipment_eol']; ?></td>
                    <td><?php echo $eq['eos_bu']; ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>Оборудование с указанным кодом не найдено.</p>
    <?php } ?>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
