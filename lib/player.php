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
        $this->entry = $Entry;
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
     * Get the player's handicap.
     *
     * @param none
     * @return float
     */
    public function getHandicap()
    {
        # Get the meta data
        $meta = $this->loadMeta();

        # Retrieve the starting handicap information
        $startValue = isset($meta['my5280_handicap_start']) ? $meta['my5280_handicap_start'] : 7;
        $startGames = isset($meta['my5280_lifetime_start']) ? $meta['my5280_lifetime_start'] : 0;

        # Retrieve points and games since the beginning
        $totalPoints = isset($meta['my5280_points']) ? $meta['my5280_points'] : 0;
        $totalGames = isset($meta['my5280_games']) ? $meta['my5280_games'] : 0;

        # Handle no total games
        if($totalGames == 0) {
            # Just use the starting value
            return $startValue;
        } elseif($totalGames < 50) {
            # We'll pull in enough games to get to 50 or the most that are available
            $lessGames = 50 - $totalGames;
            $availGames = min($lessGames, $startGames);
            $totalPoints += ($startValue * $availGames);
            $totalGames += $availGames;
        }

        # Return the average handicap
        return $totalPoints / $totalGames;
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
        if($this->entry->getId() == null) {
            if($this->entry->save()) {
                // Retrieve the entry from Connections (again)
                $cnRetrieve = $connections->retrieve;
                $entry = $cnRetrieve->entry($connections->lastInsertID);
                $this->entry = new cnEntry($entry);
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
                $myMeta[$key] = $values[0];
            }
        }
        $this->meta = $myMeta;
        return $myMeta;
    }
}
