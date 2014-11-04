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
    }


    /**
     * appendToLeagueMenu:  Adds links to the league menu for a pool league.
     *
     * @param none
     */
    public function appendToLeagueMenu($menu)
    {
        global $leaguemanager;

        // Remove the teams and matches menu items
#        $menu['team']['show'] = false;
#        $menu['match']['show'] = false;
#        unset($menu['team'], $menu['match']);

        /*
        // Add a link for importing an XLSX file for the active session
        if($leaguemanager->getSeason($leaguemanager->getCurrentLeague())) {
            $menu['upload'] = array(
                'title' => 'Upload',
                'file' => dirname(__FILE__) . '/upload.php',
                'show' => true,
            );
        }
         */

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
     * Display the import players page.
     *
     * @param none
     */
    public function displayPlayerImport()
    {
        global $leaguemanager;

        include(MY5280_PLUGIN_DIR . 'admin/import_players.php');
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
     * import players from a Players.xlsx file.
     *
     * @param array $file Excel file.
     * @return void
     */
    public function importPlayers($file)
    {
        global $connections;
        $cnRetrieve = $connections->retrieve;

        require_once(MY5280_PLUGIN_DIR . '/lib/PHPExcel/Classes/PHPExcel.php');

        // Load the Players sheet from the file
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly('Players');
        $excel = $reader->load($file['tmp_name']);
        $sheet = $excel->getSheetByName('Players');

        // Get the my5280 instance
        $my5280 = my5280::$instance;

        // Extract the players
        $lastRow = $sheet->getHighestRow();
        for($row = 2; $row <= $lastRow; ++$row) {
            // Get data
            $cells = $sheet->rangeToArray('A' . $row . ':N' . $row, null, true, true, false);
            $cells = $cells[0];
            if(!$cells[0]) continue;

            // Get the player and assign information
            $player = $my5280->getPlayer($cells[0]);

            // Set the address (if available)
            if($cells[1] != null) {
                $player->setAddress('home', $cells[1], $cells[2], $cells[3], $cells[4], 'USA');
            }

            // Add date of birth
            if($cells[5] != null) {
                if(is_numeric($cells[5])) {
                    $date = PHPExcel_Shared_Date::ExcelToPHP($cells[5]);
                } else {
                    $date = strtotime($cells[5]);
                }
                $player->setBirthDate(date('Y-m-d', $date));
            }

            // Add email
            if($cells[6] != null) {
                $player->setEmailAddress($cells[6]);
            }

            // Add phone
            if($cells[7] != null) {
                $player->setPhoneNumber('cellphone', $cells[7]);
            }

            // Assign the starting handicap and lifetime games
            $player->setStartingHandicap($cells[8], $cells[9]);

            // Assign legal first name
            if($cells[13]) {
                $player->setLegalFirstName($cells[13]);
            }

            // Save the player
            $player->save();
        }
    }


    /**
     * adds menu to the admin interface
     *
     * @param none
     */
    public function menu()
    {
        $page = add_submenu_page(
            'leaguemanager',
            __('Import Players','my5280'),
            __('Import Players','my5280'),
            'manage_options',
            'my5280',
            array(&$this, 'displayPlayerImport')
        );
        $page = add_submenu_page(
            'leaguemanager',
            __('Update Handicaps','my5280'),
            __('Update Handicaps','my5280'),
            'manage_options',
            'my5280_update_handicaps',
            array($this, 'updateHandicaps')
        );
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
                            $players[$player] = array(
                                'object' => $p,
                                'name' => $p->getName(),
                                'currentGames' => $p->getTotalGames(),
                                'currentPoints' => $p->getTotalPoints(),
                                'currentHandicap' => $p->getHandicap(),
                                'actualGames' => 0,
                                'actualPoints' => 0,
                            );
                        }

                        // Update games and points
                        $players[$player]['actualGames'] += $points['games'];
                        $players[$player]['actualPoints'] += $points['points'];
                    }
                }
            }
        }

        // Make the adjustment for the players
        $changed = array();
        foreach($players as $p) {
            $gameChange = $p['actualGames'] - $p['currentGames'];
            $pointChange = $p['actualPoints'] - $p['currentPoints'];
            if($gameChange != 0 || $pointChange != 0) {
                $p['object']->adjustHandicap($gameChange, $pointChange);
                $p['object']->save();
                $p['actualHandicap'] = $p['object']->getHandicap();
                $changed[] = $p;
            }
        }

        // Sort the players
        usort($changed, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Include the template
        include(MY5280_PLUGIN_DIR . 'admin/update_handicaps.php');
    }


    /**
     * upload:  Upload a new Excel file for a session.
     *
     * @param int $league_id
     * @param array $file Excel file
     * @param string $name
     * @return string
     */
    public function upload($league_id, $file, $name)
    {
        global $lmLoader, $leaguemanager;
        $lmAdmin = $lmLoader->adminPanel;

        $league = $leaguemanager->getCurrentLeague();
        if($file['size'] > 0) {
            // Get the session
            $session = my5280::$instance->getSession($league, $name);

            // Import the file
            include_once(dirname(__FILE__) . '/../lib/importer.php');
            $errors = array();
            if(!my5280_Importer::import($session, $file, $errors)) {
                $lmAdmin->setMessage(implode("\n", $errors), true);
            } else {
                return true;
            }
        } else {
            $lmAdmin->setMessage(__('The uploaded file seems to be empty', 'leaguemanager'), true);
        }

        return false;
    }
}
