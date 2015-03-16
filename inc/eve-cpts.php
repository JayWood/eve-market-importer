<?php

/**
 * Auto registers and sets up CPTs
 */
class JW_Eve_CPTs {
	// Instance of this class
	public static $instance = null;

	/**
	 * @return JW_Eve_CPTs|null
	 */
	public static function go() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		$this->plugin_dir_path = plugin_dir_path( dirname( __FILE__ ) );
		$this->cpts_dir        = $this->plugin_dir_path . 'inc/cpts/';
		$this->taxes_dir       = $this->plugin_dir_path . 'inc/taxonomies/';

		require_once $this->plugin_dir_path . 'lib/CPT_Core/CPT_Core.php';
		require_once $this->plugin_dir_path . 'lib/Taxonomy_Core/Taxonomy_Core.php';

		foreach( glob( $this->cpts_dir . '*.php' ) as $filename ){
			require_once( $filename );
			$var_name = str_ireplace( array( $this->cpts_dir, '.php' ), '', $filename );
			$class = 'JW_Eve_' . $this->normalize_classname( $var_name ) . '_CPT';
			$this->$var_name = new $class();
		}

		foreach( glob( $this->taxes_dir . '*.php' ) as $filename ){
			require_once( $filename );
			$var_name = str_ireplace( array( $this->taxes_dir, '.php' ), '', $filename );

			$class = 'JW_Eve_' . $this->normalize_classname( $var_name ) . '_Taxonomy';
			$this->$var_name = new $class();
		}
	}

	protected function normalize_classname( $string ){
		$string = str_replace( '_', ' ', $string );
		return str_replace( ' ', '_', ucwords( $string ) );
	}



}