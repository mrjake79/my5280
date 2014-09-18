<?php
/**
scoresheet for scotch doubles

*/

if($curMatch) {
    // Build the team list
    $teams = $session->listTeams();
    $teams = array(
        'HOME' => $teams[$curMatch->home_team],
        'AWAY' => $teams[$curMatch->away_team],
    );

    // Check for player scores
    if(isset($curMatch->custom['home_players'])) {
        // Get handicap information
        $handicapPoints = array();
        foreach($curMatch->custom['home_players'] as $name => $player) {
            $handicapPoints['home']['singles']['total'] += $player['handicap'];
            $handicapPoints['home']['players'][] = $name;
        }

        foreach($curMatch->custom['away_players'] as $name => $player) {
            $handicapPoints['away']['singles']['total'] += $player['handicap'];
            $handicapPoints['away']['players'][] = $name;
        }

        sort($handicapPoints['home']['players']);
        $key = implode('+', $handicapPoints['home']['players']);
        $handicapPoints['home']['doubles']['total'] = $curMatch->custom['doubles'][$key]['handicap'];

        sort($handicapPoints['away']['players']);
        $key = implode('+', $handicapPoints['away']['players']);
        $handicapPoints['away']['doubles']['total'] = $curMatch->custom['doubles'][$key]['handicap'];

        // Calculate per-round information
        if($handicapPoints['home']['singles']['total'] > $handicapPoints['away']['singles']['total']) {
            $handicapPoints['home']['singles']['perRound'] = 0;
            $handicapPoints['away']['singles']['perRound'] = $handicapPoints['home']['singles']['total'] - $handicapPoints['away']['singles']['total'];
        } else {
            $handicapPoints['away']['singles']['perRound'] = 0;
            $handicapPoints['home']['singles']['perRound'] = $handicapPoints['away']['singles']['total'] - $handicapPoints['home']['singles']['total'];
        }

        if($handicapPoints['home']['doubles']['total'] > $handicapPoints['away']['doubles']['total']) {
            $handicapPoints['home']['doubles']['perRound'] = 0;
            $handicapPoints['away']['doubles']['perRound'] = $handicapPoints['home']['doubles']['total'] - $handicapPoints['away']['doubles']['total'];
        } else {
            $handicapPoints['away']['doubles']['perRound'] = 0;
            $handicapPoints['home']['doubles']['perRound'] = $handicapPoints['away']['doubles']['total'] - $handicapPoints['home']['doubles']['total'];
        }
    }
} else {
    $teams = array(
        'HOME' => null,
        'AWAY' => null,
    );
}

$canSubmit = ($curMatch && ($curMatch->home_points + $curMatch->away_points) == 0);
$viewMode = ($curMatch && !$canSubmit);
$canSubmit = false;

