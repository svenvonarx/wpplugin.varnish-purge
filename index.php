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

	private $options_name = 'Varnish Purge Einstellungen';	
	private $options_group = 'varnish-options-group';
	private $options_slug = 'varnish-options';
	private $options_prefix = 'varnish_options_';
	private $varnish_ip = '';
	private $varnish_secret = '';
	
	public function __construct() {
		
		if ( is_admin() ){ // admin actions
			add_action( 'admin_menu', array( $this, 'add_varnish_settings_menu' ) );			
			add_action( 'admin_init', array( $this, 'register_varnish_settings' ) );
		}
		
/*
		add_action( 'wp_ajax_nopriv_purge_all', array( $this, 'purge_all' ) );
		add_action( 'wp_ajax_purge_all', array( $this, 'purge_all' ) );
*/

		add_action( 'save_post', array( $this, 'purge_all' ), 10, 3 );
		
		// set variables
		$this->varnish_ip = get_option( $this->options_prefix . 'ip' );
		$this->varnish_secret = get_option( $this->options_prefix . 'secret' );
		
	}
	
	public function register_varnish_settings() { 
		
		add_settings_section( $this->options_group, 'Server Einstellungen', null, $this->options_slug);
		
		add_settings_field( $this->options_prefix . 'ip', 'Varnish IP', array( $this, 'render_varnish_ip_field' ), $this->options_slug, $this->options_group );
		add_settings_field( $this->options_prefix . 'secret', 'Varnish Secret', array( $this, 'render_varnish_secret_field' ), $this->options_slug, $this->options_group );
		
		register_setting( $this->options_group, $this->options_prefix . 'ip' );
		register_setting( $this->options_group, $this->options_prefix . 'secret' );
		
	}
	
	public function render_varnish_ip_field( ) {
			
		echo '<input type="text" name="' . $this->options_prefix . 'ip' . '" id="' . $this->options_prefix . 'ip' . '" value="' . get_option( $this->options_prefix . 'ip' ) . '" />';
		
	}
	
	public function render_varnish_secret_field( ) {
			
		echo '<input type="text" name="' . $this->options_prefix . 'secret' . '" id="' . $this->options_prefix . 'secret' . '" value="' . get_option( $this->options_prefix . 'secret' ) . '" />';
		
	}	
	
	public function render_varnish_settings() {
	    ?>
		    <div class="wrap">
			    <h1><?php echo $this->options_name; ?></h1>
			    <form method="post" action="options.php">
			        <?php
			            settings_fields( $this->options_group );
			            do_settings_sections( $this->options_slug );      
			            submit_button(); 
			        ?>          
			    </form>
			</div>
		<?php
	}
	
	public function add_varnish_settings_menu() {
		add_options_page( $this->options_name, $this->options_name, "manage_options", $this->options_slug, array( $this, 'render_varnish_settings' ), null, 99 );
	}
	

	public function purge_all( $purge_url = '' ) {
		
		if( $purge_url == '' ) {
			$varnishurl = get_site_url();	
		} else {
			$varnishurl = $purge_url;
		}
		
	    $varnishhost = 'Host: ' . $this->varnish_ip;
	    $varnishcommand = "PURGE";
	    $curl = curl_init($varnishurl);
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $varnishcommand);
	    curl_setopt($curl, CURLOPT_ENCODING, $varnishhost);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, false);
	    $result = curl_exec($curl);
	    curl_close($curl);
		
	}
	
}

new VarnishPurge();
