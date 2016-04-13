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

        add_filter('leaguemanager_matches_file_5280pool', function($league, $season) {
            global $wpdb, $leaguemanager;

            // Check for team update information
            if(isset($_POST['updateLeague']) && $_POST['updateLeague'] === 'team' && isset($_POST['my5280_players']) && is_array($_POST['my5280_players'])) {
                if($_POST['team_id'] == '') {
                    $teamID = $wpdb->insert_id;
                } else {
                    $teamID = $_POST['team_id'];
                }

                // Get the team
                include_once(__DIR__ . '/../lib/team.php');
                $team = new my5280_Team($leaguemanager->getTeam($teamID));
                $team->clearPlayers();
                foreach($_POST['my5280_players'] as $playerID) {
                    if($playerID !== 'NONE') {
                        $player = my5280::$instance->getPlayer($playerID);
                        if($player) {
                            $team->addPlayer($player);
                        }
                    }
                }
                $team->save();
            }

            // Display the matches
            return __DIR__ . '/matches.php';
        }, 10, 2);
    }


    /**
     * appendToLeagueMenu:  Adds links to the league menu for a pool league.
     *
     * @param none
     */
    public function appendToLeagueMenu($menu)
    {
        global $leaguemanager;

        $menu['match']['title'] = 'Schedule';
        $menu['match']['file'] = __DIR__ . '/schedule.php';

        return $menu;
    }


    /**
     * Display the league report.
     *
     * @param none
     */
    public function displayLeagueReport()
    {
        global $leaguemanager;

        include(MY5280_PLUGIN_DIR . 'admin/league_report.php');
    }


    /**
     * Override match editing.
     */
    public function editMatch($league, $teams, $season, $max_matches, $matches, $submit_title, $mode)
    {
        $is_finals = null;
        $cup = null;
        $edit = true;
        $bulk = isset($_GET['match_day']);
        $mode = 'edit';
        $class = 'alternate';
        $non_group = 0;

        // Include the template for editing the match information
        include(MY5280_PLUGIN_DIR . 'admin/match.php');

        if(!$bulk) {
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
}
