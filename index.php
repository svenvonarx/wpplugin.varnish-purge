<?php
/*
Plugin Name: Purge Varnish
Plugin URI: http://cubetech.ch
Description: Plugin für Varnish Cache Purge
Author: Sven von Arx
Version: 1.0
Author URI: 
*/


/**
* VarnishPurge
*/
class VarnishPurge {
	
	/**
    * Construct for hooks
    */
	public function __construct() {
				
		// initialize javascript
		add_action( 'admin_enqueue_scripts', array( $this, 'varnish_script_enqueue' ) );

		// initialize dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'varnish_dashboard_widget' ) );
		
		// initialize ajax calls
		add_action( 'wp_ajax_nopriv_purge_all', array( $this, 'purge_all' ) );
		add_action( 'wp_ajax_purge_all', array( $this, 'purge_all' ) );		
		
		// hook on save post
		add_action( 'save_post', array( $this, 'purge_selective' ), 10, 3 );
		
		add_action( 'acf/render_field', array($this, 'last_reset_field'), 10, 1 );
		
	}
	
	/**
    * Enqueue needed scripts for this plugin
    */
	public function varnish_script_enqueue() {
	    wp_enqueue_script( 'varnish_ajax', plugin_dir_url( __FILE__ ) . '/assets/js/varnish.js' );
	}
	
	/**
    * This method registers the dashboard widget
    */
	public function varnish_dashboard_widget() {
		wp_add_dashboard_widget( 'varnish-purge', __('Cache zurücksetzen', 'varnish-purge'), array( $this, 'varnish_render_widget' ), $control_callback = null );
	}

	/**
    * This method renders the dashboard widget
    * @callback from wp_add_dashboard_widget
    */	
	public function varnish_render_widget() {
		echo '<p>'.__('Wenn Sie auf "Cache zurücksetzen" klicken, wird der gesamte Cache der Webseite zurückgesetzt.', 'varnish-purge').'</p>';
		echo '<a href="#" class="button" id="purge-varnish">'.__('Cache zurücksetzen', 'varnish-purge').'</a>';	
	}	

	/**
    * This method purges cache completely via cURL
    */
	public function purge_all() {
		
		// get site url		
		$varnishurl = get_site_url();	
		
		// set host for request
	    $varnishhost = 'Host: ' . $varnishurl;
	    
	    // set command BAN for regex
	    $varnishcommand = "BAN";
	    
	    ob_start();
	    
	    // initialize cURL
	    $curl = curl_init($varnishurl);
	    
	    // set cURL options
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $varnishcommand);
	    curl_setopt($curl, CURLOPT_ENCODING, $varnishurl);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
	    
	    // execute cURL
	    $result = curl_exec($curl);
	    
	    // close cURL
	    curl_close($curl);
	    
	    ob_end_clean();
	    
	    if( $result == false ) {
		    echo json_encode( array( 'msg' => __('Zurücksetzen des Caches fehlgeschlagen!', 'varnish-purge' ) ) );
	    } else {
   		    echo json_encode( array( 'msg' => __('Zurücksetzen des Caches erfolgreich!', 'varnish-purge' ) ) );
	    }
	    
