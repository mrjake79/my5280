<?php
/**
 * Format-specific functions for 8-Ball Scotch Doubles.
 */

/**
 * Calculate the away team game number.
 */
function my5280_getAwayGame_8ballscotch($HomeGame)
{
    switch($HomeGame) {
    case 2:
        return 3;
    case 3:
        return 2;
    default:
        return $HomeGame;
    }
}

/**
 * Calculate the round for a game number.
 */
function my5280_getRoundNumber_8ballscotch($Game)
{
    if($Game < 4) {
        return floor($Game / 2);
    } else {
        return $Game - 2;
    }
}


/**
 * Get the number of players per team.
 */
function my5280_getPlayerCount_8ballscotch()
{
    return 2;
}


/**
 * Retrieve the number of doubles games played.
 */
function my5280_getDoublesGames_8ballscotch()
{
    return 5;
}
