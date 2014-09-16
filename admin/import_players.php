<?php
if ( !current_user_can( 'manage_leaguemanager' ) ) :
	echo '<p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p>';
	
else :

    if(isset($_POST['import'])) {
        check_admin_referer('my5280_import-players');
        my5280::$instance->adminPanel->importPlayers($_FILES['my5280_import']);
        $leaguemanager->printMessage();
	}

?>
<div class='wrap'>
	<p class="leaguemanager_breadcrumb">
        <a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a>
        &raquo; 
        <?php _e( 'Import Players', 'leaguemanager' ) ?>
    </p>

	<div class="narrow">

    <p><?php _e('Please provide a players Excel file from which to import players.', 'my5280') ?></p>

    <form action="" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('my5280_import-players') ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="my5280_import"><?php _e('File','my5280') ?></label></th>
                <td>
                    <input type='file' name="my5280_import" id='my5280_import' size='40'/>
                </td>
            </tr>
        </table>

        <p class="submit"><input type="submit" name="import" value="<?php _e( 'Import Players' ); ?>" class="button" /></p>
    </form>
    </div>
</div>
<?php endif; ?>
