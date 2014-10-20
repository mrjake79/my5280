<?php
/**
scoresheet view form for scotch doubles

 */
?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<?php if($title): ?>
    <h2 class='subtitle'><?php print $title; ?></h2>
    <br />
<?php endif; ?>
<div class="scoresheet 8ballscotch">
    <div class='dateBox'>
        Date:
        <?php if($curMatch): ?>
            <div class='date'><?php print date('n/j/Y', strtotime($curMatch->getDate())); ?></div>
        <?php endif; ?>
    </div>
    <?php foreach($teams as $label => $team): ?>
        <div class='teamSection view'>
            <div class='teamName'>
                <?php print $label; ?> TEAM:
                <?php if($team): ?>
                    <div class='teamNameValue'><?php print $team->title; ?></div>
                <?php endif; ?>
            </div>
            <?php if($team): ?>
            <div class='teamRoster'>
                <div class='singlesHandicaps'>
                    <div class='caption'>Singles Handicaps</div>
                    <?php foreach($team->listPlayers() as $player): ?>
                        <div class='player'>
                            <div>
                                <?php print htmlentities($player->getName()); ?>: 
                                <?php print round($player->getHandicap(), 0); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class='doublesHandicaps'>
                    <div class='caption'>Doubles Handicaps</div>
                    <?php foreach($team->listDoubles() as $double): ?>
                        <div class='doubles'>
                            <div>
                                <?php print htmlentities($double->getName()); ?>:
                                <?php print round($double->getHandicap(), 0); ?>
                            </div> 
                        </div>
                    <?php endforeach; ?>
                </div>
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
