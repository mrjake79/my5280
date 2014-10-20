<?php
/**
scoresheet edit form for scotch doubles

 */
?>
<form method='post'>
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
            <div class='teamSection edit'>
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
                                <input type='number' name='paid[]' maxlength='2' size='2' min='0' max='99' step='1' />
                            </div>
                            <div class='cell playerName'>
                                <select name="player[]">
                                    <option value="NONE">(None/Forfeit)</option>
                                    <?php foreach($team->listPlayers() as $player): ?>
                                        <option value="<?php print $player->getId(); ?>"><?php print htmlentities($player->getName()); ?></option>
                                    <?php endforeach; ?>
                                    <option value="OTHER">(Other)</option>
                                </select>
                            </div>
                            <div class='cell handicap'>
                                <input type='number' name='handicap[]' maxlength='2' size='2' min='0' max='15' step='1' />
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
        <?php endforeach; ?>
    </div>
    <p class="submit"><input type="submit" value="Save Scores &raquo;" class="button" /></p>
</form>
