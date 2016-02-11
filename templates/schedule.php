<?php
/**
Template page for the league schedule

The following variables are usable:
	
	$session: instance of my5280_session
    $teams: array of teams
    $matches: array of matches

*/

$currentWeek = $session->getCurrentWeek();
$date_keys = array_keys($dates);
$firstWeek = array_shift($date_keys);

?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/schedule.css" />
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
<br style='clear: left;' class='clearFloats' />
<p>PLEASE NOTE: The schedule now displays AWAY team first and HOME team second (AWAY @ HOME).</p>
<div class="schedule">
    <?php foreach($dates as $date => $data):
        $class = 'week';
        if($date == $currentWeek) $class .= ' nextWeek';
        elseif($date < $currentWeek) $class .= ' pastWeeks';
        ?>
        <div class="<?php print $class; ?>">
            <div class='headerContainer'>
                <div class="header">
                    <?php if(isset($data['matches'][0])): ?>
                        <div class='headerPart weekNumber'>
                            Week <?php print htmlentities($data['matches'][0]->getMatchDay() + 1); ?>
                            <span class='separator'>-</span>
                        </div>
                    <?php endif; ?>
                    <div class='headerPart date'>
                        <?php print htmlentities(date('n/j/Y', strtotime($date))); ?>
                    </div>
                </div>
            </div>
			<?php if(count($data['matches']) == 0): ?>
                <div class='noMatchContainer'>
                    <div class='noMatch'>
                        <?php echo isset($data['note']) ? htmlentities($data['note']) : 'No Scheduled Matches'; ?>
                    </div>
                </div>
                <?php for($iBlank = 0; $iBlank < ($maxMatches - 1); $iBlank++): ?>
                    <div class='noMatchContainer blank'><br /></div>
                <?php endfor; ?>
			<?php else: ?>
                    <?php foreach($data['matches'] as $match): ?>
                        <div class='matchContainer'>
                            <div class='match'>
                                <div class='matchPart'>
                                    <?php if($scoresheet_url): ?>
                                        <a href="<?php print $scoresheet_url; ?>?match=<?php print $match->getId(); ?>">
                                    <?php endif; ?>
                                    <?php print $match->getAwayTeam()->getName(); ?>
                                    <?php if($match->getHomeScore() > 0 || $match->getAwayScore() > 0): 
                                        print '(' . $match->getAwayScore() . ')';
                                    endif; ?><br />
                                    <small>@</small> 
                                    <?php print $match->getHomeTeam()->getName(); ?>
                                    <?php if($match->getHomeScore() > 0 || $match->getAwayScore() > 0): 
                                        print '(' . $match->getHomeScore() . ')';
                                    endif; ?>
                                    <?php if($scoresheet_url) print '</a>'; ?>
                                </div>
                                <?php if($match->getHomeScore() == 0 && $match->getAwayScore() == 0): ?>
                                    <div class='location matchPart'>
                                        <?php print '(' . htmlentities(ucwords($match->getLocation())) . ')'; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
            <?php endif; ?>
        </div>
	<?php endforeach; ?>
</table>
