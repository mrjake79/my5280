<?php
/*
Plugin Name: 5280 Pool League
Description: Provides functionality specific to 5280 Pool League.
Version: 0.0.1
Author: Jake Bahnsen

Copyright 2014  Jake Bahnsen  (email : jake@5280pool.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
* Loading class for the WordPress plugin LeagueOperator
*
* @author 	Jake Bahnsen
* @package	my5280
* @copyright 	Copyright 2014
*/
class my5280 //extends LeagueManager
{
    /**
     * Plugin Version
     *
     * @var string
     */
    var $version = '0.0.1';

    /**
     * Database Version
     *
     * @var string
     */
    var $dbversion = '0.1';


    /**
     * admin Panel object
     *
     * @var object
     */
    var $adminPanel;


    /**
     * constructor
     *
     * @param none
     * @return void
     */
    public function __construct()
    {
        global $my5280, $wpdb;
        $wpdb->show_errors();
        $this->loadLibraries();

        // Register the 5280 Pool League "sport"
        add_filter('leaguemanager_sports', array($this, 'sports'));

        // Register actions
        add_action('league_settings_5280pool', array($this, 'settings'));
        add_action('wp_head', function() {
            wp_register_script('storageapi', WP_PLUGIN_URL . '/my5280/jquery.storageapi.min.js', array('jquery'), '1.7.2');
            wp_register_script('my5280', WP_PLUGIN_URL . '/my5280/my5280.js', array('storageapi'), '0.1');
            wp_print_scripts(array('storageapi', 'my5280'));
        });
        add_action('wp_ajax_my5280_update_scoresheet', array($this, 'updateScoresheet'));
        add_action('wp_ajax_my5280_update_schedule', array($this, 'updateSchedule'));
        //add_action('cn_metabox_publish_atts', array($this, 'connectionsPublishAtts'));

        // Register short codes
        add_shortcode('my5280_schedule', array($this, 'showSchedule'));
        add_shortcode('my5280_standings', array($this, 'showStandings'));
        add_shortcode('my5280_rosters', array($this, 'showRosters'));
        add_shortcode('my5280_scoresheet', array($this, 'showScoresheet'));

        if(is_admin()) {
            $this->adminPanel = new my5280AdminPanel();
        }
    }


    /**
     * callback for Connections to alter the publish attributes
     *
     * @param array
     * @return array
     */
    public function connectionsPublishAtts($atts)
    {
        return array(
            'entry_type' => array(
                'Player' => 'individual',
                'Location' => 'organization',
                'Team' => 'team',
                'Doubles' => 'doubles',
            ),
        );
    }


    /**
     * Retrieve a match.
     *
     * @param integer Match ID.
     * @return object my5280_Match object
     */
    public function getMatch($ID)
    {
        global $leaguemanager;
        foreach($leaguemanager->getMatches('id=' . $ID) as $match) {
            $session = $this->getSession($match->league_id, $match->season);
            return $session->getMatch($match);
        }
        return null;
    }


    /**
     * Retrieve a doubles team.
     *
     * @param mixed First player's name or ID for doubles team.
     * @param string Second player's name
     * @param bool (Optional) Boolean indicating if the doubles team should be created automatically.
     * @return my5280_Doubles object
     */
    public function getDoubles($First, $Second = null, $Create = true)
    {
        require_once(MY5280_PLUGIN_DIR . 'lib/doubles.php');
        $doubles = new my5280_Doubles($First, $Second);
        if($doubles->getId() === null) {
            if(!$Create) return null;
            $doubles->save();
        }
        return $doubles;
    }


