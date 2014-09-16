<?php
/**
Template page for team standings

*/

$weeks = count($team->stats['matches']);

?>
<h2 class='subtitle'>
    <?php print $team->title; ?>
</h2>
<table class='stats'>
    <caption>Stats</caption>
    <tr>
        <th class='header cell'>Place</th>
        <td class='cell'>#<?php print $team->stats['place']; ?></td>
    </tr>
    <tr>
        <th class='header cell'>Total Points</th>
        <td class='cell'><?php print $team->stats['points']; ?></td>
    </tr>
    <tr>
        <th class='header cell'>Average Points</th>
        <td class='cell'><?php 
            print ($weeks > 0 ? round($team->stats['points'] / $weeks, 1) : 'n/a'); 
        ?></td>
    </tr>
    <tr>
        <th class='header cell'>Wins</th>
        <td class='cell'><?php print $team->stats['wins']; ?></td>
    </tr>
    <tr>
        <th class='header cell'>Losses</th>
        <td class='cell'><?php print ($weeks - $team->stats['wins']); ?></td>
    </tr>
    <tr>
        <th class='header cell'>Total Games</th>
        <td class='cell'><?php print $weeks; ?></td>
    </tr>
</table>
<br />
<table class='statList'>
    <caption>Matches</caption>
    <?php foreach($team->stats['matches'] as $date => $match): ?>
        <tr>
            <th><?php print date('n/j/Y', strtotime($date)); ?></th>
            <td>You: <?php print $match['score']; ?></td>
            <td>
                <?php print $teams[$match['opponent']]->title; ?>:
                <?php print $teams[$match['opponent']]->stats['matches'][$date]['score']; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
