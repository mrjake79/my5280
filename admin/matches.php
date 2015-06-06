<?php

$weeks = array();
$per_week_matches = 0;
if($matches) {
    foreach($matches as $match) {
        $date = substr($match->date, 0, 10);
        if(!isset($weeks[$date])) $weeks[$date] = array();
        $weeks[$date][] = $match;
        $per_week_matches = max($per_week_matches, count($weeks[$date]));
    }
    $match_width = round(90 / $per_week_matches, 0) . '%';
}
else
{
    $match_width = 'auto';
}

?>

<style type="text/css">
    #competitions-filter th { text-align: center; }
    #competitions-filter td.match-column { vertical-align: top; width: <?php echo $match_width; ?>; }
    #competitions-filter .date-column { white-space: nowrap; width: auto; }
</style>

<form id="competitions-filter" action="" method="post">
<?php wp_nonce_field('matches-bulk') ?>

	<div class="tablenav" style="margin-bottom: 0.1em; clear: none;">
		<!-- Bulk Actions -->
		<select name="action2" size="1">
			<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
			<option value="delete"><?php _e('Delete')?></option>
		</select>
		<input type="submit" value="<?php _e('Apply'); ?>" name="doaction2" id="doaction2" class="button-secondary action" />
	</div>
	

    <table class="widefat" summary="" title="<?php _e('Match Plan', 'leaguemanager') ?>" style="margin-bottom: 2em;">
        <thead>
            <tr>
                <th class='date-column'>Date</th>
                <?php for($iWeek = 1; $iWeek <= $per_week_matches; $iWeek++): ?>
                    <th colspan='2'>Match <?php print $iWeek ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody id="the-list-matches-<?php echo $group ?>" class="form-table">
            <?php $class = ''; ?>
            <?php foreach($weeks as $date => $matches): ?>
                <?php $class = ('alternate' == $class) ? '' : 'alternate'; ?>
                <tr class="<?php echo $class; ?>">
                    <td class='date-column'><?php print mysql2date(get_option('date_format'), $date); ?></td>
                    <?php foreach($matches as $match): 
                        $title = $leaguemanager->getMatchTitle($match->id);
                        ?>
                        <td>
                            <input type='hidden' name="matches[<?php echo $match->id ?>]" value="<?php echo $match->id ?>" />
                            <input type="hidden" name="home_team[<?php echo $match->id ?>]" value="<?php echo $match->home_team ?>" />
                            <input type='hidden' name="away_team[<?php echo $match->id ?>]" value="<?php echo $match->away_team ?>" />
                            <input type='checkbox' value="<?php echo $match->id ?>" name="match[<?php echo $match->id ?>]" />
                        </td>
                        <td class='match-column'>
                            <a href="admin.php?page=leaguemanager&amp;subpage=match&amp;league_id=<?php echo $league->id ?>&amp;edit=<?php echo $match->id ?>&amp;season=<?php echo $season['name'] ?><?php if(isset($group)) echo '&amp;group=' . $group ?>"><?php echo $title ?></a>
                            <div class='match-location'><?php echo $match->location; ?></div>
                            <?php if($match->home_points > 0 || $match->away_points > 0): ?>
                                <div class='match-score'><?php echo $match->home_points . ' - ' . $match->away_points; ?></div>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</form>