    /**
     * Retrieve a player.
     *
     * @param mixed Player name or ID.
     * @param bool (Optional) Boolean indicating if the player should be automatically created.  When
     *              $Name is numeric (an ID), this argument has no effect.
     * @return object my5280_Player object
     */
    public function getPlayer($Name, $Create = true)
    {
        global $connections;
        $cnRetrieve = $connections->retrieve;

        // Handle a numeric name
        if(is_numeric($Name)) {
            $entry = $cnRetrieve->entry($Name);
            if($entry) {
                $entry = new cnEntry($entry);
            } else {
                return null;
            }
        } elseif(is_string($Name)) {
            // Parse the name
            $parts = explode(',', $Name);
            $count = count($parts);
            if($count == 2) {
                $firstName = trim($parts[1]);
                $lastName = trim($parts[0]);
            } elseif($count == 1) {
                $parts = explode(' ', $Name);
                $count = count($parts);
                $firstName = $parts[0];
                if($count >= 2) {
                    $lastName = implode(' ', array_slice($parts, 1));
                } else {
                    $lastName = null;
                }
            } else {
                $firstName = $Name;
                $lastName = null;
            }

            // Search for the contact
            $ret = $cnRetrieve->entries(array(
                'first_name' => $firstName,
                'last_name' => $lastName,
            ));

            // Get the matching entry
            $entry = null;
            foreach($ret as $possible) {
                if(strcasecmp($possible->first_name, $firstName) == 0 && strcasecmp($possible->last_name, $lastName) == 0) {
                    $entry = $possible;
                    break;
                }
            }

            // Create a new entry (if requested)
            if($entry === null) {
                if($Create) {
                    // Set basic information
                    $entry = new cnEntry();
                    $entry->setFirstName($firstName);
                    $entry->setLastName($lastName);
                    $entry->setEntryType('individual');
                    $entry->setVisibility('private');
                    $entry->setStatus('approved');
                } else {
                    return null;
                }
            } else {
                $entry = new cnEntry($entry);
            }
        } else {
            print '<pre>';
            print_r(debug_backtrace());
            exit;
        }

        // Return the my5280_Player object
        require_once(MY5280_PLUGIN_DIR . 'lib/player.php');
        return new my5280_Player($entry);
    }


    /**
     * Retrieve a session.
     *
     * @param mixed league ID, league object, or NULL
     * @param mixed season name, season object, or NULL
     * @return object my5280_Session object
     */
    public function getSession($League = null, $Session = null)
    {
        return new my5280_Session($League, $Session);
    }


	/**
	 * load libraries
	 *
	 * @param none
	 * @return void
	 */
	public function loadLibraries()
    {
        require_once(dirname(__FILE__) . '/lib/session.php');
        if(is_admin()) {
            require_once(dirname(__FILE__) . '/admin/admin.php');
        }
    }


    /**
     * add custom settings for the league.
     *
     * @param none
     * @return void
     */
    function settings($league)
    {
        $choices = array('8ballscotch' => 'Scotch Doubles', '8ball5x5' => '8-Ball 5x5');

        ?><tr valign="top">
            <th scope="row"><label for="league_format">League Format</label></th>
            <td>
                <select name="settings[league_format]" id='league_format'>
                    <option value='unknown'>(Unknown)</option>
                    <?php foreach($choices as $value => $label):
                        print '<option value="' . $value . '"';
                        if(isset($league->league_format) && $league->league_format == $value) {
                            print ' selected="selected"';
                        }
                        print '>' . $label . '</option>';
                    endforeach; ?>
                </select>
            </td>
        </tr><?php
    }


    /**
     * register the sport
     *
     * @param array
     * @return array
     */
    function sports($sports)
    {
        $sports['5280pool'] = __('5280 Pool League (BCA)', 'my5280');
        return $sports;
    }


    /*
     * show team rosters
     *
     * @param array
     * @param bool
     * @return void
     */
    function showRosters($atts, $widget = false)
    {
        global $leaguemanager;

        // Extract attributes
        extract(shortcode_atts(array(
            'league_id' => 0,
            'league_name' => '',
            'season' => false,
        ), $atts ));

        // Get the session, its teams, and its matches
        $league = $leaguemanager->getLeague($league_id);
        $session = new my5280_Session($league_id, $season);
        $teams = $session->listTeams();

        include(MY5280_PLUGIN_DIR . '/templates/teamrosters.php');
    }


