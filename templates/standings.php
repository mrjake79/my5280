<?php
/**
Template page for the league standings

The following variables are usable:
	
    $league: the league
	$session: instance of my5280_session
    $teams: array of teams

*/
?>
<h2 class='subtitle'>
    <?php print htmlentities($league->title); ?>
    <div style='font-size: smaller;'><?php print htmlentities($session->getLabel()); ?></div>
</h2>
    <br />
    <table border="0" cellpadding="0" cellspacing="0" class="standings">
        <caption>Team Standings</caption>
        <tr>
            <th class='header cell'>Place</th>
            <th class='header cell nameCell'>Team</th>
            <th class='header cell'>Points</th>
            <th class='header cell'>Avg</th>
        </tr>
        <?php foreach($teams as $team): ?>
            <?php 
                if($team->title == 'BYE') continue; 
                $weeks = count($team->stats['matches']);
            ?>
            <tr>
                <td class='cell'><?php print $team->stats['place']; ?></td>
                <td class='cell nameCell'>
                    <a href="?team=<?php print$team->teamNumber; ?>"><?php print $team->title; ?></a>
                </td>
                <td class='cell'><?php print $team->stats['points'] . ' / ' . $weeks; ?></td>
                <td class='cell'><?php print ($weeks > 0 ? number_format($team->stats['points'] / $weeks, 1) : 'n/a'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br />
    <table border="0" cellpadding="0" cellspacing="0" class="standings">
        <caption>Player Standings</caption>
        <tr>
            <th class='header cell'>Place</th>
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
