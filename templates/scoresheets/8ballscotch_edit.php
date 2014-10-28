<?php
/**
scoresheet edit form for scotch doubles

 */
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
<form method='post'>
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
                                    <?php print $double->getName(); ?>:
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
                    <?php for($i = $firstPlayer; $i < $firstPlayer + 2; $i++): ?>
                        <div class='row player'>
                            <div class='cell paid'>
                                <input type='number' name='paid[]' maxlength='2' size='2' min='0' max='99' step='1'
                                <?php if(isset($info['selPlayers'][$i])) print 'value="' . $info['selPlayers'][$i]['paid'] . '"'; ?>
                                />
                            </div>
                            <div class='cell playerName player<?php print $i ?>'>
                                <select class='teamPlayer' name="player[<?php print $i ?>]">
                                    <option value="NONE">(None/Forfeit)</option>
                                    <?php foreach($info['players'] as $player): ?>
                                        <option value="<?php print $player['id']; ?>"
                                        handicap="<?php print $player['handicap']; ?>"
                                        <?php if(in_array($i, $player['sel'])) print 'selected="selected"'; ?>
                                        ><?php print $player['name']; ?></option>
                                    <?php endforeach; ?>
                                    <option value="OTHER">(Other)</option>
                                </select>
                                <div class='otherPlayer'>
                                    <select id="otherPlayer<?php print $i; ?>" class='otherPlayer' name='otherPlayer[<?php print $i; ?>]'>
                                        <option value="">(Other Player)</option>
                                        <?php if($i == 0): foreach($allPlayers as $player): ?>
                                            <option value="<?php print $player->getId(); ?>"
                                            handicap="<?php print round($player->getHandicap(), 0); ?>"
                                            ><?php print $player->getName(); ?></option>
                                        <?php endforeach; endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class='cell handicap'>
                                <input type='number' name='handicap[]' maxlength='2' size='2' min='0' max='15' step='1'
                                <?php if(isset($info['selPlayers'][$i])) print 'value="' . $info['selPlayers'][$i]['handicap'] . '"'; ?>
                                />
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
                                    <?php if($label == 'HOME'): ?>
                                        <input type='number' name='score[]' maxlength='2' size='2' min='0' max='15' step='1' />
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endfor; ?>
                    <div class='row handicaps'>
                        <?php for($i = 0; $i < 2; $i++): ?>
                            <div class='cell handicap'>
                            </div>
                        <?php endfor ?>
                    </div>
                    <div class='row totals'>
                        <?php for($i = 0; $i < 2; $i++): ?>
                            <div class='cell total'>
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
                                <?php if($label == 'HOME'): ?>
                                    <input type='number' name='score[]' maxlength='2' size='2' min='0' max='15' step='1' />
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                        <div class='cell totalScore'>
                        </div>
                    </div>
                    <div class='row handicaps'>
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <div class='cell handicap'>
                            </div>
                        <?php endfor; ?>
                        <div class='cell totalHandicap'>
                        </div>
                    </div>
                    <div class='row totals'>
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <div class='cell total'>
                            </div>
                        <?php endfor; ?>
                        <div class='cell total'>
                        </div>
                    </div>
                </div>
            </div>
        <?php $firstPlayer += 2; endforeach; ?>
    </div>
    <p class="submit"><input type="submit" value="Save Scores &raquo;" class="button" /></p>
</form>
