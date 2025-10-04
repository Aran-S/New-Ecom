<?php
$dbuser = "root";
$dbpass = "";
$host = "localhost";
$db = "e_com";
$mysqli = new mysqli($host, $dbuser, $dbpass, $db);



define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'e_com');
// Establish database connection.
try {
    $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit("Error: " . $e->getMessage());
}


define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DATABASE_NAME', 'e_com');
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DATABASE_NAME);
if ($link === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}


$host = "localhost"; /* Host name */
$user = "root"; /* User */
$password = ""; /* Password */
$dbname = "e_com"; /* Database name */
$con = mysqli_connect($host, $user, $password, $dbname);
if ($con === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}