    /**
     * show the schedule for a league
     *
     * @param none
     * @return void
     */
    function showSchedule($atts, $widget = false)
    {
        global $leaguemanager;

        // Extract attributes
        extract(shortcode_atts(array(
            'league_id' => 0,
            'league_name' => '',
            'season' => false,
            'scoresheet_url' => null,
        ), $atts ));

        // Get the session, its teams, and its matches
        $league = $leaguemanager->getLeague($league_id);
        $session = new my5280_Session($league_id, $season);
        $teams = $session->listTeams();

        // Initialize some helpful variables
        $maxMatches = 0;

        // Build the list of dates with their assigned matches
        $dates = array();
        foreach($session->listMatches() as $match) {
            // Initialize the date
            $date = $match->getDate();
            if(!isset($dates[$date])) {
                $dates[$date] = array(
                    'note' => null,
                    'noMatches' => false,
                    'matches' => array()
                );
            }

            // Add the match
            $dates[$date]['matches'][$match->getNumber()] = $match;
            $maxMatches = max($maxMatches, count($dates[$date]['matches']));
        }

        // Add special dates
        foreach($session->listSpecialDates() as $date => $special) {
            // Initialize the date
            if(!isset($dates[$date])) {
                $dates[$date] = array(
                    'note' => null,
                    'noMatches' => false,
                    'matches' => array(),
                );
            }

            // Add the note and update the noMatches flag
            $dates[$date]['note'] = htmlentities($special['description']);
            $dates[$date]['noMatches'] = !$special['matches'];
        }

        // Sort the dates
        ksort($dates);

        include(MY5280_PLUGIN_DIR . '/templates/schedule.php');
    }


