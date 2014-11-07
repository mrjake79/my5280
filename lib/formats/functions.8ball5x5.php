<?php
/**
 * Format-specific functions for 8-Ball 5x5.
 */


/**
 * Calculate the away team game number.
 */
function my5280_getAwayGame_8ball5x5($HomeGame)
{
    // Determine the round
    $iRound = floor($HomeGame / 5);

    // Calculate the away game
    $awayGame = $HomeGame + $iRound;
    if($awayGame >= (($iRound + 1) * 5)) {
        $awayGame -= 5;
    }
    return $awayGame;
}


/**
 * Calculate the round for a game number.
 */
function my5280_getRoundNumber_8ball5x5($Game)
{
    return floor($Game / 5);
}


/**
 * Get the number of players per team.
 */
function my5280_getPlayerCount_8ball5x5()
{
    return 5;
}


/**
 * Retrieve the number of doubles games played.
 */
function my5280_getDoublesGames_8ball5x5()
{
    return 0;
}
