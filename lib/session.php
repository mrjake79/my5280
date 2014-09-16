<?php

/**
 * Class to read (and perhaps one day write) data from an Excel session file.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

require_once(dirname(__FILE__) . '/PHPExcel/Classes/PHPExcel.php');

class my5280_Session
{
    /**
     * the teams for the session
     */
    var $teams;
    var $teamLookup;

    /**
     * the league and session
     */
    protected $league;
    protected $session;

    /**
     * the season name
     */
    var $name;

    /**
     * the matches for the session
     */
    var $matches;


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
     * getCurrentWeek:  Retrieve the date considered to be the "current" week.
     *
     * @param none
     * @return string
     */
    public function getCurrentWeek()
    {
        $today = new DateTime('now', new DateTimeZone('America/Denver'));
        $today = $today->format('Y-m-d');
        foreach(array_keys($this->listMatches()) as $match) {
            if($match >= $today) return $match;
        }
        return null;
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
     * getName: Retrieve the name of the session.
     *
     * @param none
     * @return string
     */
    public function getName()
    {
        return $this->season['name'];
    }


    /**
     * import:  Import all information from the Excel file.
     *
     * @param array The upload array for the Excel file.
     * @param array An array within which to return errors.
     * @return bool
     */
    public function import($File, &$Errors = array())
    {
        // Import the teams
        if(!$this->importTeams($File, $Errors)) {
            $Errors[] = 'Unable to import teams.';
            return false;
        }

        // Import doubles
        if(!$this->importDoubles($File, $Errors)) {
            $Errors[] = 'Unable to import doubles.';
            return false;
        }

        // Import the matches
        if(!$this->importMatches($File, $Errors)) {
            $Errors[] = 'Unable to import matches.';
            return false;
        }

        // Import the scores
        if(!$this->importScores($File, $Errors)) {
            $Errors[] = 'Unable to import scores.';
            return false;
        }

        return true;
    }


    /**
     * listMatches:  Retrieve an array of matches for the session.
     *
     * @param none
     * @return array
     */
    public function listMatches()
    {
        if(empty($this->matches)) {
            $matches = array();

            // Load the teams (for lookup)
            $this->listTeams();

            global $leaguemanager;
            $filter = "league_id = {$this->getLeagueId()} and season = '{$this->getName()}'";
            foreach($leaguemanager->getMatches($filter) as $match) {
                // Switch out team IDs with team numbers
                $match->home_team_id = $match->home_team;
                $match->home_team = $this->teamLookup[$match->home_team];
                $match->away_team_id = $match->away_team;
                $match->away_team = $this->teamLookup[$match->away_team];

                // Add the match indexed on date and then date match #
                $date = substr($match->date, 0, 10);
                if(!isset($matches[$date])) $matches[$date] = array();
                $matches[$date][$match->date_match] = $match;
            }

            $this->matches = $matches;
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
                        print '<pre>';print_r($match);exit;
                        if(isset($match->custom[$key . '_players'])) {

                        }
                    }
                }
            }
        }
        return $this->players;
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

            global $leaguemanager;
            $filter = "league_id = {$this->getLeagueId()} and season = '{$this->getName()}'";
            foreach($leaguemanager->getTeams($filter) as $team) {
                $this->teams[$team->teamNumber] = $team;
                $this->teamLookup[$team->id] = $team->teamNumber;
            }
        }
        return $this->teams;
    }


    /**
     * importDoubles:  Import doubles from the Excel file (if they exist).
     *
     * @param array $File Upload array from the file.
     * @param array $Errors An array within which any errors will be added.
     * @return bool
     */
    protected function importDoubles($File, &$Errors = array())
    {
        global $wpdb, $connections;
        $cnRetrieve = $connections->retrieve;

        // Load the Doubles sheet from the file
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly('Doubles');
        $excel = $reader->load($File['tmp_name']);
        $sheet = $excel->getSheetByName('Doubles');
        if(!$sheet) return true;

        // Load the doubles
        $lastRow = $sheet->getHighestRow();
        $rows = $sheet->rangeToArray('A2:C' . $lastRow, null, true, true, false);
        foreach($rows as $row) {
            if($row[0] == null) continue;

            # Get the "family" name
            $names = explode('+', $row[0]);
            $family_name = ucwords($names[0]) . ' & ' . ucwords($names[1]);

            # Search for an existing entry
            $ret = $cnRetrieve->entries(array(
                'family_name' => $family_name,
            ));
            if(count($ret) == 0) {
                # Create the entry
                $entry = new cnEntry();
                $entry->setEntryType('family');
                $entry->setFamilyName($family_name);
                $entry->setVisibility('private');
                $entry->setStatus('approved');

                # Create the "family"
                $family = array();
                foreach($names as $name) {
                    $parts = explode(',', ucwords($name));
                    $ret = $cnRetrieve->entries(array(
                        'first_name' => $parts[1],
                        'last_name' => $parts[0],
                    ));
                    if(count($ret) == 1) {
                        $family[] = array('entry_id' => $ret[0]->id, 'relation' => 'partner');
                    }
                }
                $entry->setFamilyMembers($family);

                // Save the entry
                if($entry->save()) {
                    $id = $connections->lastInsertID;
                } else {
                    continue;
                }

                // Update meta data
                $meta = array(
                    array('key' => 'my5280_handicap_start', 'value' => $row[1]),
                    array('key' => 'my5280_lifetime_start', 'value' => $row[2]),
                );
                cnEntry_Action::meta('add', $id, $meta);
            }
        }

        return true;
    }


    /**
     * importMatches:  Iimport matches from the Excel file.
     *
     * @param none
     * @return bool
     */
    protected function importMatches($File, &$Errors = array())
    {
		global $wpdb;
        global $lmLoader, $leaguemanager;
        $lmAdmin = $lmLoader->adminPanel;

        // Get the existing matches
        $existing = $this->listMatches();

        // Get the teams and scores
        $teams = $this->listTeams();

        // Load the Teams sheet from the file
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly('Schedule');
        $excel = $reader->load($File['tmp_name']);
        $sheet = $excel->getSheetByName('Schedule');

        // Extract schedule information from the sheet
        $rows = $sheet->rangeToArray('A3:O30', null, true, true, false);
        $maxWeek = 0;
        foreach($rows as $row) {
            // Skip rows with no week number
            $week = $row[0];
            if(empty($week)) continue;
            $maxWeek = max($week, $maxWeek);

            // Get the date and round
            $date = PHPExcel_Shared_Date::ExcelToPHP($row[1]);
            $date = date('Y-m-d', $date);
            $round = $row[2];

            // Process each match
            $iDateMatch = 1;
            for($iMatch = 3; $iMatch < 14; $iMatch += 2) {
                $home = $row[$iMatch];
                $away = $row[$iMatch+1];
                // Make sure both teams exist
                if(isset($teams[$home]) && isset($teams[$away])) {
                    // Check if the match already exists
                    if(isset($existing[$date][$iDateMatch])) {
                        $old = $existing[$date][$iDateMatch];
                        $matchId = $old->id;
                        $lmAdmin->editMatch(
                            $date,
                            $teams[$home]->id,
                            $teams[$away]->id,
                            $week,
                            $teams[$home]->stadium,
                            $this->getLeagueId(),
                            $old->id,
                            $old->group,
                            null,
                            array(
                                'round' => $round,
                                'date_match' => $iDateMatch++,
                            )
                        );
                    } else {
                        $matchId = $lmAdmin->addMatch(
                            $date,
                            $teams[$home]->id,
                            $teams[$away]->id,
                            $week,
                            $teams[$home]->stadium,
                            $this->getLeagueId(),
                            $this->getName(),
                            null,
                            false,
                            array(
                                'round' => $round,
                                'date_match' => $iDateMatch++,
                            )
                        );
                    }
                }
            }
        }

        // Update the number of match days
        $league = $this->league;
        $this->season['num_match_days'] = $maxWeek;
        $league->seasons[$this->season['name']] = $this->season;
        $lmAdmin->saveSeasons($league->seasons, $this->getLeagueId());

        // Clear the cached matches to allow new information to be loaded
        $this->matches = null;
        return true;
    }


    /**
     * importScores:  Import scores from the Excel file.
     *
     * @param none
     * @return bool
     */
    protected function importScores($File, &$Errors = array())
    {
        global $leaguemanager, $lmLoader;
        $lmAdmin = $lmLoader->adminPanel;

        // Initialize arrays for results
        $results = array(
            'league_id' => $this->getLeagueId(),
            'matches' => array(),
            'home_points' => array(),
            'away_points' => array(),
            'home_team' => array(),
            'away_team' => array(),
            'custom' => array(),
            'final' => false,
            'message' => false,
        );

        // Get the matches
        $matches = $this->listMatches();

        // Build a lookup array of team names to numbers
        $teams = array();
        foreach($this->listTeams() as $team) {
            $name = htmlspecialchars_decode($team->title, ENT_QUOTES);
            $teams[strtolower($name)] = $team;
        }

        // Load the team scores sheet
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(array('Team Scores', 'Doubles Scores', 'Player Scores'));
        $excel = $reader->load($File['tmp_name']);
        $sheet = $excel->getSheetByName('Team Scores');
        $lastRow = $sheet->getHighestRow();

        // Process all team scores
        for($row = 2; $row <= $lastRow; ++$row) {
            $cells = $sheet->rangeToArray('A' . $row . ':K' . $row, null, true, true, false);
            $cells = $cells[0];
            if(!$cells[0]) continue;

            // Get the date
            $date = PHPExcel_Shared_Date::ExcelToPHP($cells[0]);
            $date = date('Y-m-d', $date);

            // Initialize the team score
            $team = strtolower($cells[3]);
            $team = $teams[$team];

            // Find the match to update
            if(isset($matches[$date])) {
                foreach($matches[$date] as $match) {
                    if($match->home_team == $team->teamNumber) {
                        $results['matches'][$match->id] = $match->id;
                        $results['home_team'][$match->id] = $match->home_team_id;
                        if(!isset($results['home_points'][$match->id])) {
                            $results['home_points'][$match->id] = 0;
                        }
                        $results['home_points'][$match->id] += $cells[6];
                    } elseif($match->away_team == $team->teamNumber) {
                        $results['matches'][$match->id] = $match->id;
                        $results['away_team'][$match->id] = $match->away_team_id;
                        if(!isset($results['away_points'][$match->id])) {
                            $results['away_points'][$match->id] = 0;
                        }
                        $results['away_points'][$match->id] += $cells[6];
                    }
                }
            }
        }

        // Process all player scores
        $sheet = $excel->getSheetByName('Player Scores');
        $lastRow = $sheet->getHighestRow();
        for($row = 2; $row <= $lastRow; ++$row) {
            $cells = $sheet->rangeToArray('A' . $row . ':S' . $row, null, true, true, false);
            $cells = $cells[0];
            if(!$cells[0]) continue;

            // Get the date
            $date = PHPExcel_Shared_Date::ExcelToPHP($cells[0]);
            $date = date('Y-m-d', $date);
            if(!isset($scores[$date])) $scores[$date] = array();

            // Get the match
            if(isset($matches[$date][$cells[1]])) {
                $match = $matches[$date][$cells[1]];
                if(!isset($results['custom'][$match->id])) {
                    $results['custom'][$match->id] = array(
                        'home_players' => array(),
                        'away_players' => array(),
                    );
                }

                // Extract information from the cells
                $key = strtolower($cells[12]);
                $name = strtolower($cells[6]);
                $handicap = $cells[7];
                $score = $cells[8];
                $paid = $cells[18];

                // Update custom data
                if(!isset($results['custom'][$match->id][$key . '_players'][$name])) {
                    $results['custom'][$match->id][$key . '_players'][$name] = array(
                        'handicap' => $handicap,
                        'paid' => 0,
                        'scores' => array(),
                    );
                }
                $results['custom'][$match->id][$key . '_players'][$name]['paid'] += $paid;
                $results['custom'][$match->id][$key . '_players'][$name]['scores'][] = $score;
            }
        }

        // Process all doubles scores
        $sheet = $excel->getSheetByName('Doubles Scores');
        if($sheet) {
            $lastRow = $sheet->getHighestRow();
            for($row = 2; $row < $lastRow; ++$row) {
                $cells = $sheet->rangeToArray('A' . $row . ':N' . $row, null, true, true, false);
                $cells = $cells[0];
                if(!$cells[0]) continue;

                // Get the date
                $date = PHPExcel_Shared_Date::ExcelToPHP($cells[0]);
                $date = date('Y-m-d', $date);
                
                // Get the match
                if(isset($matches[$date][$cells[1]])) {
                    $match = $matches[$date][$cells[1]];
                    if(!isset($results['custom'][$match->id])) {
                        $results['custom'][$match->id] = array(
                            'doubles' => array(),
                        );
                    } elseif(!isset($results['custom'][$match->id]['doubles'])) {
                        $results['custom'][$match->id]['doubles'] = array();
                    }

                    // Extract information from the cells
                    $name = strtolower($cells[5]);
                    $handicap = $cells[8];
                    $score = $cells[9];

                    // Update custom data
                    if(!isset($results['custom'][$match->id]['doubles'][$name])) {
                        $results['custom'][$match->id]['doubles'][$name] = array(
                            'handicap' => $handicap,
                            'scores' => array(),
                        );
                    }
                    $results['custom'][$match->id]['doubles'][$name]['scores'][] = $score;
                }
            }
        }

        // Update results
        call_user_func_array(array($lmAdmin, 'updateResults'), $results);

        return true;
    }


    /**
     * importTeams:  Import teams from the Excel file.
     *
     * @param none
     * @return bool
     */
    protected function importTeams($File, &$Errors = array())
    {
        global $leaguemanager, $lmLoader;
        $lmAdmin = $lmLoader->adminPanel;
        if(!$lmAdmin) return false;

        // Get the league
        $league = $this->league;

        // Get the existing teams from the database
        $existing = $this->listTeams();

        // Load teams from the database
        $teams = $this->loadTeams($File);
        foreach($teams as $team) {
            // De-dup based on team number (name would be bad if someone was renamed)
            if(isset($existing[$team['number']])) {
                $old = $existing[$team['number']];
                $lmAdmin->editTeam(
                    $old->id,
                    $team['name'],
                    $old->website,
                    $old->coach,
                    $team['location'],
                    $old->home,
                    $old->group,
                    $old->roster,
                    array(
                        'teamNumber' => $team['number'],
                        'address' => $team['address'],
                        'players' => $team['players'],
                    ),
                    $old->logo
                );
            } else {
                $lmAdmin->addTeam(
                    $this->getLeagueId(),
                    $this->getName(),
                    $team['name'],
                    '', // website
                    '', // coach
                    $team['location'],
                    '', // home
                    '', // group
                    '', // roster
                    array(
                        'teamNumber' => $team['number'],
                        'address' => $team['address'],
                        'players' => $team['players'],
                    )
                );
            }
        }

        $this->teams = null;
        return true;
    }


    /**
     * Load teams and rosters from the Excel file.
     *
     * @param none
     * @return array
     */
    protected function loadTeams($File)
    {
        $teams = array();

        // Load the Teams sheet from the file
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly('Team Rosters');
        $excel = $reader->load($File['tmp_name']);
        $sheet = $excel->getSheetByName('Team Rosters');
        $lastRow = $sheet->getHighestRow();

        // Process the team rows
        for($row = 3; $row <= $lastRow; $row += 10) {
            $cells = array();
            $a = ord('A');
            for($i = 0; $i < 10; $i++) {
                $cells[$i] = array();
                for($j = 1; $j < 11; $j++) {
                    $cell = $sheet->getCell(chr($a + $j) . ($row + $i));
                    try {
                        $value = $cell->getCalculatedValue();
                    } catch(Exception $e) {
                        $value = $cell->getOldCalculatedValue();
                    }
                    $cells[$i][] = $value;
                }
            }

            if($cells[1][0] != null && $cells[3][0] !== '') {
                // Get basic information
                $name = $cells[1][0];
                if($name == strtolower($name)) {
                    $name = ucwords($name);
                }
                $number = $cells[3][0];
                $location = ucwords($cells[5][0]);
                $address = $cells[7][0];

                // Get players
                $players = array();
                for($i = 0; $i < 10; $i++) {
                    $plName = $cells[$i][1];
                    if($plName != null) {
                        $players[ucwords($plName)] = $cells[$i][8];
                    }
                }

                // Add the team to the list
                $teams[$number] = array(
                    'number' => $number,
                    'name' => $name,
                    'location' => $location,
                    'address' => $address,
                    'players' => $players,
                );
            }
        }

        return $teams;
    }
}
