<?php
/*
Plugin Name: Instagram For WordPress Teams
Plugin URI: http://spark6.com
Description: Import photos from Instagram to a custom post type.
Author URI: http://spark6.com
Author: SPARK6
Version: 1.0
*/

define("ICP_PLUGIN_NAME", "Instagram For WordPress Teams", true);
define("ICP_API_KEY", "05d30c376a62455fa2682361b54a142c", true);
define("ICP_AUTH_URL", "http://www.spark6.com/plugins/instagram_auth", true);
define("ICP_POST_TYPE", "wpteam_instagram", true);

require_once ('lib/instagram.class.php');

register_activation_hook(__FILE__, 'icp_activation');

add_action('icp_user_photos', 'icp_get_user_photos');
add_action('icp_hashtag_photos', 'icp_get_hashtag_photos');

function icp_activation() {
	wp_schedule_event( current_time( 'timestamp' ), 'twicedaily', 'icp_user_photos');
	wp_schedule_event( current_time( 'timestamp' ), 'twicedaily', 'icp_hashtag_photos');
	update_option('icp_import_interval','twicedaily');
}

register_deactivation_hook(__FILE__, 'icp_deactivation');

function icp_deactivation() {
	wp_clear_scheduled_hook('icp_user_photos');
	wp_clear_scheduled_hook('icp_hashtag_photos');
	icp_remove_wp_pointers();
}

function icp_remove_wp_pointers(){

	$admin_users = get_users('role=administrator');
    foreach ($admin_users as $admin_user):
        
		$user_meta = get_user_meta( $admin_user->ID, 'dismissed_wp_pointers' );
		$pointers = explode(',', $user_meta[0]);

		$indexPointer = array_search('wpteam_instagram', $pointers);
		if($indexPointer!==false):
			unset($pointers[$indexPointer]);
			$new_pointers = implode(',', $pointers);			
			update_user_meta($admin_user->ID, 'dismissed_wp_pointers', $new_pointers );
		endif;

    endforeach;

}

function icp_register_settings() {

	$settings = get_option( "icp_settings" );
	if ( empty( $settings ) ) {
		$settings = array(
			'icp_auth' => 'no',
			'icp_access_token' => '',
			'icp_auth_user_id' => '',
			'icp_auth_user' => '',
			'icp_post_type' => ICP_POST_TYPE,
			'icp_import_interval' => 'twicedaily',
			'icp_import_limit' => '20',
			'icp_user' => array(),
			'icp_user_id' => array(),
			'icp_hashtag' => array(),
			'icp_user_public_hashtag' => '',
			'icp_rename_post_singular' => 'Photo',
			'icp_rename_post_plural' => 'Photos',
			'icp_post_status' => 'draft',
			'icp_featured_image' => ''
		);
		add_option( "icp_settings", $settings, '', 'yes' );
	}	

}
 
add_action( 'admin_init', 'icp_register_settings' );

add_action('admin_menu', 'icp_plugin_settings');

function icp_shortcode($atts){

	extract( shortcode_atts(
		array(
			'photos' => '12',
			'lightbox' => 'yes',
			'class' => ' wpteam_instagram_photo',
			'style' => 'yes'
		), $atts )
	);	

	$settings = get_option( "icp_settings" );

	$args = array(
		'post_type' => $settings['icp_post_type'],
		'posts_per_page' => $photos
	);

	$icp_html = '';

	$icp_photos_query = new WP_Query( $args );	

	if ( $icp_photos_query->have_posts() ) {
	    $icp_html = '<div class="wp-instagram-grid">';
		while ( $icp_photos_query->have_posts() ) {
			$icp_photos_query->the_post();
			if ( has_post_thumbnail() ):
				$icp_html .= '<div class="wp-instagram-item '.$class.'">' . get_the_post_thumbnail(get_the_ID(), 'medium') . '</div>';
			else:
				$icp_html .= '<div class="wp-instagram-item '.$class.'">' . get_the_content() . '</div>';
			endif;
		}
	    $icp_html .= '</div>';
	} else {
		$icp_html = 'No photos found. ';
	}

	if($style==='yes'):
		$icp_html .= '<link href="'.plugins_url( '/css/icp-grid.css', __FILE__ ).'" rel="stylesheet">';
	endif;

	wp_reset_postdata();

	return $icp_html;
}

add_shortcode('wpteam_instagram', 'icp_shortcode');

function icp_init(){
	load_plugin_textdomain( 'icpinstagram', false, dirname( plugin_basename( __FILE__ ) ) );
}

function icp_load_css_js(){

    wp_enqueue_script( 'icp_plugins_js', plugins_url( '/js/icp-plugins.js', __FILE__ ), array('jquery') );
    wp_enqueue_script( 'icp_main_js', plugins_url( '/js/icp-main.js', __FILE__ ), array('jquery') );
    wp_enqueue_style( 'icp_css', plugins_url( '/css/icp.css', __FILE__ ) );

}

add_action('admin_enqueue_scripts', 'icp_load_css_js');

// DEV TIMING
function my_add_oneminute( $schedules ) {
	$schedules['oneminute'] = array(
		'interval' => 60,
		'display' => __('Once every 60 seconds')
	);
	return $schedules;
}

add_filter( 'cron_schedules', 'my_add_oneminute' ); 

// General Settings Pages
function icp_plugin_settings() {
    $settings_page = add_menu_page( __('Instagram WP', 'icpinstagram'), __('Instagram WP', 'icpinstagram'), 'administrator', 'wp_instagram', 'icp_display_settings', plugins_url( 'images/icon.png' , __FILE__ ) );
	add_action( "load-{$settings_page}", 'icp_load_settings_page' );
}

