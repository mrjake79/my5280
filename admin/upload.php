<?php
if ( !current_user_can( 'manage_leaguemanager' ) ) :
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
	
else :

    $league = $leaguemanager->getCurrentLeague();
    if(isset($_POST['upload'])) {
        check_admin_referer('my5280_upload-season');
        my5280::$instance->adminPanel->upload($league->id, $_FILES['my5280_upload'], $_POST['season_name']);
        $this->printMessage();
	}

?>
<div class='wrap'>
	<p class="leaguemanager_breadcrumb">
        <a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a>
        &raquo; 
        <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>">
            <?php echo $league->title ?>
        </a>
        &raquo;
        <?php _e( 'Upload', 'leaguemanager' ) ?>
    </p>

	<div class="narrow">

    <p><?php _e('Choose the XLSM file to replace the one for the current session.', 'my5280') ?></p>

    <form action="" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('my5280_upload-season') ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="my5280_upload"><?php _e('File','my5280') ?></label></th>
                <td>
                    <input type='file' name="my5280_upload" id='my5280_upload' size='40'/>
                </td>
            </tr>
        </table>

        <input type='hidden' name='season_name' value="<?php print htmlentities($_GET['season']); ?>" />
        <p class="submit"><input type="submit" name="upload" value="<?php _e( 'Upload file' ); ?>" class="button" /></p>
    </form>
    </div>
</div>
<?php endif; ?>
