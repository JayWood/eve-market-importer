<?php

/**
 * CLI Class
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class EveOnline_Market {

		protected $args, $assocargs, $page;

		protected $market_groups = array(
			'endpoint' => 'market/groups',
		);

		protected $market_types = array(
			'endpoint' => 'market/types',
		);

		protected $post_type = 'eve-market-items';
		protected $taxonomy  = 'eve-market-groups';

		/**
		 * Import Market Items
		 *
		 * ## Options
		 *
		 * verbose
		 * : Floods your screen with data
		 *
		 * warn
		 * : Logging level will only warn of errors and continue
		 *
		 * live
		 * : Actually imports the data
		 *
		 * @synopsis [--verbose] [--warn] [--live]
		 */
		public function types( $args = array(), $assocargs = array() ) {
			$this->setup_args( $args, $assocargs );

			$this->con = new Eve_Crest_Request( $this->market_types );
			$types     = $this->con->get_decoded_body();
			if ( ! $types ) {
				$this->handle_errors( 'There are no available types.', 1 );
			}

			$items = isset( $types->items ) ? $types->items : false;
			if ( ! $items ) {
				$this->handle_errors( 'There are no available items.', 1 );
			}

			$this->progress_bar( count( $items ), 'Importing %s items' );
			foreach ( $items as $item ) {
				// We need these.
				if ( ! isset( $item->marketGroup ) || ! isset( $item->type ) ) {
					$this->progress_bar( 'tick' );
					continue;
				}

				if ( $this->is_live() ) {
					$result = $this->import_type( $item );
					if ( is_wp_error( $result ) ) {
						$this->handle_errors( $result->get_error_message() );
					}
				}

				$this->progress_bar( 'tick' );
			}
			$this->progress_bar( 'finished' );

			if ( $this->has_next_page( $types ) ) {
				// Dynamic variable, only sets itself if there's a new page.
				$this->page = 2;
				WP_CLI::line( 'A new page is available, moving on to that one.' );
				$this->import_next_page( $types );
			}

		}

		protected function set_post_data( $post_id = 0, $type ) {
			// Temporary variable
			$result      = null;

			$parent_slug = isset( $type->marketGroup->id ) ? $type->marketGroup->id : false;
			if ( $parent_slug && 0 !== $post_id ) {
				$term_data = get_term_by( 'slug', $parent_slug, $this->taxonomy );
				if ( $term_data && isset( $term_data->term_id ) ) {
					$terms = array(
						$term_data->term_id,
					);
					$ancestors = get_ancestors( $term_data->term_id, $this->taxonomy );
					if( $ancestors ){
						foreach( $ancestors as $parent ){
							$terms[] = $parent;
						}
					}

					$result = wp_set_object_terms( $post_id, $terms, $this->taxonomy );
				} else {
					$this->handle_errors( 'Looking for group ID ' . $parent_slug . ' but cannot find it, are you sure you imported the groups?', 1 );
				}
			}

			if ( is_wp_error( $result ) ) {
				$this->handle_errors( $result->get_error_message(), 1 );
			}

			$id = isset( $type->type->id ) ? $type->type->id : false;
			if ( $id ) {
				$result = update_post_meta( $post_id, '_type_id', $id );
				if ( ! $result ) {
					$this->handle_errors( 'Cannot update the post meta for ' . $post_id . ' when trying to set _type_id to ' . $id, 1 );
				}
			}

			//@TODO: Import images for each item type at this point.

			return $post_id;
		}

		protected function import_type( $type ) {

			$type_data  = $type->type;
			$post_title = isset( $type_data->name ) ? $type_data->name : false;
			if ( ! $post_title ) {
				$this->handle_errors( 'Cannot insert a post without a title, sorry!', 1 );
			}

			$args = array(
				'post_title'  => $post_title,
				'post_type'   => $this->post_type,
				'post_status' => 'publish',
			);

			$new_post_id = wp_insert_post( $args, true );
			if ( is_wp_error( $new_post_id ) ) {
				return $new_post_id;
			}

			return $this->set_post_data( $new_post_id, $type );
		}

		protected function has_next_page( $object ) {
			return isset( $object->next );
		}

		protected function import_next_page( $object ) {
			if ( ! $this->page ) {
				$this->handle_errors( 'FATAL: Trying to import a page without a page var.', 1 );

				return null; // bail, we need this.
			}

			// Set the endpoint
			$endpoint               = $this->market_types;
			$endpoint['url_params'] = array(
				'page' => $this->page,
			);

			$this->con = new Eve_Crest_Request( $endpoint );
			$types     = $this->con->get_decoded_body();
			if ( ! $types ) {
				$this->handle_errors( 'There are no available types.', 1 );
			}

			$items = isset( $types->items ) ? $types->items : false;
			if ( ! $items ) {
				$this->handle_errors( 'There are no available items.', 1 );
			}

			$this->progress_bar( count( $items ), 'Importing %s items' );
			foreach ( $items as $item ) {
				// We need these.
				if ( ! isset( $item->marketGroup ) || ! isset( $item->type ) ) {
					$this->progress_bar( 'tick' );
					continue;
				}

				if ( $this->is_live() ) {
					$result = $this->import_type( $item );
					if ( is_wp_error( $result ) ) {
						$this->handle_errors( $result->get_error_message() );
					}
				}

				$this->progress_bar( 'tick' );
			}
			$this->progress_bar( 'finished' );

			if ( $this->has_next_page( $types ) ) {
				$this->page ++;
				WP_CLI::line( 'A new page is available, moving on to that one.' );
				$this->import_next_page( $types );
			} else {
				WP_CLI::success( 'Finished importing item types.' );
			}


		}

		/**
		 * Import Market Groups
		 *
		 * ## Options
		 *
		 * verbose
		 * : Floods your screen with data
		 *
		 * warn
		 * : Logging level will only warn of errors and continue
		 *
		 * @synopsis [--verbose] [--warn]
		 */
		public function groups( $args = array(), $assocargs = array() ) {
			$this->setup_args( $args, $assocargs );

			$this->con = new Eve_Crest_Request( $this->market_groups );

			$groups = $this->con->get_decoded_body();
			if ( ! $groups ) {
				$this->handle_errors( 'There are no available groups.', 1 );
			}

			if ( ! isset( $groups->items ) ) {
				$this->handle_errors( 'There are no available items', 1 );
			}

			$data_map = $this->build_data_map( $groups );
			$tree     = '';;
			if ( ! $data_map ) {
				$this->handle_errors( 'No datamap can be built', 1 );
			} else {
				$tree = $this->buildTree( $data_map );

				$this->display_verbose( 'Dumping data map to error log' );
				error_log( print_r( $tree, 1 ) );
				$this->display_verbose( 'Dumped' );

			}

			if ( ! $tree ) {
				$this->handle_errors( "Data tree cannot be built" );
			}

			WP_CLI::line( 'Attempting to insert terms now.' );
			$this->progress_bar( count( $data_map ), 'Importing %s Groups' );

			foreach ( $tree as $key => $value ) {
				if ( isset( $value['name'] ) ) {
					$insert_args = array(
						'slug'        => $key,
						'description' => isset( $value['description'] ) ? $value['description'] : '',
					);
					$term        = wp_insert_term( $value['name'], $this->taxonomy, $insert_args );
					if ( is_wp_error( $term ) ) {
						$this->handle_errors( $term->get_error_message() );
					}
					$this->progress_bar( 'tick' );

					if ( $this->has_children( $key, $value, $term['term_id'] ) ) {
						$this->import_children( $key, $value, $term['term_id'] );
					}
				}
			}

			$this->progress_bar( 'finished' );
			WP_CLI::success( "Finished importing terms" );
		}

		protected function has_children( $cur_key, $cur_value, $parent_term = false ) {
			return isset( $cur_value['children'] ) && is_array( $cur_value['children'] );
		}

		protected function import_children( $cur_key, $cur_value, $parent_term ) {
			$this->display_verbose( 'Importing child of ' . $cur_key );

			$tree = $cur_value['children'];
			foreach ( $tree as $key => $value ) {
				if ( isset( $value['name'] ) ) {
					$insert_args = array(
						'slug'        => $key,
						'description' => isset( $value['description'] ) ? $value['description'] : '',
						'parent'      => $parent_term,
					);
					$term        = wp_insert_term( $value['name'], $this->taxonomy, $insert_args );
					if ( is_wp_error( $term ) ) {
						$this->handle_errors( $term->get_error_message() );
					}
					$this->progress_bar( 'tick' );

					if ( $this->has_children( $key, $value, $term['term_id'] ) ) {
						$this->import_children( $key, $value, $term['term_id'] );
					}
				}
			}
		}

		protected function buildTree( $ar, $pid = null ) {
			$op = array();
			foreach ( $ar as $item ) {
				if ( $item['parent'] == $pid ) {
					$op[ $item['id'] ] = array(
						'name'        => $item['name'],
						'parent'      => $item['parent'],
						'description' => $item['description'],
					);
					// using recursion
					$children = $this->buildTree( $ar, $item['id'] );
					if ( $children ) {
						$op[ $item['id'] ]['children'] = $children;
					}
				}
			}

			return $op;
		}


		protected function term_slug( $name, $id ) {
			return sanitize_title( $name . '-' . $id );
		}

		protected function get_item_map( $item ) {
			if ( ! isset( $item->href ) ) {
				$this->handle_errors( "Cannot obtain group ID, no href." );
				$this->progress_bar( 'tick' );

				return false;
			}

			$group_id = $this->get_group_id( $item->href );
			if ( ! $group_id ) {
				$this->handle_errors( 'No group ID found' );
				$this->progress_bar( 'tick' );

				return false;
			}

			if ( ! isset( $item->name ) ) {
				$this->handle_errors( 'Group has no name' );
				$this->progress_bar( 'tick' );

				return false;
			}

			$name = $item->name;
			$desc = '';

			if ( ! isset( $item->description ) ) {
				$this->handle_errors( 'Group has no description' );
				// No need to skip, we only need at least a name and ID.
			} else {
				$desc = $item->description;
			}

			return array(
				'id'          => $group_id,
				'name'        => $name,
				'description' => $desc,
			);
		}

		protected function build_data_map( $groups ) {
			WP_CLI::line( 'Attempting to rebuild the data.' );
			$this->progress_bar( count( $groups->items ), "Re-mapping %s groups." );
			$data_map = array();
			foreach ( $groups->items as $item ) {

				$item_map = $this->get_item_map( $item );
				if ( ! $item_map ) {
					continue;
				}

				$item_map['parent'] = 0;

				$group_id  = $this->get_group_id( $item->href );
				$parent_id = isset( $item->parentGroup ) ? $item->parentGroup : false;
				if ( $parent_id && isset( $parent_id->href ) ) {
					// We have a parent, deal with it?!
					$parent_id   = $this->get_group_id( $parent_id->href );
					$parent_data = $this->get_parent_data( $parent_id, $groups->items );

					if ( $parent_data && isset( $parent_data->name ) ) {
						$item_map['parent']      = $parent_id;
						$item_map['parent_name'] = $parent_data->name;
					}

				}

				$data_map[ $group_id ] = $item_map;

				$this->progress_bar( 'tick' );

			}

			$this->progress_bar( 'finish' );

			return empty( $data_map ) ? false : $data_map;
		}

		protected function get_parent_data( $id, $items ) {
			foreach ( $items as $item ) {
				$group_id = $this->get_group_id( $item->href );
				if ( $group_id === $id ) {
					return $item;
				}
			}

			return false;
		}

		/**
		 * Handles CLI progress bars
		 *
		 * @since  0.1.0
		 *
		 * @param  mixed $arg If integer, sets up the progress bar, otherwise takes 'tick' or 'finnish'
		 */
		protected function progress_bar( $arg = 0, $msg = '' ) {
			static $progress_bar = null;

			$msg = sprintf( $msg, $arg );

			if ( $arg && is_numeric( $arg ) ) {
				$progress_bar = \WP_CLI\Utils\make_progress_bar( $msg, $arg );
			} elseif ( 'tick' == $arg ) {
				$progress_bar->tick();
			} elseif ( 'finish' == $arg ) {
				$progress_bar->finish();
			}

			return false;

		}

		protected function get_group_id( $href ) {
			$href       = trailingslashit( $href );
			$matches    = null;
			$pattern    = apply_filters( 'eve_group_id_regex', '/\/([0-9]+)\/$/' );
			$return_val = preg_match( $pattern, $href, $matches );

			if ( $matches && isset( $matches[1] ) ) {
				return $matches[1];
			} else {
				$this->display_verbose( 'Looking for a number in ' . $href . ' but failed when using regex pattern /([0-9]+)/$' );
				$this->display_verbose( print_r( $matches, 1 ) );
			}

			return false;

		}

		protected function handle_errors( $message, $override = false ) {

			WP_CLI::line( '' );
			if ( $this->is_warn() && ! $override ) {
				WP_CLI::warning( $message );
			} else {
				WP_CLI::error( $message );
			}
		}

		protected function is_warn() {
			return isset( $this->assocargs['warn'] );
		}

		protected function is_live() {
			return isset( $this->assocargs['live'] );
		}

		protected function is_verbose() {
			return isset( $this->assocargs['verbose'] );
		}

		protected function display_verbose( $message ) {
			if ( $this->is_verbose() ) {
				WP_CLI::line( $message );
			}
		}

		protected function setup_args( $args, $assocargs ) {
			$this->args      = $args;
			$this->assocargs = $assocargs;
		}

	}

	WP_CLI::add_command( 'eve-market-import', 'EveOnline_Market' );
}