    /**
     * show the scoresheet for a league
     *
     * @param array
     * @param bool
     * @return void
     */
    function showScoresheet($atts, $widget = false)
    {
        global $leaguemanager;

        // Extract attributes
        extract(shortcode_atts(array(
            'league_id' => 0,
            'league_name' => '',
            'season' => false,
            'mode' => null,
            'title' => null,
            'match_id' => isset($_GET['match']) ? $_GET['match'] : null,
        ), $atts ));

        // Get the league, session, and teams
        $league = $leaguemanager->getLeague($league_id);
        $session = new my5280_Session($league_id, $season);
        $format = $session->getLeagueFormat();

        // Determine the title
        if(!isset($title) || $title === null) {
            $title = $session->getLeagueName() . '<br />' . $session->getName(true);
        }

        // Check for a specific match
        $curMatch = null; $helperMatch = null;
        $matches = $session->listMatches(false);
        if(isset($match_id) && $match_id !== null) {
            if(isset($matches[$match_id])) {
                $curMatch = $matches[$match_id];
                $helperMatch = $curMatch;
            } else {
                $helperMatch = array_pop($matches);
            }
        } else {
            $helperMatch = array_pop($matches);
        }

        // Get the home and away teams
        if($curMatch) {
            // Load format-specific functionality
            $format = $session->getLeagueFormat();
            require_once(MY5280_PLUGIN_DIR . 'lib/formats/functions.' . $format . '.php');

            // Initialize the team array
            $teams = array();

            // Get home team information
            $team = $curMatch->getHomeTeam();
            $players = array();
            foreach($team->listPlayers() as $player) {
                $players[$player->getId()] = array(
                    'id' => $player->getId(),
                    'name' => $player->getName(),
                    'handicap' => round($player->getHandicap(), 0),
                    'sel' => array(),
                );
            }

            // Indicate the selected players
            $homeHcp = 0;
            foreach($curMatch->listHomePlayers() as $index => $player) {
                $id = $player['id'];
                if($id) {
                    // Make sure the player is in the list and is selected
                    if(!isset($players[$id])) {
                        $players[$id] = array(
                            'id' => $id,
                            'name' => $player['player']->getName(),
                            'handicap' => $player['handicap'],
                            'sel' => array($index),
                        );
                    } else {
                        $players[$id]['handicap'] = $player['handicap'];
                        $players[$id]['sel'][] = $index;
                    }

                    // Calculate total home handicap
                    $homeHcp += $player['handicap'];
                }
            }

            // Get round handicaps
            $roundHandicaps = $curMatch->listRoundHandicaps();

            // Add the home team to the list
            $teams['HOME'] = array(
                'team' => $team,
                'players' => $players,
                'selPlayers' => $curMatch->listHomePlayers(),
                'scores' => $curMatch->listHomeScores(),
                'handicap' => $homeHcp,
                'hcpPerRound' => 0,
                'roundTotals' => array(),
                'roundHandicaps' => array(),
                'playerTotals' => array(),
                'totalHcpPoints' => 0,
                'totalPoints' => 0,
            );

            // Get away team information
            $team = $curMatch->getAwayTeam();
            $players = array();
            foreach($team->listPlayers() as $player) {
                $players[$player->getId()] = array(
                    'id' => $player->getId(),
                    'name' => $player->getName(),
                    'handicap' => round($player->getHandicap(), 0),
                    'sel' => array(),
                );
            }

            // Indicate the selected players
            $awayHcp = 0;
            foreach($curMatch->listAwayPlayers() as $index => $player) {
                $id = $player['id'];
                if($id) {
                    // Make sure the player is in the list
                    if(!isset($players[$id])) {
                        $players[$id] = array(
                            'id' => $id,
                            'name' => $player['player']->getName(),
                            'handicap' => $player['handicap'],
                            'sel' => array($index),
                        );
                    } else {
                        $players[$id]['handicap'] = $player['handicap'];
                        $players[$id]['sel'][] = $index;
                    }

                    // Calculate total away handicap
                    $awayHcp += $player['handicap'];
                }
            }

            // Add the away team to the list
            $teams['AWAY'] = array(
                'team' => $team,
                'players' => $players,
                'selPlayers' => $curMatch->listAwayPlayers(),
                'scores' => $curMatch->listAwayScores(),
                'handicap' => $awayHcp,
                'hcpPerRound' => 0,
                'roundTotals' => array(),
                'roundHandicaps' => array(),
                'playerTotals' => array(),
                'totalHcpPoints' => 0,
                'totalPoints' => 0,
            );

            // Process scores
            foreach(array('HOME', 'AWAY') as $key) {
                foreach($teams[$key]['scores'] as $game => $score) {
                    // Calculate round totals
                    $round = $curMatch->getRoundNumber($game);
                    if(!isset($teams[$key]['roundTotals'][$round])) {
                        // Determine handicap points for the round
                        if(isset($roundHandicaps[$round])) {
                            $hcpPts = $roundHandicaps[$round][ ($key == 'HOME' ? 0 : 1) ];
                        } else {
                            $hcpPts = 0;
                        }

                        // Add the handicap points to the round totals, total handicap, and total points for the team
                        $teams[$key]['roundHandicaps'][$round] = $hcpPts;
                        $teams[$key]['roundTotals'][$round] = $hcpPts;
                        $teams[$key]['totalHcpPoints'] += $hcpPts;
                        $teams[$key]['totalPoints'] += $hcpPts;
                    }

                    // Calculate round totals and total points
                    $teams[$key]['roundTotals'][$round] += $score;
                    $teams[$key]['totalPoints'] += $score;

                    // Calculate player totals
                    $player = ($key == 'HOME' ? $curMatch->getHomePlayerNumber($game) : $curMatch->getAwayPlayerNumber($game));
                    if(!isset($teams[$key]['playerTotals'][$player])) {
                        $teams[$key]['playerTotals'][$player] = $score;
                    } else {
                        $teams[$key]['playerTotals'][$player] += $score;
                    }
                }
            }
        } else {
            $teams = array(
                'HOME' => null,
                'AWAY' => null,
            );
        }

        // Handle flags
        if(!isset($mode) || $mode === null || $curMatch === null) {
            $mode = 'view';
        }

        // Get an array of all players in the system
        $allPlayers = cnRetrieve::individuals();
        foreach(array_keys($allPlayers) as $id) {
            $allPlayers[$id] = my5280::$instance->getPlayer($id);
        }
        uasort($allPlayers, function($a, $b) {
            return strcasecmp($a->getName(), $b->getName());
        });

        // Determine the path to the template
        $template = null;
        if(isset($league->league_format)) {
            $template = MY5280_PLUGIN_DIR . '/templates/scoresheets/' . $league->league_format . '_' . $mode . '.php';
            if(!file_exists($template)) $template = null;
        }

        // Display the template
        if($template) {
            include($template);
        } else {
            print "<i>There is no scoresheet for this league.</i>";
        }
    }