	    exit;
	    
	}
	
	public function last_reset_field( $field ) {
		//field_577d7ec8d0b41
		if( $field['key'] == 'field_577d7ec8d0b41' ) {
			global $post;
			
			$reset_cache_date = get_post_meta($post->ID, 'last_reset_cache', true);

				echo '<style> 
					.acf-field-577d7ec8d0b41 { padding-top: 70px !important; }
				</style>';
				echo '<p style="position: absolute; top: 8px; line-height: 1.3;">Der Cache für diesen Inhalt wird beim Aktualisieren automatisch zurückgesetzt.</p>';
			if( $reset_cache_date !== '' ) {
				echo '<style> 
					.acf-field-577d7ec8d0b41 .acf-checkbox-list { top: -48px; } 
				</style>';
				echo '<p style="font-style: italic; position: relative; top: 38px; line-height: 1.3;">Die verknüpften Seiten wurden zuletzt am<br>' . $reset_cache_date . ' zurückgesetzt.</p>';	
			}
		}
		
	
	}
	
	/**
    * This method purges cache on post save
    */
	public function purge_selective() {
		
		global $wpdb;
		
		setlocale(LC_ALL, "de_DE.UTF-8");
		
		if( $_POST['post_type'] == 'pushs_vorlagen' || $_POST['post_type'] == 'pushs' || $_POST['post_type'] == 'acf-field-group' ) {
			return;
		}
		
		if( !empty( $_POST ) && $_POST['_wp_http_referer'] !== '/wp-admin/nav-menus.php' ) {
			
			$post_type = $_POST['post_type'];
			$post_id = $_POST['post_ID'];
			
			$reset_cache = get_field_object( 'reset_cache', $post_id );
			$reset_cache = $reset_cache['key'];
			$reset_cache = reset( $_POST['acf'][$reset_cache] );
			
			$urls_to_purge = array();
			
			$urls_to_purge[] = get_permalink( $post_id );

			if( $reset_cache == '1' ) {
				$_POST['acf'][$reset_cache] = '';
				update_field('reset_cache', '', $post_id);
				$date = current_time('timestamp');
				var_dump(strftime('%e. %B %Y um %T', $date ));
				update_post_meta( $post_id, 'last_reset_cache', strftime('%e. %B %Y um %T', $date ) );
				$sql = get_posts( array( 'post_type' => 'page', 'posts_per_page' => -1 ) );
			    
			    foreach( $sql as $shortcode_post ) {
				    $urls_to_purge[$shortcode_post->ID] = get_permalink( $shortcode_post->ID );
			    }
			}

			foreach( $urls_to_purge as $url ) {
				// get site url		
				$varnishurl = $url;
				
				$varnishurl = str_replace( 'http:', 'https:', $varnishurl );
				
				// set host for request
			    $varnishhost = 'Host: ' . $varnishurl;
			    
			    // set command BAN for regex
			    $varnishcommand = "BAN";
			    
			    ob_start();
			    
			    // initialize cURL
			    $curl = curl_init($varnishurl);
			    
			    // set cURL options
			    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $varnishcommand);
			    curl_setopt($curl, CURLOPT_ENCODING, $varnishurl);
			    curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
			    
			    // execute cURL
			    $result = curl_exec($curl);
			    
			    // close cURL
			    curl_close($curl);
			    
			    ob_end_clean();
			}

		}
	    
	}
	
}

new VarnishPurge();


//acf fields
if( function_exists('acf_add_local_field_group') ):

acf_add_local_field_group(array (
	'key' => 'group_577d7e6ba9988',
	'title' => 'Cache zurücksetzen',
	'fields' => array (
		array (
			'key' => 'field_577d7ec8d0b41',
			'label' => 'Verknüpfte Seiten zurücksetzen',
			'name' => 'reset_cache',
			'type' => 'checkbox',
			'instructions' => 'Falls Sie sicherstellen möchten, dass Seiten, welche mit diesem Inhalt verknüpft sind, zurückgesetzt werden, kann diese Option angewählt werden. (Der Speicherprozess benötigt etwas mehr Zeit.)',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array (
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array (
				1 => 'Cache zurücksetzen',
			),
			'default_value' => array (
			),
			'layout' => 'vertical',
			'toggle' => 0,
		),
	),
	'location' => array (
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'post',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'specialopeningtimes',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'sportler',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'sportlerteam',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'sportfunktionaer',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'cubetech_clubs',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'event',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'video',
			),
		),
		array (
			array (
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'facilities',
			),
		),
	),
	'menu_order' => -100,
	'position' => 'side',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => 1,
	'description' => '',
));

endif;