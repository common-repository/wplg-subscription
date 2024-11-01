<?php
/*
Plugin Name: WPLG – Subscription
Plugin URI: http://www.wpletsgo.com/wplg-woocommerce-subscription-plugin/
Description: Adds subscription options to downloadable products via WooCommerce. 
Version: 1.0.1
Author: wpletsgo
Author URI: http://wpletsgo.com/
License: GPLv2 or later
Text domain: wplg-subscription
Domain path: /languages
*/

define( 'WPLGSUB_VERSION', '1.0.1' );


/*
 * The class defines most of the subscription functionalities, while this file defines its interaction with wordpress
*/

require_once( 'class-wplg-subscription.php' );

$wplg_subscription = new WPLG_Subscription();	


/**
 * Register the custom product type after init
 */
function register_wplg_subscription_product_type() {
	require_once('class-wc-product-wplg-subscription.php');
}
add_action( 'init', 'register_wplg_subscription_product_type' );


/**
 * Add the custom product type to woocommerce types
 */

function add_wplg_subscription_product_type( $types ){

	// Key must be exactly the same as in the class product_type parameter
	$types[ 'wplg_subscription' ] = __( 'Subscription', 'wplg-subscription' );

	return $types;

}


add_filter( 'product_type_selector', 'add_wplg_subscription_product_type' );

/* Schedule the function that checks subscriptions and send email reminders when necessary */
register_activation_hook(__FILE__, 'wplg_subscription_schedule_reminders');

function wplg_subscription_schedule_reminders() {
    if (! wp_next_scheduled ( 'wplg_subscription_daily_schedule' ) ) {
		wp_schedule_event(time(), 'daily', 'wplg_subscription_daily_schedule' );
    } 
}

add_action('wplg_subscription_daily_schedule', function() {
	global $wplg_subscription;
		
	$wplg_subscription->send_reminder_emails();
});


register_deactivation_hook(__FILE__, 'wplg_subscription_remove_schedule_reminders');

function wplg_subscription_remove_schedule_reminders() {
	wp_clear_scheduled_hook('wplg_subscription_daily_schedule');
}

/*
 * Add subscription settings to ‘General’ sub-menu of the product type we defined
 */
add_action( 'woocommerce_product_options_general_product_data', 'wplg_subscription_general_settings' );
function wplg_subscription_general_settings() {
	global $woocommerce, $post, $wplg_subscription;
    echo '<div class="wplg_subscriptions_options_group show_if_wplg_subscription">';

    // Create a number field for renewal fee
    woocommerce_wp_text_input(
    array(
       'id'                	=> 'wplg_subscription_renewal_fee',
	   'label'             	=> __( 'Renewal fee', 'wplg-subscription' ),
       'placeholder'       	=> '',
       'desc_tip'    	   	=> 'true',
       'description'       	=> __( 'When a customer purchases this product, the regular or sales price will be charged. Select a renewal fee and a renewal period here. If the license is not renewed, the customer will no longer have access to the product.', 'wplg-subscription' ),
       'type'              	=> 'text'
    ));
	
	// Create a number field for the number of time units a renewal is valid for
    woocommerce_wp_text_input(
    array(
       'id'               	=> 'wplg_subscription_renewal_time_units',
       'label'             	=> __( 'Per', 'wplg-subscription' ),
       'value'				=> 	1,
	   'placeholder'       	=> '',
       'type'              	=> 'number'
    ));

    // List of selectable time units (i.e. days, weeks, months, years)
	woocommerce_wp_select(
	array(
		'id' 				=> 'wplg_subscription_renewal_time_unit',
		'label'				=>  null,
		'options' 			=> WC_Product_WPLG_Subscription::localized_time_units(),
	));
	
	echo '</div>';
}

	

	/*
	 * Save the custom subscription options when product is created
	*/
