<?php

class WC_Product_WPLG_Subscription extends WC_Product_Simple {

	var $time_units = array(), $time_units_singular = array(), $time_units_plural = array();
    
	public function __construct( $product ) {

		$this->product_type = 'wplg_subscription';

		$this->virtual = 'yes';
        //$this->downloadable = 'yes';
        $this->manage_stock = 'no';
		$this->is_wplg_subscription = true;
		parent::__construct( $product );
		
		
		/*
		 * Retrieve renewal parameters for the current product
		*/
		$renewal_fee = get_post_meta( $this->post->ID, 'wplg_subscription_renewal_fee', true );
		$renewal_time_units = get_post_meta( $this->post->ID, 'wplg_subscription_renewal_time_units', true );
		$renewal_time_unit = get_post_meta( $this->post->ID, 'wplg_subscription_renewal_time_unit', true );

		/*
		 * Stores them in the class
		*/
		$this->subscription_name = $this->post->post_title;
		$this->initial_fee = $this->price;
		$this->renewal_fee = $renewal_fee;
		$this->renewal_time_units = $renewal_time_units;
		$this->renewal_time_unit = $renewal_time_unit;
		
		
		
		/*
		 * Localize time units
		 * A time unit is the unit type (day, year, etc)
		 * Time units instead are the amount of that given type
		 * For example: time unit 1 and 2 time units means 2 times the time unit 1 (which is day, as defined below)
		*/
		
		$this->time_units_singular = array(
			'1' => __( 'Day', 'wplg-subscription' ),
			'2' => __( 'Week', 'wplg-subscription' ),
			'3' => __( 'Month', 'wplg-subscription' ),
			'4' => __( 'Year', 'wplg-subscription' ),
		);
		
		$this->time_units_plural = array(
			'1' => __( 'Days', 'wplg-subscription' ),
			'2' => __( 'Weeks', 'wplg-subscription' ),
			'3' => __( 'Months', 'wplg-subscription' ),
			'4' => __( 'Years', 'wplg-subscription' ),
		);
	
		$this->test_for_renewal();
		
		//Will go through cart and make necessary changes to subscription renewals (where necessary)
		add_action( 'woocommerce_before_calculate_totals', array($this, 'apply_to_cart_totals') );
		
	}
	
	/*
	 * This definition of the time units is used when populating the dropdown list while creating a new subscription product type
	*/
	static public function localized_time_units() {
		return array(
			'4' => __( 'Year(s)', 'wplg-subscription' ),
			'1' => __( 'Day(s)', 'wplg-subscription' ),
			'2' => __( 'Week(s)', 'wplg-subscription' ),
			'3' => __( 'Month(s)', 'wplg-subscription' ),
			
		);
	}
	
	
	/******************************************
	 ******************************************
	 ** This part handles the output of component of the plugin
	 ******************************************
	 ******************************************
	 ******************************************
	*/
	
	
	/*
	 * Outputs initial prices with current currency
	*/
	public function output_initial_price() {
		
		$currency_symbol = get_woocommerce_currency_symbol();
		
		return sprintf( __('%2$s%1$01.2f' , 'wplg-subscription'), $this->initial_fee, $currency_symbol );
	}
	
	/*
	 * Outputs a strings that describes the renewal price and time frame
	 * I.e.  "$15 for 6 months"
	*/
	public function output_renewal_string() {
		$renewal_fee = $this->renewal_fee;
		$time_units = $this->renewal_time_units;
		$time_unit = $this->renewal_time_unit;
		
		$currency_symbol = get_woocommerce_currency_symbol();
		
		$time_unit_string = ( 1 == $time_units ) ? $this->time_units_singular[ $time_unit ] : $this->time_units_plural[ $time_unit ];
		
		return sprintf( _n('%2$s%1$01.2f / %4$s', '%2$s%1$01.2f / %3$d %4$s' , $time_units , 'wplg-subscription'), $renewal_fee, $currency_symbol, $time_units, $time_unit_string );
	}
	
