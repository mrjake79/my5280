<form method="post">
    <?php if(count($changed) == 0): ?>
        <p>There are no corrections needed.</p>
    <?php else: ?>
        <?php if(!$saveChanges): ?>
            <p>The following corrections are needed for handicaps:</p>
        <?php else: ?>
            <p>The changes were made successfully:</p>
        <?php endif; ?>
        <table class="widefat">
            <thead>
                <tr>
                    <th scope="col">Player</th>
                    <th scope="col">Previous Games</th>
                    <th scope="col">Previous Points</th>
                    <th scope="col">Previous Handicap</th>
                    <th scope="col">New Games</th>
                    <th scope="col">New Points</th>
                    <th scope="col">New Handicap</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($changed as $p): ?>
                    <tr>
                        <td>
                            <?php print $p['name']; ?>
                            <?php if(count($p['otherPlayers'])): ?>
                                <div style='font-size: smaller; font-style: italic;'>Merging <?php print count($p['otherPlayers']); ?> Duplicate(s)</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php print $p['currentGames']; ?>
                        </td>
                        <td><?php print $p['currentPoints']; ?></td>
                        <td><?php print $p['currentHandicap']; ?></td>
                        <td><?php print $p['actualGames']; ?></td>
                        <td><?php print $p['actualPoints']; ?></td>
                        <td><?php print $p['actualHandicap']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if(!$saveChanges): ?>
            <p class="submit"><input type="submit" name="action" value="Update &raquo;" class="button" /></p>
        <?php endif; ?>
    <?php endif; ?>
</form>
