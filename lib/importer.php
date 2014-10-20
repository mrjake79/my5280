<?php

/**
 * Script to import a session from an XLSX file.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

class my5280_Importer
{
    /**
     * Import an Excel file.
     */
    public static function import($Session, $File, &$Errors = array())
    {
        global $leaguemanager, $lmLoader;
        $lmAdmin = $lmLoader->adminPanel;
        if(!$lmAdmin) return false;

        // Get the my5280 instance
        $my5280 = my5280::$instance;

        // Get the existing teams from the database
        $existing = array();
        foreach($Session->listTeams() as $team) {
            $existing[$team->getTeamNumber()] = $team;
        }

        // Load the Teams sheet from the file
        $teams = array();
        $reader = PHPExcel_IOFactory::createReader('Excel2007');
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(array('Team Rosters', 'Schedule', 'Doubles', 'Player Scores', 'Doubles Scores'));
        $excel = $reader->load($File['tmp_name']);
        $sheet = $excel->getSheetByName('Team Rosters');
        $lastRow = $sheet->getHighestRow();

        // Load the team list
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

        // Process the teams
        $teamLookup = array();
        foreach($teams as $team) {
            // De-dup based on team number (name would be bad if someone was renamed)
            if(isset($existing[$team['number']])) {
                $myTeam = $existing[$team['number']];
            } else {
                $myTeam = $Session->addTeam($team['name'], $team['location']);
                $myTeam->setTeamNumber($team['number']);
            }

            // Set the location and address
            $myTeam->setLocation($team['location']);
            $myTeam->setAddress($team['address']);

            // Add players
            $myTeam->clearPlayers();
            foreach($team['players'] as $playerName => $handicap) {
                $myTeam->addPlayer($my5280->getPlayer($playerName));
            }

            // Save the team and add it to the array
            $myTeam->save();
            $teamLookup[$myTeam->getTeamNumber()] = $myTeam;
        }

        // Load doubles
        $sheet = $excel->getSheetByName('Doubles');
        if($sheet) {
            include_once(MY5280_PLUGIN_DIR . 'lib/doubles.php');

            // Load the doubles
            $lastRow = $sheet->getHighestRow();
            $rows = $sheet->rangeToArray('A2:C' . $lastRow, null, true, true, false);
            foreach($rows as $row) {
                if($row[0] == null) continue;

                // Get the "family" name
                $names = explode('+', $row[0]);
                $family_name = ucwords($names[0]) . ' & ' . ucwords($names[1]);

                // Create or retrieve the doubles team
                $doubles = new my5280_Doubles($names[0], $names[1]);

                // Assign starting values
                $doubles->setStartingHandicap($row[1]);
                $doubles->setStartingGames($row[2]);

                // Save the doubles team
                $doubles->save();
            }
        }

        // Load matches
        $sheet = $excel->getSheetByName('Schedule');
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
            $iDateMatch = 0;
            for($iMatch = 3; $iMatch < 14; $iMatch += 2) {
                // Determine the team numbers for the home and away teams
                $home = $row[$iMatch];
                $away = $row[$iMatch+1];

                // Make sure both teams exist
                if(isset($teamLookup[$home]) && isset($teamLookup[$away])) {
                    // Get the match
                    $match = $Session->addMatch($date, $iDateMatch++);

                    // Setup the match
                    $match->setHomeTeam($teamLookup[$home]);
                    $match->setAwayTeam($teamLookup[$away]);
                    $match->setLocation($teamLookup[$home]->getLocation());
                }
            }
        }

        // Import player scores
        $sheet = $excel->getSheetByName('Player Scores');
        $lastRow = $sheet->getHighestRow();
        for($row = 2; $row <= $lastRow; ++$row) {
            // Get the necessary cells
            $cells = $sheet->rangeToArray('A' . $row . ':S' . $row, null, true, true, false);
            $cells = $cells[0];
            if(!$cells[0]) continue;

            // Determine the date
            $date = PHPExcel_Shared_Date::ExcelToPHP($cells[0]);
            $date = date('Y-m-d', $date);

            // Get the current match
            if($curMatch == null || $curMatch->getDate() != $date || $curMatch->getNumber() != ($cells[1] - 1)) {
                $curMatch = $Session->addMatch($date, $cells[1] - 1);
            }

            // Add player's only for 1st round information
            if($cells[3] == 1) {
                // Make sure there is a player
                if($cells[6] != '') {
                    // Add the player
                    $player = my5280::$instance->getPlayer(ucwords($cells[6]));
                    $curMatch->addPlayer($cells[2] - 1, $player, $cells[7], $cells[18]);
                } else {
                    // Add a forfeit player
                    $curMatch->addPlayer($cells[2] - 1, null, $cells[7], $cells[18]);
                }
            }

            // Add the score only for the home team
            if($cells[12] == 'Home') {
                $curMatch->addScore($cells[4] - 1, $cells[8]);
            }
        }

        // Save the session (and matches)
        $Session->save();

        return true;
    }
}
