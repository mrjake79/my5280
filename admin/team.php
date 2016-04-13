<?php
/**
 * Team edit form extension for selecting players.
 */

// Get an array of individuals from connections
$individuals = cnRetrieve::individuals();
foreach($individuals as $id => $name) {
    $parts = explode(', ', $name);
    $individuals[$id] = trim($parts[1] . ' ' . $parts[0]);
}
uasort($individuals, function($a, $b) {
    return strcasecmp($a, $b);
});

// Get the list of existing players
$players = array_keys($Team->listPlayers());

?>
<tr>
    <th scope='row'><?php _e('Players', 'my5280') ?></th>
    <td>
        <?php for($i = 0; $i < 10; $i++): ?>
            <div>
                <select name="my5280_players[]">
                    <option value="NONE">(None/No More)</option>
                    <?php foreach($individuals as $id => $name): ?>
                        <option value="<?php print $id; ?>"
                        <?php if(isset($players[$i]) && $players[$i] == $id) print 'selected="selected"'; ?>
                        ><?php print $name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endfor; ?>
        <input type='hidden' name="custom[number]" value="<?php print $Team->getTeamNumber(); ?>" />
    </td>
</tr>
