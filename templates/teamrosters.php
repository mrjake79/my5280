<?php
/*
Template page for team rosters.

*/

usort($teams, function($a, $b) {
    return strcasecmp($a->title, $b->title);
});

?>
<?php foreach($teams as $team): ?>
    <?php if(count($team->players)): ?>
        <table class='roster'>
            <caption><?php print $team->title; ?></caption>
            <?php foreach($team->players as $player => $handicap): ?>
                <tr>
                    <th><?php print htmlentities($player); ?></th>
                    <td><?php print $handicap; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br />
    <?php endif; ?>
<?php endforeach; ?>
