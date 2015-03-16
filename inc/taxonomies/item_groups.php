<?php

class JW_Eve_Item_Groups_Taxonomy extends Taxonomy_Core {
	public function __construct(){
		parent::__construct(
			array(
				__( 'Market Group', 'jw_eveonline_importer' ),
				__( 'Market Groups', 'jw_eveonline_importer' ),
				'eve-market-groups',
			),
			array(
				'hierarchical' => true,
			),
			array(
				'eve-market-items'
			)
		);
	}
}