	/*
	 * Displays the renewal fee along with the current woocommerce currency
	*/
	public function output_renewal_price() {
		
		return sprintf( __('%1$s%2$01.2f' , 'wplg-subscription' ), get_woocommerce_currency_symbol(), $this->renewal_fee );
	}
	
	
	/*
	 * Outputs a complete string describing the price of the subscription
	 * i.e. $49 then $15 / year
	*/
	public function output_full_price_string( $initial_fee_string ) {
		
		$initial_fee_string = $this->output_initial_price();
		$renewal_string = $this->output_renewal_string();
		
		return sprintf( __( '%1$s then %2$s' , 'wplg-subscription' ), $initial_fee_string, $renewal_string);
	}
	
	/*
	* Prepares the alternative title to be displayed when purchasing a renewal
	* I.e. "My Subscription 2 years license renewal"
	*/
	public function renewal_product_title( ) {
		
		$time_units = $this->renewal_time_units;
		$time_unit = $this->renewal_time_unit;
		
		
		$time_unit_string = ( 1 == $time_units ) ? $this->time_units_singular[ $time_unit ] : $this->time_units_plural[ $time_unit ];
		
		
		return sprintf( __('%1$s %2$d %3$s license renewal', 'wplg-subscription' ) ,  $this->subscription_name, $time_units, $time_unit_string );
		
	}
	
	/******************************************
	 ******************************************
	 ** This part fetches and handles informations about the subscription
	 ******************************************
	 ******************************************
	 ******************************************
	*/
	
	
	/*
	 * Calculates the new expiral time (in unix time) for a renewed license, accordingly to the subscription's renewal lenght
	 * @param	int		$start_time		The unix timestamp of the moment from which we are calculating the new subscription expiral time (default now)
	 * @param	int		$renewal_count	How many renewal cycles are being purchased (1 default)
	*/
	public function calculate_license_expiral_time( $start_time = null, $renewal_count = null ) {
		$renewal_count = intval( $renewal_count );
		if ( !$renewal_count ) $renewal_count = 1;
		if ( !$start_time ) $start_time = time();
		
		
		$time_unit = $this->renewal_time_unit;
		$time_units = $this->renewal_time_units * $renewal_count;
		
		
		
		//Start to prepare string for strtotime()
		$time_modifier = "+$time_units ";
		
		
		if ( 1 == $time_unit ) {
			//Days
			$time_modifier .= "day";
		} elseif ( 2 == $time_unit ) {
			//Weeks
			$time_modifier .= "week";
		} elseif ( 3 == $time_unit ) {
			//Months
			$time_modifier .= "month";
		} elseif ( 4 == $time_unit ) {
			//Years
			$time_modifier .= "year";
		}
		
		$end_time = strtotime( $time_modifier, $start_time );
		
		return $end_time;
	}
	
	
	/*
	 * Returns the unix timestamp of the expiral moment of this subscription for a given user
	 * Will return boolean false if the user has not purchased this subscription yet. 
	 * Thus, this function also provides a test of whether or not user has a subscription
	 *
	 * @param	int		$user_id	The user for which we are checking the subscription or current user if null (default)
	 * 
	*/
	function get_user_expiral_time( $user_id = null ) {
		if ( !$user_id ) $user_id = get_current_user_id();
		
		//Identify the list of subscriptions / renewals by the combination of subscription and user id
		$sub_id = $this->id . '__' . $user_id;
		
		//Prepare interaction with database
		global $wpdb;
		
		$order_items_table = $wpdb->prefix . 'woocommerce_order_items';
		$order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$posts_table = $wpdb->prefix . 'posts';
		
		
		/*
		 * First, look for an expiration time set by admin (through the edit subscription page)
		 */
		$meta_key = '_wplg_sub_admin_expiral__' . $sub_id;
		
		$strSQL = "SELECT MAX( meta.meta_value )
FROM $posts_table ord
JOIN $order_items_table item ON ord.`ID` = item.`order_id`
JOIN $order_itemmeta_table meta ON item.`order_item_id` = meta.`order_item_id`
WHERE meta.`meta_key` = '$meta_key'";
		
		$admin_expiration_time = $wpdb->get_var( $strSQL );
		/*
		 * If found, this value is meant to override anything else and is what will be returned
		 */
		if ( $admin_expiration_time ) return $admin_expiration_time;

		/*
		 * This code will be executed if an expiration time set by admin is not found
		 * It will search for the standard expiration time
		*/
		
		$meta_key = '_wplg_sub_expiral__' . $sub_id;
		
		$strSQL = "SELECT MAX( meta.meta_value )
FROM $posts_table ord
JOIN $order_items_table item ON ord.`ID` = item.`order_id`
JOIN $order_itemmeta_table meta ON item.`order_item_id` = meta.`order_item_id`
WHERE meta.`meta_key` = '$meta_key'";
		
		return $wpdb->get_var( $strSQL );
		
	}
	
	
	/*
	 * Shortcut function that tests whether a given user's license for this subscription is active
	 *
	 * @param	int		$user_id	The user for which we are checking the subscription or current user if null (default)
	 */
	public function is_user_license_active( $user_id = null ) {
		return ( $this->get_user_expiral_time( $user_id ) >= time() );
	}
	