function icp_load_settings_page() {
	if(isset($_POST["icp-settings-submit"]))
		if ( $_POST["icp-settings-submit"] == 'Y' ) {
			check_admin_referer( "icp-settings-page" );
			icp_save_theme_settings();
			$url_parameters = isset($_GET['tab'])? 'updated=true&tab='.$_GET['tab'] : 'updated=true';
			wp_redirect(admin_url('admin.php?page=wp_instagram&'.$url_parameters));
			exit;
		}
}


function icp_save_theme_settings() {
	global $pagenow;
	$settings = get_option( "icp_settings" );
	
	if ( $pagenow == 'admin.php' && $_GET['page'] == 'wp_instagram' ){ 
		if ( isset ( $_GET['tab'] ) )
	        $tab = $_GET['tab']; 
	    else
	        $tab = 'homepage'; 

	    switch ( $tab ){ 
	        case 'homepage' :
				$settings['icp_user'] 			= $_POST['icp_user'];
				$settings['icp_user_id'] 		= $_POST['icp_user_id'];
				$settings['icp_hashtag'] 		= $_POST['icp_hashtag'];
				$settings['icp_public_hashtag'] = $_POST['icp_public_hashtag'];
			break; 
	        case 'post_type' : 
				$settings['icp_post_type'] 				= $_POST['icp_post_type'];
				$settings['icp_rename_post_singular'] 	= $_POST['icp_rename_post_singular'];
				$settings['icp_rename_post_plural'] 	= $_POST['icp_rename_post_plural'];
			break;
			case 'options' : 

				$old_interval = $settings['icp_import_interval'];
				if($old_interval!==$_POST['icp_import_interval']):
					wp_clear_scheduled_hook('icp_user_photos');
					wp_clear_scheduled_hook('icp_hashtag_photos');
					wp_schedule_event( current_time( 'timestamp' ), $_POST['icp_import_interval'], 'icp_user_photos');
					wp_schedule_event( current_time( 'timestamp' ), $_POST['icp_import_interval'], 'icp_hashtag_photos');
				endif;

				$settings['icp_post_status']	  = $_POST['icp_post_status'];
				$settings['icp_featured_image']	  = $_POST['icp_featured_image'];
				$settings['icp_import_limit']	  = $_POST['icp_import_limit'];
				$settings['icp_import_interval']  = $_POST['icp_import_interval'];

			break;
	    }
	}

	$updated = update_option( "icp_settings", $settings );
}


function icp_admin_tabs( $current = 'homepage' ) { 
    $tabs = array( 'homepage' => 'Hashtags & Users', 'post_type' => 'Post Type Configuration', 'options' => 'Plugin Options', 'unlink'=> 'Unlink Account', 'help' => 'Help' ); 
    $links = array();
    echo '<h2 class="nav-tab-wrapper icpNavTab">';
    foreach( $tabs as $tab => $name ){
        $class = ( $tab == $current ) ? ' nav-tab-active' : '';
        echo "<a class='nav-tab$class' href='?page=wp_instagram&tab=$tab'>$name</a>";
        
    }
    echo '</h2>';
}

