<?php

use database\DatabaseHandler;
use classes\GameLogic;
use classes\Game;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\Stub;

class GameTests extends TestCase
{
    private Game $game;
    private Stub $dbHandlerStub;

    public function setUp(): void
    {
        $this->dbHandlerStub = self::createStub(DatabaseHandler::class);
        $this->game = new Game($this->dbHandlerStub, new GameLogic());
    }

    # bug 3
    public function testQueenMustPlayAfterThreePlayedPieces()
    {   
        // Arrange the game state
        $this->game->setBoard('0,0', 'B');
        $this->game->setBoard('0,1', 'S');
        $this->game->setBoard('0,2', 'A');
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 1, 'B' => 1, 'S' => 1, 'A' => 2, 'G' => 3]);

        // Perform a play - act
        $this->game->play('B', '0,3');
        
        // Assert that the Queen must be played error is set
        self::assertEquals('Must play queen bee', $this->game->getError());
    }

    # Bug 4
    public function testMoveClearsFromPosition()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('1,0', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move - act
        $this->game->move('0,0', '0,1');

        // Assert that the 'from' position is empty
        self::assertEmpty($this->game->getBoard()['0,0']);
    }


    # Bug 2
    public function testSlideQueenPieceToPosition()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('1,0', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move - act
        $this->game->move('0,0', '0,1');

        // Assert that the 'to' position is not empty
        self::assertEmpty($this->game->getError());
    }


    # Bug 1
    public function testPlayPieceNotInHand()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'B'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('0,-1', 'B');
        $this->game->setBoard('1,0', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['Q' => 1, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a play - act
        $this->game->play('B', '0,-1');

        // Assert that the piece is not in the hand by checken the error message
        self::assertEquals('Player does not have tile', $this->game->getError());
    }

    public function testBoardPositionHasNoNeighbour()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setPlayer(1); // Black player
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0); // White player
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a play - act
        $this->game->play('A', '1,1');

        // Assert that board position has no neighbour by checking the error message
        self::assertEquals('Board position has opposing neighbour', $this->game->getError());
    }

    public function testTileNotEmpty()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0); // White player
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,1');

        // Assert that tile is not empty by checking the error message
        self::assertEquals('Tile not empty', $this->game->getError());
    }

    public function testTileMustSlide()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0); // White player
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,2');

        // Assert that the 'from' position is empty
        self::assertEquals('Tile must slide', $this->game->getError());
    }

    public function testBoardPositionIsNotEmpty()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('1,0', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a play - act
        $this->game->play('A', '1,0'); // Assume 'B' is the piece belonging to player 0 and playing on 'Q' position

        // Assert that the position is not empty by checking the error message
        self::assertEquals('Board position is not empty', $this->game->getError());
    }

    public function testWouldSplitHive()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0); // White player
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move
        $this->game->move('0,0', '0,4');

        // Assert that the 'from' position is empty
        self::assertEquals('Move would split hive', $this->game->getError());
    }

    # Feature 4
    public function testCannotPassWhenBoardIsEmpty()
    {
        // Arrange the game state
        $this->game->setBoard('0', '0'); // Set empty board
        $this->game->setPlayer(0); // White player
        $this->game->setPlayerHand(0, ['Q' => 1, 'B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a pass - act
        $this->game->pass();

        // Assert that player cannot pass when board is empty by checking the error message
        self::assertEquals('Cannot pass', $this->game->getError());
    }

    public function testCannotPassWhenHandIsNotEmpty()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to player 0
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to player 1
        $this->game->setPlayer(0); // White player
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a pass - act
        $this->game->pass();

        // Assert that player cannot pass when hand is not empty by checking the error message
        self::assertEquals('Cannot pass', $this->game->getError());
    }
}