add_action( 'woocommerce_process_product_meta', 'wplg_subscription_save_custom_settings' );
function wplg_subscription_save_custom_settings( $post_id ){
	//Options to save
	$save = array(
		'wplg_subscription_renewal_fee',
		'wplg_subscription_renewal_time_units',
		'wplg_subscription_renewal_time_unit',
				  );
	
	// save renewal fee
	foreach ($save as $option) {
		$posted_value = $_POST[ $option ];
		if ( !empty( $option) ) {
			//Store it for this element
			update_post_meta( $post_id, $option, esc_attr( $posted_value) );	
		}
	}
}





if ( is_admin() ) {

	
	/*
	 * Admin actions
	*/
	add_action( 'admin_menu', function () {
		global $wplg_subscription;
		
		/*
		 * External resources
		*/
		wp_enqueue_style( 'wplg-subscription-settings-css', plugins_url( '/css/wplg-subscription-settings.css', __FILE__ ) );
		
		
		/*
		 * Javascript used to retrieve standard options for Subscription type, and to show / hide subscription options according
		*/
		add_action('admin_enqueue_scripts','wplg_subscription_scripts');
		function wplg_subscription_scripts() {
			wp_enqueue_script( 'wplg-subscription-js', plugins_url( '/js/wplg-subscription.js', __FILE__ ));
		}
		
		add_menu_page( $wplg_subscription->main_title, $wplg_subscription->main_title, 'manage_woocommerce',  'wplg-subscription-menu',
					  /* Front menu page displays informations about the plugin */
					  function() {
						global $wplg_subscription;
						echo '<h1>' . $wplg_subscription->main_title. '</h1>';
						echo '<p>' . __( 'The <b>WPLG – Subscription</b> plugin is a WordPress plugin that works with and extends the functionality of the WooCommerce plugin. It is designed to create subscription based <b>downloadable products</b> via WooCommerce and set different renewal price and period for each product.' , 'wplg-subscription' ) . '</p>';
						
						echo '<p>' . __( 'To display subscription details to the customer, please add the following shortcode to your page (preferably into the WooCommerce my-account page): <b>[wplg_subscription_my_list]</b>', 'wplg-subscription' ) . '</p>';
					  } , $icon = plugin_dir_url( __FILE__ ) . 'assets/icon-active.png' );
		
		foreach ( $wplg_subscription->_menu_pages as $key => $title ) {
			add_submenu_page ( 'wplg-subscription-menu', $wplg_subscription->main_title . ' &mdash; ' . $title, $title, 'manage_woocommerce', 'wplg_subscription_' . $key, array($wplg_subscription, 'admin_page_' . $key ) );	
		}
		
		/* Page to request confirmation before orders containing subscriptions are deleted */
		$confirm_post_deletion_title = __( 'Confirm order deletion' , 'wplg-subscription' );
		add_submenu_page ( null, null, $confirm_post_deletion_title , 'manage_woocommerce', 'wplg_subscription_request_order_delete_confirmation' , array($wplg_subscription, 'request_order_delete_confirmation' ) );	
		
	});
	
	add_action( 'admin_init', function () {
		/*
		 * Register settings
		*/
		global $wplg_subscription;
		foreach ( array_keys( $wplg_subscription->_options ) as $option ) {
			register_setting( 'wplg-subscription-option-group', 'wplg_subscription_' . $option );
		}
	});
} else {
	add_action ('init', function(){
	   //Plugin graphics
	   wp_enqueue_style( 'wplg-subscription-settings-css', plugins_url( '/css/wplg-subscription-settings.css', __FILE__ ) );
	});
	
}



