<?php

namespace classes;

use database\DatabaseHandler;

class Game
{
    private DatabaseHandler $db;
    private GameLogic $logic;

    private $gameId;
    private $hand;
    private $player;
    private $board;
    private $lastMove;
    private $error;

    public function __construct($databaseHandler, $logic)
    {
        $this->db = $databaseHandler;
        $this->logic = $logic;

        $this->gameId = $_SESSION['game_id'];
        $this->hand = $_SESSION['hand'];
        $this->player = $_SESSION['player'];
        $this->board = $_SESSION['board'];
        $this->lastMove = $_SESSION['last_move'] ?? null;
        $this->error = $_SESSION['error'] ?? '';
    }

    public function handlePostRequest()
    {
        $this->clearError();

        if (isset($_POST['action'])) {
            $this->processRequests($_POST['action']);
            $this->updateSessionState();
            $this->redirectToIndex();
        }
    }

    private function processRequests($actionRequest)
    {
        switch($actionRequest) {
            case 'play':
                $piece = $_POST['piece'];
                $to = $_POST['to'];
                $this->play($piece, $to);
                break;
            case 'move':
                $from = $_POST['from'];
                $to = $_POST['to'];
                $this->move($from, $to);
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
    }

    private function updateSessionState()
    {
        $_SESSION["game_id"] = $this->gameId;
        $_SESSION["player"] = $this->player;
        $_SESSION["hand"] = $this->hand;
        $_SESSION["board"] = $this->board;
        $_SESSION["last_move"] = $this->lastMove;
        $_SESSION["error"] = $this->error;
    }

    private function clearError()
    {
        $this->error = '';
    }

    public function getError()
    {
        return $this->error;
    }

    public function getPlayer()
    {
        return $this->player;
    }
    
    public function setPlayer($player)
    {
        $this->player = $player;
    }

    public function getPlayerHand($index)
    {
        return $this->hand[$index];
    }

    public function setPlayerHand($index, $hand)
    {
        $this->hand[$index] = $hand;
    }

    public function getBoard()
    {
        return $this->board;
    }

    public function setBoard($to, $piece)
    {
        $this->board[$to] = [[$this->player, $piece]];
    }

    private function redirectToIndex()
    {
        header('Location: index.php');
    }

    public function play($piece, $to)
    {
        $player = $this->player;
        $board = $this->board;
        $hand = $this->hand[$player];

        if (!$hand[$piece])
            $this->error = "Player does not have tile";
        elseif (isset($board[$to]))
            $this->error = 'Board position is not empty';
        elseif (count($this->board) && !$this->logic->hasNeighBour($to, $this->board))
            $this->error = "board position has no neighbour";
        elseif (array_sum($hand) < 11 && !$this->logic->neighboursAreSameColor($this->player, $to, $this->board))
            $this->error = "Board position has opposing neighbour";
        elseif (array_sum($hand) <= 8 && $hand['Q']) {
            # bug 3 fix
            if ($piece != 'Q') $this->error = 'Must play queen bee';
            else {
                $this->setBoard($to, $piece);
                $this->hand[$player][$piece]--;
                $this->player = 1 - $this->player;
                $this->lastMove = $this->db->play($this->gameId, $piece, $to, $this->lastMove);
            }
        } else {
            $this->setBoard($to, $piece);
            $this->hand[$player][$piece]--;
            $this->player = 1 - $this->player;
            $this->lastMove = $this->db->play($this->gameId, $piece, $to, $this->lastMove);
        }
    }

    public function move($from, $to)
    {

        $player = $this->player;
        $board = $this->board;
        $hand = $this->hand[$player];
        unset($_SESSION['error']);

        if (!isset($board[$from]))
            $this->error = 'Board position is empty';
        elseif ($board[$from][count($board[$from])-1][0] != $player)
            $this->error = "Tile is not owned by player";
        elseif ($hand['Q'])
            $this->error = "Queen bee is not played";
        else {
            $tile = array_pop($board[$from]);
            if (!$this->logic->hasNeighBour($to, $board))
                $this->error = "Move would split hive";
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
                if ($tile[1] == 'G'){
                    if (!$this->validateGrasshopperMove($board, $from, $to)) {
                        $this->error = 'This is not a valid Grasshopper move';
                        return;
                    }
                }
                if ($tile[1] == 'A'){
                    if (!$this->validateAntMove($board, $from, $to)) {
                        $this->error = 'This is not a valid Ant move';
                        return;
                    }
                }
                if ($tile[1] == 'S'){
                    if (!$this->validateSpiderMove($board, $from, $to)) {
                        $this->error = 'This is not a valid Spider move';
                        return;
                    }
                }
                if ($all) {
                    $this->error = "Move would split hive";
                } else {
                    if ($from == $to) $this->error = 'Tile must move';
                    elseif (isset($board[$to]) && $tile[1] != "B") $this->error = 'Tile not empty';
                    elseif ($tile[1] == "Q" || $tile[1] == "B") {
                        if (!$this->logic->slide($board, $from, $to))
                            $this->error = 'Tile must slide';
                    }
                }
            }
            if (isset($_SESSION['error'])) {
                if (isset($board[$from])) array_push($board[$from], $tile);
                else $board[$from] = [$tile];
            } else {
                if (isset($board[$to])) array_push($board[$to], $tile);
                else $board[$to] = [$tile];
                $this->player = 1 - $player;
                $this->lastMove = $this->db->move($this->gameId, $from, $to, $this->lastMove);
                # bug 4 fix
                unset($board[$from]);
            }
            $this->board = $board;
        }
    }

