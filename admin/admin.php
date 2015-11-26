<?php
/**
 * Admin class holding all administrative functions for the wordPress plugin my5280
 *
 * @author  Jake Bahnsen
 * @package my5280
 * @copyright   Copyright 2014
 */

class my5280AdminPanel
{
    /**
     * load admin area
     *
     * @param none
     * @return void
     */
    public function __construct()
    {
        require_once(ABSPATH . 'wp-admin/includes/template.php');

        add_action('admin_menu', array(&$this, 'menu'));
        add_action('leaguemanager_edit_match_5280pool', array($this, 'editMatch'), 10, 7);
        add_action('team_edit_form_5280pool', array($this, 'editTeam'));
        add_action('admin_enqueue_scripts', function($hook) {
            if(isset($_GET['page']) && $_GET['page'] == 'leaguemanager' && isset($_GET['subpage']) && $_GET['subpage'] == 'match') {
                wp_register_script('storageapi', WP_PLUGIN_URL . '/my5280/jquery.storageapi.min.js', array('jquery'), '1.7.2');
                wp_register_script('my5280', WP_PLUGIN_URL . '/my5280/my5280.js', array('storageapi'), '0.1');
                wp_enqueue_script('my5280');
            }
        });

        add_filter('league_menu_5280pool', array($this, 'appendToLeagueMenu'));
        add_filter('leaguemanager_matches_file_5280pool', function($league) {
            return __DIR__ . '/matches.php';
        });
    }


    /**
     * appendToLeagueMenu:  Adds links to the league menu for a pool league.
     *
     * @param none
     */
    public function appendToLeagueMenu($menu)
    {
        global $leaguemanager;

        // Easier match setup
        if($leaguemanager->getSeason($leaguemanager->getCurrentLeague())) {
            $menu['schedule'] = array(
                'title' => 'Schedule',
                'file' => dirname(__FILE__) . '/schedule.php',
                'show' => true,
            );
        }

        return $menu;
    }


    /**
     * Override match editing.
     */
    public function editMatch($league, $teams, $season, $max_matches, $matches, $submit_title, $mode)
    {
        // Include the template for editing the match information
        include(MY5280_PLUGIN_DIR . 'admin/match.php');

        // Call the showScoresheet method of my5280
        my5280::$instance->showScoresheet(array(
            'league_id' => $league->id,
            'league_name' => '',
            'season' => $season,
            'title' => 'Enter Scores',
            'mode' => 'edit',
            'match_id' => $matches[0]->id,
        ));
    }


    /**
     * Add functionality to the team edit form.
     */
    public function editTeam($Team)
    {
        require_once(MY5280_PLUGIN_DIR . 'lib/team.php');
        $Team = new my5280_Team($Team);

        include(MY5280_PLUGIN_DIR . 'admin/team.php');
    }


    /**
     * adds menu to the admin interface
     *
     * @param none
     */
    public function menu()
    {
        /*
        $page = add_submenu_page(
            'leaguemanager',
            __('Update Handicaps','my5280'),
            __('Update Handicaps','my5280'),
            'manage_options',
            'my5280_update_handicaps',
            array($this, 'updateHandicaps')
        );
         */
    }


    /**
     * update:  Update the handicaps using all available match data.
     */
    public function updateHandicaps()
    {
        global $leaguemanager;

        // Calculate total points, games, and wins for all players using data
        // from every match in the system
        $players = array();
        foreach($leaguemanager->getLeagues() as $league) {
            foreach($league->seasons as $season) {
                $session = new my5280_Session($league, $season['name']);
                foreach($session->listMatches() as $match) {
                    // Process player points
                    foreach($match->listPlayerPoints() as $player => $points) {
                        // Add the player if not already in the list
                        if(!isset($players[$player])) {
                            $p = my5280::$instance->getPlayer($player);
                            if($p) {
                                $players[$player] = array(
                                    'object' => $p,
                                    'name' => $p->getName(),
                                    'currentGames' => $p->getTotalGames(),
                                    'currentPoints' => $p->getTotalPoints(),
                                    'currentHandicap' => $p->getHandicap(),
                                    'actualGames' => 0,
                                    'actualPoints' => 0,
                                );
                            } else {
                                $players[$player] = array(
                                    'object' => null,
                                    'name' => '!!! Player # ' . $player . ' (DELETED)',
                                    'currentGames' => null,
                                    'currentPoints' => null,
                                    'currentHandicap' => null,
                                    'actualGames' => 0,
                                    'actualPoints' => 0,
                                );
                            }
                        }

                        // Update games and points
                        $players[$player]['actualGames'] += $points['games'];
                        $players[$player]['actualPoints'] += $points['points'];
                    }
                }
            }
        }

        // Check for form submission
        $saveChanges = (isset($_POST['action']) && !empty($_POST['action']));

        // Process the player information
        $changed = array();
        foreach($players as $p) {
            $gameChange = $p['actualGames'] - $p['currentGames'];
            $pointChange = $p['actualPoints'] - $p['currentPoints'];
            if(!$p['object']) {
                $changed[] = $p;
            } elseif($gameChange != 0 || $pointChange != 0) {
                $p['object']->adjustHandicap($gameChange, $pointChange);
                $p['actualHandicap'] = $p['object']->getHandicap();
                $changed[] = $p;

                if($saveChanges) {
                    $p['object']->save();
                }
            }
        }

        // Sort the players
        usort($changed, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Include the template
        include(MY5280_PLUGIN_DIR . 'admin/update_handicaps.php');
    }
}
