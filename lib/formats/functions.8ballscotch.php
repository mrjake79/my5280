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
