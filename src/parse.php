<?php
include_once "db.php";
include_once "simple_html_dom.php";

function getPage(string $url, string $referer = 'https://google.com')
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_USERAGENT,
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 OPR/109.0.0.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, $referer);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

function send(array $data): bool
{
    $mysqli = new mysqli('localhost', 'root', '', 'parsed_info');
    $stmt = $mysqli->prepare("SELECT id FROM `lots` WHERE case_number = ? AND lot_info = ?");
    $stmt->bind_param("ss", $data['case_number'], $data['lot_info']);
    if (!$stmt->execute()) {
        var_dump($stmt->error);
        $stmt->close();
        $mysqli->close();
        return false;
    }
    $rows = $stmt->get_result()->num_rows;
    $stmt->close();
    if ($rows == 0) {
        $stmt = $mysqli->prepare("INSERT INTO `lots`(url, lot_info, price, email, phone, inn, case_number, date_start, date_on) VALUES (?,?,?,?,?,?,?,?,?);");
        $stmt->bind_param("ssdssssss", $data['link'], $data['lot_info'], $data['price'], $data['email'], $data['phone'],
            $data['inn'], $data['case_number'], $data['date_start'], $data['date_on']);
        if (!$stmt->execute()) {
            var_dump($stmt->error);
            $stmt->close();
            $mysqli->close();
            return false;
        }
        $stmt->close();
        $mysqli->close();
        return true;

    }
    $stmt = $mysqli->prepare("UPDATE `lots` SET url = ?, lot_info = ?, price = ?, email = ?, phone = ?, inn = ?, case_number = ?, date_start = ?, date_on = ? WHERE case_number = ? AND lot_info = ?;");
    $stmt->bind_param("ssdssssssss", $data['link'], $data['lot_info'], $data['price'], $data['email'], $data['phone'],
        $data['inn'], $data['case_number'], $data['date_start'], $data['date_on'], $data['case_number'],
        $data['lot_info']);
    if (!$stmt->execute()) {
        var_dump($stmt->error);
        $stmt->close();
        $mysqli->close();
        return false;
    }
    $stmt->close();
    $mysqli->close();
    return true;
}

$tradeNum = htmlentities($_POST['tradeNumber']);
$lotNum = htmlentities($_POST['lotNumber']);

$url = "https://nistp.ru/?lot_description=&trade_number=$tradeNum&lot_number=$lotNum&debtor_info=&arbitr_info=&app_start_from=&app_start_to=&app_end_from=&app_end_to=&trade_type=Любой&trade_state=Любой&trade_org=&pagenum=";

$page = getPage($url);
$html = str_get_html($page);
$post = $html->find('table.data', 0);
$tr = $post->find('tr', 1);
if ($tr->find('td', 1) == null) {
    $parsedData = "Лот не найден";
} else {
    $parsedData['link'] = $tr->find('a', 0)->href;
    $html->clear();

    $page = getPage($parsedData['link']);
    $html = str_get_html($page);
    $tables = $html->find('table.node_view');
    foreach ($tables as $table) {
        if ($table->id == "table_lot_$lotNum") {
            $tds = $table->find('td');
            foreach ($tds as $td) {
                if ($td->plaintext == "Cведения об имуществе (предприятии) должника, выставляемом на торги, его составе, характеристиках, описание") {
                    $parsedData['lot_info'] = $td->next_sibling()->plaintext;
                }
                if ($td->plaintext == "Начальная цена") {
                    $parsedData['price'] = str_replace(' ', '', $td->next_sibling()->plaintext);
                }
            }
        }
        $ths = $table->find('th');
        foreach ($ths as $th) {
            if ($th->plaintext == "Информация о должнике ") {
                $tds = $table->find('td');
                foreach ($tds as $td) {
                    if ($td->plaintext == "ИНН") {
                        $parsedData['inn'] = $td->next_sibling()->plaintext;
                    }
                    if ($td->plaintext == "Номер дела о банкротстве") {
                        $parsedData['case_number'] = $td->next_sibling()->plaintext;
                    }
                }
            }
            if ($th->plaintext == "Контактное лицо организатора торгов ") {
                $tds = $table->find('td');
                foreach ($tds as $td) {
                    if ($td->plaintext == "E-mail") {
                        $parsedData['email'] = $td->next_sibling()->plaintext;
                    }
                    if ($td->plaintext == "Телефон") {
                        $parsedData['phone'] = $td->next_sibling()->plaintext;
                    }
                }
            }
            if ($th->plaintext == "Информация о ходе торгов ") {
                $tds = $table->find('td');
                foreach ($tds as $td) {
                    if ($td->plaintext == "Дата начала представления заявок на участие") {
                        $parsedData['date_start'] = substr($td->next_sibling()->plaintext, 0, 10);
                        $dateStart = explode('.', $parsedData['date_start']);
                        $parsedData['date_start'] = $dateStart[2] . '-' . $dateStart[1] . '-' . $dateStart[0];
                    }
                    if ($td->plaintext == "Дата проведения") {
                        $parsedData['date_on'] = substr($td->next_sibling()->plaintext, 0, 10);
                        $dateEnd = explode('.', $parsedData['date_on']);
                        $parsedData['date_on'] = $dateEnd[2] . '-' . $dateEnd[1] . '-' . $dateEnd[0];
                    }
                }
            }
        }
    }
}
if (is_array($parsedData)) {
    if (!send($parsedData)) {
        die("ERROR");
    }
    $output = "
        <html>
        <head>
        <title>Поиск лота</title>
</head>
<body>
<table border='1px solid black'>
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
<tr>
<td><a href={$parsedData['link']}>{$parsedData['link']}</a></td>
<td>{$parsedData['inn']}</td>
<td>{$parsedData['email']}</td>
<td>{$parsedData['phone']}</td>
<td>{$parsedData['case_number']}</td>
<td>{$parsedData['lot_info']}</td>
<td>{$parsedData['price']}</td>
<td>{$parsedData['date_start']}</td>
<td>{$parsedData['date_on']}</td>
</tr>
</tbody>
</table>
</body>";
} else {
    $output = $parsedData;
}
echo $output;
