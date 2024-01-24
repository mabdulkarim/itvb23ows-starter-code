<?php

namespace database;
use mysqli;

class DatabaseHandler
{
    private $db;

    public function __construct()
    {
        $this->db = new mysqli('localhost', 'root', '', 'hive');
    }

    private function getState()
    {
        return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
    }

    private function setState($state)
    {
        list($a, $b, $c) = unserialize($state);
        $_SESSION['hand'] = $a;
        $_SESSION['board'] = $b;
        $_SESSION['player'] = $c;
    }

    public function getPreviousMoves($game_id)
    {
        $stmt = $this->db->prepare('SELECT * FROM moves WHERE game_id = '.$game_id);
        $stmt->execute();

        return  $stmt->get_result();
    }

    public function play($gameId, $piece, $moveTo, $previousId)
    {
        $stmt = $this->db->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "play", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $piece, $moveTo, $previousId, $this->getState());
        $stmt->execute();

        return $this->db->insert_id;
    }

    public function pass($gameId, $previousId)
    {
        $stmt = $this->db->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "pass", null, null, ?, ?)');
        $stmt->bind_param('iis', $gameId, $previousId, $this->getState());
        $stmt->execute();

        return $this->db->insert_id;
    }

    public function move($gameId, $piece, $moveTo, $previousId)
    {
        $stmt = $this->db->prepare('INSERT INTO moves (game_id, type, move_from, move_to, previous_id, state) VALUES (?, "move", ?, ?, ?, ?)');
        $stmt->bind_param('issis', $gameId, $piece, $moveTo, $previousId, $this->getState());
        $stmt->execute();

        return $this->db->insert_id;
    }

    public function restart()
    {
        $stmt = $this->db->prepare('INSERT INTO games VALUES ()');
        $stmt->execute();

        return $this->db->insert_id;
    }
}