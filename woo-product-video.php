<?php
	/*
		Plugin Name: Woo Product Video
		Plugin URI: http://molaydas.com/woo-product-video
		Description: Adding additional tab for product video in product details page for each product.
		Version: 1.0
		Author: Molay Das
		Author URI: https://profiles.wordpress.org/molay
		License: GPL2
		Text Domain: woo-product-video
	*/

	// Clean up wp_head
	// Remove Really simple discovery link
	remove_action('wp_head', 'rsd_link');

	// Remove Windows Live Writer link
	remove_action('wp_head', 'wlwmanifest_link');

	// Remove the version number
	remove_action('wp_head', 'wp_generator');
	 
	// Remove curly quotes
	remove_filter('the_content', 'wptexturize');
	remove_filter('comment_text', 'wptexturize');
	 
	// Allow HTML in user profiles
	remove_filter('pre_user_description', 'wp_filter_kses');
	 
	// SEO
	// add tags as keywords
	function woo_product_video_tags_to_keywords() {
	    global $post; // Get access to the $post object
	    $video_tag_array = array();
	    if ( is_single() || is_page() ) { // only run on posts or pages
	        $video_tags = wp_get_post_tags( $post->ID ); // get post tags
	        foreach( $video_tags as $video_tag ) { // loop through each tag
	            $video_tag_array[] = $video_tag->name; // create new array with only tag names
	        }
	        $video_tag_string = implode( ', ', $video_tag_array ); // convert array into comma seperated string
	        if ( $video_tag_string !== '' ) { // it we have tags
	            echo "<meta name='keywords' content='" . $video_tag_string . "' />\r\n"; // add meta tag to <head>
	        }
	    }
	}
	add_action( 'wp_head', 'woo_product_video_tags_to_keywords' );

	// add except as description
	function woo_product_video_excerpt_to_description() {
	    global $post; // get access to the $post object
	    if ( is_single() || is_page() ) { // only run on posts or pages
	        $all_post_content = wp_get_single_post( $post->ID ); // get all content from the post/page
	        $excerpt = substr( $all_post_content->post_content, 0, 100 ) . ' [...]'; // get first 100 characters and append "[...]" to the end
	        echo "<meta name='description' content='" . $excerpt . "' />\r\n"; // add meta tag to <head>
	    }
	    else { // only run if not a post or page
	        echo "<meta name='description' content='" . get_bloginfo( 'description' ) . "' />\r\n"; // add meta tag to <head>
	    }
	}
	add_action( 'wp_head', 'woo_product_video_excerpt_to_description' ); // add woo_product_video_excerpt_to_description to wp_head function
	 
	//Optimize Database
	function woo_product_video_optimize_database() {
	    global $wpdb; // get access to $wpdb object
	    $all_tables = $wpdb->get_results( 'SHOW TABLES', ARRAY_A ); // get all table names
	    foreach ( $all_tables as $tables ) { // loop through every table name
	        $table = array_values( $tables ); // get table name out of array
	        $wpdb->query( "OPTIMIZE TABLE " . $table[0] ); // run the optimize SQL command on the table
	    }
	}
	function woo_product_video_optimization_cron_on() {
	    wp_schedule_event( time(), 'daily', 'woo_product_video_optimize_database' ); // rdd woo_product_video_optimize_database to wp cron events
	}
	function woo_product_video_optimization_cron_off() {
	    wp_clear_scheduled_hook( 'woo_product_video_optimize_database' ); // remove woo_product_video_optimize_database from wp cron events
	}
	register_activation_hook( __FILE__, 'woo_product_video_optimization_cron_on' ); // run woo_product_video_optimization_cron_on at plugin activation
	register_deactivation_hook( __FILE__, 'woo_product_video_optimization_cron_off' ); // run woo_product_video_optimization_cron_off at plugin deactivation

	//add additional video tab for woocommerce product details page
	add_filter( 'woocommerce_product_tabs', 'woo_product_video_tab' );
	function woo_product_video_tab( $tabs ) {
		global $post;
		$product_video_url_tab_disp  = get_post_meta( $post->ID, 'product_video_url_tab_meta_field', true );
		if ( $product_video_url_tab_disp != 'on' ) {
			// Adds the new tab
			$tabs['product_video_tab'] = array(
				'title' 	=> __( 'Product Video', 'woo-product-video' ),
				'priority' 	=> 50,
				'callback' 	=> 'woo_product_video_tab_content'
			);
			return $tabs;
		}
	}

	//add additional video tab content for woocommerce product details page
	function woo_product_video_tab_content() {
		global $post;
		$product_video_url_tab_disp  = get_post_meta( $post->ID, 'product_video_url_tab_meta_field', true );
		$product_video_title_disp  = get_post_meta( $post->ID, 'product_video_title_meta_field', true );
		$product_video_desc_disp  = get_post_meta( $post->ID, 'product_video_desc_meta_field', true );
		$product_video_url_disp  = get_post_meta( $post->ID, 'product_video_url_meta_field', true );

		//check youtube or vimeo video url
		function videoType( $check_video_url ) {
		    if ( strpos( $check_video_url, 'youtube' ) > 0 ) {
		        return 'youtube';
		    } elseif ( strpos( $check_video_url, 'vimeo' ) > 0 ) {
		        return 'vimeo';
		    } else {
		        return 'wrongurl';
		    }
		}

		echo '<h2>Product Video</h2>';
		$product_video_url = get_post_meta( get_the_ID(), 'product_video_url_meta_field', true );
		$video_url_type = videoType( $product_video_url );
		if ( $video_url_type == 'youtube' ) {
	    	preg_match( '/[\\?\\&]v=([^\\?\\&]+)/', $product_video_url, $matches );
	    	$product_video_url_id = $matches[1];
	    	echo '<iframe id="ytplayer" type="text/html" width="100%" height="400px" src="https://www.youtube.com/embed/' . $product_video_url_id . '?rel=0&showinfo=0&color=white&iv_load_policy=3" frameborder="0" allowfullscreen></iframe>';
	    }
		else if ( $video_url_type == 'vimeo' ) {
			$vimeo_video_url_id = ( int ) substr( parse_url( $product_video_url, PHP_URL_PATH ), 1 );
			echo '<iframe src="http://player.vimeo.com/video/' . $vimeo_video_url_id . '" width="100%" height="400px" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>';
		}
		else {
			echo '<p>Please use youtube and vimeo video urls.</p>';
		}
	}

	add_action( 'add_meta_boxes', 'product_video_add_meta_box' );
	add_action( 'save_post', 'save_product_video_meta_box_data' );

	/**
	 * add_meta_box
	 */
	function product_video_add_meta_box() {
		add_meta_box( 'product_meta', 'Product Video Details', 'display_product_video_meta_form', 'product', 'advanced', 'high' );
	}
	 
	/**
	 * display_product_video_meta_form	
	 */
	 
	function display_product_video_meta_form( $post ) {
	 
		wp_nonce_field( 'product_meta_box', 'product_meta_box_nonce' );
	 	
	 	$product_video_url_tab  = get_post_meta( $post->ID, 'product_video_url_tab_meta_field', true );
		$product_video_title  = get_post_meta( $post->ID, 'product_video_title_meta_field', true );
		$product_video_desc  = get_post_meta( $post->ID, 'product_video_desc_meta_field', true );
		$product_video_url  = get_post_meta( $post->ID, 'product_video_url_meta_field', true );
?>
			<div class="wrap">
				<label for="product_video_url_tab_meta_field">
					<?php _e( 'Hide Video Tab', 'woo-product-video' ); ?>
				</label>
				<input class="text widefat" type="checkbox" id="product_video_url_tab_meta_field" name="product_video_url_tab_meta_field" <?php if ( $product_video_url_tab == 'on' ) { echo "checked"; } ?> />
			</div>

			<div class="wrap">
				<label for="product_video_title_meta_field">
					<?php _e( 'Video Title', 'woo-product-video' ); ?>
				</label>
				<input class="text widefat" type="text" id="product_video_title_meta_field" name="product_video_title_meta_field" value="<?php echo esc_attr( $product_video_title ); ?>" />
			</div>

			<div class="wrap">
				<label for="product_video_desc_meta_field">
					<?php _e( 'Video Description', 'woo-product-video' ); ?>
				</label>
				<textarea class="text widefat" type="text" id="product_video_desc_meta_field" name="product_video_desc_meta_field"><?php echo esc_attr( $product_video_desc ); ?></textarea>
			</div>
	 
			<div class="wrap">
				<label for="product_video_url_meta_field">
					<?php _e( 'Video Url', 'woo-product-video' ); ?>
				</label>
				<input class="text widefat" type="text" id="product_video_url_meta_field" name="product_video_url_meta_field" value="<?php echo esc_attr( $product_video_url ); ?>" />
			</div>
<?php
	}


	/**
	 * save_meta_box_data
	 * function called on save_post hook to sanitize and save the data
	 */
	 
	function save_product_video_meta_box_data( $post_id ){

	  	// Check if nonce is set.
	    if ( ! isset( $_POST['product_meta_box_nonce'] ) ) {
		  return;
	    }
	 
	  	// Verify that the nonce is valid.
	    if ( ! wp_verify_nonce( $_POST['product_meta_box_nonce'], 'product_meta_box' ) ) {
		   return;
	    }
	 
	  	// If autosave, don't do anything
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		  return;
	    }
	 
	  	// Check the user's permissions.
	    if ( isset( $_POST['post_type'] ) && $_POST['post_type'] == 'product' ) {
	            if ( ! current_user_can( 'edit_page', $post_id ) ) {
			     return;
		    }
	 
	    } else {
	            if ( ! current_user_can( 'edit_post', $post_id ) ) {
			     return;
		    }
	    }
	 
	    // Save the information into the database
	    $product_video_url_tab_meta_field = sanitize_text_field( $_POST['product_video_url_tab_meta_field'] );
		update_post_meta( $post_id, 'product_video_url_tab_meta_field', $product_video_url_tab_meta_field );

	    if ( isset( $_POST['product_video_title_meta_field'] ) ) {
	        $product_video_title_meta_field = sanitize_text_field( $_POST['product_video_title_meta_field'] );
		    update_post_meta( $post_id, 'product_video_title_meta_field', $product_video_title_meta_field );
		}

		if ( isset( $_POST['product_video_desc_meta_field'] ) ) {
	        $product_video_desc_meta_field = sanitize_text_field( $_POST['product_video_desc_meta_field'] );
		    update_post_meta( $post_id, 'product_video_desc_meta_field', $product_video_desc_meta_field );
		}

	    if ( isset( $_POST['product_video_url_meta_field'] ) ) {
	        $product_video_url_meta_field = sanitize_text_field( $_POST['product_video_url_meta_field'] );
		    update_post_meta( $post_id, 'product_video_url_meta_field', $product_video_url_meta_field );
		}

	}

?>