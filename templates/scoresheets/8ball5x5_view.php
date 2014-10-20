<?php
/**
scoresheet view page for 8-Ball 5x5

*/

?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<style type='text/css'>
    .scoresheet .score { vertical-align: bottom; }
    .matchNumber { float: left; font-size: 5pt; vertical-align: top; border-right: solid 1px; width: 1.5em; height: 100%; }
</style>
<h2 class='subtitle'><?php print $league->title; ?></h2>
<br style='clear: left;' />
<div class='scoresheet 8ball5x5'>
    <div class='dateBox'>
        Date:
        <?php if($curMatch): ?>
            <div class='date'><?php print date('n/j/Y', strtotime($curMatch->getDate())); ?></div>
        <?php endif; ?>
    </div>
    <?php foreach($teams as $label => $team): ?>
        <div class='teamSection <?php print $label; ?>'>
            <div class='teamName'>
                <?php print $label; ?> TEAM:
                <?php if($team): ?>
                    <div class='teamNameValue'><?php print $team->getName(); ?></div>
                <?php endif; ?>
            </div>
            <?php if($team && !$viewMode): ?>
            <div class='table teamRoster'>
                <?php foreach($team->listPlayers() as $player): ?>
                    <div class='row player'>
                        <div class='cell playerName'>
                            <?php print $player->getName() ?>
                        </div>
                        <div class='cell handicap'>
                            <?php print round($player->getHandicap(), 0); ?>
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
                <?php for($i = 0; $i < 5; $i++): ?>
                    <div class='row player'>
                        <div class='cell paid'>
                            <?php print isset($players[$i]) ? $players[$i]['paid'] : '<br />'; ?>
                        </div>
                        <div class='cell playerName'>
                            <?php print isset($players[$i]) ? $players[$i]['name'] : '<br />'; ?>
                        </div>
                        <div class='cell handicap'>
                            <?php if(isset($players[$i])):
                                print $players[$i]['handicap'];
                            endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
                <div class='row'>
                    <div class='cell blank'><br /></div>
                    <div class='cell'>Handicap Points</div>
                    <div class='cell teamHandicap'>
                    </div>
                </div>
                <div class='row totals'>
                    <div class='cell blank'><br /></div>
                    <div class='cell'>TOTALS</div>
                    <div class='cell blank'><br /></div>
                </div>
            </div>
            <div class='table rounds'>
                <div class='caption'>Scores</div>
                <div class='row headers'>
                    <div class='cell header'>1</div>
                    <div class='cell header'>2</div>
                    <div class='cell header'>3</div>
                    <div class='cell header'>4</div>
                    <div class='cell header'>5</div>
                    <div class='cell header'>TOT</div>
                </div>
                <?php for($i = 0; $i < 5; $i++): $lineTotal = null; ?>
                    <div class='row scores'>
                        <?php for($j = 0; $j < 5; $j++): ?>
                            <?php 
                                // Determine the game number
                                $iGame = ($j * 5) + $i;
                                if($iTeam):
                                    $iGame -= $j;
                                    if($iGame <= ($j * 5)):
                                        $iGame += 5;
                                    endif;
                                endif;
                                
                                // Determine the break
                                $break = ($j < 4) ? (($j % 2) == $iTeam) : (($iGame % 2) != $iTeam);
                            ?>
                            <div class="cell score">
                                <?php if(isset($scores[$i]['scores'][$j])):
                                    print $scores[$i]['scores'][$j];
                                    $lineTotal += $scores[$i]['scores'][$j];
                                else:
                                    print '<div class="matchNumber">' . $iGame . ($break ? '<br />B' : '') . '</div>';
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
