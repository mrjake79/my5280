<?php

/**
 * Class to represent a pairing of players as a doubles team.
 */

require_once(__DIR__ . '/player.php');
class my5280_Doubles extends my5280_Player
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
            if($name instanceof my5280_Player) {
                $player = $name;
            } else {
                $player = my5280::$instance->getPlayer($name, true);
            }

            // Add the name to the list
            $family[] = $player;
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
            if(isset($result[0])) {
                return $result[0]->handicap;
            }
        }
        foreach($this->familyMembers as $player) {
            $totalPoints += round($player->getHandicap($AsOfDate, $MaxGames), 0);
        }
        return $totalPoints / 2;
    }


    /**
     * Get the team's unique ID.
     */
    public function getId($AutoCreate = false)
    {
        $this->load();
        return isset($this->cnData->id) ? $this->cnData->id : null;
    }


    /**
     * Retrieve the family name as the name of the doubles team.
     */
    public function getName($LastNameFirst = false)
    {
        return $this->familyName;
    }


    /**
     * Save the doubles team.
     */
    public function save(&$Error = null)
    {
        // Load existing data
        $this->load();

        // Determine if we are creating or updating
        if(isset($this->cnData->id)) {
            // Use the existing entry
            $entry = new cnEntry($this->cnData);
            $entry->setEntryType($this->cnData->entry_type);
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
     * Assign the starting handicap.
     */
    public function setStartingHandicap($Handicap, $Games)
    {
        $this->load();
        $this->cnMeta['my5280_handicap_start'] = $Handicap;
        $this->cnMeta['my5280_lifetime_start'] = $Games;
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
                'allow_public_override' => true,
                'private_override' => true,
            ));
            if(count($ret) == 1) {
                $this->cnData = $ret[0];
                $this->cnMeta = array();
                $allMeta = cnMeta::get('entry', $this->cnData->id);
                if(is_array($allMeta)) {
                    foreach($allMeta as $key => $values) {
                        $this->cnMeta[$key] = $values[0];
                    }
                }
            } else {
                $this->cnData = new StdClass;
                $this->cnMeta = array();
            }
        }
    }
}