    /**
     * show the standings for a league
     *
     * @param none
     * @return void
     */
    function showStandings($atts, $widget = false)
    {
        global $leaguemanager;

        // Extract attributes
        extract(shortcode_atts(array(
            'league_id' => 0,
            'league_name' => '',
            'season' => false,
        ), $atts ));

        // Get the league, session, and teams
        $league = $leaguemanager->getLeague($league_id);
        $session = new my5280_Session($league_id, $season);
        $teams = $session->listTeams();

        // Sort the teams on total points
        uasort($teams, function($a, $b) {
            if($a->getRank() < $b->getRank()) {
                return -1;
            } elseif($a->getRank() > $b->getRank()) {
                return 1;
            } else {
                return 0;
            }
        });

        // Calculate player information
        $players = array(); $doubles = array();
        foreach($session->listMatches(true) as $match) {
            foreach($match->listPlayerPoints() as $playerId => $points) {
                if(!isset($players[$playerId]) && !isset($doubles[$playerId])) {
                    $player = my5280::$instance->getPlayer($playerId);
                    if($player->getType() != 'family') {
                        $players[$playerId] = $points;
                        $players[$playerId]['id'] = $playerId;
                        $players[$playerId]['name'] = $player->getName();
                        $players[$playerId]['win%'] = round($players[$playerId]['wins'] / $players[$playerId]['games'] * 100, 2);
                    } else {
                        $doubles[$playerId] = $points;
                        $doubles[$playerId]['id'] = $playerId;
                        $doubles[$playerId]['name'] = $player->getName();
                        $doubles[$playerId]['win%'] = round($doubles[$playerId]['wins'] / $doubles[$playerId]['games'] * 100, 2);
                    }
                } elseif(isset($players[$playerId])) {
                    $players[$playerId]['games'] += $points['games'];
                    $players[$playerId]['points'] += $points['points'];
                    $players[$playerId]['wins'] += $points['wins'];
                    $players[$playerId]['win%'] = round($players[$playerId]['wins'] / $players[$playerId]['games'] * 100, 2);
                } else {
                    $doubles[$playerId]['games'] += $points['games'];
                    $doubles[$playerId]['points'] += $points['points'];
                    $doubles[$playerId]['wins'] += $points['wins'];
                    $doubles[$playerId]['win%'] = round($doubles[$playerId]['wins'] / $doubles[$playerId]['games'] * 100, 2);
                }
            }
        }

        // Sort players on win %
        uasort($players, function($a, $b) {
            if($a['win%'] > $b['win%']) {
                return -1;
            } elseif($a['win%'] < $b['win%']) {
                return 1;
            } else {
                return 0;
            }
        });

        // Sort doubles on win %
        uasort($doubles, function($a, $b) {
            if($a['win%'] > $b['win%']) {
                return -1;
            } elseif($a['win%'] < $b['win%']) {
                return 1;
            } else {
                return 0;
            }
        });

        // Display overall standings
        include(MY5280_PLUGIN_DIR . '/templates/standings.php');
    }



    /**
     * Handle schedule submission.
     */
    public function updateSchedule()
    {
        // Check for league and season name
        $league = isset($_POST['league']) ? $_POST['league'] : null;
        $season = isset($_POST['season']) ? $_POST['season'] : null;
        if($league && $season) {
            // Get the session
            $session = $this->getSession($league, $season);

            // Handle special dates
            if(isset($_POST['specialDates']) && is_array($_POST['specialDates'])) {
                // Clear the existing special dates
                $session->clearSpecialDates();

                // Add the new dates
                foreach($_POST['specialDates'] as $sd) {
                    $date = trim($sd['date']);
                    $descr = trim($sd['description']);
                    if($date != null && $descr != null) {
                        $date = strtotime($date);
                        if($date !== false) {
                            $session->addSpecialDate(date('Y-m-d', $date), $descr, (bool) $sd['noMatches']);
                        }
                    }
                }
            }

            // Handle team numbers
            $teamLookup = array();
            if(isset($_POST['teamNumbers']) && is_array($_POST['teamNumbers'])) {
                // Get the teams
                $teams = $session->listTeams();

                // Process the numbers
                foreach($_POST['teamNumbers'] as $number => $teamId) {
                    $team = $teams[$teamId];
                    $team->setTeamNumber($number);
                    $team->save();

                    $teamLookup[$number] = $team;
                }
            } else {
                foreach($session->listTeams() as $team) {
                    $teamLookup[$team->getTeamNumber()] = $team;
                }
            }

            // Get schedule information
            $dates = (isset($_POST['date']) && is_array($_POST['date'])) ? $_POST['date'] : array();
            $homeTeams = (isset($_POST['homeTeam']) && is_array($_POST['homeTeam'])) ? $_POST['homeTeam'] : array();
            $awayTeams = (isset($_POST['awayTeam']) && is_array($_POST['awayTeam'])) ? $_POST['awayTeam'] : array();

            // Add matches
            foreach($dates as $index => $date) {
                $date = strtotime($date);
                if($date !== false) {
                    $date = date('Y-m-d', $date);
                    foreach($homeTeams[$index] as $number => $homeTeam) {
                        if(trim($homeTeam) != null && trim($awayTeams[$index][$number]) != null) {
                            $match = $session->addMatch($date, $number);
                            $match->setHomeTeam($teamLookup[$homeTeam]);
                            $match->setAwayTeam($teamLookup[$awayTeams[$index][$number]]);
                            $match->setLocation($teamLookup[$homeTeam]->getLocation());
                        }
                    }
                }
            }
        }

        // Save the session
        $session->save();

        // Redirect back to the page
        header(
            "Location: " . admin_url('admin.php') 
            . '?page=leaguemanager&subpage=schedule&league_id=' . $session->getLeagueId()
            . '&season=' . $session->getName()
        );
        exit;
    }


