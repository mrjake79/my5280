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
