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
	
	/**
    * This method purges cache on post save
    */
	public function purge_selective() {
		
		global $wpdb;
		
		if( $_POST['post_type'] == 'pushs_vorlagen' || $_POST['post_type'] == 'pushs' || $_POST['post_type'] == 'acf-field-group' ) {
			return;
		}
		
		if( !empty( $_POST ) && $_POST['_wp_http_referer'] !== '/wp-admin/nav-menus.php' ) {
			
			$post_type = $_POST['post_type'];
			$post_id = $_POST['post_ID'];
			
			$reset_cache = get_field_object( 'reset_cache', $post_id );
			$reset_cache = $reset_cache['key'];
			$reset_cache = $_POST['acf'][$reset_cache];
			
			$urls_to_purge = array();
			
			$urls_to_purge[] = get_permalink( $post_id );
			
			if( $reset_cache == '1' ) {
				$_POST['acf'][$reset_cache] = '0';
				update_field('reset_cache', '0', $post_id);
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