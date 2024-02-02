<?php

session_start();

require_once 'database/DatabaseHandler.php';

use database\DatabaseHandler;

$db = new DatabaseHandler();

include_once 'util.php';

$piece = $_POST['piece'];
$to = $_POST['to'];

$player = $_SESSION['player'];
$board = $_SESSION['board'];
$hand = $_SESSION['hand'][$player];

if (!$hand[$piece])
    $_SESSION['error'] = "Player does not have tile";
elseif (isset($board[$to]))
    $_SESSION['error'] = 'Board position is not empty';
elseif (count($board) && !hasNeighBour($to, $board))
    $_SESSION['error'] = "board position has no neighbour";
elseif (array_sum($hand) < 11 && !neighboursAreSameColor($player, $to, $board))
    $_SESSION['error'] = "Board position has opposing neighbour";
elseif (array_sum($hand) <= 8 && $hand['Q']) {
    $_SESSION['error'] = 'Must play queen bee';
} else {
    $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
    $_SESSION['hand'][$player][$piece]--;
    $_SESSION['player'] = 1 - $_SESSION['player'];
    $_SESSION['last_move'] = $db->play($_SESSION['game_id'], $piece, $to, $_SESSION['last_move']);
}

header('Location: index.php');

?>