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
        $menu['team']['show'] = false;
        $menu['match']['show'] = false;
#        unset($menu['team'], $menu['match']);

        // Add a link for importing an XLSX file for the active session
        if($leaguemanager->getSeason($leaguemanager->getCurrentLeague())) {
            $menu['upload'] = array(
                'title' => 'Upload',
                'file' => dirname(__FILE__) . '/upload.php',
                'show' => true,
            );
        }

        return $menu;
    }


    /**
     * display - Display the import players page.
     *
     * @param none
     */
    public function display()
    {
        global $leaguemanager;

        include(MY5280_PLUGIN_DIR . 'admin/import_players.php');
    }


    /**
     * import:  Import a seasion from a 5280 Pool League Excel session.
     *
     * @param int $league_id
     * @param array $file Excel file
     * @param string $name
     * @return string
     */
    public function import($league_id, $file, $name)
    {
        global $lmLoader, $leaguemanager;
        $lmAdmin = $lmLoader->adminPanel;

        $league = $leaguemanager->getCurrentLeague();
        if(isset($leagues->seasons[$name])) {
            $lmAdmin->setMessage(__('A season called "' . $name . '" already exists.'), true);
        } elseif($file['size'] > 0) {
            // Add the season
            $lmAdmin->saveSeason($name, 1, false);

            // Import the file
            $session = my5280::$instance->getSession($league, $name);
            $errors = array();
            if(!$session->import($file, $errors)) {
                $lmAdmin->setMessage(implode("\n", $errors), true);
                $lmAdmin->delSeasons(array($name), $league_id);
            } else {
                return true;
            }
        } else {
            $lmAdmin->setMessage(__('The uploaded file seems to be empty', 'leaguemanager'), true);
        }

        return false;
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

        // Extract the players
        $lastRow = $sheet->getHighestRow();
        for($row = 2; $row <= $lastRow; ++$row) {
            // Get data
            $cells = $sheet->rangeToArray('A' . $row . ':N' . $row, null, true, true, false);
            $cells = $cells[0];
            if(!$cells[0]) continue;

            // Extract information
            #$fullName = $cells[0];
            #$address = $cells[1];
            #$city = $cells[2];
            #$state = $cells[3];
            #$zip = $cells[4];
            #$dob = $cells[5];
            #$email = $cells[6];
            #$phone = $cells[7];
            #$startHCP = $cells[8];
            #$startLifetime = $cells[9];
            #$legalFirstName = $cells[13];

            // Parse the full name
            $parts = explode(',', $cells[0]);
            if(count($parts) == 2) {
                $firstName = trim($parts[1]);
                $lastName = trim($parts[0]);
            } else {
                $firstName = trim($parts[0]);
                $lastName = '';
            }

            // Search for the contact
            $ret = $cnRetrieve->entries(array(
                'first_name' => $firstName,
                'last_name' => $lastName,
            ));
            if(count($ret) == 0) {
                $entry = new cnEntry();

                // Set basic information
                $entry->setFirstName($firstName);
                $entry->setLastName($lastName);
                $entry->setVisibility('private');
                $entry->setStatus('approved');

                // Add address
                if($cells[1] != null) {
                    $entry->setAddresses(array(
                        array(
                            'type' => 'home',
                            'visibility' => 'unlisted',
                            'line_1' => $cells[1],
                            'city' => $cells[2],
                            'state' => $cells[3],
                            'zipcode' => $cells[4],
                            'country' => 'USA',
                        ),
                    ));
                }

                // Add date of birth
                if($cells[5] != null) {
                    if(is_numeric($cells[5])) {
                        $date = PHPExcel_Shared_Date::ExcelToPHP($cells[5]);
                    } else {
                        $date = strtotime($cells[5]);
                    }
                    $date = date('Y-m-d', $date);
                        
                    $entry->setDates(array(
                        array(
                            'type' => 'birthday',
                            'date' => $date,
                            'visibility' => 'unlisted',
                        ),
                    ));
                }

                // Add email
                if($cells[6] != null) {
                    $entry->setEmailAddresses(array(
                        array(
                            'visibility' => 'unlisted',
                            'type' => 'personal',
                            'address' => $cells[6],
                        ),
                    ));
                }

                // Add phone
                if($cells[7] != null) {
                    $entry->setPhoneNumbers(array(
                        array(
                            'type' => 'cellphone',
                            'visibility' => 'unlisted',
                            'number' => $cells[7],
                        ),
                    ));
                }

                // Save the entry
                if($entry->save()) {
                    $id = $connections->lastInsertID;
                } else {
                    continue;
                }

                // Update meta data
                $meta = array(
                    array('key' => 'my5280_handicap_start', 'value' =>  $cells[8]),
                    array('key' => 'my5280_lifetime_start', 'value' => $cells[9]),
                );
                if($cells[13]) {
                    $meta[] = array(
                        'key' => 'my5280_legal_first',
                        'value' => $cells[13]
                    );
                }
                cnEntry_Action::meta('add', $id, $meta);
            }
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
            array(&$this, 'display')
        );
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
            $session = my5280::$instance->getSession($league);
            $errors = array();
            if(!$session->import($file, $errors)) {
                $lmAdmin->setMessage(__('Could not import the file.', 'my5280'), true);
            } else {
                $lmAdmin->setMessage(__('The file has been imported.', 'my5280'), false);
            }
        } else {
            $lmAdmin->setMessage(__('The uploaded file seems to be empty', 'leaguemanager'), true);
        }

        return false;
    }
}
