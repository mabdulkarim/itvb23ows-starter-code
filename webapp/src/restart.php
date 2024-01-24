<?php

require_once 'database/DatabaseHandler.php';

use database\DatabaseHandler;

$db = new DatabaseHandler();

session_start();

$_SESSION['board'] = [];
$_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
$_SESSION['player'] = 0;
$_SESSION['game_id'] = $db->restart();

header('Location: index.php');

?>