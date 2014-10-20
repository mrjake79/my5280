<?php

/**
 * Class to represent a team in a session.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

class my5280_Team
{
    /**
     * constructor
     *
     * @param object team information
     */
    public function __construct($info = null)
    {
        if($info === null) $info = new StdClass;
        $this->info = $info;
        $this->info->title = html_entity_decode($this->info->title);
    }

    /**
     * getter
     */
    public function __get($Name)
    {
        if(isset($this->info->$Name)) {
            return $this->info->$Name;
        }
        return null;
    }

    /**
     * setter
     */
    public function __set($Name, $Value)
    {
        $this->info->$Name = $Value;
    }


    /**
     * Add a player.
     *
     * @param object The player to add.
     * @return void
     */
    public function addPlayer(my5280_Player $Player)
    {
        $players = $this->listPlayers();
        $id = $Player->getId(true);
        if(!isset($this->players[$id])) {
            $this->players[$id] = $Player;
        }
    }


    /**
     * Remove all players from the team.
     *
     * @param none
     * @return void
     */
    public function clearPlayers()
    {
        $this->players = array();
    }


    /**
     * Retrieve the team ID.
     *
     * @param none
     * @param integer
     */
    public function getId()
    {
        return isset($this->info->id) ? $this->info->id : null;
    }


    /**
     * Retrieve the location for the team.
     *
     * @param none
     * @return string
     */
    public function getLocation()
    {
        return isset($this->info->stadium) ? $this->info->stadium : null;
    }


    /**
     * Retrieve the team name.
     *
     * @param none
     * @return string
     */
    public function getName()
    {
        return $this->info->title;
    }


    /**
     * Retrieve the team number.
     *
     * @param none
     * @param integer
     */
    public function getTeamNumber()
    {
        if(isset($this->info->number)) {
            return $this->info->number;
        }
        return null;
    }


    /**
     * Retrieve an array of doubles information.
     */
    public function listDoubles()
    {
        if($this->doubles === null) {
            include_once(dirname(__FILE__) . '/doubles.php');

            $this->doubles = array();
            $players = $this->listPlayers();
            $keys = array_keys($players);
            $count = count($players);
            for($i = 0; $i < ($count - 1); $i++) {
                for($j = $i + 1; $j < $count; $j++) {
                    $this->doubles[] = new my5280_Doubles($players[$keys[$i]]->getName(), $players[$keys[$j]]->getName());
                }
            }
        }
        return $this->doubles;
    }


    /**
     * Retrieve an array of player information.
     */
    public function listPlayers()
    {
        if($this->players === null) {
            $this->players = array();
            if(isset($this->info->players)) {
                include_once(dirname(__FILE__) . '/player.php');
                foreach($this->info->players as $id) {
                    if($id !== 'NONE') {
                        $this->players[$id] = my5280::$instance->getPlayer($id);
                    }
                }
                uasort($this->players, function($a, $b) {
                    return strcasecmp($a->getName(), $b->getName());
                });
            }
        }
        return $this->players;
    }


    /**
     * Save the team.
     *
     * @param none
     * @return boolean
     */
    public function save()
    {
        global $lmLoader;
        $lmAdmin = $lmLoader->adminPanel;

        // Build the custom array
        $custom = array(
            'address' => $this->info->address,
            'number' => $this->info->number,
        );

        // Save the new player list
        if($this->players !== null) {
            $players = array();
            foreach($this->players as $player) {
                $players[] = $player->getId();
            }
            $custom['players'] = $players;
        }

        // Determine if this is an existing team
        if(!empty($this->info->id)) {
            $lmAdmin->editTeam(
                $this->info->id,
                $this->info->title,
                $this->info->website,
                $this->info->coach,
                $this->info->stadium,
                $this->info->home,
                $this->info->group,
                $this->info->roster,
                $custom,
                $old->logo
            );
        } else {
            // Add the new team
            $teamId = $lmAdmin->addTeam(
                $this->info->league_id,
                $this->info->season,
                $this->info->title,
                '', // website
                '', // coach
                $this->info->stadium,
                '', // home
                '', // group
                '', // roster
                $custom
            );
            $this->info->id = $teamId;
        }
    }


    /**
     * Assign the address for the team's location.
     */
    public function setAddress($Address)
    {
        $this->info->address = $Address;
    }


    /**
     * Assign the location.
     */
    public function setLocation($Location)
    {
        $this->info->stadium = $Location;
    }


    /**
     * Assign the team name.
     */
    public function setName($Name)
    {
        $this->info->title = $Name;
    }


    /**
     * Assign the session for the team.
     */
    public function setSession($Session)
    {
        $this->info->league_id = $Session->getLeagueId();
        $this->info->season = $Session->getName();
    }


    /**
     * Assign the team number.
     */
    public function setTeamNumber($Value)
    {
        $this->info->number = $Value;
    }


    /**
     * team information
     */
    protected $info;


    /**
     * array of players
     */
    protected $players = null;


    /**
     * array of doubles
     */
    protected $doubles = null;
}
