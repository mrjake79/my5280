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
     * Retrieve the ID for the league.
     *
     * @param none
     * @return integer
     */
    public function getLeagueId()
    {
        return $this->info->league_id;
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
     * Retrieve the number of matches played.
     *
     * @param none
     * @return integer
     */
    public function getMatchesPlayed()
    {
        $count = 0;
        foreach($this->listMatches() as $match) {
            if(($match->getHomeScore() + $match->getAwayScore()) > 0) {
                $count++;
            }
        }
        return $count;
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
     * Retrieve the team's rank.
     *
     * @param none
     * @return integer
     */
    public function getRank()
    {
        return $this->info->rank;
    }


    /**
     * Retrieve the season name.
     *
     * @param none
     * @return string
     */
    public function getSeason()
    {
        return $this->info->season;
    }


    /**
     * Retrieve the associated session.
     *
     * @param none
     * @return object The session object.
     */
    public function getSession()
    {
        return my5280::$instance->getSession($this->info->league_id, $this->info->season);
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
     * Get total points.
     *
     * @param none
     * @return integer
     */
    public function getTotalPoints()
    {
        return $this->info->points_plus;
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
     * Retrieve an array of matches for the team.
     */
    public function listMatches()
    {
        global $leaguemanager;

        // Get the session
        $session = $this->getSession();

        // Build the filter
        $id = $this->getId();
        $matches = array();

        $filter = array(
            "league_id" => $this->getLeagueId(),
            "season" => $this->getSeason(),
            "home_team" => $id,
        );

        foreach($leaguemanager->getMatches($filter) as $match) {
            $matches[] = $session->getMatch($match);
        }

        $filter = array(
            "league_id" => $this->getLeagueId(),
            "season" => $this->getSeason(),
            "away_team" => $id,
        );

        foreach($leaguemanager->getMatches($filter) as $match) {
            $matches[] = $session->getMatch($match);
        }

        return $matches;
    }


    /**
     * Retrieve an array of player information.
     */
    public function listPlayers()
    {
        global $wpdb;

        if($this->players === null) {
            $this->players = array();

            foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}my5280_team_players WHERE team_id = {$this->getId()}") as $player) {
                $player = my5280::$instance->getPlayer($player->player_id);
                if($player) {
                    $this->players[$player->getId()] = $player;
                }
            }

            uasort($this->players, function($a, $b) {
                return strcasecmp($a->getName(), $b->getName());
            });
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
        global $lmLoader, $wpdb;
        $lmAdmin = $lmLoader->adminPanel;

        // Build the custom array
        $custom = array(
            'address' => isset($this->info->address) ? $this->info->address : '',
            'number' => $this->info->number,
        );

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
                $this->info->profile,
                $custom,
                $this->info->logo
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

        // Save the new player list
        if($this->players !== null) {
            $existing = array();
            foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}my5280_team_players WHERE team_id = {$this->info->id}") as $player) {
                $existing[$player->player_id] = $player;
            }

            foreach($this->players as $player) {
                $playerID = $player->getId();
                if(isset($existing[$playerID])) {
                    unset($existing[$playerID]);
                } else {
                    $wpdb->insert($wpdb->prefix."my5280_team_players", array('team_id' => $this->info->id, 'player_id' => $playerID), array('%d', '%d'));
                }
            }

            foreach($existing as $removed) {
                $wpdb->delete($wpdb->prefix.'my5280_team_players', array('id' => $removed->id));
            }
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
