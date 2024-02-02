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
    private $aiMoveNumber;

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
        $this->aiMoveNumber = $_SESSION['ai_move_number'] ?? 0;
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
            case 'ai':
                $this->ai();
                break;
            // case 'undo':
            //     $this->undo();
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

    private function redirectToIndex()
    {
        header('Location: index.php');
    }

    public function ai()
    {
        $url = 'http://ai:5000/';
        $body = [
            'move_number' => $this->aiMoveNumber++,
            'hand' => $this->hand,
            'board' => $this->board
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($body),
            ],
        ];

        $context = stream_context_create($options);
        $result = json_decode(file_get_contents($url, false, $context));

        if ($result[0] == 'play') $this->play($result[1], $result[2]);
        elseif ($result[0] == 'move') $this->move($result[1], $result[2]);
        elseif ($result[0] == 'pass') $this->pass();
        else $this->error = 'AI returned invalid move';
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
        if ($this->canMove($from, $to)) {
            return;
        }

        $tile = array_pop($this->board[$from]);

        if (isset($this->board[$to])) {
            array_push($this->board[$to], $tile);
        } else {
            $this->board[$to] = [$tile];
        }

        $this->player = 1 - $this->player;
        $this->lastMove = $this->db->move($this->gameId, $from, $to, $this->lastMove);

        if (empty($this->board[$from])) {
            unset($this->board[$from]);
        }
    }

    public function pass()
    {
        if ($this->canPass()) {
            $this->lastMove = $this->db->pass($this->gameId, $this->lastMove);
            $this->player = 1 - $this->player;
        } else {
            $this->error = 'Cannot pass';
        }
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

    private function getAllPositions()
    {
        $to = [];
        foreach ($GLOBALS['OFFSETS'] as $pq) {
            foreach (array_keys($this->board) as $pos) {
                $pq2 = explode(',', $pos);
                $to[] = ($pq[0] + $pq2[0]).','.($pq[1] + $pq2[1]);
            }
        }
        $to = array_unique($to);
        if (!count($to)) $to[] = '0,0';

        return $to;
    }

    private function getAllPossiblePositions()
    {
        $allPositions = $this->getAllPositions();

        return array_filter($allPositions, function($position) {
            return $this->checkValidPosition($position);
        });
    }

    private function checkValidPosition($position)
    {
        $isOccupied = isset($this->board[$position]);
        $hasNoNeighbours = !$this->logic->hasNeighBour($position, $this->board);
        $tilesPlayed = array_sum($this->hand[$this->player]);
        $queenNotPlayed = $tilesPlayed <= 8 && $this->hand[$this->player]['Q'];
        $neighbourColorIssue = $tilesPlayed < 11 && !$this->logic->neighboursAreSameColor($this->player, $position, $this->board);
        
        if ($isOccupied || $hasNoNeighbours || $neighbourColorIssue || $queenNotPlayed) {
            return false;
        }
    
        return true;
    }
    
    public function canPass()
    {
        if (!empty($this->getAllPossiblePositions())) {
            foreach ($this->board as $position => $piece) {
                if ($piece[0][0] == $this->player) {
                    foreach ($this->getAllPositions() as $possibleMove) {
                        if ($this->canMove($position, $possibleMove)) return false;
                    }
                }
            }
        } else if (count($this->hand[$this->player]) > 0) {
            return false;
        }

        return true;
    }

    private function canMove($from, $to)
    {
        $player = $this->player;
        $board = $this->board;
        $hand = $this->hand[$player];
        unset($_SESSION['error']);

        if (!isset($board[$from])) {
            $this->error = 'Board position is empty';
        } elseif ($board[$from][count($board[$from])-1][0] != $player) {
            $this->error = "Tile is not owned by player";
        } elseif ($hand['Q']) {
            $this->error = "Queen bee is not played";
        } elseif ($from == $to) {
            $this->error = 'Tile must move';
        } elseif (isset($board[$to]) && $board[$from][count($board[$from])-1][1] != "B") {
            $this->error = 'Tile not empty';
        } elseif (!$this->logic->hasNeighBour($to, $board)) {
            $this->error = "Move would split hive";
        } else {
            $tile = end($board[$from]);
            if ($tile[1] == 'G' && !$this->validateGrasshopperMove($board, $from, $to)) {
                $this->error = 'This is not a valid Grasshopper move';
            } elseif ($tile[1] == 'A' && !$this->validateAntMove($board, $from, $to)) {
                $this->error = 'This is not a valid Ant move';
            } elseif ($tile[1] == 'S' && !$this->validateSpiderMove($board, $from, $to)) {
                $this->error = 'This is not a valid Spider move';
            } else {
                $allPositionsBeforeMove = array_keys($board);
                if (($key = array_search($from, $allPositionsBeforeMove)) !== false) {
                    unset($allPositionsBeforeMove[$key]);
                }
                $allPositionsBeforeMove[] = $to; 
                if (!$this->checkHiveConnectivity($allPositionsBeforeMove)) {
                    $this->error = "Move would split hive";
                } else {
                    if (($tile[1] == "Q" || $tile[1] == "B") && !$this->logic->slide($board, $from, $to)) {
                        $this->error = 'Tile must slide';
                    }
                }
            }
        }

        return $this->error !== '';
    }
    
    private function checkHiveConnectivity($positions)
    {
        if (empty($positions)) return true; 

        $visited = [];
        $queue = [current($positions)]; 
        while ($queue) {
            $current = array_shift($queue);
            if (!in_array($current, $visited)) {
                $visited[] = $current;
                $nextPositions = $this->getAdjacentPositions($current, $positions);
                foreach ($nextPositions as $next) {
                    if (!in_array($next, $queue) && !in_array($next, $visited)) {
                        $queue[] = $next;
                    }
                }
            }
        }

        return count($visited) == count($positions);
    }

    private function getAdjacentPositions($current, $positions)
    {
        list($x, $y) = explode(',', $current);
        $adjacent = [];

        foreach ($GLOBALS['OFFSETS'] as $offset) {
            list($dx, $dy) = $offset;
            $neighbor = ($x + $dx) . ',' . ($y + $dy);
            if (in_array($neighbor, $positions)) {
                $adjacent[] = $neighbor;
            }
        }

        return $adjacent;
    }

    function hasWon($board, $player)
    {
        $isQueenSurrounded = false;
    
        foreach ($board as $coordinate => $piece) {
            $tile = $piece[count($piece) - 1];
    
            if ($tile[0] === $player && $tile[1] === 'Q') {
                list($x, $y) = explode(',', $coordinate);
                $encircled = 0;
    
                foreach ($GLOBALS['OFFSETS'] as $direction) {
                    $adjacent = ($x + $direction[0]) . ',' . ($y + $direction[1]);
                    if (array_key_exists($adjacent, $board)) {
                        $encircled += 1; 
                    }
                }

                if ($encircled === 6) {
                    $isQueenSurrounded = true;
                    break;
                }
            }
        }
    
        return $isQueenSurrounded;
    }
    
}
