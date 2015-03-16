<?php
/**
 * Plugin Name: Eve Online Market Importer ( Alpha )
 * Plugin URI: http://plugish.com/plugins/eve-market-importer
 * Description: A plugin with CLI capability to import market groups into a custom post type.
 * Author: Jerry Wood
 * Version: 1.6
 * Author URI: http://plugish.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once 'inc/eve-cpts.php';
require_once 'inc/eve-con-api.php';
require_once 'inc/CLI.php';

class JW_EveOnline_Market {

	public function __construct() {
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		load_plugin_textdomain( 'jw_eveonline_importer', false, dirname( $this->basename ) . '/lang' );

		if( class_exists( 'JW_Eve_CPTs') ){
			$this->cpts = JW_Eve_CPTs::go();
		}
	}

	public function do_hooks() {
//		add_action( 'init', array( $this, 'register_cpts' ), 9 );
//		add_action( 'init', array( $this, 'register_taxes' ) );
	}
}

$GLOBALS['jw_eveonline_market'] = new JW_EveOnline_Market();
$GLOBALS['jw_eveonline_market']->do_hooks();

require_once 'inc/CLI.php';