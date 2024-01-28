<?php
global $mysql;
$server = 'localhost';
$username = 'u888870075_gigl';
$password = 'Wahabolaadmin2015@';
$database = 'u888870075_gigl';

$mysql = mysqli_connect($server, $username, $password, $database);

if(!$mysql) {
    die("Error: " . mysqli_connect_error());
}