<?php
/**
scoresheet edit form for scotch doubles

 */

$homeTeam = $teams['HOME']['team'];
$awayTeam = $teams['AWAY']['team'];

$homePlayer = array_pop($homeTeam->listPlayers());
$awayPlayer = array_pop($awayTeam->listPlayers());

$homeScores = $teams['HOME']['scores'];
$awayScores = $teams['AWAY']['scores'];

$homeTotal = $teams['HOME']['totalPoints'];
$awayTotal = $teams['AWAY']['totalPoints'];

$games_per_match = 11;

$homeHandicap = isset($teams['HOME']['selPlayers'][0]['handicap']) ? $teams['HOME']['selPlayers'][0]['handicap'] : round($homePlayer->getHandicap(), 0);
$awayHandicap = isset($teams['AWAY']['selPlayers'][1]['handicap']) ? $teams['AWAY']['selPlayers'][1]['handicap'] : round($awayPlayer->getHandicap(), 0);

$homeHandicapPoints = $awayHandicap - $homeHandicap;
if($homeHandicapPoints < 0)
{
    $awayHandicapPoints = -($homeHandicapPoints) * $games_per_match;
    $homeHandicapPoints = 0;
}
else
{
    $homeHandicapPoints = $homeHandicapPoints * $games_per_match;
    $awayHandicapPoints = 0;
}

?>
<link rel='stylesheet' type='text/css' href="<?php print MY5280_PLUGIN_URL; ?>styles/scoresheet.css" />
<?php if($title): ?>
    <h2 class='subtitle'><?php print $title; ?></h2>
    <br />
<?php endif; ?>
<div class="scoresheet my8ballscotch">
    <div class='dateBox'>
        Date:
        <?php if($curMatch): ?>
            <div class='date'><?php print date('n/j/Y', strtotime($curMatch->getDate())); ?></div>
        <?php endif; ?>
    </div>
    <table class='table scoresheet'>
        <thead>
            <tr class='row'>
                <th class='cell header'>TEAM:</th>
                <th class='cell header'>
                    <?php print $homePlayer->getName(); ?>
                </th>
                <th class='cell header'>
                    <?php print $awayPlayer->getName(); ?>
                </th>
            </tr>
            <tr class='row'>
                <th class='cell header'>Handicap:</th>
                <th class='cell header'><?php print $homeHandicap; ?></th>
                <th class='cell header'><?php print $awayHandicap; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php for($iGame = 0; $iGame < $games_per_match; $iGame++): ?>
                <tr>
                    <td class='cell'>Game <?php print ($iGame + 1); ?></td>
                    <td class="cell score">
                        <?php if(isset($homeScores[$iGame])) print $homeScores[$iGame]; ?>
                    </td>
                    <td class="cell score AWAYgame<?php print $iGame; ?>" awayplayer="1">
                        <?php if(isset($awayScores[$iGame])) print $awayScores[$iGame]; ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr class='row handicaps'>
                <td class='cell playerName'>Handicap Points</td>
                <td class='cell handicap'><?php print $homeHandicapPoints; ?></td>
                <td class='cell handicap'><?php print $awayHandicapPoints; ?></td>
            </tr>
            <tr class='row totals'>
                <td class='cell playerName'>TOTALS</td>
                <td class='cell total'><?php if($homeTotal !== null) print $homeTotal; ?></td>
                <td class='cell total'><?php if($awayTotal !== null) print $awayTotal; ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type='text/javascript'>
    function my5280_getAwayGame(homeGame)
    {
        return homeGame;
    }

    jQuery(function() {
        my5280.init();
    });
</script>
