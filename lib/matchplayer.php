<?php
/**
 * Represents a player in a match.
 *
 * @author      Jake Bahnsen
 * @package     my5280
 * @copyright   Copyright 2014
 */

class my5280_MatchPlayer
{
    /**
     * Constructor.
     */
    public function __construct($Team, $PlayerNumber, $Player = null, $Handicap = 0, $Paid = 0)
    {
        if(is_object($Team)) {
            $this->team = $Team->getId();
        } else {
            $this->team = $Team;
        }

        if(is_object($Player)) {
            $this->player = $Player->getId();
        } else {
            $this->player = $Player;
        }

        $this->playerNumber = $PlayerNumber;
        $this->handicap = $Handicap;
        $this->paid = $Paid;
    }


    /**
     * Get the amount paid.
     */
    public function getAmountPaid()
    {
        return $this->paid;
    }


    /**
     * Get the handicap for the player.
     */
    public function getHandicap()
    {
        return $this->handicap;
    }


    /**
     * Retrieve the player ID.
     */
    public function getPlayerID()
    {
        return $this->player;
    }


    /**
     * Retrieve the player number.
     */
    public function getPlayerNumber()
    {
        return $this->playerNumber;
    }


    /**
     * Retrieve the team's ID.
     */
    public function getTeamID()
    {
        return $this->team;
    }


    /**
     * Determine if this is a forfeit.
     */
    public function isForfeit()
    {
        return ($this->player === null);
    }


    /**
     * The handicap in effect for the player during the associated match.
     */
    protected $handicap = 0;

    /**
     * The ID of the team for which the player played.
     */
    protected $team;

    /**
     * The amount paid by the player for the match.
     */
    protected $paid = 0;

    /**
     * The position of the player within the match.
     */
    protected $playerNumber;

    /**
     * The ID of the player or NULL for no player.
     */
    protected $player = null;
}