	public function is_user_subscription_order_completed() {
		
	}
	
	
	/*
	 * When a subscription order is set to complete, use the custom statuses active or expired for it, instead of standard "completed"
	*/
	public function get_subscription_status( $order_id, $user_id = null ) {
		$order = new WC_Order( $order_id );
		$order_status = $order->get_status();
		if ( 'completed' == $order_status ) {
			if ( $this->is_user_license_active( $user_id ) ) {
				$subscription_status = 'wc-active-subscription';
			} else {
				$subscription_status = 'wc-expired-subscription';
			}
			return $subscription_status;
		} else return $order_status;
	}
	
	/*
	 * Provides a coherent way to display statuses
	*/
	public function display_subscription_status( $order_id, $user_id = null ) {
		
		$subscription_status = $this->get_subscription_status( $order_id, $user_id);
		
		$subscription_status_label = wc_get_order_status_name( $subscription_status );
		
		$return = '<div class="order_status column-order_status wplg-subscription-button wplg-subscription-status-' . $subscription_status . '">' . $subscription_status_label . '</div>';
		return $return;
	}
	
	/*
	 * If necessary (user is renewing) change the product name and price accordingly
	*/
	public function handle_as_renewal() {
		$this->post->post_title = $this->renewal_product_title();
		
		$this->price = $this->renewal_fee;
	}
	
	/*
	 * Checks whether we are handling a renewal of an existing subscription, and if so makes changes accordingly
	*/
	public function test_for_renewal() {
		/*
		 * get_user_expiral_time() returns 0 if no expiral time is found
		 * which means user does not have purchased this subscription yet, thus it can be used as a test whether or not renewal setting should be applied
		 */
		if ( $expiral = $this->get_user_expiral_time() ) {
			/*
			 * List of hooks where the handle_as_renewal method must be applied to show renewal information instead oge generic product informations
			*/
			$where_renewal = array(
				'woocommerce_before_cart_contents',
				'woocommerce_before_cart_table',
				);
			
			foreach ( $where_renewal as $hook) add_action( $hook, array($this, 'handle_as_renewal'), 10 );
			
			
		}
		
		
	}
	
	
	/******************************************
	 ******************************************
	 ** This part deals with the hooks and filters which will apply the subscription behaviours to WooCommerce
	 ******************************************
	 ******************************************
	 ******************************************
	*/
	
	/**
     * Returns the price in html format
     *
     * @param string $price (default: '')
     * @return string
     */
    public function get_price_html( $price = '' ) {
		//Does this only when NOT renewing, because otherwise the normal price string is used (although with renewal fee)
		if ( !$this->get_user_expiral_time() ) {
			//Display initial price followed by renewal fee
			$price = $this->output_full_price_string( $this->output_initial_price() );
		} else $price = $this->output_renewal_price();
        

        return apply_filters( 'woocommerce_get_price_html', $price, $this );
    }
	
	
	
	
	/*
	 * Goes through the user's cart and, if subscription items that are being renewed are find, makes changes accordingly
	*/
	function apply_to_cart_totals( $cart_object ) {
		
		foreach ( $cart_object->cart_contents as $key => $value ) {
			$item = $value['data'];
			if ( $item->is_wplg_subscription) {
				if ($expiral = $this->get_user_expiral_time() ) $item->handle_as_renewal();
			}
		}
		
	}
	
