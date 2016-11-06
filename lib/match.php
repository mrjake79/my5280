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
     * Factory
     */
    public static function factory($Data, $Format = null)
    {
        if($Format === null)
        {
            $league = my5280::$instance->getSession($Data['league_id'], $Data['season']);
            $Format = $league->getLeagueFormat();
        }

        $classFile = dirname(__FILE__) . '/formats/match.' . $Format . '.php';
        if(is_file($classFile)) {
            $class = 'my5280_Match_' . $Format;
        } else {
            $class = 'my5280_Match';
            $classFile = dirname(__FILE__) . '/match.php';
        }
        require_once($classFile);

        return new $class($Data, $Format);
    }

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
        $this->listPlayers();

        $session = $this->getSession();

        $info = array(
            'id' => null,
            'match_id' => $this->getId(),
            'position' => $Position,
            'team_id' => ($this->isHomeTeamPosition($Position) ? $this->data->home_team : $this->data->away_team),
            'player_id' => ($Player ? $Player->getId() : null),
            'handicap' => ($Handicap === null && $Player ? round($Player->getHandicap($this->getDate(), $session->getMaxHandicapGames()), 0) : $Handicap),
            'paid' => $Paid,
            'player' => $Player,
        );

        if(isset($this->players[$Position])) {
            $info['id'] = $this->players[$Position]->id;
        }
        $this->players[$Position] = (object) $info;

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
        $this->listScores();

        // Save the home team score
        if(isset($this->scores[$Game][$this->data->home_team])) {
            $this->scores[$Game][$this->data->home_team]->score = $Score;
        } else {
            if(!isset($this->scores[$Game])) {
                $this->scores[$Game] = array();
            }
            $this->scores[$Game][$this->data->home_team] = (object) array(
                'id' => null,
                'match_player_id' => null,
                'round' => $this->getRoundNumber($Game),
                'game' => $Game,
                'score' => $Score,
            );
        }

        // Save the away team score
        $Score = $this->calculateAwayScore($Game, $Score);
        if(isset($this->scores[$Game][$this->data->away_team])) {
            $this->scores[$Game][$this->data->away_team]->score = $Score;
        } else {
            if(!isset($this->scores[$Game])) {
                $this->scores[$Game] = array();
            }
            $this->scores[$Game][$this->data->away_team] = (object) array(
                'id' => null,
                'match_player_id' => null,
                'round' => $this->getRoundNumber($Game),
                'game' => $Game,
                'score' => $Score,
            );
        }

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

    public function getAwayTeamId()
    {
        return $this->data->away_team;
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

    public function getHomeTeamId()
    {
        return $this->data->home_team;
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
     * Determine if the player position is a home team position.
     *
     * @param int
     * @return bool
     */
    public function isHomeTeamPosition($Position)
    {
        throw new Exception("Unimplemented");
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
            if(isset($scores[$this->data->away_team])) {
                $awayScores[$iGame] = $scores[$this->data->away_team]->score;
            } else {
                $awayScores[$iGame] = null;
            }
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
            if(isset($scores[$this->data->home_team])) {
                $homeScores[$iGame] = $scores[$this->data->home_team]->score;
            } else {
                $homeScores[$iGame] = null;
            }
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
            global $wpdb;

            $sql = "SELECT * FROM {$wpdb->prefix}my5280_match_players WHERE match_id = {$this->getId()} ORDER BY position";

            $players = array();
            foreach($wpdb->get_results($sql) as $player) {
                if(!is_null($player->player_id)) {
                    $player->player = my5280::$instance->getPlayer($player->player_id);
                } else {
                    $player->player = null;
                }
                $players[$player->position] = $player;
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

        foreach($this->listPlayers() as $player)
        {
            $players[$player->id] = array(
                'player_id' => $player->player_id,
                'points' => 0,
                'games' => 0,
                'wins' => 0,
                'forfeitWins' => 0,
            );
        }

        foreach($this->listScores() as $game => $scores) {
            list($home_score, $away_score) = array_values($scores);

            $home_player = $home_score->match_player_id;
            $players[$home_player]['points'] += $home_score->score;
            $players[$home_player]['games']++;

            $away_player = $away_score->match_player_id;
            $players[$away_player]['points'] += $away_score->score;
            $players[$away_player]['games']++;

            if($home_score->score > $away_score->score)
            {
                $players[$home_player]['wins']++;
                if($players[$away_player]['player_id'] == null)
                {
                    $players[$home_player]['forfeitWins']++;
                }
            }
            elseif($away_score->score > $home_score->score)
            {
                $players[$away_player]['wins']++;
                if($players[$home_player]['player_id'] == null)
                {
                    $players[$away_player]['forfeitWins']++;
                }
            }
        }

        $return = array();
        foreach($players as $player)
        {
            $return[$player['player_id']] = $player;
        }
        return $return;
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
        if($this->scores === null) {
            global $wpdb;

            $sql = "SELECT scores.*, players.team_id "
                . "FROM {$wpdb->prefix}my5280_match_scores scores, "
                . "{$wpdb->prefix}my5280_match_players players "
                . "WHERE players.id = scores.match_player_id "
                . "AND match_id = {$this->getId()}";

            $scores = array();
            foreach($wpdb->get_results($sql) as $score) {
                if(!isset($scores[$score->game])) {
                    $scores[$score->game] = array(
                        $score->team_id => $score,
                    );
                } else {
                    $scores[$score->game][$score->team_id] = $score;
                }
            }

            $this->scores = $scores;
        }
        return $this->scores;
    }


    /**
     * Save the match.
     *
     * @param none
     * @return boolean
     */
    public function save()
    {
        global $lmLoader, $wpdb;
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

        // Save player assignments
        if($this->players) {
            foreach($this->players as $index => $player) {
                unset($player->player);
                if($player->id) {
                    $player_id = $player->id;
                    unset($player->id);
                    $wpdb->update($wpdb->prefix.'my5280_match_players', (array)$player, array('id' => $player_id));
                    $this->players[$index]->id = $player_id;
                } else {
                    unset($player->id);
                    $wpdb->insert($wpdb->prefix.'my5280_match_players', (array)$player);
                    $this->players[$index]->id = $wpdb->insert_id;
                }
            }
        }

        // Save scores
        if($this->scores) {
            foreach($this->scores as $index => $game) {
                foreach($game as $teamId => $score) {
                    $score = (array)$score;
                    unset($score['team_id']);

                    if(!isset($score['match_player_id']) || $score['match_player_id'] === null)
                    {
                        $this->listPlayers();
                        if($teamId == $this->data->home_team) {
                            $playerIndex = $this->getHomePlayerNumber($index);
                        } else {
                            $playerIndex = $this->getAwayPlayerNumber($index);
                        }
                        $score['match_player_id'] = $this->players[$playerIndex]->id;
                    }

                    if(isset($score['id'])) {
                        $id = $score['id'];
                        unset($score['id']);
                        $wpdb->update($wpdb->prefix.'my5280_match_scores', $score, array('id' => $id));
                    } else {
                        unset($score['id']);
                        $wpdb->insert($wpdb->prefix.'my5280_match_scores', $score);
                        $this->scores[$index][$teamId]->id = $wpdb->insert_id;
                    }
                }
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
     * Scores
     */
    protected $scores = null;


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