?>
<link type='text/css' rel='stylesheet' href="<?php print WP_PLUGIN_URL; ?>/my5280/styles/scoresheet.css" />
<h2 class='subtitle'><?php print $league->title; ?></h2>
<br />
<div class="scoresheet 8ballscotch">
    <div class='dateBox'>
        Date:
        <?php if($curMatch): ?>
            <div class='date'><?php print date('n/j/Y', strtotime($curMatch->date)); ?></div>
        <?php endif; ?>
    </div>
    <?php foreach($teams as $label => $team): ?>
        <?php
            if($team):
                // Initialize variables
                $scores = array();
                $doubles = array();
                $roundTotals = array();
                $totalScore = 0;

                // Check for scores
                $key = strtolower($label);
                if(isset($curMatch->custom[$key . '_players'])):
                    foreach($curMatch->custom[$key . '_players'] as $name => $player):
                        $player['name'] = $name;
                        $scores[] = $player;
                    endforeach;
                    if(isset($curMatch->custom['doubles'])):
                        $names = array_keys($curMatch->custom[$key . '_players']);
                        sort($names);
                        $name = implode('+', $names);
                        if(isset($curMatch->custom['doubles'][$name])):
                            $doubles = $curMatch->custom['doubles'][$name]['scores'];
                        endif;
                    endif;
                endif;
            endif;
        ?>
        <div class='teamSection <?php if($viewMode) print 'view'; ?>'>
            <div class='teamName'>
                <?php print $label; ?> TEAM:
                <?php if($team): ?>
                    <div class='teamNameValue'><?php print $team->title; ?></div>
                <?php endif; ?>
            </div>
            <?php if($team && !$viewMode): ?>
            <div class='teamRoster'>
                <?php foreach($team->players as $player => $handicap): ?>
                    <div class='player'>
                        <div>
                            <?php print htmlentities($player); ?>: 
                            <?php print $handicap; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class='table teamPlayers'>
                <div class='caption'>Player Information</div>
                <div class='row headers'>
                    <div class='cell header'>$ Paid</div>
                    <div class='cell header'>Name</div>
                    <div class='cell header'>HCP</div>
                </div>
                <?php for($i = 0; $i < 2; $i++): ?>
                    <div class='row player'>
                        <div class='cell paid'>
                            <?php print isset($scores[$i]) ? $scores[$i]['paid'] : '<br />'; ?>
                        </div>
                        <div class='cell playerName'>
                            <?php print isset($scores[$i]) ? ucwords($scores[$i]['name']) : '<br />'; ?>
                        </div>
                        <div class='cell handicap'>
                            <?php if(isset($scores[$i])):
                                print $scores[$i]['handicap'];
                            endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
                <div class='row'>
                    <div class='cell blank'><br /></div>
                    <div class='cell playerName'>Handicap Points</div>
                    <div class='cell handicap'>
                        <?php if(isset($handicapPoints)):
                            print $handicapPoints[$key]['singles']['total'];
                        endif; ?>
                    </div>
                </div>
                <div class='row totals'>
                    <div class='cell blank'><br /></div>
                    <div class='cell playerName'>TOTALS</div>
                    <div class='cell blank'><br /></div>
                </div>
            </div>
            <div class='table rounds'>
                <div class='caption'>Singles</div>
                <div class='row headers'>
                    <div class='cell header'>1</div>
                    <div class='cell header'>2</div>
                </div>
                <?php for($i = 0; $i < 2; $i++): ?>
                    <div class='row scores'>
                        <?php for($j = 0; $j < 2; $j++): ?>
                            <div class='cell score'>
                                <?php if(isset($scores[$i]['scores'][$j])):
                                    print $scores[$i]['scores'][$j];
                                    $roundTotals[$j] += $scores[$i]['scores'][$j];
                                    $totalScore += $scores[$i]['scores'][$j];
                                endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
                <div class='row handicaps'>
                    <?php for($i = 0; $i < 2; $i++): ?>
                        <div class='cell handicap'>
                            <?php if(isset($handicapPoints)):
                                print $handicapPoints[$key]['singles']['perRound'];
                            endif; ?>
                        </div>
                    <?php endfor ?>
                </div>
                <div class='row totals'>
                    <?php for($i = 0; $i < 2; $i++): ?>
                        <div class='cell total'>
                            <?php if(isset($roundTotals[$i])):
                                print $roundTotals[$i] + $handicapPoints[$key]['singles']['perRound']; 
                            endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
            <div class='table rounds doubles'>
                <div class='caption'>Doubles</div>
                <div class='row headers'>
                    <div class='cell header'>3</div>
                    <div class='cell header'>4</div>
                    <div class='cell header'>5</div>
                    <div class='cell header'>6</div>
                    <div class='cell header'>7</div>
                    <div class='cell header'>TOT</div>
                </div>
                <div class='row scores'>
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <div class='cell score'>
                            <?php if(isset($doubles[$i])):
                                print $doubles[$i];
                                $totalScore += $doubles[$i];
                                $roundTotals[$i + 2] += $doubles[$i];
                            endif; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell totalScore'>
                        <?php if($totalScore > 0) print $totalScore; ?>
                    </div>
                </div>
                <div class='row handicaps'>
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <div class='cell handicap'>
                            <?php if(isset($handicapPoints)):
                                print $handicapPoints[$key]['doubles']['perRound'];
                            endif; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell totalHandicap'>
                        <?php if($curMatch && $curMatch->home_points):
                            if($label == 'HOME' && $curMatch->home_points > 0) {
                                $fullTotal = $curMatch->home_points;
                            } elseif($label == 'AWAY' && $curMatch->away_points > 0) {
                                $fullTotal = $curMatch->away_points;
                            }
                            print $fullTotal - $totalScore;
                        endif; ?>
                    </div>
                </div>
                <div class='row totals'>
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <div class='cell total'>
                            <?php if(isset($roundTotals[$i+2])) print $roundTotals[$i+2] + $handicapPoints[$key]['doubles']['perRound']; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell total'>
                        <?php if($curMatch):
                            if($label == 'HOME' && $curMatch->home_points > 0) {
                                print $curMatch->home_points;
                            } elseif($label == 'AWAY' && $curMatch->away_points > 0) {
                                print $curMatch->away_points;
                            }
                        endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
