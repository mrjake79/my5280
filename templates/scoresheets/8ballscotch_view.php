<?php
/**
scoresheet view form for scotch doubles

 */

$curMatchDate = ($curMatch ? $curMatch->getDate() : null);
$maxGames = $session->getMaxHandicapGames();

?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<?php if($title): ?>
    <h2 class='subtitle'><?php print $title; ?></h2>
    <br />
<?php endif; ?>
<div class="scoresheet my8ballscotch">
    <div class='dateBox'>
        Date:
        <?php if($curMatch): ?>
            <div class='date'><?php print date('n/j/Y', strtotime($curMatch->getDate())); ?></div>
        <?php endif; ?>
    </div>
    <?php $numPlayers = 2; $firstPlayer = 0; $iTeam = 0;
    foreach($teams as $label => $info): ?>
        <div class='teamSection view'>
            <div class='teamName'>
                <?php print $label; ?> TEAM:
                <?php if($info): ?>
                    <div class='teamNameValue'><?php print $info['team']->getName(); ?></div>
                <?php endif; ?>
            </div>
            <?php if($info && count($info['scores']) == 0): ?>
                <div class='teamRoster'>
                    <div class='singlesHandicaps'>
                        <div class='caption'>Singles Handicaps</div>
                        <?php foreach($info['team']->listPlayers() as $player): ?>
                            <div class='player'>
                                <div>
                                    <?php print htmlentities($player->getName()); ?>: 
                                    <?php print round($player->getHandicap($curMatchDate, $maxGames), 0); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class='doublesHandicaps'>
                        <div class='caption'>Doubles Handicaps</div>
                        <?php foreach($info['team']->listDoubles() as $double): ?>
                            <div class='doubles'>
                                <div>
                                    <?php print htmlentities($double->getName()); ?>:
                                    <?php print round($double->getHandicap($curMatchDate, $maxGames), 0); ?>
                                </div> 
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <br clear='all' />
                </div>
            <?php endif; ?>
            <div class='table teamPlayers'>
                <div class='caption'>Player Information</div>
                <div class='row headers'>
                    <div class='cell header'>$ Paid</div>
                    <div class='cell header'>Name</div>
                    <div class='cell header'>HCP</div>
                </div>
                <?php for($i = $firstPlayer; $i < ($firstPlayer + 2); $i++): ?>
                    <div class='row player'>
                        <div class='cell paid'>
                            <?php print (isset($info['selPlayers'][$i])) ? $info['selPlayers'][$i]->paid : '<br />' ?>
                        </div>
                        <div class='cell playerName'>
                            <?php print isset($info['selPlayers'][$i]) ? (isset($info['selPlayers'][$i]->player) ? ucwords($info['selPlayers'][$i]->player->getName()) : '(Unknown)') : '<br />'; ?>
                        </div>
                        <div class='cell handicap'>
                            <?php if(isset($info['selPlayers'][$i])):
                                print $info['selPlayers'][$i]->handicap;
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
                <?php for($i = $firstPlayer; $i < ($firstPlayer + 2); $i++): ?>
                    <div class='row scores'>
                        <?php for($j = 0; $j < 2; $j++): ?>
                            <?php
                                // Determine the game number
                                $iGame = call_user_func(array($curMatch, 'get' . $label . 'Game'), $j, $i);

                                // Determine the break
                                $iBreak = $iGame;
                                if($iTeam):
                                    $iBreak -= $j;
                                    if($iBreak <= ($j * 2)):
                                        $iBreak += 2;
                                    endif;
                                endif;
                                $break = (($j % 2) == $iTeam);
                            ?>
                            <div class="cell score<?php if(isset($info['scores'][$iGame])) print ' played' ?>">
                                <?php if(isset($info['scores'][$iGame])):
                                    print $info['scores'][$iGame];
                                else:
                                    print '<div class="matchNumber">' . ($iGame + 1) . ($break ? '<br />B' : '') . '</div>';
                                endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
                <div class='row handicaps'>
                    <?php for($i = 0; $i < 2; $i++): ?>
                        <div class='cell handicap'>
                            <?php if(count($info['scores']) && isset($info['roundHandicaps'][$i])) print $info['roundHandicaps'][$i] ?>
                        </div>
                    <?php endfor ?>
                </div>
                <div class='row totals'>
                    <?php for($iRound = 0; $iRound < 2; $iRound++): ?>
                        <div class='cell total'>
                            <?php if(count($info['scores']) && isset($info['roundTotals'][$iRound])) print $info['roundTotals'][$iRound]; ?>
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
                <div class='row scores player<?php $label == 'HOME' ? 4 : 5; ?>'>
                    <?php for($i = 4; $i < 9; $i++): ?>
                        <div class='cell score game<?php print $i; ?>'>
                            <?php if(count($info['scores'])):
                                print isset($info['scores'][$i]) ? $info['scores'][$i] : null;
                            else:
                                print '<div class="matchNumber">' . ($i + 1) . ((($i % 2) == $iTeam) ? '<br />B' : '') . '</div>';
                            endif; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell totalScore'>
                        <?php if(count($info['scores'])) print $info['totalPoints'] - $info['totalHcpPoints']; ?>
                    </div>
                </div>
                <div class='row handicaps'>
                    <?php for($i = 2; $i < 7; $i++): ?>
                        <div class='cell handicap'>
                            <?php if(count($info['scores']) && isset($roundHandicaps[$iRound])) print $roundHandicaps[$i][($label == 'HOME' ? 0 : 1)]; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell totalHandicap'><?php if(count($info['scores'])) print $info['totalHcpPoints']; ?></div>
                </div>
                <div class='row totals'>
                    <?php for($i = 2; $i < 7; $i++): ?>
                        <div class='cell total'>
                            <?php if(count($info['scores']) && isset($info['roundTotals'][$i])) print $info['roundTotals'][$i]; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell total'>
                        <?php if(count($info['scores'])) print $info['totalPoints']; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php $firstPlayer += $numPlayers; $iTeam++; endforeach; ?>
</div>
