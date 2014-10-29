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
        require_once(MY5280_PLUGIN_DIR . 'lib/formats/functions.' . $Format . '.php');
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
        $this->awayScores = null;
        $this->players = null;
        $this->playerPoints = null;

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
        // Save the score
        if(!is_array($this->data->custom['scores'])) $this->data->custom['scores'] = array();
        $this->data->custom['scores'][$Game] = $Score;

        // Update the away score
        $this->updateAwayScore($Game, $Score);

        // Update totals
        $this->updateTotalScores();

        // Clear player points
        $this->playerPoints = null;
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
        include_once(dirname(__FILE__) . '/session.php');
        return new my5280_Session($this->data->league_id, $this->data->season);
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
        if($this->awayScores === null) {
            // Update all away scores from the current home scores
            $this->awayScores = array();
            foreach($this->listHomeScores() as $homeGame => $homeScore) {
                $this->updateAwayScore($homeGame, $homeScore);
            }
        }
        return $this->awayScores;
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
        if($this->players === null) {
            $players = array();
            if(isset($this->data->custom['players'])) {
                foreach($this->data->custom['players'] as $index => $info) {
                    $info['player'] = my5280::$instance->getPlayer($info['id']);
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
        if($this->playerPoints === null) {
            $players = $this->listPlayers();
            $scores = $this->calculateTotalPoints($players, $this->listHomeScores());
            $awayPoints = $this->calculateTotalPoints(array_values(array_slice($players, 5)), $this->listAwayScores());
            foreach($awayPoints as $player => $points) {
                if(isset($scores[$player])) {
                    $scores[$player]['games'] += $points['games'];
                    $scores[$player]['points'] += $points['points'];
                    $scores[$player]['wins'] += $points['wins'];
                } else {
                    $scores[$player] = $points;
                }
            }
            $this->playerPoints = $scores;
        }
        return $this->playerPoints;
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
     * Format of the session (league)
     */
    protected $format = null;


    /**
     * Players
     */
    protected $players = null;


    /**
     * Player points
     */
    protected $playerPoints = null;


    /**
     * Away team scores.
     */
    protected $awayScores = null;


    /**
     * Original scores.
     */
    protected $originalScores = null;


    /**
     * Update an away score.
     */
    protected function updateAwayScore($HomeGame, $HomeScore)
    {
        if($this->awayScores !== null) {
            // Get the players for the match
            $players = $this->listPlayers();

            // Determine home player
            $homePlayer = $HomeGame % 5;

            // Get the away game and player
            $awayGame = call_user_func('my5280_getAwayGame_' . $this->format, $HomeGame);
            $awayPlayer = ($awayGame % 5) + 5;

            // Add to the scores
            if($HomeScore === 0 && $players[$homePlayer]['id'] == null) {
                $awayScore = 8;
            } elseif($players[$awayPlayer]['id'] == null) {
                $awayScore = 0;
            } else {
                $awayScore = 15 - $HomeScore;
            }
            $this->awayScores[$awayGame] = $awayScore;
        }
    }


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

        $handicaps = array();

        // Calculate home team handicap
        $hcpHome = 0;
        foreach(array_slice($players, 0, $playerCount / 2) as $player) {
            $hcpHome += $player['handicap'];
            $handicaps[] = $player['handicap'];
        }

        // Calculate away team handicap
        $hcpAway = 0;
        foreach(array_slice($players, $playerCount / 2) as $player) {
            $hcpAway += $player['handicap'];
            $handicaps[] = $player['handicap'];
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
                        'wins' => 0,
                    );
                }
                $totals[$player]['games']++;
                $totals[$player]['points'] += $score;
                if($score > 7) {
                    $totals[$player]['wins']++;
                }
            }
        }

        return $totals;
    }
}
