<?php
/**
scoresheet for scotch doubles

*/

if($curMatch) {
    $teams = $session->listTeams();
    $teams = array(
        'HOME' => $teams[$curMatch->home_team],
        'AWAY' => $teams[$curMatch->away_team],
    );
} else {
    $teams = array(
        'HOME' => null,
        'AWAY' => null,
    );
}

$canSubmit = ($curMatch && ($curMatch->home_points + $curMatch->away_points) == 0);
$viewMode = ($curMatch && !$canSubmit);
$canSubmit = false;

$iPlayer = 0;

?>
<style type='text/css'>
    .scoresheet .score { vertical-align: bottom; }
    .matchNumber { float: left; font-size: 5pt; vertical-align: top; border-right: solid 1px; width: 1.5em; height: 100%; }
</style>
<h2 class='subtitle'><?php print $league->title; ?></h2>
<br style='clear: left;' />
<form method='post' id='scoresheetForm'>
    <div class='scoresheet 8ball5x5'>
        <?php if($canSubmit): ?>
            <div style='float: right;' class='startSubmissionLink'>
                <a href='javascript:void(0);'
                onclick="my5280.startScoreSubmission(<?php print $curMatch->id; ?>); return false;">Submit Scores</a>
            </div>
        <?php endif; ?>
        <div class='dateBox'>
            Date:
            <?php if($curMatch): ?>
                <div class='date'><?php print date('n/j/Y', strtotime($curMatch->date)); ?></div>
            <?php endif; ?>
        </div>
        <?php foreach($teams as $label => $team): ?>
                <?php
                if(isset($iTeam)) $iTeam++; else $iTeam = 0;
                if($team):
                    // Sort the players
                    $players = array_slice($team->players, 0, 1);
                    $otherPlayers = array_slice($team->players, 1);
                    uksort($otherPlayers, function($a, $b) {
                        return strcasecmp($a, $b);
                    });
                    $players = array_merge($players, $otherPlayers);

                    // Initialize variables
                    $scores = array();
                    $handicapTotal = 0;

                    // Check for scores
                    $key = strtolower($label);
                    if(isset($curMatch->custom[$key . '_players'])):
                        foreach($curMatch->custom[$key . '_players'] as $name => $player):
                            $player['name'] = $name;
                            $scores[] = $player;
                        endforeach;
                    endif;
                endif;
            ?>
            <div class='teamSection <?php print $label; ?>'>
                <div class='teamName'>
                    <?php print $label; ?> TEAM:
                    <?php if($team): ?>
                        <div class='teamNameValue'><?php print $team->title; ?></div>
                    <?php endif; ?>
                </div>
                <?php if($team && !$viewMode): ?>
                <div class='table teamRoster'>
                    <?php foreach($players as $player => $handicap): ?>
                        <div class='row player'>
                            <div class='cell playerName'>
                                <?php print htmlentities($player); ?>
                            </div>
                            <div class='cell handicap'>
                                <?php print $handicap; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class='table teamPlayers'>
                    <div class='caption'>Player Information</div>
                    <div class='row headers'>
                        <div class='cell header number'>#</div>
                        <div class='cell header'>$ Paid</div>
                        <div class='cell header'>Name</div>
                        <div class='cell header'>HCP</div>
                    </div>
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <div class='row player'>
                            <div class='cell number'><?php print ($iPlayer++) + 1; ?></div>
                            <div class='cell paid'>
                                <?php print isset($scores[$i]) ? $scores[$i]['paid'] : '<br />'; ?>
                            </div>
                            <div class='cell playerName'>
                                <?php print isset($scores[$i]) ? ucwords($scores[$i]['name']) : '<br />'; ?>
                            </div>
                            <div class='cell handicap'>
                                <?php if(isset($scores[$i])):
                                    $handicapTotal += $scores[$i]['handicap'];
                                    print $scores[$i]['handicap'];
                                endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <div class='row'>
                        <div class='cell blank number'><br /></div>
                        <div class='cell blank'><br /></div>
                        <div class='cell'>Handicap Points</div>
                        <div class='cell teamHandicap'>
                            <?php print $handicapTotal > 0 ? $handicapTotal : null; ?>
                        </div>
                    </div>
                    <div class='row totals'>
                        <div class='cell blank number'><br /></div>
                        <div class='cell blank'><br /></div>
                        <div class='cell'>TOTALS</div>
                        <div class='cell blank'><br /></div>
                    </div>
                </div>
                <div class='table rounds'>
                    <div class='caption'>Scores</div>
                    <div class='row headers'>
                        <div class='cell header number'>#</div>
                        <div class='cell header'>1</div>
                        <div class='cell header'>2</div>
                        <div class='cell header'>3</div>
                        <div class='cell header'>4</div>
                        <div class='cell header'>5</div>
                        <div class='cell header'>TOT</div>
                    </div>
                    <?php for($i = 0; $i < 5; $i++): $lineTotal = null; ?>
                        <div class='row scores'>
                            <div class='cell number'>
                                <?php
                                    print $i + $iPlayer - 4;
                                ?>
                            </div>
                            <?php for($j = 0; $j < 5; $j++): ?>
                                <?php 
                                    // Determine the match number
                                    $iMatch = ($j * 5) + $i + 1; 
                                    if($iTeam):
                                        $iMatch -= $j;
                                        if($iMatch <= ($j * 5)):
                                            $iMatch += 5;
                                        endif;
                                    endif;
                                    
                                    // Determine the break
                                    $break = ($j < 4) ? (($j % 2) == $iTeam) : (($iMatch % 2) != $iTeam);
                                ?>
                                <div class="cell score match<?php print $iMatch; ?>">
                                    <?php if(isset($scores[$i]['scores'][$j])):
                                        print $scores[$i]['scores'][$j];
                                        $lineTotal += $scores[$i]['scores'][$j];
                                    else:
                                        print '<div class="matchNumber">' . $iMatch . ($break ? '<br />B' : '') . '</div>';
                                    endif; ?>
                                </div>
                            <?php endfor; ?>
                            <div class='cell totalScore'>
                                <?php if($lineTotal !== null):
                                    print $lineTotal;
                                endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <div class='row handicaps'>
                        <div class='cell number'>HCP</div>
                        <div class='cell handicap'></div>
                        <div class='cell handicap'></div>
                        <div class='cell handicap'></div>
                        <div class='cell handicap'></div>
                        <div class='cell handicap'></div>
                        <div class='cell totalHandicap'></div>
                    </div>
                    <div class='row totals'>
                        <div class='cell total number'>TOT</div>
                        <div class='cell total'></div>
                        <div class='cell total'></div>
                        <div class='cell total'></div>
                        <div class='cell total'></div>
                        <div class='cell total'></div>
                        <div class='cell overallTotal'>
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
        <?php if($canSubmit): ?>
            <div style='float: right;' class='startMatchLink mobile'>
                <a href='javascript:void(0);'
                onclick="my5280.startScoreSubmission(<?php print $curMatch->id; ?>, true); return false;">Begin Match</a>
            </div>
            <div id='mobileSubmit' class='mobile' style='display: none;'>
                <?php for($i = 1; $i < 3; $i++): ?>
                    <table id='match<?php print $i; ?>' class='mobileSubmit'>
                        <caption>Table <?php print $i; ?></caption>
                        <tr>
                            <th class='homePlayer'></th>
                            <td><input type='text' id='match<?php print $i; ?>score' maxlength='2' size='2' />
                        </tr>
                        <tr>
                            <th class='awayPlayer'></th>
                            <td></td>
                        </tr>
                    </table>
                    <div class='mobileSubmitLink'>
                        <div class='back'>
                            <a href="javascript:void(0);"
                                onclick="my5280.displayPrevGame(<?php print $i; ?>); return false;">&lt;&lt; Prev</a>
                        </div>
                        <div class='next'>
                            <a href="javascript:void(0);"
                                onclick="my5280.displayNextGame(<?php print $i; ?>); return false;">Next &gt;&gt;</a>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</form>
<?php if($curMatch): ?>
    <script type='text/javascript'>
        jQuery(function() {
            my5280.resumeScoreSubmission(<?php print $curMatch->id; ?>);
        });
    </script>
<?php endif; ?>
