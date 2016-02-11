<?php

if(!current_user_can('manage_leaguemanager')):
    echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';

elseif(isset($_GET['edit'])):
    include(__DIR__ . '/../../leaguemanager/admin/match.php');
else:
    // Get the session
    if(!isset($session)) {
        $league = $leaguemanager->getCurrentLeague();
        $session = my5280::$instance->getSession($league);
    }

    // Get the teams and determine the team numbers
    $teams = array();
    $noNumbers = array();
    foreach($session->listTeams() as $team) {
        $number = $team->getTeamNumber();
        if($number) {
            $teams[$number] = $team;
        } else {
            $noNumbers[] = $team;
        }
    }

    // Sort the teams and get the values so the indexes are zero-based
    ksort($teams);
    $teams = array_values($teams);

    // Add the unnumbered teams
    $teams = array_merge($teams, $noNumbers);

    // Build a lookup array for team numbers
    $teamLookup = array();
    foreach($teams as $index => $team) {
        $teamLookup[$team->getId()] = $index + 1;
    }

    // Determine the number of teams and weekly matches
    $count = count($teams);
    $weeklyMatches = ceil($count / 2);

    // Get the matches
    $matches = array();
    foreach($session->listMatches() as $match) {
        $date = $match->getDate();
        if(!isset($matches[$date])) $matches[$date] = array();
        $matches[$date][$match->getNumber()] = $match;
    }

    // Sort on date and get a full list of dates
    ksort($matches);
    $dates = array_keys($matches);

    // Determine the current number of match days
    $matchDays = $session->getMatchDays();
    if($matchDays == 0) {
        $matchDays = (ceil($count / 2) * 2) - 1;
    }

    // Get special dates
    $specialDates = array();
    foreach($session->listSpecialDates() as $date => $info) {
        $specialDates[] = array(
            'dateSort' => $date,
            'date' => date('n/j/Y', strtotime($date)),
            'description' => $info['description'],
            'noMatches' => !$info['matches'],
        );
    }
    usort($specialDates, function($a, $b) {
        return strcmp($a['dateSort'], $b['dateSort']);
    });

    // Add empty special dates
    for($i = count($specialDates); $i < 5; $i++) {
        $specialDates[] = array(
            'date' => '',
            'description' => '',
            'noMatches' => true,
        );
    }

?>
<style type='text/css'>
    table.schedule td { border: solid 1px; }
    table.schedule input { text-align: center; }
    td.number { text-align: center; width: 3em; }
    table.form th { text-align: left; }

    input.date { width: 7em; }
    input.description { width: 30em; }
</style>
<p class="leaguemanager_breadcrumb" style='clear: both;'>
    <a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> 
    &raquo; 
    <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $session->getLeagueId(); ?>">
        <?php echo $session->getLeagueName(); ?></a> 
        &raquo; Schedule
