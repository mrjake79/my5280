<?php
/**
 * Match implementation for 8-Ball 3x3.
 */

require_once(MY5280_PLUGIN_DIR . 'lib/match.php');
class my5280_Match_8ball3x3 extends my5280_Match
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
        if($iAway < 3) {
            $iAway += 3;
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
        $homeGame = $this->getHomeGame($Round, $Player - 3);

        // Calculate the away game
        $awayGame = $homeGame - $Round;
        if($awayGame < ($Round * 3)) {
            $awayGame += 3;
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
        return ($Round * 3) + $Player;
    }


    /**
     * Calculate the home player number for a game.
     *
     * @param int Game number.
     * @param int Player number.
     */
    public function getHomePlayerNumber($Game)
    {
        return $Game % 3;
    }


    /**
     * Calculate the round number for a game.
     *
     * @param int Game number.
     * @return int Round number.
     */
    public function getRoundNumber($Game)
    {
        return floor($Game / 3);
    }


    /**
     * Determine if the player position is a home team position.
     */
    public function isHomeTeamPosition($Position)
    {
        return ($Position < 3);
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
        $iHome = $Game % 3;
        $iRound = floor($Game / 3);
        $iAway = $iHome + $iRound;
        if($iAway < 3) {
            $iAway += 3;
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
        for($i = 0; $i < 3; $i++) {
            $rounds[$i] = $handicaps;
        }
        return $rounds;
    }
}
