<?php

require_once 'database/DatabaseHandler.php';

use database\DatabaseHandler;

$db = new DatabaseHandler();

session_start();

$_SESSION['last_move'] = $db->pass($_SESSION['game_id'], $_SESSION['last_move']);
$_SESSION['player'] = 1 - $_SESSION['player'];

header('Location: index.php');

?>