<p>The following changes were made to player handicaps:</p>
<table>
    <thead>
        <tr>
            <th>Player</th>
            <th>Previous Games</th>
            <th>Previous Points</th>
            <th>Previous Handicap</th>
            <th>New Games</th>
            <th>New Points</th>
            <th>New Handicap</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($changed as $p): ?>
            <tr>
                <td><?php print $p['name']; ?></td>
                <td><?php print $p['currentGames']; ?></td>
                <td><?php print $p['currentPoints']; ?></td>
                <td><?php print $p['currentHandicap']; ?></td>
                <td><?php print $p['actualGames']; ?></td>
                <td><?php print $p['actualPoints']; ?></td>
                <td><?php print $p['actualHandicap']; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
