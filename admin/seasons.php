<?php
if ( !current_user_can( 'manage_leaguemanager' ) ) :
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
	
else :

    $league = $leaguemanager->getCurrentLeague();

    if(isset($_POST['import'])) {
        check_admin_referer('my5280_import-season');
        my5280::$instance->adminPanel->import($league->id, $_FILES['my5280_import'], $_POST['season_name']);
        $this->printMessage();
	} elseif ( isset($_POST['doaction']) ) {
		check_admin_referer('seasons-bulk');
		$league = $leaguemanager->getCurrentLeague();
		if ( 'delete' == $_POST['action'] ) {
			$this->delSeasons( $_POST['del_season'], $league->id );
		}
	}


?>

<div class='wrap'>
	<p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a> &raquo; <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo; <?php _e( 'Seasons', 'leaguemanager' ) ?></p>

	<div class="narrow">

	<h2><?php _e( 'Seasons', 'leaguemanager' ) ?></h2>
	<form id="seaons-filter" action="" method="post">
		<?php wp_nonce_field( 'seasons-bulk' ) ?>
		
		<div class="tablenav" style="margin-bottom: 0.1em;">
			<!-- Bulk Actions -->
			<select name="action" size="1">
				<option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
				<option value="delete"><?php _e('Delete')?></option>
			</select>
			<input type="submit" value="<?php _e('Apply'); ?>" name="doaction" id="doaction" class="button-secondary action" />
		</div>
		<table class="widefat">
		<thead>
		<tr>
			<th scope="col" class="check-column"><input type="checkbox" onclick="Leaguemanager.checkAll(document.getElementById('seaons-filter'));" /></th>
			<th scope="col"><?php _e( 'Season', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Match Days', 'leaguemanager' ) ?></th>
			<th scope="col"><?php _e( 'Actions', 'leaguemanager' ) ?></th>
		</tr>
		</thead>
		<tbody id="the-list">
			<?php if ( !empty($league->seasons) ) : ?>
			<?php foreach( (array)$league->seasons AS $key => $season ) : $class = ( 'alternate' == $class ) ? '' : 'alternate' ?>
			<tr class="<?php echo $class ?>">
				<th scope="row" class="check-column"><input type="checkbox" value="<?php echo $key ?>" name="del_season[<?php echo $key ?>]" /></th>
				<td><?php echo $season['name'] ?></td>
				<td><?php echo $season['num_match_days'] ?></td>
				<td><a href="admin.php?page=leaguemanager&amp;subpage=seasons&amp;league_id=<?php echo $league->id ?>&amp;edit=<?php echo $key ?>"><?php _e( 'Edit', 'leaguemanager' ) ?></a></td>
			</tr>
			<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		</table>
	</form>

	<h3>
		<?php if ( $season_id ) _e('Edit Season', 'leaguemanager'); else _e( 'Add New Season', 'leaguemanager' ) ?>
		<?php if ( $season_id ) : ?>
		(<a href="admin.php?page=leaguemanager&amp;subpage=seasons&amp;league_id=<?php echo $league->id ?>"><?php _e( 'Add New', 'leaguemanager') ?></a>)
		<?php endif; ?>
	</h3>

    <p><?php _e('Choose an XLSM file to upload and import as a new season for this league.', 'my5280') ?></p>
    
    <form action="" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('my5280_import-season') ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="my5280_import"><?php _e('File','my5280') ?></label></th>
                <td>
                    <input type='file' name="my5280_import" id='my5280_import' size='40'/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for='season_name'>Name</label>
                </th>
                <td>
                    <input type='text' name='season_name' id='season_name' size='40' />
                </td>
            </tr>
        </table>

        <p class="submit"><input type="submit" name="import" value="<?php _e( 'Upload file and import' ); ?>" class="button" /></p>
    </form>
    </div>
</div>
<?php endif; ?>
