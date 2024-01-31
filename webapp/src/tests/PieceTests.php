<?php

use database\DatabaseHandler;
use classes\GameLogic;
use classes\Game;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\Stub;

class PieceTests extends TestCase
{
    private Game $game;
    private Stub $dbHandlerStub;

    public function setUp(): void
    {
        $this->dbHandlerStub = self::createStub(DatabaseHandler::class);
        $this->game = new Game($this->dbHandlerStub, new GameLogic());
    }

    public function testGrasshopperCanMoveStraight()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to White
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to Black
        $this->game->setBoard('0,-1', 'G'); // Assume 'G' is the piece belonging to White
        $this->game->setBoard('0,2', 'B'); // Assume 'B' is the piece belonging to Black

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move - act
        $this->game->move('0,-1', '0,3');

        // Assert that the 'to' position is empty
        self::assertNotEmpty($this->game->getBoard()['0,3']);
    }

    public function testNotValidGrasshopperMove()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to White
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to Black
        $this->game->setBoard('0,3', 'G'); // Assume 'G' is the piece belonging to White
        $this->game->setBoard('0,2', 'B'); // Assume 'B' is the piece belonging to Black

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move - act
        $this->game->move('0,3', '1,2');

        // Assert that the grasshopper cannot move to the 'to' position by checking the error message
        self::assertEquals('This is not a valid Grasshopper move', $this->game->getError());
    }

    public function testGrasshopperCannotMoveToTheSameTileHeIsOn()
    {
        // Arrange the game state
        $this->game->setBoard('0,0', 'Q'); // Assume 'Q' is the piece belonging to White
        $this->game->setBoard('0,1', 'Q'); // Assume 'Q' is the piece belonging to Black
        $this->game->setBoard('0,-1', 'G'); // Assume 'G' is the piece belonging to White

        $this->game->setPlayer(0);
        $this->game->setPlayerHand(0, ['B' => 2, 'S' => 2, 'A' => 3, 'G' => 3]);

        // Perform a move - act
        $this->game->move('0,-1', '0,-1');

        // Assert that the grasshopper can't move to the same tile he is on by checking the error message
        self::assertEquals('This is not a valid Grasshopper move', $this->game->getError());
    }
}