<?php
/**
scoresheet edit form for scotch doubles

 */

$curMatchDate = ($curMatch ? $curMatch->getDate() : null);
$maxGames = $session->getMaxHandicapGames();
?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<style type='text/css'>
    div.otherPlayer { display: none; }
</style>
<noscript>
    <style type='text/css'>
        div.otherPlayer { display: block; }
    </style>
</noscript>
<form method='post' action="<?php print admin_url('admin-ajax.php'); ?>">
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
        <?php $firstPlayer = 0; foreach($teams as $label => $info): $team = $info['team']; ?>
            <div class='teamSection edit <?php print $label; ?>'>
                <div class='teamName'>
                    <?php print $label; ?> TEAM:
                    <?php if($team): ?>
                        <div class='teamNameValue'><?php print $team->getName(); ?></div>
                    <?php endif; ?>
                </div>
                <?php if($team): ?>
                <div class='teamRoster'>
                    <div class='singlesHandicaps'>
                        <div class='caption'>Singles Handicaps</div>
                        <?php foreach($team->listPlayers() as $player): ?>
                            <div class='player'>
                                <div>
                                    <?php print $player->getName(); ?>: 
                                    <?php print round($player->getHandicap($curMatchDate, $maxGames), 0); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class='doublesHandicaps'>
                        <div class='caption'>Doubles Handicaps</div>
                        <?php foreach($team->listDoubles() as $double): ?>
                            <div class='doubles'>
                                <div>
                                    <?php print $double->getName(); ?>:
                                    <?php print round($double->getHandicap($curMatchDate, $maxGames), 0); ?>
                                </div> 
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class='activeDoublesHandicap'>
                        <div class='caption'>Active Doubles HCP</div>
                        <div class='row'>
                            <div class='cell'>
                                <input type='number' name="doublesHandicap[<?php print $label; ?>]" max="15" min="0" size='2' length='2' 
                                value="<?php print $info['doublesHandicap']; ?>"
                                />
                            </div>
                        </div>
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
                    <?php for($i = $firstPlayer; $i < $firstPlayer + 2; $i++): ?>
                        <div class='row player'>
                            <div class='cell paid'>
                                <input type='number' name='paid[]' maxlength='2' size='2' min='0' max='99' step='1'
                                <?php if(isset($info['selPlayers'][$i])) print 'value="' . round($info['selPlayers'][$i]->paid, 0) . '"'; ?>
                                />
                            </div>
                            <div class='cell playerName player<?php print $i ?>'>
                                <select class="teamPlayer <?php print $label; ?>player<?php print $i;?>" name="player[<?php print $i ?>]">
                                    <option value="NONE">(Select one)</option>
                                    <?php foreach($info['players'] as $player): ?>
                                        <option value="<?php print $player['id']; ?>"
                                        handicap="<?php print $player['handicap']; ?>"
                                        <?php if(in_array($i, $player['sel'])) print 'selected="selected"'; ?>
                                        ><?php print $player['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class='cell handicap'>
                                <input type='number' name='handicap[]' maxlength='2' size='2' min='0' max='15' step='1'
                                <?php if(isset($info['selPlayers'][$i])) print 'value="' . $info['selPlayers'][$i]->handicap . '"'; ?>
                                />
                            </div>
                        </div>
                    <?php endfor; ?>
                    <div class='row'>
                        <div class='cell blank'><br /></div>
                        <div class='cell playerName'>Handicap Points</div>
                        <div class='cell handicap'><?php print $info['handicap']; ?></div>
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
                    <?php for($iPlayer = $firstPlayer; $iPlayer < ($firstPlayer + 2); $iPlayer++): ?>
                        <div class='row scores player<?php print $iPlayer; ?>'>
                            <?php for($iRound = 0; $iRound < 2; $iRound++): ?>
                                <?php 
                                    // Determine the game number
                                    $iGame = call_user_func(array($curMatch, 'get' . $label . 'Game'), $iRound, $iPlayer);
                                ?>
                                <div class='cell score game<?php print $iGame; ?>' player="<?php print $iPlayer; ?>" round="<?php print $iRound; ?>">
                                    <?php if($label == 'HOME'): ?>
                                        <input type='number' name='score[<?php print $iGame; ?>]' maxlength='2' size='2' min='0' max='15' step='1' 
                                            class="HOMEgame HOMEgame<?php print $iGame; ?>"
                                            <?php if(isset($info['scores'][$iGame])) print 'value="' . $info['scores'][$iGame] . '"'; ?>
                                        />
                                    <?php else: ?>
                                        <span class="AWAYgame AWAYgame<?php print $iGame; ?>">
                                            <?php if(isset($info['scores'][$iGame])) print $info['scores'][$iGame]; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='row handicaps'>
                        <?php for($iRound = 0; $iRound < 2; $iRound++): ?>
                            <div class='cell handicap round<?php print $iRound; ?>'>
                                <?php print isset($roundHandicaps[$iRound]) ? ($label == 'HOME' ? $roundHandicaps[$iRound][0] : $roundHandicaps[$iRound][1]) : null; ?>
                            </div>
                        <?php endfor ?>
                    </div>
                    <div class='row totals'>
                        <?php for($i = 0; $i < 2; $i++): ?>
                            <div class='cell total'><?php if(isset($info['roundTotals'][$i])) print $info['roundTotals'][$i]; ?></div>
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
                    <div class='row scores player<?php print $label == 'HOME' ? 4 : 5; ?>'>
                        <?php for($i = 4; $i < 9; $i++): ?>
                            <div class='cell score game<?php print $i; ?>'>
                                <?php if($label == 'HOME'): ?>
                                    <input type='number' name='score[<?php print $i; ?>]' maxlength='2' size='2' min='0' max='15' step='1' 
                                        class="HOMEgame HOMEgame<?php print $i; ?>"
                                    <?php if(isset($info['scores'][$i])) print 'value="' . $info['scores'][$i] . '"'; ?>
                                    />
                                <?php else: ?>
                                    <span class="AWAYgame AWAYgame<?php print $i; ?>">
                                        <?php if(isset($info['scores'][$i])) print $info['scores'][$i]; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                        <div class='cell totalScore'>
                            <?php print $info['totalPoints'] - $info['totalHcpPoints']; ?>
                        </div>
                    </div>
                    <div class='row handicaps'>
                        <?php for($iRound = 2; $iRound < 7; $iRound++): ?>
                            <div class='cell handicap'>
                                <?php if(isset($roundHandicaps[$iRound])) print ($label == 'HOME' ? $roundHandicaps[$iRound][0] : $roundHandicaps[$iRound][1]); ?>
                            </div>
                        <?php endfor; ?> 
                        <div class='cell totalHandicap'><?php print $info['totalHcpPoints']; ?></div>
                    </div>
                    <div class='row totals'>
                        <?php for($i = 2; $i < 7; $i++): ?>
                            <div class='cell total'>
                                <?php if(isset($info['roundTotals'][$i])) print $info['roundTotals'][$i]; ?>
                            </div>
                        <?php endfor; ?>
                        <div class='cell total'>
                            <?php print $info['totalPoints']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php $firstPlayer += 2; endforeach; ?>
    </div>

    <input type='hidden' name='action' value='my5280_update_scoresheet' />
    <input type='hidden' name='match' value="<?php print $curMatch->getId(); ?>" />

    <p class="submit"><input type="submit" value="Save Scores &raquo;" class="button" /></p>
</form>
<script type='text/javascript'>
    function my5280_getAwayGame(homeGame)
    {
        switch(homeGame) {
        case 2:
            return 3;
        case 3:
            return 2;
        default:
            return homeGame;
        }
    }

    jQuery(function() {
        my5280.init();
    });
</script>
