<?php

global $wpdb, $connections;
$cnRetrieve = $connections->retrieve;

function getPlayerFromName($name, $doubles = false)
{
    global $connections;
    $cnRetrieve = $connections->retrieve;

    $entryID = null;

    if($doubles) {
        sort($name);
        $search1 = array('family_name' => implode(' & ', $name), 'allow_public_override' => true, 'private_override' => true);
        $search2 = null;
    } else {
        $parts = explode(',', $name);
        switch(count($parts)) {
        case 1:
            $firstName = trim($parts[0]);
            $lastName = '';
            break;
        case 2:
            $firstName = trim($parts[1]);
            $lastName = trim($parts[0]);
            break;
        }
        $search1 = array('first_name' => $firstName, 'last_name' => $lastName);
        $search2 = array('first_name' => $lastName, 'last_name' => $firstName);
    }

    $matches = $cnRetrieve->entries($search1);
    if(count($matches) == 0 && $search2 != null) {
        $matches = $cnRetrieve->entries($search2);
    }

    foreach($matches as $match) {
        if($entryID === null || $match->id < $entryID) {
            $entryID = $match->id;
        }
    }

    return $entryID;
}

$teams = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}leaguemanager_teams");
foreach($teams as $team) {
    if($team->custom == null) continue;

    $custom = unserialize($team->custom);
    if(!isset($custom['players'])) continue;

    $existingPlayers = array();
    foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}my5280_team_players WHERE team_id = {$team->id}") as $player) {
        $existingPlayers[$player->player_id] = $player->player_id;
    }

    foreach($custom['players'] as $key => $value) {
        $entryID = null;

        if(is_string($key)) {
            // Old style
            $entryID = getPlayerFromName($key);
        } else {
            if($value == 'NONE') continue;

            // New style
            $entry = $cnRetrieve->entry($value);
            if($entry) {
                $entryID = $entry->id;
            }
        }

        if($entryID !== null) {
            if(!isset($existingPlayers[$entryID])) {
                $wpdb->insert($wpdb->prefix . 'my5280_team_players', array('team_id' => $team->id, 'player_id' => $entryID), array('%d', '%d'));       
            }
        }
    }
}

$teamPlayerFormats = array('%d', '%d', '%d', '%d', '%d', '%f');
$teamScoreFormats = array('%d', '%d', '%d');