add_filter( 'icp_admin_pointers-toplevel_page_wp_instagram', 'icp_register_pointer_welcome' );
function icp_register_pointer_welcome( $p ) {
    $p['wpteam_instagram'] = array(
        'target' => '#icpwelcomeTitle',
        'options' => array(
            'content' => sprintf( '<h3> %s </h3> <p> %s </p>',
                __( 'Thanks for choosing Instagram For WordPress Teams!' ,'plugindomain'),
                __( '<p><b>About This Plugin</b><br />
This plugin was created by SPARK6, is a creative agency located in Santa Monica, Ca. We believe in leveraging technology to reduce human suffering. Learn more at <a href="http://www.spark6.com" target="_blank">www.spark6.com</a>. If you have any suggestions on how this plugin can be improved please feel free to contact us at <a href="mailto:hello@spark6.com">hello@spark6.com</a>.</p>

<p><b>Like this plugin?</b><br />
If you like this plugin, please rate it 5 star on Wordpress.org! We hope you do!</p>

<p><b>Contribute!</b><br />
If you would like to extend the functionality of Instagram For WordPress Teams and are a developer, head over to our Github repository to download the full source code.</p>

<p><b>Stay Updated</b><br />
If you would like to keep up to date regarding Instagram For WordPress Teams plugin and other plugins by SPARK6, subscribe to our newsletter:</p>
<!-- Begin MailChimp Signup Form -->

<div id="mc_embed_signup">
<form action="http://spark6.us8.list-manage.com/subscribe/post?u=3f570c3013887ad5074dec610&amp;id=8456f2c27c" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" style="padding:15px;" class="validate" target="_blank" novalidate>
	
	<input style="width:100%;" type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email address" required>
	<br><br>
    <!-- real people should not fill this in and expect good things - do not remove this or risk form bot signups-->
    <div style="position: absolute; left: -5000px;"><input type="text" name="b_3f570c3013887ad5074dec610_8456f2c27c" value=""></div>
	<div class="clear"><input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button"></div>
</form>
</div>

<!--End mc_embed_signup-->
	','icpinstagram')
            ),
            'position' => array( 'edge' => 'top', 'align' => 'left' )
        )
    );
    return $p;
}	

function icp_display_settings() {

	global $pagenow;
	$settings 				= get_option( "icp_settings" );

	$icp_user 				= ( isset($settings['icp_user']) ? $settings['icp_user'] : '' );
	$icp_user_id 			= ( isset($settings['icp_user_id']) ? $settings['icp_user_id'] : '' );
	$icp_hashtag 			= ( isset($settings['icp_hashtag']) ? $settings['icp_hashtag'] : '' );
	$icp_public_hashtag 	= ( isset($settings['icp_public_hashtag']) ? $settings['icp_public_hashtag'] : '' );

	$icp_post_type 			= ( isset($settings['icp_post_type']) ? $settings['icp_post_type'] : '' );
	$icp_post_singular 		= ( isset($settings['icp_rename_post_singular']) ? $settings['icp_rename_post_singular'] : 'Photo' );
	$icp_post_plural 		= ( isset($settings['icp_rename_post_plural']) ? $settings['icp_rename_post_plural'] : 'Photos' );

	$icp_post_status 		= ( isset($settings['icp_post_status']) ? $settings['icp_post_status'] : '' );
	$icp_featured_image 	= ( isset($settings['icp_featured_image']) ? $settings['icp_featured_image'] : '' );
	$icp_import_limit 		= ( isset($settings['icp_import_limit']) ? $settings['icp_import_limit'] : '' );
	$icp_import_interval 	= ( isset($settings['icp_import_interval']) ? $settings['icp_import_interval'] : '' );


	?>
	
	<div class="wrap">
		<h2 id="icpwelcomeTitle"><?php echo ICP_PLUGIN_NAME; ?></h2>

		<?php
			if(isset($_GET['unlink'])):
				if($_GET['unlink']==='true'):
					$settings = get_option( "icp_settings" );
					$settings['icp_auth'] = 'no';
					$settings['icp_access_token'] = '';
					$settings['icp_auth_user_id'] = '';
					$settings['icp_auth_user'] 	  = '';

					$updated = update_option( "icp_settings", $settings );
		?>
			<div id="setting-error-settings_updated" class="updated settings-error"> 
				<p><strong><?php echo __( 'Your instagram access token was deleted, but you also need to revoke permissions from this plugin, <a href="https://instagram.com/accounts/manage_access" target="_blank">click here</a> and revoke access to the "Instagram for Wordpress Teams" app.', 'icpinstagram' ); ?></strong></p>
			</div>
		<?php
				endif;
			endif;
		?>
		
		<?php
			if (isset($_GET['updated']))
			if ( 'true' == esc_attr( $_GET['updated'] ) ):
		?>
		<div id="setting-error-settings_updated" class="updated settings-error"> 
			<p><strong><?php echo __( 'Settings saved.', 'icpinstagram' ); ?></strong></p>
		</div>
		<?php
			endif;
		?>

		<?php if(isset($_GET['icp_auth'])): ?>
			<?php 
				if($_GET['icp_auth']==='success'): 

					$settings = get_option( "icp_settings" );
					$settings['icp_auth'] = 'yes';
					$settings['icp_access_token'] = urldecode($_GET['access_token']);
					$settings['icp_auth_user_id'] = urldecode($_GET['user_id']);
					$settings['icp_auth_user'] 	  = urldecode($_GET['username']);

					$updated = update_option( "icp_settings", $settings );
			?>
				<div id="setting-error-settings_updated" class="updated settings-error"> 
					<p><strong><?php echo __( 'Authorization succeeded!', 'icpinstagram' ); ?></strong></p>
				</div>
			<?php else: ?>
				<div id="setting-error-settings_updated" class="error settings-error"> 
					<p><strong><?php echo __( 'There was an error, try again...', 'icpinstagram' ); ?></strong></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php
			if($settings['icp_auth']==='no'):
				$icp_redirect_url = icp_get_current_url();

		?>
			<p><?php echo __( 'Click to be taken to Instagram\'s site to securely authorize this plugin for use with your account.', 'icpinstagram' ); ?></p>
			<a href="<?php echo ICP_AUTH_URL; ?>?redirect_url=<?php echo $icp_redirect_url; ?>" target="_self" class="button-primary authenticate"><?php echo __( 'Secure Authentication', 'icpinstagram' ); ?></a>
		<?php
			else:
		?>

			<?php
				if ( isset ( $_GET['tab'] ) ) icp_admin_tabs($_GET['tab']); else icp_admin_tabs('homepage');
			?>

			<div id="poststuff">
				<form class="icp_settings_form" method="post" action="<?php admin_url( 'admin.php?page=wp_instagram' ); ?>">

				<?php
					wp_nonce_field( "icp-settings-page" ); 
				
					if ( $pagenow == 'admin.php' && $_GET['page'] == 'wp_instagram' ){ 
					
						if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; 
						else $tab = 'homepage'; 
						
						echo '<table id="icpMainTable" class="form-table">';
						$no_save = false;
						switch ( $tab ){
							case 'homepage' :
							
							$icp_total_users = count($icp_user);

					?>
								<tr valign="top">
									<td colspan="2">
										<h2>Team &amp; Tags</h2>
										<hr>
										<p>This is where your Instagram uses are managed. Click <span id="icpUserLabel">"Add New Team Member"</span> to add <span id="icpUserNumber">your first one</span>.</p>
									</td>
								</tr>
					<?php
							$icp_user_fields = 10;
							$active_users = 0;

							for($i = 1; $i <= $icp_total_users; $i++):

								$user 	 = ( isset($icp_user[$i-1]) ? $icp_user[$i-1] : '' );
								$user_id = ( isset($icp_user_id[$i-1]) ? $icp_user_id[$i-1] : '' );
								$hashtag = ( isset($icp_hashtag[$i-1]) ? $icp_hashtag[$i-1] : '' );

								
								if( ( !empty($user) && !empty($user_id) ) ):
									$active_users++;
					?>
								
								<tr valign="top" class="icp_user_hashtag">
									<td scope="row">

										<table class="form-table icp-hover-table"> 
											<tr valign="top">
												<td scope="row"><label>Instagram Username:</label></th>
												<td>
													<input name="icp_user[]" type="text" id="icp_user" class="regular-text icp-float-left icp-UserValidation" value="<?php echo $user; ?>" placeholder="johndoe">
													<div class="icp-live-icon">
														<img src="<?php echo plugins_url( '/images/loading.gif', __FILE__ ) ?>" class="icp-loading hidden" />
														<img src="<?php echo plugins_url( '/images/yes.gif', __FILE__ ) ?>" class="icp-yes hidden" />
														<img src="<?php echo plugins_url( '/images/no.png', __FILE__ ) ?>" class="icp-no hidden" />
													</div>
													<input name="icp_user_id[]" type="hidden" id="icp_user_id" value="<?php echo $user_id; ?>">
													<div class="clearfix"></div>
													<a href="#" class="icp-trash"><img src="<?php echo plugins_url( '/images/trash.png', __FILE__ ) ?>"  /></a>
												</td>
											</tr>
											<tr valign="top" >
												<td scope="row"><label>Import photos tagged:</label></th>
												<td>
													<input name="icp_hashtag[]" type="text" id="icp_hashtag" class="regular-text" value="<?php echo $hashtag; ?>" placeholder="cats,dogs,parrots">
													<p class="description"><?php echo __( 'Insert the hashtags without # and separated by comma, don\'t use blank spaces.', 'icpinstagram' ); ?></p>
												</td>
											</tr>
											
										</table>

									</td>
									
								</tr>
								<tr valign="top" class="icp_user_tr">
									<td colspan="2">
										<hr>
									</td>
								</tr>
											
					<?php
								endif;
							endfor;

							if($active_users==0):
					?>
								<tr valign="top" class="icp_user_hashtag hidden">
									<td scope="row">

										<table class="form-table icp-hover-table"> 
											<tr valign="top">
												<td scope="row"><label>Instagram Username:</label></th>
												<td>
													<input name="icp_user[]" type="text" id="icp_user" class="regular-text icp-float-left icp-UserValidation" value="<?php echo $user; ?>" placeholder="johndoe">
													<div class="icp-live-icon">
														<img src="<?php echo plugins_url( '/images/loading.gif', __FILE__ ) ?>" class="icp-loading hidden" />
														<img src="<?php echo plugins_url( '/images/yes.gif', __FILE__ ) ?>" class="icp-yes hidden" />
														<img src="<?php echo plugins_url( '/images/no.png', __FILE__ ) ?>" class="icp-no hidden" />
													</div>
													<input name="icp_user_id[]" type="hidden" id="icp_user_id" value="<?php echo $user_id; ?>">
													<div class="clearfix"></div>
													<a href="#" class="icp-trash"><img src="<?php echo plugins_url( '/images/trash.png', __FILE__ ) ?>"  /></a>
												</td>
											</tr>
											<tr valign="top" >
												<td scope="row"><label>Import photos tagged:</label></th>
												<td>
													<input name="icp_hashtag[]" type="text" id="icp_hashtag" class="regular-text" value="<?php echo $hashtag; ?>" placeholder="cats,dogs,parrots">
													<p class="description"><?php echo __( 'Insert the hashtags without # and separated by comma, don\'t use blank spaces.', 'icpinstagram' ); ?></p>
												</td>
											</tr>
											
										</table>

									</td>
									
								</tr>
								<tr valign="top" class="icp_user_tr hidden">
									<td colspan="2">
										<hr>
									</td>
								</tr>
					<?php
							endif;
					?>
							</table>
							<table class="form-table">
								<tr valign="top">
									<td  scope="row">
										<?php if($icp_user_fields==$active_users): ?>
										<a href="#" class="button-secondary" id="icp-addUser">Add New Team Member</a>
										<?php else: ?>
										<a href="#" class="button-secondary" id="icp-addUser">Add Another Team Member</a>
										<?php endif; ?>
									</td>
								</tr>
								<tr valign="top">
									<td colspan="2">
										<h2>Public Tags</h2>
										<hr>
										<p>Tags added here will import any photo found on Instagram matching these tags, even if they are not owned by a team member above. We recommend setting “Default Post Status” (under the Import Options tab) to “Draft”, “Pending” or “Private” if you use this option. </p>
									</td>
								</tr>
								<tr valign="top">
									<td scope="row">
										<table class="form-table">
											<tr valign="top">
												<td scope="row">
													<label for="icp_hashtag">Publicly Searchable Tags:</label>
												</td>
												<td>
													<input name="icp_public_hashtag" type="text" id="icp_public_hashtag" class="regular-text" value="<?php echo $icp_public_hashtag; ?>" placeholder="cats,dogs,parrots">
													<p class="description"><?php echo __( 'Insert the hashtags without # and separated by comma, don\'t use blank spaces.', 'icpinstagram' ); ?></p>
												</td>
											</tr>
											<tr valign="top">
												<td colspan="2">
													<hr>
												</td>
											</tr>
										</table>
									</td>
												
								</tr>
								
								<?php
							break; 
							case 'post_type' : 
								?>
								<tr>
									<th scope="row"><label for="icp_post_type"><?php echo __( 'Import to Post Type', 'icpinstagram' ); ?></label></th>
									<td>
										<select name="icp_post_type" id="icp_post_type">
											<?php
												$args = array(
												   'public'   => true
												);

												$post_types = get_post_types( $args ); 
												$current_post_type = isset( $icp_post_type ) ? $icp_post_type : '';
												foreach ( $post_types  as $post_type ):
											?>
													<option value="<?php echo $post_type; ?>" <?php selected( $current_post_type, $post_type ); ?> ><?php echo $post_type; ?></option>
											<?php
												endforeach;
											?>
										</select>
										<p class="description"><?php echo __( 'Choose the post type that all photos will be imported as.', 'icpinstagram' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="icp_rename_post_singular">Rename Post Type Singular:</label></th>
									<td>
										<input name="icp_rename_post_singular" type="text" id="icp_rename_post_singular" class="regular-text" value="<?php echo $icp_post_singular; ?>" placeholder="Photo">
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="icp_rename_post_plural">Rename Post Type Plural:</label></th>
									<td>
										<input name="icp_rename_post_plural" type="text" id="icp_rename_post_plural" class="regular-text" value="<?php echo $icp_post_plural; ?>" placeholder="Photos">
									</td>
								</tr>
								<tr valign="top">
									<td colspan="2">
										<hr>
									</td>
								</tr>
								<?php
							break;
							case 'options' : 
								?>
								<tr valign="top">
									<th scope="row"><label for="icp_post_status"><?php echo __( 'Default post status', 'icpinstagram' ); ?></label></th>
									<td>
										<select name="icp_post_status" id="icp_post_status">
											<?php
												$draft_status = $icp_post_status;
											?>
											<option value="draft" <?php selected( $draft_status, 'draft' ); ?>>
												<?php _e( 'Draft', 'icpinstagram' ); ?>
											</option>
											<option value="publish" <?php selected( $draft_status, 'publish' ); ?>>
												<?php _e( 'Published', 'icpinstagram' ); ?>
											</option>
											<option value="pending" <?php selected( $draft_status, 'pending' ); ?>>
												<?php _e( 'Pending', 'icpinstagram' ); ?>
											</option>
											<option value="private" <?php selected( $draft_status, 'private' ); ?>>
												<?php _e( 'Private', 'icpinstagram' ); ?>
											</option>
										</select>
										<p class="description"><?php echo __( 'Choose the post status of all the imported photos.', 'icpinstagram' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><?php echo __( 'Featured Image?', 'icpinstagram' ); ?></th>
									<td>
										<label for="icp_featured_image">
											<input name="icp_featured_image" type="checkbox" id="icp_featured_image" value="yes" <?php checked( $icp_featured_image, 'yes'); ?>>
											<?php echo __( 'Save imported image as featured image.', 'icpinstagram' ); ?>
										</label>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="icp_import_limit"><?php echo __( 'Import Quantity', 'icpinstagram' ); ?></label></th>
									<td>
										<select name="icp_import_limit" id="icp_import_limit">
											<?php
												$limit_saved = $icp_import_limit;
											?>
											<option value="20" <?php selected( $limit_saved, '20' ); ?>>
												<?php _e( '20 Photos', 'icpinstagram' ); ?>
											</option>
											<option value="40" <?php selected( $limit_saved, '40' ); ?>>
												<?php _e( '40 Photos', 'icpinstagram' ); ?>
											</option>
											<option value="60" <?php selected( $limit_saved, '60' ); ?>>
												<?php _e( '60 Photos', 'icpinstagram' ); ?>
											</option>
											<option value="80" <?php selected( $limit_saved, '80' ); ?>>
												<?php _e( '80 Photos', 'icpinstagram' ); ?>
											</option>
											<option value="100" <?php selected( $limit_saved, '100' ); ?>>
												<?php _e( '100 Photos', 'icpinstagram' ); ?>
											</option>
											<option value="200" <?php selected( $limit_saved, '200' ); ?>>
												<?php _e( '200 Photos', 'icpinstagram' ); ?>
											</option>
										</select>
										<p class="description"><?php echo __( 'Choose the number of items to query per API call.', 'icpinstagram' ); ?></p>
									</td>
								</tr>
								<tr valign="top">
									<th scope="row"><label for="icp_import_interval"><?php echo __( 'Import Interval', 'icpinstagram' ); ?></label></th>
									<td>
										<select name="icp_import_interval" id="icp_import_interval">
											<?php
												$import_interval = $icp_import_interval;
											?>
											<option value="oneminute" <?php selected( $import_interval, 'oneminute' ); ?>>
												<?php _e( 'Every Minute', 'icpinstagram' ); ?>
											</option>
											<option value="hourly" <?php selected( $import_interval, 'hourly' ); ?>>
												<?php _e( 'Once Hourly', 'icpinstagram' ); ?>
											</option>
											<option value="twicedaily" <?php selected( $import_interval, 'twicedaily' ); ?>>
												<?php _e( 'Twice Daily', 'icpinstagram' ); ?>
											</option>
											<option value="daily" <?php selected( $import_interval, 'daily' ); ?>>
												<?php _e( 'Once Daily', 'icpinstagram' ); ?>
											</option>
										</select>
									</td>
								</tr>
								<?php
							break;
							case 'help' : 
								$no_save = true;
							?>	
								<tr valign="top">
									<th scope="row"><label>How to use Shortcodes:</label></th>
									<td>
										<p>Include the shortcode <b>[wpteam_instagram]</b> in the page you want to show the grid.</p><br/>
										<p>There is an option to limit the number of photos that appear on the page (showing the newest first). You can enable this functionality by include photos=x in the shortcode. Example: <b>[wpteam_instagram photos=12]</b></p><br />
										<p>There is also an option to add a custom class to the images. You can enable this by adding class=somecustomclass to the shortcode. Example: <b>[wpteam_instagram photos=12 class=somecustomclass]</b></p>
									</td>
								</tr>
								<th scope="row"><label>About This Plugin:</label></th>
									<td>
										<p>This plugin was created by SPARK6, is a creative agency located in Santa Monica, Ca. We believe in leveraging technology to reduce human suffering. Learn more at <a href="http://www.spark6.com" target="_blank">www.spark6.com</a>. If you have any suggestions on how this plugin can be improved please feel free to contact us at <a href="mailto:hello@spark6.com">hello@spark6.com</a>.</p>
									</td>
								</tr>
								
								<tr valign="top">
									<td colspan="2">
										<hr>
									</td>
								</tr>
							<?php
							break;
							case 'unlink' : 
								$no_save = true;
							?>	
								<tr valign="top">
									<td colspan="2">
										<h2>Unlink Your Instagram Account</h2>
										<hr>
										<p>Use this screen to unlink your Instagram Account. If you proceed no new images will be pullsed from Instagram and you will need to reactive an account.</p>
										<br><br>
										<a href="admin.php?page=wp_instagram&unlink=true" id="icp-unlinkAccount" class="button-primary red">Unlink Instagram Account</a>
									</td>
								</tr>
								
							<?php
							break;
						}
						echo '</table>';
					}
				
					?>
					<p class="submit" style="clear: both;">
						<?php if($no_save!==true): ?>
						<input type="submit" name="Submit" id="icp-submitForm"  class="button-primary" value="Save Settings" />
						<?php endif; ?>
						<input type="hidden" name="icp-settings-submit" value="Y" />
					</p>

				</form>
				
			</div>
		<?php
			endif;
		?>
	</div>
<?php	
}

function icp_get_current_url() {	
	// As seen on http://stackoverflow.com/a/1229924/789960
	$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80"){
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	$pageURL = icp_remove_querystring_var($pageURL, 'unlink');
	return urlencode($pageURL);
}

function icp_remove_querystring_var($url, $key) { 
	// As seen on http://davidwalsh.name/php-remove-variable
	$url = preg_replace('/(.*)(?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&'); 
	$url = substr($url, 0, -1); 
	return $url; 
}

add_action('wp_ajax_icp_check_user_id', 'icp_check_user_id');

function icp_check_user_id() {

	if(empty($_POST['username'])):
		echo 'false';
		die();
	endif;
		
	$username = strtolower($_POST['username']); 
	$settings = get_option( "icp_settings" );
    $token = $settings['icp_access_token'];
    $url = "https://api.instagram.com/v1/users/search?q=".$username."&access_token=".$token;
    
	if(function_exists('curl_init')) {  
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL,$url);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($ch, CURLOPT_HEADER, 0);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);   
        $output = curl_exec($ch); 
        curl_close($ch);  
    } else{  
        $output = file_get_contents($url);  
    }  

    $json = json_decode($output);

    foreach($json->data as $user):
        if($user->username === $username){
            echo $user->id;
            die();
        }
    endforeach;

    echo 'false';	
    die(); 
    
}

function icp_get_user_photos(){
	
	global $wpdb;
	global $post;

	$settings = get_option('icp_settings');
	$auth = $settings['icp_auth'];

	if($auth==='yes'):

		$upload_images = $settings['icp_featured_image'];

		$instagram = new Instagram(ICP_API_KEY);
    	$instagram->setAccessToken( $settings['icp_access_token'] );
    	$insta_users = $settings['icp_user_id'];
    	$insta_hashtags = $settings['icp_hashtag'];
    	$page_num = $settings['icp_import_limit'] / 20;

    	if( !empty ( $insta_users ) ) :

    		$counter = 0;
    		foreach( $insta_users as $insta_user ) : 

		        if(isset($insta_hashtags[$counter])):
		            if($insta_hashtags[$counter]===''):
		                $hashtags_per_user = '';
		            else:
		                $hashtags_per_user = explode(',', $insta_hashtags[$counter]);
		            endif;
		        endif;

    			if(empty($insta_user)):
    				break;
    			endif;

    			$media = $instagram->getUserMedia($insta_user);
		   		$next_id = '';

		   		for($i=1; $i<=$page_num; $i++):

		   			if($next_id===''): 

		   				foreach ($media->data as $data):

							$new_post = array();							
			                $new_post["id"] = $data->id;		
			               
			                if($data->type === 'image'):

				                $photo_desc = strtolower($data->caption->text);

				                if(!empty($hashtags_per_user)):

					                foreach($hashtags_per_user as $hashtag):
					                	$hashtag = '#'.strtolower($hashtag);

					                	if (strpos($photo_desc, $hashtag) !== false):
									    	
											(string) $sql = "SELECT meta_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$new_post["id"]."' AND meta_key = 'id'";

											if($wpdb->get_var($sql) == NULL):

												$new_post["post_date"]   	= date('Y-m-d H:i:s');
							                	$new_post["post_title"]  	= strip_tags($data->caption->text);
							                	$new_post["post_content"]	= '<img src="'.$data->images->standard_resolution->url.'" />';
							                	$new_post["post_type"]    	= $settings['icp_post_type'];
								                $new_post["post_status"]    = $settings['icp_post_status'];

							                	$post_id = wp_insert_post($new_post);

							                	add_post_meta($post_id, $meta_key = "id", $meta_value=$new_post["id"], $unique=TRUE);

							                	if($upload_images==='yes'):
							                		$photoURL = icp_upload_image($data->images->standard_resolution->url, $post_id);
							                		if($photoURL!=='error'):
							                			$post_update = array(
														    'ID'           => $post_id,
														    'post_content' => '<img src="'.$photoURL.'" />'
														);
														wp_update_post( $post_update );
							                		endif;
							                	endif;

											endif;

										endif;
									endforeach;

								else:

				                	(string) $sql = "SELECT meta_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$new_post["id"]."' AND meta_key = 'id'";

									if($wpdb->get_var($sql) == NULL):

										$new_post["post_date"]   	= date('Y-m-d H:i:s');
					                	$new_post["post_title"]  	= strip_tags($data->caption->text);
					                	$new_post["post_content"]	= '<img src="'.$data->images->standard_resolution->url.'" />';
					                	$new_post["post_type"]    	= $settings['icp_post_type'];
						                $new_post["post_status"]    = $settings['icp_post_status'];

					                	$post_id = wp_insert_post($new_post);

					                	add_post_meta($post_id, $meta_key = "id", $meta_value=$new_post["id"], $unique=TRUE);

					                	if($upload_images==='yes'):
					                		$photoURL = icp_upload_image($data->images->standard_resolution->url, $post_id);
					                		if($photoURL!=='error'):
					                			$post_update = array(
												    'ID'           => $post_id,
												    'post_content' => '<img src="'.$photoURL.'" />'
												);
												wp_update_post( $post_update );
					                		endif;
					                	endif;

									endif;
								
								endif;

							endif;
		               
			            endforeach;
		   			else: 

    					$media = $instagram->pagination($media);

						foreach ($media->data as $data):

							$new_post = array();							
			                $new_post["id"]     		= $data->id;	
			                
			                if($data->type === 'image'):		            	

				                // If we don't find matches with the hashtag break the loop.
				                $photo_desc = strtolower($data->caption->text);

				                if(!empty($hashtags_per_user)):

					                foreach($hashtags_per_user as $hashtag):
					                	$hashtag = '#'.strtolower($hashtag);
					                	
					                	if (strpos($photo_desc, $hashtag) !== false):
									    	
											(string) $sql = "SELECT meta_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$new_post["id"]."' AND meta_key = 'id'";

											if($wpdb->get_var($sql) == NULL):

												$new_post["post_date"]   	= date('Y-m-d H:i:s');
							                	$new_post["post_title"]  	= strip_tags($data->caption->text);
							                	$new_post["post_content"]	= '<img src="'.$data->images->standard_resolution->url.'" />';
							                	$new_post["post_type"]    	= $settings['icp_post_type'];
								                $new_post["post_status"]    = $settings['icp_post_status'];

							                	$post_id = wp_insert_post($new_post);

							                	add_post_meta($post_id, $meta_key = "id", $meta_value=$new_post["id"], $unique=TRUE);

							                	if($upload_images==='yes'):
							                		$photoURL = icp_upload_image($data->images->standard_resolution->url, $post_id);
							                		if($photoURL!=='error'):
							                			$post_update = array(
														    'ID'           => $post_id,
														    'post_content' => '<img src="'.$photoURL.'" />'
														);
														wp_update_post( $post_update );
							                		endif;
							                	endif;

											endif;

										endif;
									endforeach;

								else:

				                	(string) $sql = "SELECT meta_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$new_post["id"]."' AND meta_key = 'id'";

									if($wpdb->get_var($sql) == NULL):

										$new_post["post_date"]   	= date('Y-m-d H:i:s');
					                	$new_post["post_title"]  	= strip_tags($data->caption->text);
					                	$new_post["post_content"]	= '<img src="'.$data->images->standard_resolution->url.'" />';
					                	$new_post["post_type"]    	= $settings['icp_post_type'];
						                $new_post["post_status"]    = $settings['icp_post_status'];

					                	$post_id = wp_insert_post($new_post);

					                	add_post_meta($post_id, $meta_key = "id", $meta_value=$new_post["id"], $unique=TRUE);

					                	if($upload_images==='yes'):
					                		$photoURL = icp_upload_image($data->images->standard_resolution->url, $post_id);
					                		if($photoURL!=='error'):
					                			$post_update = array(
												    'ID'           => $post_id,
												    'post_content' => '<img src="'.$photoURL.'" />'
												);
												wp_update_post( $post_update );
					                		endif;
					                	endif;

									endif;
								
								endif;
							
							endif;
		               
			            endforeach;

		   			endif;

		   			if(!isset($media->pagination->max_tag_id)):
				   		break;
				   	else:
				   		$next_id = $media->pagination->max_tag_id;
				   	endif;

		   		endfor;

		   		$counter++;

    		endforeach;
    	endif;


	endif;

}

function icp_get_hashtag_photos(){
	
	global $wpdb;
	global $post;

	$settings = get_option('icp_settings');
	$auth = $settings['icp_auth'];

	if($auth==='yes'):

		$upload_images = $settings['icp_featured_image'];

		$instagram = new Instagram(ICP_API_KEY);
    	$instagram->setAccessToken( $settings['icp_access_token'] );
    	$hashtags = explode(',', $settings['icp_public_hashtag'] );
    	$page_num = $settings['icp_import_limit'] / 20;

    	if( !empty ( $hashtags ) ) :

    		$counter = 0;
    		foreach( $hashtags as $hashtag ) : 

    			if(empty($hashtag)):
    				break;
    			endif;
    			
    			$media = $instagram->getTagMedia($hashtag);
		   		$next_id = '';

		   		for($i=1; $i<=$page_num; $i++):

		   			if($next_id===''): 

		   				foreach ($media->data as $data):

							$new_post = array();							
			                $new_post["id"]     		= $data->id;			    
			                
			                if($data->type === 'image'):

			                	(string) $sql = "SELECT meta_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$new_post["id"]."' AND meta_key = 'id'";

								if($wpdb->get_var($sql) == NULL):

									$new_post["post_date"]   	= date('Y-m-d H:i:s');
				                	$new_post["post_title"]  	= strip_tags($data->caption->text);
				                	$new_post["post_content"]	= '<img src="'.$data->images->standard_resolution->url.'" />';
				                	$new_post["post_type"]    	= $settings['icp_post_type'];
					                $new_post["post_status"]    = $settings['icp_post_status'];

				                	$post_id = wp_insert_post($new_post);

				                	add_post_meta($post_id, $meta_key = "id", $meta_value=$new_post["id"], $unique=TRUE);

				                	if($upload_images==='yes'):
				                		$photoURL = icp_upload_image($data->images->standard_resolution->url, $post_id);
				                		if($photoURL!=='error'):
				                			$post_update = array(
											    'ID'           => $post_id,
											    'post_content' => '<img src="'.$photoURL.'" />'
											);
											wp_update_post( $post_update );
				                		endif;
				                	endif;

								endif;

							endif;
		               
			            endforeach;
		   			else: 

    					$media = $instagram->pagination($media);

						foreach ($media->data as $data):

							$new_post = array();							
			                $new_post["id"]     		= $data->id;	
			                
			                if($data->type === 'image'):		            	

				                (string) $sql = "SELECT meta_id FROM ".$wpdb->postmeta." WHERE meta_value = '".$new_post["id"]."' AND meta_key = 'id'";

								if($wpdb->get_var($sql) == NULL):

									$new_post["post_date"]   	= date('Y-m-d H:i:s');
				                	$new_post["post_title"]  	= strip_tags($data->caption->text);
				                	$new_post["post_content"]	= $data->images->standard_resolution->url;
				                	$new_post["post_type"]    	= $settings['icp_post_type'];
					                $new_post["post_status"]    = $settings['icp_post_status'];

				                	$post_id = wp_insert_post($new_post);

				                	add_post_meta($post_id, $meta_key = "id", $meta_value=$new_post["id"], $unique=TRUE);

				                	if($upload_images==='yes'):
				                		$photoURL = icp_upload_image($data->images->standard_resolution->url, $post_id);
				                		if($photoURL!=='error'):
				                			$post_update = array(
											    'ID'           => $post_id,
											    'post_content' => '<img src="'.$photoURL.'" />'
											);
											wp_update_post( $post_update );
				                		endif;
				                	endif;

								endif;
							
							endif;
		               
			            endforeach;

		   			endif;

		   			if(!isset($media->pagination->max_tag_id)):
				   		break;
				   	else:
				   		$next_id = $media->pagination->max_tag_id;
				   	endif;

		   		endfor;

    		endforeach;
    	endif;


	endif;

}

function icp_upload_image($url, $post_id){

	require_once (ABSPATH.'/wp-admin/includes/file.php');
	require_once (ABSPATH.'/wp-admin/includes/media.php');
	require_once (ABSPATH.'/wp-admin/includes/image.php');

	if($url!==''):
		$tmp = download_url( $url );

		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;

		if(is_wp_error($tmp)):
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
			return 'error';
		endif;

		$photo_id = media_handle_sideload( $file_array, $post_id, '' );

		if (is_wp_error($photo_id)):
			@unlink($file_array['tmp_name']);
			return 'error';
		endif;

		set_post_thumbnail( $post_id, $photo_id );

		return wp_get_attachment_url( $photo_id );

	endif;

}

add_action( 'admin_enqueue_scripts', 'icp_pointer_load', 1000 );
 
function icp_pointer_load( $hook_suffix ) {
 
    if ( get_bloginfo( 'version' ) < '3.3' )
        return;
 
    $screen = get_current_screen();
    $screen_id = $screen->id;
 
    $pointers = apply_filters( 'icp_admin_pointers-' . $screen_id, array() );
 
    if ( ! $pointers || ! is_array( $pointers ) )
        return;
 
    $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
    $valid_pointers =array();
 
    foreach ( $pointers as $pointer_id => $pointer ) {
 
        if ( in_array( $pointer_id, $dismissed ) || empty( $pointer )  || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) )
            continue;
 
        $pointer['pointer_id'] = $pointer_id;
 
        $valid_pointers['pointers'][] =  $pointer;
    }
 
    if ( empty( $valid_pointers ) )
        return;
 
    wp_enqueue_style( 'wp-pointer' );
 
    wp_enqueue_script( 'icp-pointer', plugins_url( 'js/icp-pointer.js', __FILE__ ), array( 'wp-pointer' ) );
 
    wp_localize_script( 'icp-pointer', 'icpPointer', $valid_pointers );
}

