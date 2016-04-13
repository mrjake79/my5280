<?php
/**
scoresheet view page for 8-Ball 5x5

 */

$curMatchDate = ($curMatch ? $curMatch->getDate() : null);
$maxGames = $session->getMaxHandicapGames();

?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<style type='text/css'>
    .scoresheet .score { vertical-align: bottom; }
    .scoresheet .score.played { vertical-align: middle; }
</style>
<h2 class='subtitle'><?php print $title; ?></h2>
<br style='clear: left;' />
<div class='scoresheet 8ball5x5'>
    <div class='dateBox'>
        Date:
        <?php if($curMatch): ?>
            <div class='date'><?php print date('n/j/Y', strtotime($curMatch->getDate())); ?></div>
        <?php endif; ?>
    </div>
    <?php $numPlayers = 5; $firstPlayer = 0; $iTeam = 0; foreach($teams as $label => $info): ?>
        <div class='teamSection <?php print $label; ?>'>
            <div class='teamName'>
                <?php print $label; ?> TEAM:
                <?php if($info): ?>
                    <div class='teamNameValue'><?php print $info['team']->getName(); ?></div>
                <?php endif; ?>
            </div>
            <?php if($info && count($info['scores']) == 0): ?>
                <div class='table teamRoster'>
                    <div class='caption'>Roster</div>
                    <?php foreach($info['team']->listPlayers() as $player): ?>
                        <div class='row player'>
                            <div class='cell playerName'>
                                <?php print $player->getName() ?>
                            </div>
                            <div class='cell handicap'>
                                <?php print round($player->getHandicap($curMatchDate, $maxGames), 0); ?>
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
                <?php for($i = $firstPlayer; $i < ($firstPlayer + $numPlayers); $i++): ?>
                    <div class='row player'>
                        <div class='cell paid'>
                            <?php print isset($info['selPlayers'][$i]) ? $info['selPlayers'][$i]->paid : '<br />'; ?>
                        </div>
                        <div class='cell playerName'>
                            <?php print isset($info['selPlayers'][$i]) ? $info['selPlayers'][$i]->player->getName() : '<br />'; ?>
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
                    <div class='cell'>Handicap Points</div>
                    <div class='cell teamHandicap'><?php if(count($info['scores'])) print $info['handicap']; ?></div>
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
                <?php for($i = $firstPlayer; $i < ($firstPlayer + 5); $i++): ?>
                    <div class='row scores'>
                        <?php for($j = 0; $j < 5; $j++): ?>
                            <?php 
                                // Determine the game number
                                if($label == 'AWAY') {
                                    $iGame = $helperMatch->getAwayGame($j, $i);
                                } else {
                                    $iGame = $helperMatch->getHomeGame($j, $i);
                                }
                                
                                // Determine the break
                                $iBreak = $iGame;
                                if($iTeam):
                                    $iBreak -= $j;
                                    if($iBreak <= ($j * 5)):
                                        $iBreak += 5;
                                    endif;
                                endif;
                                $break = ($j < 4) ? (($j % 2) == $iTeam) : (($iGame % 2) == $iTeam);
                            ?>
                            <div class="cell score<?php if(isset($info['scores'][$iGame])) print ' played'; ?>">
                                <?php if(isset($info['scores'][$iGame])):
                                    print $info['scores'][$iGame];
                                else:
                                    print '<div class="matchNumber">' . ($iGame + 1) . ($break ? '<br />B' : '') . '</div>';
                                endif; ?>
                            </div>
                        <?php endfor; ?>
                        <div class='cell totalScore'>
                            <?php if(isset($info['playerTotals'][$i])):
                                print $info['playerTotals'][$i];
                            endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
                <div class='row handicaps'>
                    <div class='cell number'>HCP</div>
                    <?php for($i = 0; $i < 5; $i++): ?>
                        <div class='cell handicap'>
                            <?php if(count($info['scores']) && isset($info['roundHandicaps'][$i])) print $info['roundHandicaps'][$i]; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell totalHandicap'><?php if(count($info['scores'])) print $info['totalHcpPoints']; ?></div>
                </div>
                <div class='row totals'>
                    <div class='cell total number'>TOT</div>
                    <?php for($iRound = 0; $iRound < 5; $iRound++): ?>
                        <div class='cell total'>
                            <?php if(count($info['scores']) && isset($info['roundTotals'][$iRound])) print $info['roundTotals'][$iRound]; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='cell overallTotal'><?php if(count($info['scores'])) print $info['totalPoints']; ?></div>
                </div>
            </div>
        </div>
    <?php $firstPlayer += $numPlayers; $iTeam++; endforeach; ?>
</div>
