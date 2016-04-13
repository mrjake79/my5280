<?php
/**
 * Match implementation for 8-Ball 5x5.
 */

require_once(MY5280_PLUGIN_DIR . 'lib/match.php');
class my5280_Match_8ball5x5 extends my5280_Match
{
    /**
     * Calculate the away player number for a game.
     *
     * @param int Game number.
     * @param int Round number.
     */
    public function getAwayPlayerNumber($Game)
    {
        $iHome = $this->getHomePlayerNumber($Game);
        $iRound = $this->getRoundNumber($Game);
        $iAway = $iHome + $iRound;
        if($iAway < 5) {
            $iAway += 5;
        }
        return $iAway;
    }

    /**
     * Retrieve the away game number for the given round and player number.
     *
     * @param int The round number.
     * @param int The player number.
     * @return int The game number.
     */
    public function getAwayGame($Round, $Player)
    {
        // Determine the home game
        $homeGame = $this->getHomeGame($Round, $Player - 5);

        // Calculate the away game
        $awayGame = $homeGame - $Round;
        if($awayGame < ($Round * 5)) {
            $awayGame += 5;
        }

        return $awayGame;
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
        return ($Round * 5) + $Player;
    }


    /**
     * Calculate the home player number for a game.
     *
     * @param int Game number.
     * @param int Player number.
     */
    public function getHomePlayerNumber($Game)
    {
        return $Game % 5;
    }


    /**
     * Calculate the round number for a game.
     *
     * @param int Game number.
     * @return int Round number.
     */
    public function getRoundNumber($Game)
    {
        return floor($Game / 5);
    }


    /**
     * Determine if the player position is a home team position.
     */
    public function isHomeTeamPosition($Position)
    {
        return ($Position < 5);
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
        $iHome = $Game % 5;
        $iRound = floor($Game / 5);
        $iAway = $iHome + $iRound;
        if($iAway < 5) {
            $iAway += 5;
        }

        $players = $this->listPlayers();
        return array($players[$iHome]->player_id, $players[$iAway]->player_id);
    }


    /**
     * Retrieve an array of handicaps per round.
     *
     * @param none
     * @return array
     */
    public function listRoundHandicaps()
    {
        // Determine the home and away total handicaps
        $homeTotal = 0;
        foreach($this->listHomePlayers() as $player) {
            $homeTotal += $player->handicap;
        }

        $awayTotal = 0;
        foreach($this->listAwayPlayers() as $player) {
            $awayTotal += $player->handicap;
        }

        // Determine the handicap array
        if($homeTotal > $awayTotal) {
            $handicaps = array(
                0,
                $homeTotal - $awayTotal,
            );
        } else {
            $handicaps = array(
                $awayTotal - $homeTotal,
                0,
            );
        }

        // Add a handicap array for each round and return the array
        $rounds = array();
        for($i = 0; $i < 5; $i++) {
            $rounds[$i] = $handicaps;
        }
        return $rounds;
    }
}
