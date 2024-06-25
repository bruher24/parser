<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Parser</title>
</head>
<body>
<form action="./src/parse.php" method="post">
    <label>
        <input type="text" name="tradeNumber" value="31710-ОТПП" required>
    </label>
    <label>
        <input type="number" min="1" max="9999" name="lotNumber" value="6" required>
    </label>
    <label>
        <input type="submit" value="Найти">
    </label>
</form>
<table border="1px solid black">
    <thead>
    <tr>
        <th>Ссылка</th>
        <th>ИНН</th>
        <th>Email</th>
        <th>Телефон</th>
        <th>Номер дела</th>
        <th>Описание лота</th>
        <th>Цена</th>
        <th>Дата начала</th>
        <th>Дата проведения</th>
    </tr>
    </thead>
    <tbody>
    <?php
    include_once './src/db.php';
    $mysqli = new mysqli('localhost', 'root', '', 'parsed_info');
    $result = $mysqli->query("SELECT url, inn, email, phone, case_number, lot_info, price, date_start, date_on FROM `lots`;");
    $mysqli->close();

    foreach ($result as $row) {
        $output .= "<tr>";
        foreach ($row as $item) {
            $output .= "<td>$item</td>";
        }
        $output .= "</tr>";
    }
    echo $output;
    ?>
    </tbody>
</table>
</body>
</html>