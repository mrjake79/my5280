<?php
/**
scoresheet edit form for 8-Ball 5x5

*/

$curMatchDate = ($curMatch ? $curMatch->getDate() : null);
$maxGames = $session->getMaxHandicapGames();

?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<style type='text/css'>
    .matchNumber { float: left; font-size: 5pt; vertical-align: top; border-right: solid 1px; width: 1.5em; height: 100%; }
    div.otherPlayer { display: none; }
    div.teamRoster { width: 12em; }
</style>
<noscript>
    <style type='text/css'>
        div.otherPlayer { display: block; }
    </style>
</noscript>
<?php if($title): ?>
    <h2 class='subtitle'><?php print $title; ?></h2>
<?php endif; ?>
<br style='clear: left;' />
<form action="<?php print admin_url('admin-ajax.php'); ?>" method='post' id='scoresheetForm'>
    <div class='scoresheet 8ball5x5'>
        <div class='dateBox'>
            Date:
            <?php if($curMatch): ?>
                <div class='date'><?php print date('n/j/Y', strtotime($curMatch->getDate())); ?></div>
            <?php endif; ?>
        </div>
        <?php $numPlayers = 5; $firstPlayer = 0; foreach($teams as $label => $info): ?>
            <div class='teamSection <?php print $label; ?>'>
                <div class='teamName'>
                    <?php print $label; ?> TEAM:
                    <?php if($info): ?>
                        <div class='teamNameValue'><?php print $info['team']->getName(); ?></div>
                    <?php endif; ?>
                </div>
                <?php if($info): ?>
                <div class='table teamRoster'>
                    <?php foreach($info['team']->listPlayers() as $player): ?>
                        <div class='row player'>
                            <div class='cell playerName'>
                                <?php print $player->getName(); ?>
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
                                <input type='number' name='paid[]' maxlength='2' size='2' min='0' max='99' step='1' 
                                <?php if(isset($info['selPlayers'][$i])) print 'value="' . round($info['selPlayers'][$i]->paid, 0) . '"'; ?>
                                />
                            </div>
                            <div class='cell playerName player<?php print $i; ?>'>
                                <select class='teamPlayer <?php print $label; ?>player<?php print $i; ?>' name="player[<?php print $i; ?>]">
                                    <option value="NONE">(None/Forfeit)</option>
                                    <?php $found = false; foreach($info['players'] as $player): ?>
                                        <option value="<?php print $player['id']; ?>"
                                        handicap="<?php print $player['handicap']; ?>"
                                        <?php if(in_array($i, $player['sel'])) print 'selected="selected"'; ?>
                                        ><?php print $player['name']; ?></option>
                                    <?php endforeach; ?>
                                    <option value="OTHER"
                                    <?php if(!$found && isset($players[$i]) && $players[$i]['id'] != null) print 'selected="selected"'; ?>
                                    >(Other)</option>
                                </select>
                                <div class='otherPlayer'>
                                    <select id="otherPlayer<?php print $i; ?>" class='otherPlayer' name="otherPlayer[<?php print $i; ?>]" sel="<?php if(isset($players[$i]) && $players[$i]['id'] != null) print $players[$i]['id']; ?>">
                                        <option value="">(Other Player)</option>
                                        <?php if($i == 0): foreach($allPlayers as $player): ?>
                                            <option value="<?php print $player->getId(); ?>"
                                            handicap="<?php print round($player->getHandicap($curMatchDate, $maxGames), 0); ?>"
                                            ><?php print $player->getName(); ?></option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
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
                        <div class='cell'>Handicap Points</div>
                        <div class='cell teamHandicap'><?php print $info['handicap']; ?></div>
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
                        <div class='row scores player<?php print $i; ?>'>
                            <?php for($j = 0; $j < 5; $j++): ?>
                                <?php 
                                    // Determine the game number
                                    if($label == 'AWAY') {
                                        $iGame = $curMatch->getAwayGame($j, $i);
                                    } else {
                                        $iGame = $curMatch->getHomeGame($j, $i);
                                    }
                                ?>
                                <div class="cell score" round="<?php print $j; ?>" player="<?php print $i; ?>">
                                    <?php if($label == 'HOME'): ?>
                                        <input type='number' name='score[<?php print $iGame; ?>]' maxlength='2' size='2' min='0' max='15' step='1' 
                                            class="HOMEgame<?php print $iGame; ?> HOMEgame"
                                            <?php if(isset($info['scores'][$iGame])) print 'value="' . $info['scores'][$iGame] . '"'; ?>
                                        />
                                    <?php else: ?>
                                        <span class="AWAYgame AWAYgame<?php print $iGame; ?>">
                                            <?php if(isset($info['scores'][$iGame])) print $info['scores'][$iGame]; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                            <div class='cell totalScore'>
                                <?php if(isset($info['playerTotals'][$i])) print $info['playerTotals'][$i]; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <div class='row handicaps'>
                        <div class='cell number'>HCP</div>
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <div class='cell handicap round<?php print $i; ?>'>
                                <?php if(isset($info['roundHandicaps'][$i])) print $info['roundHandicaps'][$i]; ?>
                            </div>
                        <?php endfor; ?>
                        <div class='cell totalHandicap'>
                            <?php print $info['totalHcpPoints']; ?>
                        </div>
                    </div>
                    <div class='row totals'>
                        <div class='cell total number'>TOT</div>
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <div class='cell total round<?php print $i; ?>'>
                                <?php if(isset($info['roundTotals'][$i])) print $info['roundTotals'][$i]; ?>
                            </div>
                        <?php endfor; ?>
                        <div class='cell overallTotal'>
                            <?php print $info['totalPoints']; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php $firstPlayer += $numPlayers; endforeach; ?>
    </div>

    <input type='hidden' name='action' value='my5280_update_scoresheet' />
    <input type='hidden' name='match' value="<?php print $curMatch->getId(); ?>" />

    <p class="submit"><input type="submit" value="Save Scores &raquo;" class="button" /></p>
</form>
<script type='text/javascript'>
    function my5280_getAwayGame(homeGame)
    {
        // Determine the round 
        var iRound = Math.floor(homeGame / 5);

        // Calculate the away game
        var awayGame = homeGame + iRound;
        if(awayGame >= ((iRound + 1) * 5)) {
            awayGame -= 5;
        }
        return awayGame;
    }

    jQuery(function() {
        my5280.init();
    });
</script>
