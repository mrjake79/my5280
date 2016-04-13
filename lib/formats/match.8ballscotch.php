<?php
/**
 * Match implementation for 8-Ball Scotch Doubles.
 */

require_once(MY5280_PLUGIN_DIR . 'lib/match.php');
class my5280_Match_8BallScotch extends my5280_Match
{
    /**
     * Add a player to the match.
     *
     * @param object    Player to add or NULL for a forfeit.
     * @param integer   Position of player in line-up.
     * @return void
     */
    public function addPlayer($Position, $Player, $Handicap = null, $Paid = null)
    {
        parent::addPlayer($Position, $Player, $Handicap, $Paid);

        if(isset($this->players[0]) && isset($this->players[1]) && $this->players[0]->player !== null && $this->players[1]->player !== null) {
            $doubles = my5280::$instance->getDoubles($this->players[0]->player, $this->players[1]->player);
            if(isset($this->players[4])) {
                $this->players[4]->player_id = $doubles->getId();
                $this->players[4]->player = $doubles;
            } else {
                $session = $this->getSession();
                $info = array(
                    'id' => null,
                    'match_id' => $this->getId(),
                    'position' => 4,
                    'team_id' => $this->data->home_team,
                    'player_id' => $doubles->getId(),
                    'handicap' => round($doubles->getHandicap($this->getDate(), $session->getMaxHandicapGames()), 0),
                    'paid' => null,
                    'player' => $doubles,
                );
                $this->players[4] = (object) $info;
                ksort($this->players);
            }
        }

        if(isset($this->players[2]) && isset($this->players[3]) && $this->players[2]->player !== null && $this->players[3]->player !== null) {
            $doubles = my5280::$instance->getDoubles($this->players[2]->player, $this->players[3]->player);
            if(isset($this->players[5])) {
                $this->players[5]->player_id = $doubles->getId();
                $this->players[5]->player = $doubles;
            } else {
                if(!isset($session)) {
                    $session = $this->getSession();
                }

                $this->players[5] = (object) array(
                    'id' => null,
                    'match_id' => $this->getId(),
                    'position' => 5,
                    'team_id' => $this->data->away_team,
                    'player_id' => $doubles->getId(),
                    'handicap' => round($doubles->getHandicap($this->getDate(), $session->getMaxHandicapGames()), 0),
                    'paid' => null,
                    'player' => $doubles,
                );
                ksort($this->players);
            }
        }
    }

    /**
     * Retrieve the away team doubles handicap.
     *
     * @param none
     * @return int
     */
    public function getAwayDoublesHandicap()
    {
        $this->listPlayers();
        if(isset($this->players[5])) {
            return $this->players[5]->handicap;
        }
        return null;
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
        $this->listPlayers();
        if(isset($this->players[4])) {
            return $this->players[4]->handicap;
        }
        return null;
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
     * Determine if the player position is a home team position.
     */
    public function isHomeTeamPosition($Position)
    {
        return ($Position < 2 || $Position == 4);
    }


    /**
     * Retrieve an array of players for the away team.
     */
    public function listAwayPlayers()
    {
        $players = $this->listPlayers();
        return array_slice($players, 2, 2, true);
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
            return array($players[4]->player_id, $players[5]->player_id);
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
            return array($players[$iHome]->player_id, $players[$iAway]->player_id);
        }
    }


    /**
     * Retrieve an array of players for the home team.
     */
    public function listHomePlayers()
    {
        $players = $this->listPlayers();
        return array_slice($players, 0, 2);
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
        $this->listPlayers();
        if(isset($this->players[5])) {
            $this->players[5]->handicap = $Handicap;
        }
    }


    /**
     * Assign the doubles handicap for the home team.
     *
     * @param integer
     * @return void
     */
    public function setHomeDoublesHandicap($Handicap)
    {
        $this->listPlayers();
        if(isset($this->players[4])) {
            $this->players[4]->handicap = $Handicap;
        }
    }
}