</p>
<form method='post' action="<?php print admin_url('admin-ajax.php'); ?>">
    <div style='float: right; width: 30em; clear: right;'>
        <h1>Team Numbers</h1>
        <p>Assign numbers to the teams for use in the schedule section.</p>
        <table class='teamNumbers'>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Team</th>
                    <th>Home</th>
                    <th>Away</th>
                    <th>BYE</th>
                </tr>
            </thead>
            <tbody>
                <?php for($iTeam = 1; $iTeam <= $count; $iTeam++): ?>
                    <tr>
                        <th><?php print $iTeam; ?></th>
                        <td>
                            <select class='teamNumber' name="teamNumbers[<?php print $iTeam; ?>]">
                                <?php foreach($teams as $team): ?>
                                    <option value="<?php print $team->getId(); ?>"
                                    <?php if(isset($teams[$iTeam-1]) && $teams[$iTeam-1]->getId() == $team->getId())
                                        print 'selected="selected"'; ?>
                                    ><?php print $team->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class='homeGames number'>0</td>
                        <td class='awayGames number'>0</td>
                        <td class='byeWeeks number'>0</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
    <div>
        <h1>Special Dates</h1>
        <p>These are dates for which a message will be displayed in the schedule.  This can be used for
        holidays to have the system automatically schedule around the date if the "No Matches" radio for the
        date is set to yes.</p>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th>No Matches</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($specialDates as $i => $date): ?>
                    <tr>
                        <td>
                            <input type='text' class='date' name="specialDates[<?php print $i; ?>][date]" 
                            value="<?php print $date['date']; ?>" />
                        </td>
                        <td>
                            <input type='text' class='description' name="specialDates[<?php print $i; ?>][description]" 
                            value="<?php print htmlentities($date['description']); ?>" />
                        </td>
                        <td>
                            <label>
                                <input type='radio' name="specialDates[<?php print $i; ?>][noMatches]"
                                value='1' <?php if($date['noMatches']) print "checked='checked'"; ?> />
                                Yes
                            </label>
                            <label>
                                <input type='radio' name="specialDates[<?php print $i; ?>][noMatches]"
                                value='0' <?php if(!$date['noMatches']) print 'checked="checked"'; ?> />
                                No
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div>
        <h1>Schedule</h1>
        <p>Use the team numbers to create or adjust the schedule.</p>
        <table class='schedule'>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th colspan="<?php print $weeklyMatches; ?>">Matches</th>
                </tr>
            </thead>
            <tbody id='schedule'>
                <?php for($i = 0; $i < $matchDays; $i++): ?>
                    <tr>
                        <td class='number'><?php print $i + 1; ?></td>
                        <td>
                            <input type='text' class='date' name="date[<?php print $i; ?>]" 
                            <?php if(isset($dates[$i])) print 'value="' . date('n/j/Y', strtotime($dates[$i])) . '"'; ?>
                            />
                        </td>
                        <?php for($iMatch = 0; $iMatch < $weeklyMatches; $iMatch++): ?>
                            <td>
                                <input type='number' name="homeTeam[<?php print $i; ?>][<?php print $iMatch; ?>]"
                                size='2' maxlength='2' min='1' max="<?php print $count; ?>" 
                                <?php if(isset($dates[$i]) && isset($matches[$dates[$i]][$iMatch])): ?>
                                    value="<?php print $matches[$dates[$i]][$iMatch]->getHomeTeam()->getTeamNumber(); ?>"
                                <?php endif; ?>
                                />
                                <input type='number' name="awayTeam[<?php print $i; ?>][<?php print $iMatch; ?>]"
                                size='2' maxlength='2' min='1' max="<?php print $count; ?>" 
                                <?php if(isset($dates[$i]) && isset($matches[$dates[$i]][$iMatch])): ?>
                                    value="<?php print $matches[$dates[$i]][$iMatch]->getAwayTeam()->getTeamNumber(); ?>"
                                <?php endif; ?>
                                />
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <button class='button' type='button' value='Add Week' id='addWeekButton'>+ Add a Week</button>
    </div>
    <p class="submit"><input type="submit" value="Save Schedule &raquo;" class="button" /></p>
    <div id='generatorForm' style='display: none;'>
        <h1>Settings</h1>
        <p>These settings can help you create the schedule faster.</p>
        <table class='form'>
            <tr>
                <th>Start Date</th>
                <td>
                    <input type='text' class='date' name='settings[startDate]' />
                </td>
            </tr>
            <tr>
                <th>Repeat Weekly</th>
                <td>
                    <label>
                        <input type='radio' name='settings[repeatWeekly]' value='1' checked='checked' />
                        Yes
                    </label>
                    <label>
                        <input type='radio' name='settings[repeatWeekly]' value='0' />
                        No
                    </label>
                </td>
            </tr>
            <tr>
                <th>Repeat Schedule</th>
                <td>
                    <label>
                        <input type='radio' name='settings[repeatSchedule]' value='1' checked='checked' />
                        Yes
                    </label>
                    <label>
                        <input type='radio' name='settings[repeatSchedule]' value='0' />
                        No
                    </label>
                </td>
            </tr>
        </table>
    </div>
    <input type='hidden' name='action' value='my5280_update_schedule' />
    <input type='hidden' name='league' value="<?php print $session->getLeagueId(); ?>" />
    <input type='hidden' name='season' value="<?php print $session->getName(); ?>" />
</form>
<script type='text/javascript' src="<?php print MY5280_PLUGIN_URL; ?>/javascript/schedule.js"></script>
<script type='text/javascript'>
    jQuery(function() {
        my5280Schedule.init();
    });
</script>
<?php endif; 
