<?php
/**
Template page for the league standings

The following variables are usable:
	
    $league: the league
	$session: instance of my5280_session
    $teams: array of teams

*/
?>
<h2 class='subtitle'>Team Standings</h2>
<table border="0" cellpadding="0" cellspacing="0" class="my5280-standings">
    <tr>
        <th class='header cell'>#</th>
        <th class='header cell nameCell'>Team</th>
        <th class='header cell'>Pts</th>
        <th class='header cell'>Avg</th>
    </tr>
    <?php foreach($teams as $team): ?>
        <?php 
            if($team->title == 'BYE') continue; 
            $weeks = count($team->stats['matches']);
        ?>
        <tr>
            <td class='cell'><?php print $team->getRank(); ?></td>
            <td class='cell nameCell'>
                <a href="?team=<?php print$team->getId() ?>"><?php print $team->getName(); ?></a>
            </td>
            <td class='cell'>
                <?php print $team->getTotalPoints(); ?> /
                <?php print $team->getMatchesPlayed(); ?>
            </td>
            <td class='cell'>
                <?php if($team->getMatchesPlayed() > 0) print round($team->getTotalPoints() / $team->getMatchesPlayed(), 2); ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php if(isset($players) && count($players)): ?>
    <br />
    <table border="0" cellpadding="0" cellspacing="0" class="my5280-standings">
        <caption>Player Standings</caption>
        <tr>
            <th class='header cell'>#</th>
            <th class='header cell nameCell'>Player</th>
            <th class='header cell'>Wins</th>
            <th class='header cell'>Win %</th>
        </tr>
        <?php $iPlace = 0; $iNextPlace = 0;
            $prevPcnt = null;
            foreach($players as $player):
                $iNextPlace++;
                if($prevPcnt !== $player['win%']) {
                    $iPlace = $iNextPlace;
                }
                $prevPcnt = $player['win%'];
            ?>
            <tr>
                <td class='cell'><?php print $iPlace; ?></td>
                <td class='cell nameCell'><?php print htmlentities($player['name']); ?></td>
                <td class='cell'>
                    <?php print $player['wins'] + $player['forfeitWins']; ?>
                    <?php if($player['forfeitWins'] > 0): ?>
                        <span style='font-size: smaller; font-style: italic;'>(<?php print $player['wins']; ?>)</span>
                    <?php endif; ?>
                    / <?php print $player['games']; ?>
                </td>
                <td class='cell'><?php print ($player['win%'] !== null ? round($player['win%'], 1) . '%' : null); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<?php if(count($seasons) > 1): ?>
    <div class="screen-only">
        <h2>Other Sessions</h2>
        <p class='instructions'>You can view standings from a previous session.  Select the session in this box and click "Switch" to see the past.</p>
        <form method='get'>
            <select name="season">
                <?php foreach($seasons as $id => $name): ?>
                    <option value="<?php print htmlentities($id); ?>" <?php if($id == $season) print 'selected="selected"'; ?>><?php print htmlentities($name); ?></option>
                <?php endforeach; ?>
            </select>
            <input type='submit' value="Switch" />
        </form>
    </div>
<?php endif; ?>
