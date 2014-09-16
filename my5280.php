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
            wp_register_script('my5280', WP_PLUGIN_URL . '/my5280/jquery.storageapi.min.js', array('jquery'), '0.1');
            wp_register_script('storageapi', WP_PLUGIN_URL . '/my5280/my5280.js', array('jquery'), '1.7.2');
            wp_print_scripts(array('storageapi', 'my5280'));
        });
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
        $matches = $session->listMatches();

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
        ), $atts ));

        // Get the league, session, and teams
        $league = $leaguemanager->getLeague($league_id);
        $session = new my5280_Session($league_id, $season);

        // Check for a specific match
        $curMatch = null;
        if(isset($_GET['match'])) {
            $matches = $session->listMatches();
            foreach($matches as $date => $dateMatches) {
                foreach($dateMatches as $match) {
                    if($match->id == $_GET['match']) {
                        $curMatch = $match;
                        break 2;
                    }
                }
            }
        }

        // Determine the path to the template
        $template = null;
        if(isset($league->league_format)) {
            $template = MY5280_PLUGIN_DIR . '/templates/scoresheets/' . $league->league_format . '.php';
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

        // Get teams and initialize team totals
        $teams = $session->listTeams();
        foreach($teams as $team) {
            $team->stats = array(
                'points' => 0,
                'wins' => 0,
                'matches' => array(),
            );
        }

        // Get matches
        $players = array();
        $matches = $session->listMatches();
        foreach($matches as $date => $dateMatches) {
            foreach($dateMatches as $match) {
                if($match->home_points == 0 && $match->away_points == 0) continue;

                $team = $teams[$match->home_team];
                $team->stats['matches'][$date] = array(
                    'score' => $match->home_points,
                    'opponent' => $match->away_team,
                );
                $team->stats['points'] += $match->home_points;
                if($match->winner_id == $match->home_team_id) {
                    $team->stats['wins']++;
                } elseif($match->home_points > 0 && $match->home_points == $match->away_points) {
                    $team->stats['wins'] += 0.5;
                }

                $team = $teams[$match->away_team];
                $team->stats['matches'][$date] = array(
                    'score' => $match->away_points,
                    'opponent' => $match->home_team,
                );
                $team->stats['points'] += $match->away_points;
                if($match->winner_id == $match->away_team_id) {
                    $team->stats['wins']++;
                } elseif($match->away_points > 0 && $match->home_points == $match->away_points) {
                    $team->stats['wins'] += 0.5;
                }

                // Handle player information
                foreach(array('home', 'away') as $key) {
                    if(isset($match->custom[$key . '_players'])) {
                        foreach($match->custom[$key . '_players'] as $name => $player) {
                            # Add the player
                            if(!isset($players[$name])) {
                                $players[$name] = array(
                                    'name' => ucwords($name),
                                    'wins' => 0,
                                    'games' => 0,
                                );
                            }

                            # Process scores
                            foreach($player['scores'] as $score) {
                                $players[$name]['games']++;
                                if($score > 7) {
                                    $players[$name]['wins']++;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Sort the teams on total points
        uasort($teams, function($a, $b) {
            if($a->stats['points'] < $b->stats['points']) {
                return 1;
            } elseif($a->stats['points'] > $b->stats['points']) {
                return -1;
            } else {
                return 0;
            }
        });

        // Assign team placement
        $iPlace = 0; $iNextPlace = 0; $prevScore = 0;
        foreach($teams as $team) {
            $iNextPlace++;
            if($team->stats['points'] != $prevScore) {
                $iPlace = $iNextPlace;
            }
            $team->stats['place'] = $iPlace;
            $prevScore = $team->stats['points'];
        }

        // Calculate player information
        foreach($players as $name => $player) {
            $players[$name]['win%'] = ($player['games'] > 0 ? round($player['wins'] / $player['games'] * 100, 2) : null);
        }
        uasort($players, function($a, $b) {
            if($a['win%'] < $b['win%']) {
                return 1;
            } elseif($a['win%'] > $b['win%']) {
                return -1;
            } else {
                return 0;
            }
        });

        // Check for a requested team
        if(isset($_GET['team']) && isset($teams[$_GET['team']])) {
            /* Display team standings */

            // Get the team
            $team = $teams[$_GET['team']];
            include(MY5280_PLUGIN_DIR . '/templates/teamstandings.php');
        } else {
            // Display overall standings
            include(MY5280_PLUGIN_DIR . '/templates/standings.php');
        }
    }

    static $instance;
}

// Run the Plugin
my5280::$instance = new my5280();

// Constants
define('MY5280_PLUGIN_DIR', plugin_dir_path(__FILE__)) . '/';
