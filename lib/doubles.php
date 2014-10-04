<?php

/**
 * Class to represent a pairing of players as a doubles team.
 */

class my5280_Doubles
{
    /**
     * constructor
     *
     * @param string    Name of the first player.
     * @param string    Name of the second player.
     */
    public function __construct($Name1, $Name2)
    {
        // Get the family members
        $family = array();
        $names = array();
        foreach(array($Name1, $Name2) as $name) {
            // Get the player
            $player = my5280::$instance->getPlayer($name, true);
            $family[] = $player;

            // Add the name to the list
            $names[] = $player->getName();
        }
        $this->familyMembers = $family;

        // Build the family name
        sort($names);
        $familyName = implode(' & ', $names);
        $this->familyName = $familyName;
    }


    /**
     * Retrieve the handicap for the doubles team.
     */
    public function getHandicap()
    {
        $this->load();
        return $this->cnMeta['my5280_handicap_start'];
    }


    /**
     * Get the team's unique ID.
     */
    public function getId()
    {
        $this->load();
        return isset($this->cnData->id) ? $this->cnData->id : null;
    }


    /**
     * Retrieve the family name as the name of the doubles team.
     */
    public function getName()
    {
        return $this->familyName;
    }


    /**
     * Save the doubles team.
     */
    public function save()
    {
        // Load existing data
        $this->load();

        // Determine if we are creating or updating
        if(isset($this->cnData->id)) {
            // Use the existing entry
            $entry = new cnEntry($this->cnData);
        } else {
            // Initialize the new entry
            $entry = new cnEntry();
            $entry->setFamilyName($this->familyName);
            $entry->setEntryType('family');
            $entry->setVisibility('private');
            $entry->setStatus('approved');

            // Add the family members
            $family = array();
            foreach($this->familyMembers as $player) {
                $id = $player->getId();
                if(!$id) {
                    $player->save();
                    $id = $player->getId();
                }
                $family[] = array('entry_id' => $id, 'relation' => 'partner');
            }
            $entry->setFamilyMembers($family);

            // Save the entry
            if($entry->save() && $this->getId() === null) {
                global $connections;
                $this->cnData->id = $connections->lastInsertID;
                $isNew = true;
            } else {
                $isNew = false;
            }

            // Update meta data
            $meta = array();
            foreach($this->cnMeta as $name => $value) {
                $meta[] = array(
                    'key' => $name,
                    'value' => $value,
                );
            }
            if(count($meta)) {
                cnEntry_Action::meta( ($isNew ? 'add' : 'update'), $this->getId(), $meta);
            }
        }

        return true;
    }


    /**
     * Assign the starting games.
     */
    public function setStartingGames($Value)
    {
        $this->load();
        $this->cnMeta['my5280_lifetime_start'] = $Value;
    }


    /**
     * Assign the starting handicap.
     */
    public function setStartingHandicap($Value)
    {
        $this->load();
        $this->cnMeta['my5280_handicap_start'] = $Value;
    }


    /**
     * Family name for the connections contact.
     */
    protected $familyName;


    /**
     * Family members.
     */
    protected $familyMembers;


    /**
     * Connections data.
     */
    protected $cnData = null;


    /**
     * Connections meta data.
     */
    protected $cnMeta = null;


    /**
     * Load data from connections for the doubles team.
     */
    protected function load()
    {
        if($this->cnData === null) {
            global $connections;
            $cnRetrieve = $connections->retrieve;
            $ret = $cnRetrieve->entries(array(
                'family_name' => $this->familyName,
            ));
            if(count($ret) == 1) {
                $this->cnData = $ret[0];
                $this->cnMeta = array();
                foreach(cnMeta::get('entry', $this->cnData->id) as $meta) {
                    $this->cnMeta[$meta['meta_key']] = $meta['meta_value'];
                }
            } else {
                $this->cnData = new StdClass;
                $this->cnMeta = array();
            }
        }
    }
}