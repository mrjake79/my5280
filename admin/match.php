<form action="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id?>&amp;season=<?php echo $season['name'] ?><?php if(isset($group)) echo '&amp;group=' . $group; ?>" method="post">
  <?php wp_nonce_field( 'leaguemanager_manage-matches' ) ?>
    <?php if ( !$is_finals ) : ?>
  <table class="form-table">

    <?php if ( $cup && isset($group) ) : ?>
    <tr valign="top">
        <th scope="row"><input type="hidden" name="group" id="group" value="<?php echo $group ?>" /></th>
    </tr>
    <?php endif; ?>
    </table>
    <?php endif; ?>

    <p class="match_info"><?php if ( !$edit ) : ?><?php _e( 'Note: Matches with different Home and Guest Teams will be added to the database.', 'leaguemanager' ) ?><?php endif; ?></p>

    <table class="widefat">
        <thead>
            <tr>
                <?php if ( $bulk || $is_finals || ($mode=="add") || ($mode=="edit") ) : ?>
                <th scope="col"><?php _e( 'Date', 'leaguemanager' ) ?></th>
                <?php endif; ?>
                <?php if ( ($cup && !$is_finals) || ($mode=="add" && !$is_finals) ) : ?>
                <th scope="col"><?php _e( 'Day', 'leaguemanager' ) ?></th>
                <?php endif; ?>
                <th scope="col"><?php _e( 'Home', 'leaguemanager' ) ?></th>
                <th scope="col"><?php _e( 'Guest', 'leaguemanager' ) ?></th>
                <th scope="col"><?php _e( 'Location','leaguemanager' ) ?></th>
                <th scope="col"><?php _e( 'Begin','leaguemanager' ) ?></th>
                <?php do_action('edit_matches_header_'.$league->sport) ?>
            </tr>
        </thead>
        <tbody id="the-list" class="form-table">
        <?php for ( $i = 0; $i < $max_matches; $i++ ) : $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
        <tr class="<?php echo $class; ?>">
            <?php if ( $bulk || $is_finals || ($mode=="add") || $mode == "edit" ) : ?>
            <td><input type="text" name="mydatepicker[<?php echo $i ?>]" id="mydatepicker[<?php echo $i ?>]" class="mydatepicker" value="<?php if(isset($matches[$i]->date)) echo ( substr($matches[$i]->date, 0, 10) ) ?>" onChange="Leaguemanager.setMatchDate(this.value, <?php echo $i ?>, <?php echo $max_matches ?>);"></td>
            <?php endif; ?>
            <?php if (( $cup && !$is_finals) || ($mode=="add" && !$is_finals) ) : ?>
            <td>
                <select size="1" name="match_day[<?php echo $i ?>]" id="match_day_<?php echo $i ?>" onChange="Leaguemanager.setMatchDayPopUp(this.value, <?php echo $i ?>, <?php echo $max_matches ?>);">
                    <?php for ($d = 1; $d <= $season['num_match_days']; $d++) : ?>
                    <option value="<?php echo $d ?>"<?php if(isset($match_day) && $d == $match_day) echo ' selected="selected"' ?>><?php echo $d ?></option>
                    <?php endfor; ?>
                </select>
            </td>
            <?php endif; ?>
<!-- Home team pop up, only shows teams in a Group if set for 'Championship' -->
            <td>
                <select size="1" name="home_team[<?php echo $i ?>]" id="home_team_<?php echo $i ?>" onChange="Leaguemanager.insertHomeStadium(this.value, <?php echo $i ?>);">
                <?php $myTeam = 0; ?>
                <?php foreach ( $teams AS $team ) : ?>
                    <option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->home_team)) selected($team->id, $matches[$i]->home_team ) ?>><?php echo $team->title ?></option>
                    <?php if ( $myTeam==0 ) { $myHomeTeam = $team->id; } ?>
                    <?php $myTeam++; ?>
                <?php endforeach; ?>
                </select>
            </td>
