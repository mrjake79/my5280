<?php

/**
 * Represents a session in the league.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

class my5280_Session
{
    /**
     * Constructor
     *
     * @param string $path
     * @return void
     */
    public function __construct($League = null, $Season = null)
    {
        global $leaguemanager;

        // Get the league
        if($League === null) {
            $this->league = $leaguemanager->getCurrentLeague();
        } elseif(is_object($League)) {
            $this->league = $League;
        } else {
            $this->league = $leaguemanager->getLeague($League);
        }

        // Get the season
        if(is_object($Season)) {
            $this->season = $Season;
        } else {
            if($Season === null) $Season = false;
            $this->season = $leaguemanager->getSeason($this->league, $Season);
        }
    }


    /**
     * Add a match to the session or retrieve an existing one for the combined arguments.
     *
     * @param date      The date of the match.
     * @param integer   The match number.
     * @param object    The match object.
     */
    public function addMatch($Date, $Number)
    {
        // Check for an existing match
        foreach($this->listMatches() as $match) {
            if($match->getDate() == $Date && $match->getNumber() == $Number) {
                return $match;
            }
        }

        // Create a new match
        $data = new StdClass();
        $data->league_id = $this->getLeagueId();
        $data->season = $this->getName();
        $data->date = $Date . ' 00:00';
        $data->custom = array(
             'number' => $Number,
        );

        // Add the match to the arrays
        $index = -(count($this->matches) + 1);
        $this->matches[$index] = new my5280_Match($data, $this->getLeagueFormat());

        // Return the match
        return $this->matches[$index];
    }


    /**
     * Add a date of special significance for the session.
     *
     * @param string Date
     * @param string Description
     * @param boolean TRUE if there are no matches on this date
     */
    public function addSpecialDate($Date, $Description, $NoMatches)
    {
        // Initialize the date array
        if(!isset($this->season['specialDates'])) {
            $this->season['specialDates'] = array();
        }

        // Add the special date
        $this->season['specialDates'][$Date] = array(
            'description' => $Description,
            'matches' => !$NoMatches,
        );
    }


    /**
     * Add a new team to the session.
     *
     * @param string Name of the team.
     * @param string Home location for the team.
     * @param object The new team object.
     */
    public function addTeam($Name, $Location)
    {
        $this->listTeams();

        include_once(dirname(__FILE__) . '/team.php');
        $team = new my5280_Team();
        $team->setName($Name);
        $team->setLocation($Location);
        $team->setSession($this);
        $this->teams[] = $team;

        return $team;
    }


    /**
     * Clear the special dates from the session.
     *
     * @param none
     * @return void
     */
    public function clearSpecialDates()
    {
        unset($this->season['specialDates']);
    }


    /**
     * getCurrentWeek:  Retrieve the date considered to be the "current" week.
     *
     * @param none
     * @return string
     */
    public function getCurrentWeek()
    {
        $today = new DateTime('now', new DateTimeZone('America/Denver'));
        $today = $today->format('Y-m-d');
        $future = array();
        foreach($this->listMatches() as $match) {
            $date = $match->getDate();
            if($date >= $today) $future[$date] = $date;
        }
        return count($future) ? min($future) : null;
    }


    /**
     * Retrieve the number of doubles games.
     *
     * @param none
     * @return integer
     */
    public function getDoublesGames()
    {
        $this->loadFormatSettings();
        return $this->doublesGames;
    }


    /**
     * getLabel:    Retrieve the label portion of the session name.  This is assumed to be
     *              anything after the dash, if one exists.
     *
     * @param none
     * @return string
     */
    public function getLabel()
    {
        $name = $this->getName();
        $pos = strpos($name, '-');
        if($pos !== false) {
            $name = substr($name, $pos + 1);
        }
        return $name;
    }


    /**
     * getLeagueId: Retrieve the ID for the league.
     *
     * @param none
     * @return integer
     */
    public function getLeagueId()
    {
        return $this->league->id;
    }


    /**
     * Retrieve the name of the league.
     */
    public function getLeagueName()
    {
        return $this->league->title;
    }


    /**
     * Retrieve the league format.
     *
     * @param none
     * @return string
     */
    public function getLeagueFormat()
    {
        return $this->league->league_format;
    }


    /**
     * Retrieve the requested match.
     *
     * @param mixed The ID of the match or the match object from LeagueManager.
     * @return object The match object.
     */
    public function getMatch($ID)
    {
        if(is_numeric($ID)) {
            return my5280::$instance->getMatch($ID);
        } elseif(is_object($ID)) {
            // Include the appropriate match class file
            $format = $this->getLeagueFormat();
            $classFile = dirname(__FILE__) . '/formats/match.' . $format . '.php';
            if(is_file($classFile)) {
                $class = 'my5280_Match_' . $format;
            } else {
                $class = 'my5280_Match';
                $classFile = dirname(__FILE__) . '/match.php';
            }
            require_once($classFile);

            // Return the object
            return new $class($ID, $this->getLeagueFormat());
        }
    }


    /**
     * Retrieve the number of match days for the session.
     *
     * @param none
     * @return integer
     */
    public function getMatchDays()
    {
        return $this->season['num_match_days'];
    }


    /**
     * Retrieve the maximum number of games to use for handicaps.
     */
    public function getMaxHandicapGames()
    {
        if(isset($this->league->my5280_max_handicap_games) && $this->league->my5280_max_handicap_games != null) {
            return $this->league->my5280_max_handicap_games;
        }
        return null;
    }


    /**
     * getName: Retrieve the name of the session.
     *
     * @param none
     * @return string
     */
    public function getName($Pretty = false)
    {
        $name = $this->season['name'];
        if($Pretty) {
            $pos = strpos($name, '-');
            if($pos !== false) {
                $name = substr($name, $pos + 1);
            }
        }
        return $name;
    }


    /**
     * Retrieve the number of players per match.
     *
     * @param none
     * @return integer
     */
    public function getPlayerCount()
    {
        $this->loadFormatSettings();
        return $this->playerCount;
    }


    /**
     * Retrieve the number of player games.
     *
     * @param none
     * @return integer
     */
    public function getPlayerGames()
    {
        return $this->getPlayerCount() ^ 2;
    }


    /**
     * listMatches:  Retrieve an array of matches for the session.
     *
     * @param string (Optional) Boolean indicating if only finished matches (those with a home or away score) should
     *                          be provided.  Default FALSE.
     * @return array
     */
    public function listMatches($DoneOnly = false)
    {
        if(empty($this->matches)) {
            require_once(__DIR__ . '/match.php');

            // Include the appropriate match class file
            $format = $this->getLeagueFormat();

            $matches = array();

            // Load the teams (for lookup)
            $this->listTeams();

            global $leaguemanager;
            $filter = array('league_id' => $this->getLeagueId(), 'season' => $this->getName(), 'limit' => false);
            if($DoneOnly) {
                $filter['home_points'] = 'not_null';
                $filter['away_points'] = 'not_null';
            }

            foreach($leaguemanager->getMatches($filter) as $match) {
                // Add the match and index it
                $index = $match->id;
                if($index == null) {
                    $index = -(count($this->matches));
                }
                $matches[$index] = my5280_Match::factory($match, $format);
            }

            // Store the matches and index arrays
            if(!$DoneOnly) {
                $this->matches = $matches;
            } else {
                return $matches;
            }
        }

        return $this->matches;
    }


    /**
     * listPlayers: Retrieve an array of information about the players in the session.
     *
     * @param none
     * @return array
     */
    public function listPlayers()
    {
        if(!$this->players) {
            $this->players = array();

            // Process the matches to get player information
            foreach($this->listMatches() as $date => $matches) {
                foreach($matches as $match) {
                    foreach(array('home', 'away') as $key) {
                        if(isset($match->custom[$key . '_players'])) {

                        }
                    }
                }
            }
        }
        return $this->players;
    }


    /**
     * Retrieve a list of information on special dates.
     *
     * @param none
     * @return array
     */
    public function listSpecialDates()
    {
        if(isset($this->season['specialDates'])) {
            return $this->season['specialDates'];
        }
        return array();
    }


    /**
     * listTeams:  Retrieve an array of information about the teams in the session.
     *
     * @param none
     * @return array
     */
    public function listTeams()
    {
        if(!$this->teams) {
            $this->teams = array();
            $this->teamLookup = array();

            include_once(dirname(__FILE__) . '/team.php');

            global $leaguemanager;
            $filter = array('league_id' => $this->getLeagueId(), 'season' => $this->getName());
            foreach($leaguemanager->getTeams($filter) as $team) {
                $this->teams[$team->id] = new my5280_Team($team);
                $this->teamLookup[$team->number] = $team->id;
            }
        }
        return $this->teams;
    }


    /**
     * Load format-specific settings.
     *
     * @param none
     * @return void
     */
    protected function loadFormatSettings()
    {
        if($this->playerCount === null) {
            // Load the functions file
            $format = $this->getLeagueFormat();
            require_once(MY5280_PLUGIN_DIR . 'lib/formats/functions.' . $format . '.php');

            $this->playerCount = call_user_func('my5280_getPlayerCount_' . $format);
            $this->doublesGames = call_user_func('my5280_getDoublesGames_' . $format);
        }
    }


    /**
     * Save changes to the session.
     *
     * @param none
     * @return boolean Indicates success or failure.
     */
    public function save()
    {
        global $lmLoader;

        // Save the matches
        if($this->matches !== null) {
            // Determine the dates represented in the matches
            $dates = array();
            foreach($this->matches as $match) {
                $date = $match->getDate();
                $dates[$date] = $date;
            }

            // Sort the dates
            ksort($dates);
            $matchDays = array_flip(array_values($dates));

            // Save the matches, assigning the match day in the process
            $count = 0;
            $newList = array();
            foreach($this->matches as $match) {
                // Assign the match day
                $match->setMatchDay($matchDays[$match->getDate()]);

                // Save the match and add it to the new list (indexed on ID)
                $match->save();
                $newList[$match->getId()] = $match;
            }
            $this->matches = $newList;

            $this->season['num_match_days'] = max($this->season['num_match_days'], count($dates));
        }

        // Save session information
        $league = $this->league;
        $season = $this->season;
        $league->seasons[$this->getName()] = $season;
        $lmLoader->adminPanel->saveSeasons($league->seasons, $league->id);

        return true;
    }


    /**
     * the teams for the session
     */
    protected $teams = null;
    protected $teamLookup = null;

    /**
     * the league and session
     */
    protected $league;
    protected $session;

    /**
     * the season name
     */
    protected $name;

    /**
     * the matches for the session
     */
    protected $matches = null;
    protected $matchLookup = null;

    /**
     * Format-specific settings
     */
    protected $playerCount = null;
    protected $doublesGames = null;
}
