<?php

/**
 * Class to represent a player on a team.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

class my5280_Player
{
    /**
     * Constructor.
     *
     * @param object    cnEntry object for the player.
     */
    public function __construct($Entry)
    {
        if($Entry === false) {
            print '<pre>';
            print_r(debug_backtrace());exit;
        }
        if($Entry instanceof cnEntry) {
            $this->entry = $Entry;
        } else {
            $this->entry = new cnEntry($Entry);
        }
    }


    /**
     * Adjust the player's handicap by the number of games and points.
     *
     * @param int Game count
     * @param int Total points
     * @return none
     */
    public function adjustHandicap($Games, $Points)
    {
        // Get the meta data
        $meta = $this->loadMeta();

        // Update games
        if(!isset($meta['my5280_games'])) {
            $meta['my5280_games'] = 0;
        }
        $meta['my5280_games'] += $Games;

        // Update points
        if(!isset($meta['my5280_points'])) {
            $meta['my5280_points'] = 0;
        }
        $meta['my5280_points'] += $Points;

        // Update the meta data
        $this->meta = $meta;
    }


    /**
     * Delete the player.
     *
     * @param none
     * @return void
     */
    public function delete()
    {
        $entry = new cnEntry;
        $entry->delete($this->getId());
    }


    /**
     * Get the player's handicap.
     *
     * @param date (optional) The date on which the handicap should be calculated (includes all games for matches before the provided date).
     *                        If null, all recorded games are included (past and future).
     * @return float
     */
    public function getHandicap($AsOfDate = null, $GameLimit = null)
    {
        global $wpdb;
        $player_id = $this->getId();
        if($player_id != null) {
            $sql = "SELECT SUM(a.score) / COUNT(*) AS handicap 
                FROM (SELECT s.score FROM {$wpdb->prefix}my5280_match_scores s
                JOIN {$wpdb->prefix}my5280_match_players p ON p.id = s.match_player_id
                JOIN {$wpdb->prefix}leaguemanager_matches m ON m.id = p.match_id
                WHERE p.player_id = {$this->getId()}";

            if($AsOfDate !== null) {
                $sql .= " AND m.date < '" . $AsOfDate . " 00:00:00'";
            }
            $sql .= " ORDER BY m.date DESC, s.game DESC";
            if($GameLimit != null) {
                $sql .= " LIMIT {$GameLimit}";
            }
            $sql .= ") a";

            $result = $wpdb->get_results($sql);
            return $result[0]->handicap;
        } else {
            return null;
        }
    }


    /**
     * Get the player ID.
     *
     * @param boolean   If set to TRUE, the player will be saved to force there to be an ID.
     * @return integer
     */
    public function getId($AutoCreate = false)
    {
        $id = $this->entry->getId();
        if($id == null && $AutoCreate) {
            $this->save();
            $id = $this->entry->getId();
        }
        return $id;
    }


    /**
     *  Get the player's name.
     *
     *  @param none
     *  @return string
     */
    public function getName($LastNameFirst = false)
    {
        return html_entity_decode($this->entry->getName());
    }


    /**
     * Retrieve the starting games for the player.
     */
    public function getStartingGames()
    {
        $meta = $this->loadMeta();
        return $meta['my5280_lifetime_start'];
    }


    /**
     * Retrieve the starting handicap for the player.
     */
    public function getStartingHandicap()
    {
        $meta = $this->loadMeta();
        return $meta['my5280_handicap_start'];
    }


    /**
     * Retrieve the total games recorded for the player.
     */
    public function getTotalGames()
    {
        $meta = $this->loadMeta();
        return $meta['my5280_games'];
    }


    /**
     * Retrieve the total points records for the player.
     */
    public function getTotalPoints()
    {
        $meta = $this->loadMeta();
        return $meta['my5280_points'];
    }


    /**
     * Retrieve the type of player:
     *   "individual":  A regular player (single person).
     *   "family":      A doubles pairing.
     */
    public function getType()
    {
        return $this->entry->getEntryType();
    }


    /**
     * Save the player.
     */

    public function save(&$Error = null)
    {
        global $connections;

        // Determine if this is a new or existing player
        $entryId = $this->entry->getId();
        if($entryId == null) {
            if($this->entry->save()) {
                // Retrieve the entry from Connections (again)
                $entryId = $connections->lastInsertID;
            } else {
                // There was a problem saving
                $Error = $connections->lastQueryError;
                print 'An error occurred. ' . $Error;exit;
                return false;
            }
        } else {
            if($this->entry->update() === false) {
                // There was a problem saving
                global $wpdb;
                $Error = $wpdb->last_error;
                print 'An error occurred with ' . $this->entry->getName() . ': ' . $Error;exit;
                return false;
            }
        }

        $cnRetrieve = $connections->retrieve;
        $entry = $cnRetrieve->entry($entryId);
        $this->entry = new cnEntry($entry);
        $this->entry->setEntryType($entry->entry_type);

        // Handle meta data
        if($this->meta !== null) {
            // Initialize the add and update arrays
            $add = array();
            $update = array();

            // Load existing meta for IDs
            $lookup = array();
            $existing = $this->entry->getMeta();
            if(is_array($existing)) {
                foreach($existing as $key => $value) {
                    $lookup[$key] = $value;
                }
            }
            
            // Build the update and add arrays
            foreach($this->meta as $key => $value) {
                if(isset($lookup[$key])) {
                    $update[] = array(
                        'key' => $key,
                        'value' => $value,
                    );
                } else {
                    $add[] = array(
                        'key' => $key,
                        'value' => $value,
                    );
                }
            }

            // Save the meta data
            if(count($add)) {
                cnEntry_Action::meta('add', $this->entry->getId(), $add);
            }
            if(count($update)) {
                cnEntry_Action::meta('update', $this->entry->getId(), $update);
            }
        }

        return true;
    }



    /**
     * Assign the player's address.
     *
     * @param string
     * @return void
     */
    public function setAddress($Type, $Street, $City, $State, $ZipCode, $Country)
    {
        $this->entry->setAddresses(array(
            array(
                'type' => $Type,
                'visibility' => 'unlisted',
                'line_1' => $Street,
                'city' => $City,
                'state' => $State,
                'zipcode' => $ZipCode,
                'country' => $Country,
            )
        ));
    }


    /**
     * Assign the birthday.
     *
     * @param string Date of birth.
     * @return void
     */
    public function setBirthDate($Date)
    {
        $this->entry->setDates(array(
            array(
                'type' => 'birthday',
                'date' => $Date,
                'visibility' => 'unlisted',
            )
        ));
    }


    /**
     * Assign the email address.
     *
     * @param string Email address.
     * @return void
     */
    public function setEmailAddress($Address)
    {
        $this->entry->setEmailAddresses(array(
            array(
                'visibility' => 'unlisted',
                'type' => 'personal',
                'address' => $Address,
            )
        ));
    }


    /**
     * Assign legal first name.
     *
     * @param string Legal first name.
     */
    public function setLegalFirstName($Name)
    {
        $meta = $this->loadMeta();
        $meta['my5280_legal_first'] = $Name;
        $this->meta = $meta;
    }


    /**
     * Assign the phone number.
     *
     * @param string Type
     * @param string Phone number
     * @return void
     */
    public function setPhoneNumber($Type, $Number)
    {
        $this->entry->setPhoneNumbers(array(
            array(
                'type' => $Type,
                'visibility' => 'unlisted',
                'number' => $Number,
            )
        ));
    }


    /**
     * Assign the starting handicap.
     *
     * @param float Starting handicap.
     * @param int   Number of games on which starting handicap is based.
     * @return void
     */
    public function setStartingHandicap($Handicap, $Games)
    {
        $meta = $this->loadMeta();
        $meta['my5280_handicap_start'] = $Handicap;
        $meta['my5280_lifetime_start'] = $Games;
        $this->meta = $meta;
    }


    /**
     * Player's Connections entry.
     */
    protected $entry;
    protected $meta = null;


    /**
     * Load meta data for the player.
     */
    protected function loadMeta()
    {
        if($this->meta !== null) return $this->meta;
        $meta = $this->entry->getMeta();
        if(!is_array($meta)) {
            $myMeta = array();
        } else {
            $myMeta = array();
            foreach($meta as $key => $values) {
                if(count($values) > 1 && count(array_unique($values)) == 1) {
                    cnMeta::delete('entry', $this->entry->getId(), $key);
                    cnMeta::add('entry', $this->entry->getId(), $key, $values[0]);
                }
                $myMeta[$key] = $values[0];
            }

        }
        $this->meta = $myMeta;
        return $myMeta;
    }
}
