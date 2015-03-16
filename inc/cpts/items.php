<?php

class JW_Eve_Items_CPT extends CPT_Core {
	public function __construct() {
		parent::__construct(
			array(
				__( 'Market Item', 'jw_eveonline_importer' ),
				__( 'Market Items', 'jw_eveonline_importer' ),
				'eve-market-items',
			), array(
				'menu_icon' => 'dashicons-chart-line',
			)
		);
	}
}