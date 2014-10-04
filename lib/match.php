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
    public function __construct($Data)
    {
        // Store the data
        $this->data = $Data;

        // Determine original scores
        $players = $this->listPlayers();
        $origScores = $this->calculateTotalPoints($players, $this->listHomeScores());
        $awayPoints = $this->calculateTotalPoints(array_values(array_slice($players, 5)), $this->listAwayScores());
        foreach($awayPoints as $player => $points) {
            if(isset($origScores[$player])) {
                $origScores[$player]['games'] += $points['games'];
                $origScores[$player]['points'] += $points['points'];
            } else {
                $origScores[$player] = $points;
            }
        }

        // Store the original scores
        $this->originalScores = $origScores;
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
    }


    /**
     * Add a score to the match.
     *
     * @param integer Score
     * @param integer Game number for the score.
     */
    public function addScore($Game, $Score)
    {
        // Save the score
        if(!is_array($this->data->custom['scores'])) $this->data->custom['scores'] = array();
        $this->data->custom['scores'][$Game] = $Score;

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
        include_once(dirname(__FILE__) . '/session.php');
        return new my5280_Session($this->data->league_id, $this->data->season);
    }


    /**
     * Get the away team scores.
     *
     * @param none
     * @return array
     */
    public function listAwayScores()
    {
        if($this->awayScores === null) {
            // Include the functions for the format
            $session = $this->getSession();
            $format = $session->getLeagueFormat();
            require_once(MY5280_PLUGIN_DIR . 'lib/formats/functions.' . $format . '.php');

            // Get the players for the match
            $players = $this->listPlayers();

            // Determine the scores
            $scores = array();
            foreach($this->listHomeScores() as $homeGame => $homeScore) {
                // Determine home player
                $homePlayer = $homeGame % 5;

                // Get the away game and player
                $awayGame = call_user_func('my5280_getAwayGame_' . $format, $homeGame);
                $awayPlayer = ($awayGame % 5) + 5;

                // Add to the scores
                if($homeScore === 0 && $players[$homePlayer]['id'] == null) {
                    $awayScore = 8;
                } elseif($players[$awayPlayer]['id'] == null) {
                    $awayScore = 0;
                } else {
                    $awayScore = 15 - $homeScore;
                }
                $scores[$awayGame] = $awayScore;
            }

            // Cache the scores
            $this->awayScores = $scores;
        }
        return $this->awayScores;
    }


    /**
     * Get the home team scores.
     *
     * @param none
     * @return array
     */
    public function listHomeScores()
    {
        if(isset($this->data->custom['scores'])) {
            return $this->data->custom['scores'];
        }
        return array();
    }


    /**
     * Retrieve an array of the players.
     *
     * @param none
     * @return array
     */
    public function listPlayers()
    {
        $players = array();
        if(isset($this->data->custom['players'])) {
            foreach($this->data->custom['players'] as $index => $info) {
                $info['player'] = my5280::$instance->getPlayer($info['id']);
                $players[$index] = $info;
            }
        }
        return $players;
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

        // Add in the new home scores
        $scores = $this->calculateTotalPoints($matchPlayers, $this->listHomeScores());
        foreach($scores as $player => $score) {
            if(!isset($players[$player])) {
                $players[$player] = my5280::$instance->getPlayer($player);
            }
            $players[$player]->adjustHandicap($score['games'], $score['points']);
        }

        // Add in the new away scores
        $scores = $this->calculateTotalPoints(array_values(array_slice($matchPlayers, 5)), $this->listAwayScores());
        foreach($scores as $player => $score) {
            if(!isset($players[$player])) {
                $players[$player] = my5280::$instance->getPlayer($player);
            }
            $players[$player]->adjustHandicap($score['games'], $score['points']);
        }

        // Save the players
        foreach($players as $player) {
            $player->save();
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
     * Match data.
     */
    protected $data;


    /**
     * Away team scores.
     */
    protected $awayScores = null;


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
        // Get the players
        $players = $this->listPlayers();
        $playerCount = count($players);

        // Calculate home team handicap
        $hcpHome = 0;
        foreach(array_slice($players, 0, $playerCount / 2) as $player) {
            $hcpHome += $player['handicap'];
        }

        // Calculate away team handicap
        $hcpAway = 0;
        foreach(array_slice($players, $playerCount / 2) as $player) {
            $hcpAway += $player['handicap'];
        }

        // Determine total handicap points for each team
        if($hcpHome > $hcpAway) {
            $totalHome = 0;
            $totalAway = ($hcpHome - $hcpAway) * 5;
        } else {
            $totalHome = ($hcpAway - $hcpHome) * 5;
            $totalAway = 0;
        }

        // Determine the total home and away points
        $this->data->home_points = $totalHome + array_sum($this->listHomeScores());
        $this->data->away_points = $totalAway + array_sum($this->listAwayScores());
    }


    /**
     * Calculate total points by player for the provide scores.
     */
    protected function calculateTotalPoints($Players, $Scores)
    {
        // Build original player scores
        $totals = array();

        // Process home team scores
        foreach($Scores as $game => $score) {
            $iPlayer = $game % 5;
            if(isset($Players[$iPlayer]) && $Players[$iPlayer]['id'] != null) {
                $player = $Players[$iPlayer]['id'];
                if(!isset($totals[$player])) {
                    $totals[$player] = array(
                        'games' => 0,
                        'points' => 0,
                    );
                }
                $totals[$player]['games']++;
                $totals[$player]['points'] += $score;
            }
        }

        return $totals;
    }
}