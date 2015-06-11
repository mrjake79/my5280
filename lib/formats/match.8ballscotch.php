<?php
/**
 * Match implementation for 8-Ball Scotch Doubles.
 */

require_once(MY5280_PLUGIN_DIR . 'lib/match.php');
class my5280_Match_8BallScotch extends my5280_Match
{
    /**
     * Retrieve the away team doubles handicap.
     *
     * @param none
     * @return int
     */
    public function getAwayDoublesHandicap()
    {
        // Check for a stored handicap
        if(isset($this->data->custom['awayDoublesHandicap']) && $this->data->custom['awayDoublesHandicap'] !== '') {
            return $this->data->custom['awayDoublesHandicap'];
        }

        // Check for away players
        $players = $this->listPlayers();
        if(!isset($players[2]) || $players[2]['id'] == null) return null;
        if(!isset($players[3]) || $players[3]['id'] == null) return null;

        // Get the doubles for the players
        $doubles = my5280::$instance->getDoubles($players[2]['id'], $players[3]['id']);
        return round($doubles->getHandicap(), 0);
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
        $homeGame = ($Round * 2) + ($Player - 2);
        switch($homeGame) {
        case 2:
            return 3;
        case 3:
            return 2;
        default:
            return $homeGame;
        }
    }


    /**
     * Retrieve the away player number for a game.
     *
     * @param int Game number.
     * @return int Player number.
     */
    public function getAwayPlayerNumber($Game)
    {
        if($Game < 2) {
            return $Game + 2;
        } elseif($Game == 2) {
            return 3;
        } elseif($Game == 3) {
            return 2;
        } else {
            return 5;
        }
    }


    /**
     * Retrieve the home team doubles handicap.
     *
     * @param none
     * @return int
     */
    public function getHomeDoublesHandicap()
    {
        // Check for a stored handicap
        if(isset($this->data->custom['homeDoublesHandicap']) && $this->data->custom['homeDoublesHandicap'] !== '') {
            return $this->data->custom['homeDoublesHandicap'];
        }

        // Check for home players
        $players = $this->listPlayers();
        if(!isset($players[0]) || $players[0]['id'] == null) return null;
        if(!isset($players[1]) || $players[1]['id'] == null) return null;

        // Get the doubles for the players
        $doubles = my5280::$instance->getDoubles($players[0]['id'], $players[1]['id']);
        return round($doubles->getHandicap(), 0);
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
        return ($Round * 2) + $Player;
    }


    /**
     * Calculate the home player number for a game.
     *
     * @param int Game number.
     * @param int Player number.
     */
    public function getHomePlayerNumber($Game)
    {
        if($Game < 4) {
            return $Game % 2;
        } else {
            return 4;
        }
    }


    /**
     * Get the round number for a game.
     *
     * @param int The game number.
     * @return int The round number.
     */
    public function getRoundNumber($Game)
    {
        if($Game < 4) {
            return floor($Game / 2);
        } else {
            return $Game - 2;
        }
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

        if($Game > 3) {
            // This is a doubles game
            $home = my5280::$instance->getDoubles($players[0]['id'], $players[1]['id']);
            $away = my5280::$instance->getDoubles($players[2]['id'], $players[3]['id']);
            return array($home->getId(), $away->getId());
        } else {
            $iHome = $Game % 2;
            switch($Game) {
            case 2:
                $iAway = 3;
                break;
            case 3:
                $iAway = 2;
                break;
            default:
                $iAway = $iHome + 2;
                break;
            }
            return array($players[$iHome]['id'], $players[$iAway]['id']);
        }
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
            $homeTotal += $player['handicap'];
        }

        $awayTotal = 0;
        foreach($this->listAwayPlayers() as $player) {
            $awayTotal += $player['handicap'];
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

        // Add a handicap array for each round of singles games
        $rounds = array();
        for($i = 0; $i < 2; $i++) {
            $rounds[$i] = $handicaps;
        }

        // Determine handicaps for the doubles games
        $homeTotal = $this->getHomeDoublesHandicap();
        $awayTotal = $this->getAwayDoublesHandicap();
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

        // Add the doubles rounds
        for($i = 2; $i < 7; $i++) {
            $rounds[$i] = $handicaps;
        }

        return $rounds;
    }


    /**
     * Assign the doubles handicap for the away team.
     *
     * @param integer
     * @return void
     */
    public function setAwayDoublesHandicap($Handicap)
    {
        $this->data->custom['awayDoublesHandicap'] = $Handicap;
    }


    /**
     * Assign the doubles handicap for the home team.
     *
     * @param integer
     * @return void
     */
    public function setHomeDoublesHandicap($Handicap)
    {
        $this->data->custom['homeDoublesHandicap'] = $Handicap;
    }
}