<!-- Away team pop up, shows all teams in the league only if 'Allow non-group' check is set, otherwise only show teams in group, if set for 'Championship' -->
            <td>

                <?php if ( 1 == $non_group ) {  ?>

                    <select size="1" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" onChange="Leaguemanager.insertHomeStadium(document.getElementById('home_team_<?php echo $i ?>').value, <?php echo $i ?>);">
                    <?php foreach ( $teamsHome AS $team ) : ?>
                        <?php if ( isset($matches[$i]->away_team) ) { ?>
                            <option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->away_team)) selected( $team->id, $matches[$i]->away_team ) ?>><?php echo $team->title ?></option>
                        <?php } elseif ( $team->id == $myHomeTeam ) { ?>
<!-- BUILD THE 'SELECTED' ITEM IN THE POP-UP -->
                            <option value="<?php echo $team->id ?>" selected='selected'><?php echo $team->title ?></option>

                        <?php } else { ?>
                                <option value="<?php echo $team->id ?>"><?php echo $team->title ?></option>
                        <?php }
                    endforeach; ?>
                    </select>
                <?php } else { ?>
                    <select size="1" name="away_team[<?php echo $i ?>]" id="away_team_<?php echo $i ?>" onChange="Leaguemanager.insertHomeStadium(document.getElementById('home_team_<?php echo $i ?>').value, <?php echo $i ?>);">
                    <?php foreach ( $teams AS $team ) : ?>
                        <option value="<?php echo $team->id ?>"<?php if(isset($matches[$i]->away_team)) selected( $team->id, $matches[$i]->away_team ) ?>><?php echo $team->title ?></option>
                    <?php endforeach; ?>
                    </select>
                <?php } ?>

            </td>
            <td><input type="text" name="location[<?php echo $i ?>]" id="location[<?php echo $i ?>]" size="20" value="<?php if(isset($matches[$i]->location)) echo stripslashes($matches[$i]->location) ?>" size="30" /></td>
            <td>
                <select size="1" name="begin_hour[<?php echo $i ?>]">
                <?php for ( $hour = 0; $hour <= 23; $hour++ ) : ?>
                    <option value="<?php echo (isset($hour)) ? str_pad($hour, 2, 0, STR_PAD_LEFT) : 00 ?>"<?php (isset($matches[$i]->hour)) ? selected( $hour, $matches[$i]->hour ) : '' ?>><?php echo (isset($hour)) ? str_pad($hour, 2, 0, STR_PAD_LEFT) : 00 ?></option>
                <?php endfor; ?>
                </select>
                <select size="1" name="begin_minutes[<?php echo $i ?>]">
                <?php for ( $minute = 0; $minute <= 60; $minute++ ) : ?>
                    <?php if ( 0 == $minute % 5 && 60 != $minute ) : ?>
                    <option value="<?php echo (isset($minute)) ? str_pad($minute, 2, 0, STR_PAD_LEFT) : 00 ?>"<?php (isset($matches[$i]->minutes)) ? selected( $minute, $matches[$i]->minutes ) : '' ?>><?php echo (isset($minute)) ? str_pad($minute, 2, 0, STR_PAD_LEFT) : 00 ?></option>
                    <?php endif; ?>
                <?php endfor; ?>
                </select>
                </td>
            <?php do_action('edit_matches_columns_'.$league->sport, (isset($matches[$i]) ? $matches[$i] : ''), $league, $season, $teams, $i) ?>
        </tr>
        <input type="hidden" name="match[<?php echo $i ?>]" value="<?php echo $matches[$i]->id ?>" />
        <?php endfor; ?>
        </tbody>
    </table>

    <input type="hidden" name="mode" value="<?php echo $mode ?>" />
    <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
    <input type="hidden" name="season" value="<?php echo $season['name'] ?>" />
    <input type="hidden" name="final" value="<?php echo $finalkey ?>" />
    <input type="hidden" name="updateLeague" value="match" />

    <p class="submit"><input type="submit" value="<?php echo $submit_title ?> &raquo;" class="button" /></p>
</form>