	/*
	 * Returns the url to the subscription's downloadable file
	*/
	public function get_download_url() {
		$download_url = false;
		$file_list = $this->get_files();
		/* As every subscription only has one file, simply deal with the first element in the list */
		if ( $first_file = reset( $file_list ) ) {
			$download_url = $first_file['file'];
		}
		
		return $download_url;
	}
	
	/*
	 * Returns the complete link to the subscription's downloadable file, with a given caption
	 * @param	string	$caption	The caption of the link; if null, uses the name of the downloadable file (default)
	*/
	public function get_download_link( $caption = null ) {
		$download_url = false;
		$file_list = $this->get_files();
		/* As every subscription only has one file, simply deal with the first element in the list */
		if ( $first_file = reset( $file_list ) ) {
			$download_url = $first_file['file'];
			
			if (!$caption) $caption = $first_file['name'];
			
			$download_url = '<a href="' . $download_url . '" target="_blank">' . $caption . '</a>';
		}
		
		
		return $download_url;
	}
	
	/*
	 * Tells whether or not the download for this subscription is active for a given order
	 * @param	int		$order_id	Id of the order we are handling
	 * @param	int		$user_id	The id of the user, or current user if null
	 * @return	boolean		Whether or not the user can download
	*/
	public function get_download_status( $order_id, $user_id = null ) {
		if ( !$user_id ) $user_id = get_current_user_id();
		
		
		$order = new WC_Order( $order_id );
		$order_status = $order->get_status();
		
		//Which statuses allow the user to download the file
		$allowed_statuses = array( 'completed', 'wc-active-subscription');
		
		if ( !in_array( $order_status, $allowed_statuses ) ) {
			/*
			 * Order not completed, prevent download
			*/
			$order_status_name = wc_get_order_status_name( $order_status );
		} else {
			if ( $this->is_user_license_active( $user_id ) ) {
				/*
				 * User can download only if this is returned
				 * Notice this is never displayed and should NOT be internationalized
				 */
				$order_status_name = 'available';
			} else {
				$order_status_name = __( 'Expired' ,'wplg-subscription' );
			}
		}
		return $order_status_name;
	}
	
	/*
	 * Displays buttons to download (if licens is active ) and renew
	*/
	public function output_customer_actions( $order_id, $user_id = null ) {
		$download_status = $this->get_download_status( $order_id, $user_id);
		
		if ( 'available' != $download_status ) {
			/*
			 * Order not completed or license not active, prevent download
			*/
			$download_button = '<a class="wplg-subscription-expired-button wplg-subscription-button">' . $download_status . '</a>';	
		} else {
			/*
			 * Lincese is active and download available
			*/
			$download_url = $this->get_download_url();
			if ( !$download_url ) {
				$download_button = '<a class="wplg-subscription-download-button wplg-subscription-button">' . __( 'File not available' ,'wplg-subscription' ) .'</a>';		
			} else {
				$download_button = '<a target="_blank" href="' . $download_url . '" class="wplg-subscription-download-button wplg-subscription-button">' . __( 'Download' ,'wplg-subscription' ) .'</a>';	
			}
		}
		
		
		global $woocommerce; 
		
		$cart = new WC_Cart();
        $cart_url = $cart->get_cart_url();
		
		$renew_button = '<a href="' . add_query_arg( 'add-to-cart', $this->id, $cart_url ) . '" class="wplg-subscription-renew-button wplg-subscription-button">' . __( 'Renew' ,'wplg-subscription' ) .'</a>';
		return $download_button . $renew_button;
	}
}
