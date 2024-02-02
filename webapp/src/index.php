<?php
    session_start();

    use database\DatabaseHandler;
    use classes\GameLogic;
    use classes\Game;

    require_once './vendor/autoload.php';

    include_once 'util.php';

    $db = new DatabaseHandler();
    $logic = new GameLogic();
    $game = new Game($db, $logic);

    # handles post requests
    $game->handlePostRequest();

    if (!isset($_SESSION['board'])) {
        header('Location: restart.php');
        exit(0);
    }
    
    $board = $game->getBoard();
    $player = $game->getPlayer();
    // $hand = $game->getPlayerHand();

    $to = [];
    foreach ($GLOBALS['OFFSETS'] as $pq) {
        foreach (array_keys($board) as $pos) {
            $pq2 = explode(',', $pos);
            $to[] = ($pq[0] + $pq2[0]).','.($pq[1] + $pq2[1]);
        }
    }
    $to = array_unique($to);
    if (!count($to)) $to[] = '0,0';
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Hive</title>
        <link rel="stylesheet" href="style/style.css">
    </head>
    <body>
        <div class="board">
            <?php
                $min_p = 1000;
                $min_q = 1000;
                foreach ($board as $pos => $tile) {
                    $pq = explode(',', $pos);
                    if ($pq[0] < $min_p) $min_p = $pq[0];
                    if ($pq[1] < $min_q) $min_q = $pq[1];
                }
                foreach (array_filter($board) as $pos => $tile) {
                    $pq = explode(',', $pos);
                    $pq[0];
                    $pq[1];
                    $h = count($tile);
                    echo '<div class="tile player';
                    echo $tile[$h-1][0];
                    if ($h > 1) echo ' stacked';
                    echo '" style="left: ';
                    echo ($pq[0] - $min_p) * 4 + ($pq[1] - $min_q) * 2;
                    echo 'em; top: ';
                    echo ($pq[1] - $min_q) * 4;
                    echo "em;\">($pq[0],$pq[1])<span>";
                    echo $tile[$h-1][1];
                    echo '</span></div>';
                }
            ?>
        </div>
        <div class="hand">
            White:
            <?php
                foreach ($game->getPlayerHand(0) as $tile => $ct) {
                    for ($i = 0; $i < $ct; $i++) {
                        echo '<div class="tile player0"><span>'.$tile."</span></div> ";
                    }
                }
            ?>
        </div>
        <div class="hand">
            Black:
            <?php
            foreach ($game->getPlayerHand(1) as $tile => $ct) {
                for ($i = 0; $i < $ct; $i++) {
                    echo '<div class="tile player1"><span>'.$tile."</span></div> ";
                }
            }
            ?>
        </div>
        <div class="turn">
            Turn: <?php if ($player == 0) echo "White"; else echo "Black"; ?>
        </div>
        <form method="post">
            <select name="piece">
                <?php
                    foreach ($game->getPlayerHand($game->getPlayer()) as $tile => $ct) {
                        if ($ct	> 0) echo "<option value=\"$tile\">$tile</option>";
                    }
                ?>
            </select>
            <select name="to">
                <?php
                    foreach ($to as $pos) {
                        if (!isset($board[$pos])) echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <input type="hidden" name="action" value="play">
            <input type="submit" value="Play">
        </form>
        <form method="post">
            <select name="from">
                <?php
                    foreach (array_keys($board) as $pos) {
                        if ($board[$pos][0][0] == $player) echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <select name="to">
                <?php
                    foreach ($to as $pos) {
                        echo "<option value=\"$pos\">$pos</option>";
                    }
                ?>
            </select>
            <input type="hidden" name="action" value="move">
            <input type="submit" value="Move">
        </form>
        <form method="post">
        <input type="hidden" name="action" value="pass">
            <input type="submit" value="Pass">
        </form>
        <form method="post">
        <input type="hidden" name="action" value="restart">
            <input type="submit" value="Restart">
        </form>
        <strong><?php if (isset($_SESSION['error'])) echo($_SESSION['error']);?></strong>
        <ol>
            <?php
                $result = $db->getPreviousMoves($_SESSION['game_id']);
                while ($row = $result->fetch_array()) {
                    echo '<li>'.$row[2].' '.$row[3].' '.$row[4].'</li>';
                }
            ?>
        </ol>
        <form method="post" action="undo.php">
        <input type="hidden" name="action" value="undo">
            <input type="submit" value="Undo">
        </form>
    </body>
</html>