    /**
     * Handle scoresheet form submission.
     */
    public function updateScoresheet()
    {
        // The first step is to determine the actual response type
        $responseType = isset($_POST['responseType']) ? $_POST['responseType'] : 'html';

        // We need to get the match
        if(!empty($_POST['match'])) {
            // Get the match
            $match = $this->getMatch($_POST['match']);
            if($match) {
                // Get player, handicap, paid, and score lists
                $players = (isset($_POST['player']) && is_array($_POST['player'])) ? $_POST['player'] : array();
                $otherPlayers = (isset($_POST['otherPlayer']) && is_array($_POST['otherPlayer'])) ? $_POST['otherPlayer'] : array();
                $handicaps = (isset($_POST['handicap']) && is_array($_POST['handicap'])) ? $_POST['handicap'] : array();
                $paid = (isset($_POST['paid']) && is_array($_POST['paid'])) ? $_POST['paid'] : array();
                $scores = (isset($_POST['score']) && is_array($_POST['score'])) ? $_POST['score'] : array();

                // Process the player list
                foreach($players as $position => $id) {
                    // Get player, handicap and amount paid
                    if($id == 'NONE') {
                        $player = null;
                    } elseif($id == 'OTHER') {
                        if(isset($otherPlayers[$position])) {
                            $player = my5280::$instance->getPlayer($otherPlayers[$position]);
                        } else {
                            $player = null;
                        }
                    } else {
                        $player = my5280::$instance->getPlayer($id);
                    }
                    $handicap = isset($handicaps[$position]) ? $handicaps[$position] : null;
                    $myPaid = isset($paid[$position]) ? $paid[$position] : null;

                    // Add the player
                    $match->addPlayer($position, $player, $handicap, $myPaid);
                }

                // Handle doubles handicaps
                if(isset($_POST['doublesHandicap'])) {
                    $match->setHomeDoublesHandicap($_POST['doublesHandicap']['HOME']);
                    $match->setAwayDoublesHandicap($_POST['doublesHandicap']['AWAY']);
                }

                // Process the scores
                foreach($scores as $game => $score) {
                    $match->addScore($game, $score);
                }

                // Save the match
                if($match->save()) {
                    header(
                        "Location: " . admin_url('admin.php') 
                        . '?page=leaguemanager&subpage=match&league_id=' . $match->getLeagueId()
                        . '&edit=' . $match->getId() . '&season=' . $match->getSeasonName()
                    );
                    exit;
                } else {
                    $this->showScoresheet(array(
                        'league_id' => $match->getLeagueId(),
                        'season' => $match->getSeasonName(),
                        'mode' => 'edit',
                        'title' => 'Enter Scoresheet',
                        'match_id' => $match->getId(),
                    ));
                }
            }
        }

        // Assume someone went directly to this URL and provide a generic response
        return 0;
    }

    static $instance;
}

// Run the Plugin
my5280::$instance = new my5280();

// Constants
define('MY5280_PLUGIN_DIR', plugin_dir_path(__FILE__)) . '/';
define('MY5280_PLUGIN_URL', plugin_dir_url(__FILE__)) . '/';
