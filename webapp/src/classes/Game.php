<?php

namespace classes;

use database\DatabaseHandler;

class Game
{
    private DatabaseHandler $db;
    private GameLogic $logic;

    public function __construct($databaseHandler, $logic)
    {
        $this->db = $databaseHandler;
        $this->logic = $logic;
    }

    public function post()
    {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'play':
                    $this->play();
                    break;
                case 'move':
                    $this->move();
                    break;
                case 'pass':
                    $this->pass();
                    break;
                case 'restart':
                    $this->restart();
                    break;
                // ca se 'undo':
                //     $game->undo();
                //     break;
            }
            $this->redirectToIndex();
        }
    }

    private function redirectToIndex()
    {
        header('Location: index.php');
    }

    public function play()
    {
        $piece = $_POST['piece'];
        $to = $_POST['to'];

        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];

        if (!$hand[$piece])
            $_SESSION['error'] = "Player does not have tile";
        elseif (isset($board[$to]))
            $_SESSION['error'] = 'Board position is not empty';
        elseif (count($board) && !$this->logic->hasNeighBour($to, $board))
            $_SESSION['error'] = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->logic->neighboursAreSameColor($player, $to, $board))
            $_SESSION['error'] = "Board position has opposing neighbour";
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            $_SESSION['error'] = 'Must play queen bee';
        } else {
            $_SESSION['board'][$to] = [[$_SESSION['player'], $piece]];
            $_SESSION['hand'][$player][$piece]--;
            $_SESSION['player'] = 1 - $_SESSION['player'];
            $_SESSION['last_move'] = $this->db->play($_SESSION['game_id'], $piece, $to, $_SESSION['last_move']);
        }
    }

    public function move()
    {
        $from = $_POST['from'];
        $to = $_POST['to'];

        $player = $_SESSION['player'];
        $board = $_SESSION['board'];
        $hand = $_SESSION['hand'][$player];
        unset($_SESSION['error']);

        if (!isset($board[$from]))
            $_SESSION['error'] = 'Board position is empty';
        elseif ($board[$from][count($board[$from])-1][0] != $player)
            $_SESSION['error'] = "Tile is not owned by player";
        elseif ($hand['Q'])
            $_SESSION['error'] = "Queen bee is not played";
        else {
            $tile = array_pop($board[$from]);
            if (!hasNeighBour($to, $board))
                $_SESSION['error'] = "Move would split hive";
            else {
                $all = array_keys($board);
                $queue = [array_shift($all)];
                while ($queue) {
                    $next = explode(',', array_shift($queue));
                    foreach ($GLOBALS['OFFSETS'] as $pq) {
                        list($p, $q) = $pq;
                        $p += $next[0];
                        $q += $next[1];
                        if (in_array("$p,$q", $all)) {
                            $queue[] = "$p,$q";
                            $all = array_diff($all, ["$p,$q"]);
                        }
                    }
                }
                if ($all) {
                    $_SESSION['error'] = "Move would split hive";
                } else {
                    if ($from == $to) $_SESSION['error'] = 'Tile must move';
                    elseif (isset($board[$to]) && $tile[1] != "B") $_SESSION['error'] = 'Tile not empty';
                    elseif ($tile[1] == "Q" || $tile[1] == "B") {
                        if (!slide($board, $from, $to))
                            $_SESSION['error'] = 'Tile must slide';
                    }
                }
            }
            if (isset($_SESSION['error'])) {
                if (isset($board[$from])) array_push($board[$from], $tile);
                else $board[$from] = [$tile];
            } else {
                if (isset($board[$to])) array_push($board[$to], $tile);
                else $board[$to] = [$tile];
                $_SESSION['player'] = 1 - $_SESSION['player'];
                $_SESSION['last_move'] = $this->db->move($_SESSION['game_id'], $from, $to, $_SESSION['last_move']);
            }
            $_SESSION['board'] = $board;
        }
    }

    public function pass()
    {
        $_SESSION['last_move'] = $this->db->pass($_SESSION['game_id'], $_SESSION['last_move']);
        $_SESSION['player'] = 1 - $_SESSION['player'];
    }

    public function restart()
    {
        $_SESSION['board'] = [];
        $_SESSION['hand'] = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $_SESSION['player'] = 0;
        $_SESSION['game_id'] = $this->db->restart();
    }
}