    public function pass()
    {
        $this->lastMove = $this->db->pass($this->gameId, $this->lastMove);
        $this->player = 1 - $this->player;
    }

    public function restart()
    {
        $this->board = [];
        $this->hand = [0 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3], 1 => ["Q" => 1, "B" => 2, "S" => 2, "A" => 3, "G" => 3]];
        $this->player = 0;
        $this->gameId = $this->db->restart();
        $this->error = '';
        $this->lastMove = null;
    }

    private function validateGrasshopperMove($board, $from, $to)
    {
        if ($from === $to) {
            return false;
        }

        $fromExplode = explode(',', $from);
        $toExplode = explode(',', $to);

        $direction = [$toExplode[0] - $fromExplode[0], $toExplode[1] - $fromExplode[1]];

        if (!(($direction[0] == 0 && $direction[1] != 0) || ($direction[1] == 0 && $direction[0] != 0) || ($direction[0] == $direction[1]))) {
            return false;
        }

        $x = $fromExplode[0] + $direction[0];
        $y = $fromExplode[1] + $direction[1];

        while ($x != $toExplode[0] || $y != $toExplode[1]) {
            $pos = $x . "," . $y;

            if (isset($board[$pos])) {
                return false;
            }

            $x += $direction[0];
            $y += $direction[1];
        }

        return true;
    }

    private function validateAntMove($board, $from, $to)
    {
        if ($from === $to || isset($board[$to]) || !$this->logic->hasNeighBour($to, $board)) {
            return false;
        }

        return true;
    }

    private function validateSpiderMove($board, $from, $to)
    {
        if ($from === $to || isset($board[$to]) || !$this->logic->hasNeighBour($to, $board)) {
            return false;
        }

        $amountOfSteps = $this->calculateSteps($from, $to);

        if ($amountOfSteps != 3) {
            return false;
        }

        return true;
    }

    private function calculateSteps($from, $to) {
        $fromExplode = explode(',', $from);
        $toExplode = explode(',', $to);

        $horizontalDistance = abs($fromExplode[0] - $toExplode[0]); 
        $verticalDistance = abs($fromExplode[1] - $toExplode[1]); 

        return $horizontalDistance + $verticalDistance;
    }
}