add_action( 'after_switch_theme', 'icp_flush_rewrite_rules' );

function icp_flush_rewrite_rules() {
	flush_rewrite_rules();
}

add_action( 'init', 'icp_photo_post_type_init' );
function icp_photo_post_type_init() {

	$settings = get_option( "icp_settings" );

	$icp_post_singular = ( isset($settings['icp_rename_post_singular']) ? $settings['icp_rename_post_singular'] : 'Photo' );
	$icp_post_plural   = ( isset($settings['icp_rename_post_plural']) ? $settings['icp_rename_post_plural'] : 'Photos' );

	register_post_type( ICP_POST_TYPE, 

		array( 
			'labels' => array(
				'name' => __( $icp_post_plural, 'icpinstagram' ),
				'singular_name' => __( $icp_post_singular, 'icpinstagram' ), 
				'all_items' => __( 'All '.$icp_post_plural, 'icpinstagram' ), 
				'add_new' => __( 'Add New', 'icpinstagram' ),
				'add_new_item' => __( 'Add New '.$icp_post_singular, 'icpinstagram' ), 
				'edit' => __( 'Edit', 'icpinstagram' ),
				'edit_item' => __( 'Edit '.$icp_post_plural, 'icpinstagram' ), 
				'new_item' => __( 'New '.$icp_post_singular, 'icpinstagram' ), 
				'view_item' => __( 'View '.$icp_post_singular, 'icpinstagram' ), 
				'search_items' => __( 'Search '.$icp_post_singular, 'icpinstagram' ), 
				'not_found' =>  __( 'Nothing found in the Database.', 'icpinstagram' ),
				'not_found_in_trash' => __( 'Nothing found in Trash', 'icpinstagram' ), 
				'parent_item_colon' => ''
			), 
			'description' => __( 'This is the custom post type for the Instagram photos', 'icpinstagram' ), 
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'show_ui' => true,
			'query_var' => true,
			'menu_position' => 8, 
			'rewrite'	=> array( 'slug' => 'wpteam_instagram', 'with_front' => false ),
			'has_archive' => 'wpteam_instagram_photos',
			'capability_type' => 'post',
			'hierarchical' => false,
			'supports' => array( 'title', 'editor', 'thumbnail' )
		) 
	); 

}


