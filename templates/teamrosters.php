<?php
/*
Template page for team rosters.

*/

usort($teams, function($a, $b) {
    return strcasecmp($a->getName(), $b->getName());
});

$maxGames = $session->getMaxHandicapGames();

?>
<?php foreach($teams as $team): ?>
    <?php if(count($team->players)): ?>
        <table class='roster'>
            <caption><?php print $team->title . ' - ' . $team->getLocation(); ?></caption>
            <?php foreach($team->listPlayers() as $player): ?>
                <tr>
                    <td><?php print $player->getName(); ?></td>
                    <td style="text-align: right;"><?php print round($player->getHandicap(null, $maxGames), 0); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br />
    <?php endif; ?>
<?php endforeach; ?>