$matches = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}leaguemanager_matches");
foreach($matches as $match) {
    if($match->custom == null) continue;

    $existingPlayers = array();

    $sql = "SELECT *
        FROM {$wpdb->prefix}my5280_match_players
        WHERE match_id = {$match->id}
        ORDER BY position";

    foreach($wpdb->get_results($sql) as $player) {
        $existingPlayers[$player->position] = $player;
    }

    // Get existing scores for the match
    $existingScores = array();

    $sql = "SELECT scores.* 
        FROM {$wpdb->prefix}my5280_match_scores scores,
            {$wpdb->prefix}my5280_match_players players
        WHERE scores.match_player_id = players.id
        AND players.match_id = {$match->id}";

    foreach($wpdb->get_results($sql) as $score) {
        if(!isset($existingScores[$score->match_player_id])) {
            $existingScores[$score->match_player_id] = array(
                $score->game => $score,
            );
        } else {
            $existingScores[$score->match_player_id][$score->game] = $score;
        }
    }

    $custom = unserialize($match->custom);

    if(isset($custom['home_players']) && isset($custom['away_players'])) {
        // Old style 
        $iPosition = 0;
        foreach($custom['home_players'] as $name => $info) {
            $entryID = getPlayerFromName($name);
            $handicap = isset($info['handicap']) ? $info['handicap'] : null;
            $paid = isset($info['paid']) ? (float) $info['paid'] : null;

            $data = array(
                'match_id' => $match->id,
                'position' => $iPosition,
                'player_id' => $entryID,
                'handicap' => $handicap,
                'team_id' => $match->home_team,
                'paid' => $paid,
            );
            
            if(isset($existingPlayers[$iPosition])) {
                $wpdb->update($wpdb->prefix.'my5280_match_players', $data, array('id' => $existingPlayers[$iPosition]->id), $teamPlayerFormats, array('%d'));
            } else {
                $wpdb->insert($wpdb->prefix.'my5280_match_players', $data, $teamPlayerFormats);
                $data['id'] = $wpdb->insert_id;
                $existingPlayers[$iPosition] = $data;
            }

            $iPosition++;
        }

        foreach($custom['away_players'] as $name => $info) {
            $entryID = getPlayerFromName($name);
            $handicap = isset($info['handicap']) ? $info['handicap'] : null;
            $paid = isset($info['paid']) ? (float) $info['paid'] : null;

            $data = array(
                'match_id' => $match->id,
                'position' => $iPosition,
                'player_id' => $entryID,
                'handicap' => $handicap,
                'team_id' => $match->away_team,
                'paid' => $paid,
            );
            
            if(isset($existingPlayers[$iPosition])) {
                $wpdb->update($wpdb->prefix.'my5280_match_players', $data, array('id' => $existingPlayers[$iPosition]->id), $teamPlayerFormats, array('%d'));
            } else {
                $wpdb->insert($wpdb->prefix.'my5280_match_players', $data, $teamPlayerFormats);
                $data['id'] = $wpdb->insert_id;
                $existingPlayers[$iPosition] = $data;
            }

            $iPosition++;
        }
    } elseif(isset($custom['scores']) && isset($custom['players'])) {
        // New style
        
        $homeHandicap = 0;
        $homePoints = 0;
        $awayHandicap = 0;
        $awayPoints = 0;
        
        $firstAwayPosition = count($custom['players']) / 2;
        foreach($custom['players'] as $position => $info) {
            $data = array(
                'match_id' => $match->id,
                'position' => $position,
                'player_id' => ($info['id'] == null ? null : $info['id']),
                'handicap' => ($info['handicap'] === '' ? null : $info['handicap']),
                'team_id' => ($position < $firstAwayPosition ? $match->home_team : $match->away_team),
                'paid' => ($info['paid'] === '' ? null : $info['paid']),
            );

            if($info['handicap'] !== '') {
                if($position < $firstAwayPosition) {
                    $homeHandicap += $info['handicap'];
                } else {
                    $awayHandicap += $info['handicap'];
                }
            }

            if(isset($existingPlayers[$position])) {
                $wpdb->update($wpdb->prefix.'my5280_match_players', $data, array('id' => $existingPlayers[$position]->id), $teamPlayerFormats, array('%d'));
            } else {
                $wpdb->insert($wpdb->prefix.'my5280_match_players', $data, $teamPlayerFormats);
                $data['id'] = $wpdb->insert_id;
                $existingPlayers[$position] = (object) $data;
            }
        }

        // Determine free handicap points for each team
        if($homeHandicap > $awayHandicap) {
            $awayHandicap = ($homeHandicap - $awayHandicap) * $firstAwayPosition;
            $homeHandicap = 0;
        } else {
            $homeHandicap =  ($awayHandicap - $homeHandicap) * $firstAwayPosition;
            $awayHandicap = 0;
        }

        // Determine if this is a teams match (vs a scotch doubles match)
        $isTeams = (bool) (count($custom['scores']) == 25);

        // Identify the partners for scotch doubles
        if(!$isTeams) {
            for($position = 0; $position < 4; $position += 2) {
                $individuals = array();

                $entry = $cnRetrieve->entry($existingPlayers[$position]->player_id);
                if($entry) {
                    $individuals[] = $entry->first_name . ' ' . $entry->last_name;
                }

                $entry = $cnRetrieve->entry($existingPlayers[$position + 1]->player_id);
                if($entry) {
                    $individuals[] = $entry->first_name . ' ' . $entry->last_name;
                }

                // Get the doubles
                if(count($individuals) == 2) {
                    $doubles = getPlayerFromName($individuals, true);
                } else {
                    $doubles = null;
                }

                // Determine handicap
                if($position == 0 && isset($custom['homeDoublesHandicap']) && is_numeric($custom['homeDoublesHandicap'])) {
                    $handicap = $custom['homeDoublesHandicap'];
                } elseif($position == 2 && isset($custom['awayDoublesHandicap']) && is_numeric($custom['awayDoublesHandicap'])) {
                    $handicap = $custom['awayDoublesHandicap'];
                } else {
                    $handicap = null;
                }

                // Add the player to the match
                $data = array(
                    'match_id' => $match->id,
                    'position' => 4 + ($position / 2),
                    'player_id' => $doubles,
                    'handicap' => $handicap,
                    'team_id' => ($position == 0 ? $match->home_team : $match->away_team),
                    'paid' => null,
                );

                if(isset($existingPlayers[4 + ($position / 2)])) {
                    $wpdb->update($wpdb->prefix.'my5280_match_players', $data, array('id' => $existingPlayers[4 + ($position / 2)]->id), $teamPlayerFormats, array('%d'));
                    $data['id'] = $existingPlayers[4 + ($position / 2)]->id;
                } else {
                    $wpdb->insert($wpdb->prefix.'my5280_match_players', $data, $teamPlayerFormats);
                    $data['id'] = $wpdb->insert_id;
                }
                $data['handicap'] = $handicap;
                $existingPlayers[4 + ($position / 2)] = (object) $data;
            }
        }

        foreach($custom['scores'] as $game => $scores) {
            // Determine home and away position
            if($isTeams) {
                $round = floor($game / 5);
                $homePosition = ($game % 5);
                $awayPosition = $homePosition + $round;
                if($awayPosition < 5) {
                    $awayPosition += 5;
                }
            } else {
                if($game < 4) {
                    $round = floor($game / 2);
                    $homePosition = $game % 2;
                    if($game < 2) {
                        $awayPosition = $game + 2;
                    } elseif($game == 2) {
                        $awayPosition = 3;
                    } else {
                        $awayPosition = 2;
                    }
                } else {
                    $round = $game - 2;
                    $homePosition = 4;
                    $awayPosition = 5;
                }
            }
            
            // Handle home player score
            $homeScore = array_shift($scores);
            $data = array(
                'match_player_id' => $existingPlayers[$homePosition]->id,
                'round' => $round,
                'game' => $game,
                'score' => $homeScore
            );
            if(isset($existingScores[$data['match_player_id']][$game])) {
                $wpdb->update($wpdb->prefix.'my5280_match_scores', $data, array('id' => $existingScores[$data['match_player_id']][$game]->id), $teamScoreFormats, array('%d'));
            } else {
                $wpdb->insert($wpdb->prefix.'my5280_match_scores', $data, $teamScoreFormats);
            }
            $homePoints += $homeScore;

            // Handle away player score
            $awayScore = array_shift($scores);
            $data = array(
                'match_player_id' => $existingPlayers[$awayPosition]->id,
                'round' => $round,
                'game' => $game,
                'score' => $awayScore,
            );
            if(isset($existingScores[$data['match_player_id']][$game])) {
                $wpdb->update($wpdb->prefix.'my5280_match_scores', $data, array('id' => $existingScores[$data['match_player_id']][$game]->id), $teamScoreFormats, array('%d'));
            } else {
                $wpdb->insert($wpdb->prefix.'my5280_match_scores', $data, $teamScoreFormats);
            }
            $awayPoints += $awayScore;
        }

        $homePoints += $homeHandicap;
        $awayPoints += $awayHandicap;

        // Fake the doubles handicaps based on the difference in total points
        if(!$isTeams && $existingPlayers[4]->handicap == null && $existingPlayers[5]->handicap == null) {
            if($homePoints < $match->home_points) {
                $handicap = ($match->home_points - $homePoints) / (count($custom['scores']) - 4);
                if($handicap == ((int) $handicap)) {
                    $wpdb->update($wpdb->prefix.'my5280_match_players', array('handicap' => $handicap), array('id' => $existingPlayers[5]->id), array('%d'), array('%d'));
                    $wpdb->update($wpdb->prefix.'my5280_match_players', array('handicap' => 0), array('id' => $existingPlayers[4]->id), array('%d'), array('%d'));
                }
            } elseif($awayPoints < $match->away_points) {
                $handicap = ($match->away_points - $awayPoints) / (count($custom['scores']) - 4);
                if($handicap == ((int) $handicap)) {
                    $wpdb->update($wpdb->prefix.'my5280_match_players', array('handicap' => $handicap), array('id' => $existingPlayers[4]->id), array('%d'), array('%d'));
                    $wpdb->update($wpdb->prefix.'my5280_match_players', array('handicap' => 0), array('id' => $existingPlayers[5]->id), array('%d'), array('%d'));
                }
            }
        }
    }
}
