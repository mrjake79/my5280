<?php
/**
Template page for the league schedule

The following variables are usable:
	
	$session: instance of my5280_session
    $teams: array of teams
    $matches: array of matches

*/

$currentWeek = $session->getCurrentWeek();
$firstWeek = array_shift(array_keys($matches));

?>
<h2 class='subtitle'>
    <?php print htmlentities($league->title); ?>
    <div style='font-size: smaller;'><?php print htmlentities($session->getLabel()); ?></div>
</h2>
<br />
<?php if($currentWeek > $firstWeek): ?>
    <div style='text-align: center; margin-bottom: 5px;'>
        <a href="javascript:void(0);" onclick="jQuery('.pastWeeks').removeClass('pastWeeks'); jQuery(this.parentNode).hide();">Show Past Week(s)</a>
    </div>
<?php endif; ?>
<div class="schedule">
    <?php foreach($matches as $date => $matches):
        $class = 'week';
        if($date == $currentWeek) $class .= ' nextWeek';
        elseif($date < $currentWeek) $class .= ' pastWeeks';

        $byeTeam = null; ?>
        <div class="<?php print $class; ?>">
            <div class='headerContainer'>
                <div class="header">
                    <?php if($matches[1]): ?>
                        <div class='headerPart weekNumber'>
                            Week <?php print htmlentities($matches[1]->match_day); ?>
                            <span class='separator'>-</span>
                        </div>
                    <?php endif; ?>
                    <div class='headerPart date'>
                        <?php print htmlentities(date('n/j/Y', strtotime($date))); ?>
                    </div>
                </div>
            </div>
			<?php if(count($matches) == 0): ?>
                <div class='noMatchContainer'>
                    <div class='noMatch'>
                        <?php echo isset($week['vacation']) ? htmlentities($week['vacation']) : 'No Scheduled Matches'; ?>
                    </div>
                </div>
			<?php else: ?>
                    <?php foreach($matches as $match): ?>
                        <?php if($match->home_team != 0 && $match->away_team != 0 && $teams[$match->home_team]->title != 'BYE' && $teams[$match->away_team]->title != 'BYE'): ?>
                            <div class='matchContainer'>
                                <div class='match'>
                                    <div class='matchpart'>
                                        <?php if($scoresheet_url): ?>
                                            <a href="<?php print $scoresheet_url; ?>?match=<?php print $match->id; ?>">
                                        <?php endif; ?>
                                        <?php print $teams[$match->home_team]->title; ?>
                                        <?php if($match->home_points !== null || $match->away_points !== null):
                                            print ': ' . $match->home_points;
                                        endif; ?>
                                        <br />
                                        <small>vs.</small> 
                                        <?php print $teams[$match->away_team]->title; ?>
                                        <?php if($match->home_points !== null || $match->away_points !== null):
                                            print ': ' . $match->away_points;
                                        endif; ?>
                                        <?php if($scoresheet_url) print '</a>'; ?>
                                    </div>
                                    <div class='location matchPart'>
                                        <?php print '@ ' . htmlentities(ucwords($match->location)); ?>
                                    </div>
                                </div>
                            </div>
                        <?php else:
                            if($match->home_team == 0 || $teams[$match->home_team]->title == 'BYE') {
                                $byeTeam = $teams[$match->away_team]->title;
                            } else {
                                $byeTeam = $teams[$match->home_team]->title;
                            }
                        endif; ?>
                    <?php endforeach; ?>
                    <?php if($byeTeam): ?>
                        <div class='matchContainer'>
                            <div class='match'>
                                <div class='matchPart'><?php print $byeTeam; ?></div>
                                <div class='location matchPart'>(No Scheduled Match)</div>
                            </div>
                        </div>
                    <?php endif; ?>
            <?php endif; ?>
        </div>
	<?php endforeach; ?>
</table>
