<?php

require_once(MY5280_PLUGIN_DIR . 'lib/match.php');
class my5280_Match_8ballsingles extends my5280_Match
{
    /**
     * Retrieve the away game number for the given round and player number.
     *
     * @param int The round number.
     * @param int The player number.
     * @return int The game number.
     */
    public function getAwayGame($Round, $Player)
    {
        return $Round;
    }


    /**
     * Retrieve the away player number for a game.
     *
     * @param int Game number.
     * @return int Player number.
     */
    public function getAwayPlayerNumber($Game)
    {
        return 1;
    }


    /**
     * Retrieve the home game number for the given round and player number.
     *
     * @param int Round number.
     * @param int Player number.
     * @return int Game number.
     */
    public function getHomeGame($Round, $Player)
    {
        return $Round;
    }


    /**
     * Calculate the home player number for a game.
     *
     * @param int Game number.
     * @param int Player number.
     */
    public function getHomePlayerNumber($Game)
    {
        return 0;
    }


    /**
     * Get the round number for a game.
     *
     * @param int The game number.
     * @return int The round number.
     */
    public function getRoundNumber($Game)
    {
        return $Game;
    }


    /**
     * Retrieve an array of players for a particular game.  The 1st player in the array
     * is the home player and the 2nd is the away player.
     *
     * @param none
     * @return array
     */
    public function listGamePlayers($Game)
    {
        $players = $this->listPlayers();
        return array($players[0]['id'], $players[1]['id']);
    }
}
