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

    private function get_state()
    {
        return serialize([$_SESSION['hand'], $_SESSION['board'], $_SESSION['player']]);
    }

    private function set_state($state)
    {
        list($a, $b, $c) = unserialize($state);
        $_SESSION['hand'] = $a;
        $_SESSION['board'] = $b;
        $_SESSION['player'] = $c;
    }
}