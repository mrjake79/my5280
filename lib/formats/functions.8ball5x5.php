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
    $awayGame = $HomeGame - $iRound;
    if($awayGame < (($iRound) * 5)) {
        $awayGame += 5;
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
 * Determine the players for a game.
 */
function my5280_listGamePlayers_8ball5x5($Match, $Game)
{
    $iHome = $Game % 5;
    $iRound = floor($Game / 5);
    $iAway = $iHome + $iRound;
    if($iAway < 5) {
        $iAway += 5;
    }

    $players = $Match->listPlayers();
    return array($players[$iHome]['id'], $players[$iAway]['id']);
}
