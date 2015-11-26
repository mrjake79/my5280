<?php

/**
 * Class to represent a match for a session.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

include_once(dirname(__FILE__) . '/player.php');

class my5280_Match
{
    /**
     * Constructor.
     *
     * @param object Match data.
     */
    public function __construct($Data, $Format = null)
    {
        // Store the data
        $this->data = $Data;

        // Inclue the functions file for the session's format
        if($Format === null) {
            $session = $this->getSession();
            $Format = $session->getLeagueFormat();
        }
        $this->format = $Format;

        // Store the original scores
        $this->originalScores = $this->listPlayerPoints();
    }


    /**
     * Add a player to the match.
     *
     * @param object    Player to add or NULL for a forfeit.
     * @param integer   Position of player in line-up.
     * @return void
     */
    public function addPlayer($Position, $Player, $Handicap = null, $Paid = null)
    {
        if($Player) {
            if($Handicap === null) $Handicap = round($Player->getHandicap(), 0);
            $info = array(
                'id' => $Player->getId(),
                'handicap' => $Handicap,
                'paid' => $Paid,
                'forfeit' => false,
            );
        } else {
            $info = array(
                'id' => null,
                'handicap' => $Handicap,
                'paid' => $Paid,
                'forfeit' => true,
            );
        }

        if(!is_array($this->data->custom['players'])) $this->data->custom['players'] = array();
        $this->data->custom['players'][$Position] = $info;
        ksort($this->data->custom['players']);

        // Clear the away scores and player list
        $this->players = null;

        // Update total points (in case of handicap change)
        $this->updateTotalScores();
    }


    /**
     * Add a score to the match.
     *
     * @param integer Score
     * @param integer Game number for the score.
     */
    public function addScore($Game, $Score)
    {
        // Get the players
        $players = $this->listGamePlayers($Game);

        // Save the score
        if(!is_array($this->data->custom['scores'])) $this->data->custom['scores'] = array();
        $this->data->custom['scores'][$Game] = array(
            $players[0] => $Score,
            $players[1] => $this->calculateAwayScore($Game, $Score),
        );

        // Update totals
        $this->updateTotalScores();
    }


    /**
     * Retrieve the away team's score.
     *
     * @param none
     * @return integer
     */
    public function getAwayScore()
    {
        return $this->data->away_points;
    }


    /**
     * Retrieve the away team.
     *
     * @param none
     * @return object
     */
    public function getAwayTeam()
    {
        $session = $this->getSession();
        $teams = $session->listTeams();
        return $teams[$this->data->away_team];
    }


    /**
     * Retrieve the date of the match.
     *
     * @param none
     * @return string
     */
    public function getDate()
    {
        return substr($this->data->date, 0, 10);
    }


    /**
     * Retrieve the home team's score.
     *
     * @param none
     * @return integer
     */
    public function getHomeScore()
    {
        return $this->data->home_points;
    }


    /**
     * Retrieve the home team.
     *
     * @param none
     * @return object
     */
    public function getHomeTeam()
    {
        $session = $this->getSession();
        $teams = $session->listTeams();
        return $teams[$this->data->home_team];
    }


    /**
     * Retrieve the match's ID.
     *
     * @param none
     * @return integer
     */
    public function getId()
    {
        return $this->data->id;
    }


    /**
     * Get the league's ID.
     */
    public function getLeagueId()
    {
        return $this->data->league_id;
    }


    /**
     * Retrieve the match day.
     */
    public function getMatchDay()
    {
        return $this->data->match_day;
    }


    /**
     * Get the location of the match.
     *
     * @param none
     * @return string
     */
    public function getLocation()
    {
        return $this->data->location;
    }


    /**
     * Get the number of the match within its week.
     *
     * @param none
     * @return integer
     */
    public function getNumber()
    {
        return isset($this->data->custom['number']) ? $this->data->custom['number'] : null;
    }


    /**
     * Retrieve the season name.
     */
    public function getSeasonName()
    {
        return $this->data->season;
    }


    /**
     * Retrieve the session.
     *
     * @param none
     * @return object
     */
    public function getSession()
    {
        if($this->session === null) {
            include_once(dirname(__FILE__) . '/session.php');
            $this->session = new my5280_Session($this->data->league_id, $this->data->season);
        }
        return $this->session;
    }


    /**
     * Get the away players.
     *
     * @param none
     * @return array
     */
    public function listAwayPlayers()
    {
        $players = $this->listPlayers();
        $count = count($players);
        return array_slice($players, $count / 2, null, true);
    }


    /**
     * Get the away team scores.
     *
     * @param none
     * @return array
     */
    public function listAwayScores()
    {
        $awayScores = array();
        foreach($this->listScores() as $iGame => $scores) {
            $awayScores[$iGame] = array_pop($scores);
        }
        return $awayScores;
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
        return array(null, null);
    }


    /**
     * Get the home players.
     *
     * @param none
     * @return array
     */
    public function listHomePlayers()
    {
        $players = $this->listPlayers();
        $count = count($players);
        return array_slice($players, 0, $count / 2);
    }


    /**
     * Get the home team scores.
     *
     * @param none
     * @return array
     */
    public function listHomeScores()
    {
        $homeScores = array();
        foreach($this->listScores() as $iGame => $scores) {
            $homeScores[$iGame] = array_shift($scores);
        }
        return $homeScores;
    }


    /**
     * Retrieve an array of the players.
     *
     * @param none
     * @return array
     */
    public function listPlayers()
    {
        if($this->players === null) {
            $players = array();
            if(isset($this->data->custom['players'])) {
                foreach($this->data->custom['players'] as $index => $info) {
                    if(!is_null($info['id'])) {
                        $info['player'] = my5280::$instance->getPlayer($info['id']);
                        if($info['player'] == null) {
                            $info['id'] = null;
                        }
                    }
                    $players[$index] = $info;
                }
            }
            $this->players = $players;
        }
        return $this->players;
    }


    /**
     * Retrieve an array of totals points by player.
     *
     * @param none
     * @return array
     */
    public function listPlayerPoints()
    {
        $players = array();
        foreach($this->listScores() as $score) {
            if(is_array($score)) {
                foreach($score as $player => $points) {
                    if($player == null) continue;
                    if(!isset($players[$player])) {
                        $players[$player] = array('points' => 0, 'games' => 0, 'wins' => 0);
                    }
                    $players[$player]['points'] += $points;
                    $players[$player]['games']++;
                    if($points > 7) {
                        $players[$player]['wins']++;
                    }
                }
            }
        }
        return $players;
    }


    /**
     * Retrieve an array of handicaps per round.
     *
     * @param none
     * @return array
     */
    public function listRoundHandicaps()
    {
        return array();
    }


    /**
     * Retrieve an array of score information for all games.
     *
     */
    public function listScores()
    {
        if(isset($this->data->custom['scores'])) {
            $scores = array();
            foreach($this->data->custom['scores'] as $iGame => $score) {
                if(is_array($score)) {
                    $scores[$iGame] = $score;
                } else {
                    $players = $this->listGamePlayers($iGame);
                    $awayScore =  $this->calculateAwayScore($iGame, $score);
                    $scores[$iGame] = array(
                        $players[0] => $score,
                        $players[1] => $awayScore,
                    );
                }
            }
            $this->data->custom['scores'] = $scores;
        } else {
            $this->data->custom['scores'] = array();
        }
        return $this->data->custom['scores'];
    }


    /**
     * Save the match.
     *
     * @param none
     * @return boolean
     */
    public function save()
    {
        global $lmLoader;
        $lmAdmin = $lmLoader->adminPanel;

        // Save the match data
        if(!empty($this->data->id)) {
            // This is an existing match
            $lmAdmin->editMatch(
                $this->data->date,
                $this->data->home_team,
                $this->data->away_team,
                $this->data->match_day,
                $this->data->location,
                $this->data->league_id,
                $this->data->id,
                $this->data->group,
                null,
                $this->data->custom
            );
        } else {
            // This is a new match
            $matchId = $lmAdmin->addMatch(
                $this->data->date,
                $this->data->home_team,
                $this->data->away_team,
                $this->data->match_day,
                $this->data->location,
                $this->data->league_id,
                $this->data->season,
                null,
                false,
                $this->data->custom
            );
            $this->data->id = $matchId;
        }

        // Update total match points
        $matchId = $this->getId();
        $results = array(
            'league_id' => $this->getLeagueId(),
            'matches' => array($matchId => $matchId),
            'home_points' => array($matchId => $this->data->home_points),
            'away_points' => array($matchId => $this->data->away_points),
            'home_team' => array($matchId => $this->data->home_team),
            'away_team' => array($matchId => $this->data->away_team),
            'custom' => array($matchId => $this->data->custom),
            'final' => false,
            'message' => false,
        );
        call_user_func_array(array($lmAdmin, 'updateResults'), $results);

        // Updating scores.

        // Get the player list.
        $players = array();
        $matchPlayers = $this->listPlayers();
        foreach($this->listPlayers() as $player) {
            $players[$player['id']] = $player['player'];
        }

        // Remove the original scores for the player
        foreach($this->originalScores as $player => $score) {
            if(!isset($players[$player])) {
                $players[$player] = my5280::$instance->getPlayer($player);
            }
            $players[$player]->adjustHandicap(-($score['games']), -($score['points']));
        }

        // Record new points for the players
        foreach($this->listPlayerPoints() as $player => $points) {
            if(!isset($players[$player])) {
                $players[$player] = my5280::$instance->getPlayer($player);
            }
            $players[$player]->adjustHandicap($points['games'], $points['points']);
        }

        // Save the players
        foreach($players as $player) {
            if($player) {
                $player->save();
            }
        }

        return true;
    }


    /**
     * Assign the away team.
     *
     * @param string
     * @return void
     */
    public function setAwayTeam($Team)
    {
        $this->data->away_team = $Team->getTeamNumber();
        $this->data->away_team = $Team->getId();
    }


    /**
     * Assign the date of the match.
     *
     * @param string
     * @return void
     */
    public function setDate($Date)
    {
        $this->data->date = $Date . ' 00:00';
    }


    /**
     * Assign the home team.
     *
     * @param string
     * @return void
     */
    public function setHomeTeam($Team)
    {
        $this->data->home_team = $Team->getTeamNumber();
        $this->data->home_team = $Team->getId();
    }


    /**
     * Assign the location.
     *
     * @param string
     * @return void
     */
    public function setLocation($Location)
    {
        $this->data->location = $Location;
    }


    /**
     * Assign the match day.
     *
     * @param integer The match day.
     * @return void
     */
    public function setMatchDay($Value)
    {
        $this->data->match_day = $Value;
    }


    /**
     * Match data.
     */
    protected $data;


    /**
     * The session.
     */
    protected $session = null;


    /**
     * Format of the session (league)
     */
    protected $format = null;


    /**
     * Players
     */
    protected $players = null;


    /**
     * Original scores.
     */
    protected $originalScores = null;


    /**
     * Update the total scores from the score array.
     *
     * @param none
     * @return void
     */
    protected function updateTotalScores()
    {
        // Determine total handicaps for each time
        $totalHome = 0; $totalAway = 0;
        foreach($this->listRoundHandicaps() as $handicaps) {
            $totalHome += $handicaps[0];
            $totalAway += $handicaps[1];
        }

        // Determine the total home and away points
        $this->data->home_points = $totalHome + array_sum($this->listHomeScores());
        $this->data->away_points = $totalAway + array_sum($this->listAwayScores());
    }


    /**
     * Determine the away team score for a particular game given the home team score.
     */
    public function calculateAwayScore($Game, $Score)
    {
        $players = $this->listGamePlayers($Game);
        if($players[0] == null) {
            return 0;
        } elseif($players[1] == null) {
            return 0;
        } else {
            return 15 - $Score;
        }
    }
}
