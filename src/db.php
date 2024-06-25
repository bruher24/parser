<?php
$mysqli = new mysqli('localhost', 'root', '');
if (mysqli_connect_errno()) {
    exit('Connect failed: ' . mysqli_connect_error());
}
$sql = "CREATE DATABASE IF NOT EXISTS `parsed_info` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
$mysqli->query($sql);
$mysqli->select_db('parsed_info');

$sql = "CREATE TABLE IF NOT EXISTS `lots`(
    id int primary key AUTO_INCREMENT,
    url varchar(100) not null,
    lot_info varchar(250) not null,
    price decimal(10,2) not null,
    email varchar(100) not null,
    phone varchar(30) not null,
    inn varchar(50) not null,
    case_number varchar(50) not null,
    date_start date not null,
    date_on date
)";

if (!$mysqli->query($sql)) {
    exit('query failed: ' . $mysqli->error);
}
$mysqli->close();