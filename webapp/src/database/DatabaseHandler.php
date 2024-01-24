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
}