/*
 * Order statuses for subscriptions
*/
function register_subscriptions_order_status() {
    register_post_status( 'wc-active-subscription', array(
        'label'                     => __( 'Active', 'wplg-subscription'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>' )
    ) );
	
	register_post_status( 'wc-expired-subscription', array(
        'label'                     => __( 'Expired', 'wplg-subscription'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'register_subscriptions_order_status' );

//Add the new statuses to woocommerce statuses
function wc_add_subscription_statuses( $order_statuses ) {
  
    $new_order_statuses = array();
  
    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {
  
        $new_order_statuses[ $key ] = $status;
  
        if ( 'wc-completed' === $key ) {
			$new_order_statuses['wc-active-subscription'] = __( 'Active', 'wplg-subscription');
            $new_order_statuses['wc-expired-subscription'] = __( 'Expired', 'wplg-subscription');
        }
    }
  
    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'wc_add_subscription_statuses' );


/******************************************
 *	Handler subscription after order
 ******************************************
 ******************************************
 ******************************************/


add_action('woocommerce_thankyou','wplg_subscription_order_item_meta');
if(!function_exists('wplg_subscription_order_item_meta'))
{
	/*
	 * @param object|int	$order			Either an order id or a WC_Order object
	 * @param	int			$start_time		A unix time for the subscription start time, or current time if null (default)
	 * @param	int			$expiral_time	If set, force the subscription to end at that time, otherwise calculate the time according to its license length
	 * @param	boolean		$is_new_subscription	If set, forces the item to be stored as a new subscription (if true ) or as a renewal (if false ). If null (default) let the code determine which
	*/
	function wplg_subscription_order_item_meta($order, $start_time = null, $expiral_time = null, $is_new_subscription = false ) {
	
		if ( !$start_time ) $start_time = time();
        global $woocommerce;
        /*
		 * If there are some subscription types here, add meta value so they are then recognized
        */
		if (!is_object( $order) ) {
			$order = new WC_Order( $order );
		} 
		
		$user_id = $order->customer_user;
		
		$items = $order->get_items();
		
		
		
		foreach ( $items as $item_id => $item ) {
			//Fill with the subscription data for this item
			$order_item_meta = array();
		
			$product = $order->get_product_from_item( $item );
			/* Loop over WPLG Subscription products */
			if ( $product->is_wplg_subscription ) {
				/*
				 * Get current expiration time for this subscription and this user
				 */
				$current_expiral_time = $product->get_user_expiral_time( $user_id );
				
				/*
				 * This also serves as a test on whether this is a new subscription or a renewal,
				 * since if user has not this subscription yet he will not have an expiral time either
				*/
				if ( $is_new_subscription ) $is_renewing = false;
				else $is_renewing = (bool) $current_expiral_time ;
				
				/*
				 * Store when subscription (or renewal) is purchased
				*/	
				if ( $is_renewing ) {
					$order_item_meta['_wplg_subscription_renewal_time'] = $start_time;
				} else {
					$order_item_meta['_wplg_subscription_start_time'] = $start_time;
				}
				
				if ( !$expiral_time ) {
					//If expiral time is in the future (subscription is active) use that value, otherwise use present moment as start time
					$new_license_start_time = max( $current_expiral_time , time() );
					$new_expiral_time = $product->calculate_license_expiral_time( $new_license_start_time, $item['qty'] );
				} else {
					//If a specific expiral time is passed (i.e. when creating subscription from admin backend ), use that value
					$new_expiral_time = $expiral_time;
				}

				/* this will be used to identify the whole subscription expiral for this user (across different purchases / renewals ) */
				$sub_id = $product->id . '__' . $user_id;
				$order_item_meta['_wplg_sub_expiral_id'] = $sub_id;
				$order_item_meta[ '_wplg_sub_expiral__'. $sub_id ] = $new_expiral_time;
				
				
				/*
				 * This one pertains ONLY this current item, regardless of future purchrases / renewals
				*/
				$order_item_meta['_wplg_subscription_expiral_time'] = $new_expiral_time;
				
				//Apply order item meta
				foreach ( $order_item_meta as $meta_key => $meta_value ) {
					wc_add_order_item_meta( $item_id, $meta_key, $meta_value, $unique = true );
				}
			}	//End of If subscription type
		} // End of items loop
		
  }
}

add_action( 'woocommerce_order_status_completed', array( $wplg_subscription, 'send_welcome_email') );

/* Clear subscription data when order is deleted */
add_action( 'woocommerce_api_delete_order', array( $wplg_subscription, 'delete_subscription_data' ) );

/*
 * Used to fetch user's billing details and populate the corresponding form in the new subscription page
*/
add_action( 'wp_ajax_wplg_subscription_billing_details', 'ajax_fetch_user_billing_details' );
/*
* User by ajax to retrieve a customer's billing details when they are selected from the dropdown list, in the "New subscription" page
*/
function ajax_fetch_user_billing_details() {
   global $woocommerce;
   
   $customer_id = intval( $_POST['customer_id'] );
   
   $return = array();
   
   
   
   $billing_fields = $woocommerce->countries->get_address_fields( null , 'billing_' );
   
   
   
   $user_values = get_user_meta( $customer_id );
		   
   
   
   foreach( $billing_fields as $field_key => $data ) {
		   $return[ $field_key ] = $user_values[ $field_key ];
	   }
	   
	echo json_encode( $return );
	wp_die();
}

/*
 * Shortcode to display User's downloads
*/
add_shortcode( 'wplg_subscription_my_list' , array( $wplg_subscription, 'customer_subscriptions') ); 

/*
 * Filter that removes expired / deleted subscriptions from WooCommerce's standard "Available Downloads" section
*/
add_filter( 'woocommerce_customer_get_downloadable_products',
		   'block_standard_downloads'
		   ) ;
function block_standard_downloads( $downloads ) {
	
	foreach ( $downloads as $array_key => $download) {
		
		$order = new WC_Order( $download['order_id'] );
		
		if ( get_post_meta( $download['order_id'], 'subscription_deleted' ) ) {
			/*
			 * Subscrition has been deleted, so do not display the download
			*/
			unset( $downloads[ $array_key] );
		}
		
		$items = $order->get_items();
		
		foreach ( $items as $item_id => $item ) {
			$product = $order->get_product_from_item( $item );
			/* Loop over WPLG Subscription products */
			if ( $product->is_wplg_subscription && $download['order_id'] == $product->id ) {
				/*
				 * Do not show is subscription statis is not available
				 * This may be because the order is not complete or because the license is not active
				*/
				if ( 'available' != $product->get_download_status( $download['order_id'] ) ) {
					unset( $downloads[ $array_key] );
					}				
			}

		}
	}
	
	return $downloads;
	}


/*
* Enqueue external files for manage_subscription() page
*/
function manage_subscription_enqueue($hook) {
   if ('wplg-subscription_page_wplg_subscription_manage_subscription' != $hook ) return;
   // Load the datepicker script (pre-registered in WordPress).
	   wp_enqueue_script( 'jquery-ui-datepicker' );
   
	   // Styling for the datepicker. Hosted jQuery UI CSS.
	   wp_enqueue_style( 'jquery-ui-datepicker-css', plugins_url( '/css/jquery-ui.css', __FILE__ ) );
}

add_action( 'admin_enqueue_scripts', 'manage_subscription_enqueue');

	
/*
 * Ask confirmation before orders containing subscriptions are deleted
*/		
function wplg_subscription_restrict_post_deletion($post_ID ){
	
	if ( isset( $_GET['wplg-subscription-confirm-delete'] ) && $_GET['wplg-subscription-confirm-delete'] ) {
		/* Confirmed already, so just go with standard function */
		return; 
	} 
	
	/*
	 * For subscription containing orders, prompt for a confirmation form 
	*/
    $type = get_post_type($post_ID);
    if ($type == 'shop_order') {
            $order = new WC_Order($post_ID);
           
		   $items = $order->get_items();
			
			foreach ( $items as $item_id => $item ) {
				
				$product = $order->get_product_from_item( $item );
				/* Loop over WPLG Subscription products */
				if ( $product->is_wplg_subscription ) {
					/* If any subscription is found, instead of deleting redirects to a confirmation post */
					
					wp_redirect( add_query_arg( $_GET, admin_url( 'admin.php?page=wplg_subscription_request_order_delete_confirmation' ) ) );
					exit();
				}
			}
    }
}
add_action('wp_trash_post', 'wplg_subscription_restrict_post_deletion', 10, 1);
add_action('before_delete_post', 'wplg_subscription_restrict_post_deletion', 10, 1);