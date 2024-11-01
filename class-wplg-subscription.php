<?php

class WPLG_Subscription {
    
    var $main_title;
	
	
    /*
     * List of options (settings) the plugin stores
    */
    var $_options = array();
    /*
     * List of admin menu page
    */
    var $_menu_pages = array();
	
    public function __construct() {
		add_action( 'init', array( $this, 'setup_plugin' ) );
		
    }
	
	public function setup_plugin() {
		/*
		* Load text domain
	   */
		
		load_plugin_textdomain( 'wplg-subscription', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
		
		/*
		 * Check proper php requirements (uncomment if required)
		* /
		$php_required_version = "5.4";
		$required_version_message = sprintf( __('Your PHP version (' . PHP_VERSION .') is out of date. Plugin <b>WPLG Subscription</b> is developed for PHP %s or beyond. It is recommended to upgrade your php version.', 'wplg-subscription'), $php_required_version ) ;
		//(uncomment if required)
		if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) add_action( 'admin_notices', create_function( '$required_version_message', "echo '<div class=\"error\"><p>". $required_version_message ."</p></div>';" ) ); */
		
		$this->main_title = __( 'WPLG - Subscription', 'wplg-subscription' );
		
		/*
		 * Default values for menu pages
		 * The key value will be used to create the url and to call the page.
		 * I.e. 'my_menu_page' => 'My title'
		 * will call the function of this class admin_page_my_menu_page(), which will call a function my_menu_page() of this class
		 * and will be opened at the link wplg_subscription_my_menu_page
		*/
		$this->_menu_pages = array(
			'subscriptions'      	=>      __( 'Subscriptions' , 'wplg-subscription'),
			'manage_subscription'   =>      __( 'New subscription' , 'wplg-subscription'),
			'settings'      	 	=>      __( 'Settings' , 'wplg-subscription'),
								   );
			
		/*
		* Default values for the option fields
		*
		* This plugin's options regard automatic emails that can be sent at different times
	    *
		* Reminder's email code must end with '_reminder': these will be scheduled to be called daily, as they occur on certain days before or after a given event
		* Other type of emails can be set too, but they must be called directly when needed (i.e. welcome email)
	   */
		
		$this->_options = array(
			/*
			 * Welcome email is sent when a new subscription is completed
			*/
			'welcome_email_enabled' => false,
			'welcome_email_subject' => __( 'Thank you for your payment', 'wplg-subscription' ),
			'welcome_email_heading' => __( 'Payment received', 'wplg-subscription' ),
			'welcome_email_content' => __( 'Dear {user_name}!
We would like to let you know that your payment for {product_name} has been received and your subscription is now activated. You may download your product by clicking the following link: {item_download_link}' , 'wplg-subscription' ),
			/*
			 * Expiration reminder is send N days before a subscription expires. N value is configured by the variable expiration_reminder_days
			*/
			'expiration_reminder_email_enabled' => false,
			'expiration_reminder_days' => 10,
			'expiration_reminder_email_subject' => __( 'Your {site_title} subscription is about to expire', 'wplg-subscription' ),
			'expiration_reminder_email_heading' => __( 'Your subscription is expiring', 'wplg-subscription' ),
			'expiration_reminder_email_content' => __( 'Dear {user_name}!
We would like to let you know that your {product_name} subscription is expiring in {reminder_period} days. You may now renew your licence by clicking on the following link: {renew_product}' , 'wplg-subscription' ),
			/*
			 * Expired reminder is send N days after a subscription expires. N value is configured by the variable expired_reminder_days
			*/
			'expired_reminder_email_enabled' => false,
			'expired_reminder_days' => 10,
			'expired_reminder_email_subject' => __( 'Your {site_title} subscription has expired', 'wplg-subscription' ),
			'expired_reminder_email_heading' => __( 'Your subscription expired', 'wplg-subscription' ),
			'expired_reminder_email_content' => __( 'Dear {user_name}!
We would like to let you know that your {product_name} subscription has expired {reminder_period} days ago. You may now renew your licence by clicking on the following link: {renew_product}' , 'wplg-subscription' ),
			
			/*
			 * Payment reminder is send N days after a subscription checkout, if the payment is not yet completed. Addittional X reminders are sent at N days intervals. After the last reminder, the subscriptions status is set to the status defined in payment_reminder_final_status
			 * N value is configured by the variable payment_reminder_days
			 * X value is configured by the variable payment_reminder_max_times
			*/
			'payment_reminder_email_enabled' => false,
			'payment_reminder_days' => 10,
			'payment_reminder_max_times' => 5,
			'payment_reminder_final_status' => 'Cancelled',
			'payment_reminder_email_subject' => __( 'Your {site_title} order is still pending', 'wplg-subscription' ),
			'payment_reminder_email_heading' => __( 'Your order is pending', 'wplg-subscription' ),
			'payment_reminder_email_content' => __( 'Dear {user_name}!
We would like to let you know that you haven&#39;t completed the order for {product_name}, which you placed on {order_date}' , 'wplg-subscription' ),
		);
		
		/*
		 * Use _options_groups and _options_properties to define a set of grouped options.
		 * For each group (defined by a prefix) the option will be prefix_property
		*/
		
		/*
		 * Defined a set of prefixes to group the options by.
		 * All options that will have a certain prefix will go within a "group"
		 * Each group option reside in a collapsable box
		 * The array is in the format prefix => group_name
		*/
		$this->_options_groups = array(
			'welcome'			=> __( 'Enable welcome email', 'wplg-subscription' ),
			'expiration_reminder'	=> __( 'Enable expiration reminder', 'wplg-subscription' ),
			'expired_reminder'		=> __( 'Enable expired reminder', 'wplg-subscription' ),
			'payment_reminder'		=> __( 'Enable payment reminder', 'wplg-subscription' ),
				);
		
		/*
		 * Defined a set of properties to be used for each group
		 * The array is in the format property => group_name
		*/
		$this->_options_properties = array(
			'email_enabled'			=> __( 'Enable E-mail' , 'wplg-subscription' ),
			//For reminders
			'days'					=> __( 'Send reminder' , 'wplg-subscription' ),
			//Email settings
			'email_subject'			=> __( 'E-mail subject' , 'wplg-subscription' ),
			'email_heading'			=> __( 'E-mail heading' , 'wplg-subscription' ),
			'email_content'			=> __( 'E-mail content' , 'wplg-subscription' ),
				);
		
		/*
		 * Finally, add specific labels to elements which are not recurrent
		*/
		$this->_options_labels = array(
			'expiration_reminder_days'			=> __( 'days before the subscription expires' , 'wplg-subscription' ),
			'expired_reminder_days'				=> __( 'days after the subscription expired' , 'wplg-subscription' ),
			'payment_reminder_days'				=> __( 'days after checkout' , 'wplg-subscription' ),
			'payment_reminder_max_times' 		=> __( 'Max number of emails to send' , 'wplg-subscription' ),
			'payment_reminder_final_status' 	=> __( 'After last reminder change status to' , 'wplg-subscription' ),
				);
		
		foreach ( $this->_options as $option_name => $default_value ) {
			/*
			 * Retrieve stored values for options, if available
			*/
			if ($stored_value = get_option( 'wplg_subscription_' . $option_name) ) {
				$this->_options[ $option_name ] = $stored_value;
			}
		}
	}
	
    
	/****************************************
	 * MANAGE SUBSCRIPTIONS SECTION
	 ****************************************
	 * Functions to edit, delete, fetch subscription data
	 ****************************************
	 ****************************************
	*/
	
	/*
	 * When an order containing a subscription is deleted or trashed, this form will be used to request confirmation
	*/
	public function request_order_delete_confirmation() {
		echo '<h2>' . __( 'Confirm order deletion' , 'wplg-subscription' ) . '</h2>';
		
		if ( !isset( $_GET['post'] ) ) {
			echo __( 'You need to specify some valid orders' , 'wplg-subscription' );
			return ; 
		}
		
		if ( isset( $_GET[ 'wplg-subscription-confirm-delete' ] ) && $_GET[ 'wplg-subscription-confirm-delete' ] ) {
			/*
			 * Fetch the posts to be deleted
			*/
			$post = (array) $_GET['post'];
			$deleted = 0;
			$delete_failed = 0;
			foreach ( $post as $post_id ) {
				if ( isset( $_GET['action'] ) && 'trash' == $_GET['action'] ) {
					$res = wp_trash_post( $post_id );
				} else $res = wp_delete_post( $post_id );
				if ( false === $res) {
					$delete_failed++;
				} else {
					$deleted++;
				}
			}
			
			/*
			 * If some posts failed to be deleted, outputs a notice
			*/
			if ( $delete_failed ) {
				add_action( 'admin_notices', function( $delete_failed) {
						$class = 'notice notice-error';
						$message = sprintf( __( 'Failed to delete or trash %d orders', 'wplg-subscription' ), $delete_failed );
					
						printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
					} );				
			}
			
			$class = 'notice notice-success';
			$message = sprintf( __( 'You have proceeded to trash or delete %d orders. The subscription data associated with these orders have been affected accordingly', 'wplg-subscription' ), $deleted );
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			

			/*
			 * Reload the page to trash or delete, along with confirmation. This options maintains the standard wordpress output, but gives problems with the nonce verification on some systems
			* /
			$query_args = $_GET;
			$query_args['wplg-subscription-confirm-delete'] = 1;
			unset( $query_args['page'] );
			$confirm_link = add_query_arg( $query_args, admin_url('edit.php') );
			wp_redirect($confirm_link);
			
			exit();*/
		} else {
			$query_args = $_GET;
			$query_args['wplg-subscription-confirm-delete'] = 1;
			$confirm_link = add_query_arg( $query_args, admin_url('admin.php') );
			
			echo '<p>' . __( 'Please notice this order contains some <b>WPLG Subscription</b> items. If you proceed, the subscription will be affected as well.' , 'wplg-subscription' ) . '</p>'
				. '<a class="wplg-subscription-download-button wplg-subscription-button" href="' . $confirm_link . '">' . __( 'Continue', 'wplg-subscription'). '</a>';
			
			
			/*$form =  '<p>' . __( 'Please notice this order contains some <b>WPLG Subscription</b> items. If you proceed, the subscription will be affected as well.' , 'wplg-subscription' ) . '</p>'
					. '<form method="POST"><input type="hidden" name="wplg-subscription-confirm-delete" value="1">'
					. '<button class="wplg-subscription-download-button wplg-subscription-button" type="submit">' . __( 'Continue', 'wplg-subscription'). '</button>'
					. '</form>';
			echo $form;	*/
		}
		
		
		

	}
	
	/*
	 * Creates an array with the data of a certain subset of subscriptions
	 * @param		string|array		$what			Which data to fetch; Accepts an array with the columns to return, or a string 'all' to return everything (default), or 'count' to return the number of matching subscriptions only
	 * @param		string				$where  		Additional conditions for the query in SQL format. If null, fetches all subscriptions
	 * @param		string				$result_type	The format in which wpdb must result the info (applied only if $what is not 'count'	
	*/
	public function fetch_subscriptions_data($what = 'all', $where = null, $limit = null, $offset = 0, $result_type = OBJECT ) {
		global $wpdb; 
		
		if (null == $what ) $what = 'all';
		
		if ( 'all' == $what ) {
			$selector = '*';
		} elseif ('count' == $what ) {
			$selector = 'COUNT(order_id)';
		} elseif ( is_array( $what ) ) {
			$selector = implode(", ", $what );
		}
		
		if ( $where ) {
			/* Append additional conditions to query string */
			$where = " AND ($where)";
		}
		
		/*
		 * If necessary add offset and limit
		*/
		$limit_elements = '';
		
		
		if ( $limit = intval( $limit ) ) {
			$limit_elements .= " LIMIT $limit";
		}
		
		if ( $offset = intval( $offset ) ) {
			$limit_elements .= " OFFSET $offset";
		}
		
		/*
		 * Fetches a list of orders which contain WPLG subscriptions, and return the single elements
		*/
		$order_items_table = $wpdb->prefix . 'woocommerce_order_items';
		$order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		$posts_table = $wpdb->prefix . 'posts';
		
		$strSQL = "SELECT $selector
FROM $posts_table ord
JOIN $order_items_table item ON ord.`ID` = item.`order_id`
JOIN $order_itemmeta_table meta ON item.`order_item_id` = meta.`order_item_id`
WHERE meta.`meta_key` = '_wplg_subscription_start_time'" . $where . $limit_elements;
		
		if ( 'count' == $what ) {
			$res = $wpdb->get_var($strSQL);
		} else {
			$res = $wpdb->get_results( $strSQL, $result_type );
		}
			
		
		return $res;
		
	}
	
	
	/*
	 * Allow to change the expiral data of a subscription, given an item_id
	*/
	public function edit_subscription() {
		
		/*
		 * Fetches data
		*/
		
		//order id + order item id
		$order_id = intval( $_GET['order_id'] );
		$order_item_id = intval( $_GET['order_item_id'] );
		
		$order = new WC_Order( $order_id );
		$items = $order->get_items();
			
		foreach ( $items as $item_id => $item ) {
			if ( $item_id == $order_item_id ) {
				$product = $order->get_product_from_item( $item );
				$current_item = $item;
			}
		}

		$customer_id = get_post_meta( $order_id, '_customer_user', true );
		
		/*
		 * Divide the page in different sections, each of which will be opened when clicking on its name
		*/
		$page_sections = array(
			'purchase_details' 	=> __( 'Purchase details', 'wplg-subscription' ),
			'customer_details' 	=> __( 'Customer details', 'wplg-subscription'),
			'edit_schedule'		=> __( 'Edit subscription schedule', 'wplg-subscription' ),
			);
		
		/* Name of the icon to be used*/
		$page_sections_icons = array(
			'purchase_details' 	=> 'dashicons-cart',
			'customer_details' 	=> 'dashicons-admin-users',
			'edit_schedule'		=> 'dashicons-calendar-alt',
			);
		
		
		/*
		 * Define the content for each section
		*/
		
		/*
		 * Purchase details
		*/
		$guid = $product->post->guid;
		$product_link = '<a target="_blank" href="' . $guid . '">' . $product->post->post_title . '</a>';
		
		$purchase_details =  '<p>' . sprintf( __( '<b>Subscription name</b>: %s' , 'wplg-subscription' ) , $product_link) . '</p>';
		$purchase_details .=  '<p>' . sprintf( __( '<b>Price</b>: %s' , 'wplg-subscription' ) , $product->output_initial_price() ) . '</p>';
		
		
		
		//start and expiration date
		$start_date =	date_i18n( 'F j, Y' , wc_get_order_item_meta( $order_item_id, '_wplg_subscription_start_time' ) );
		$expiration_date = date_i18n( 'F j, Y' , $product->get_user_expiral_time( $customer_id ) );
		$purchase_details .=  '<p>' . sprintf( __( '<b>Date of purchase</b>: %s' , 'wplg-subscription' ) , $start_date ) . '</p>';
		$purchase_details .=  '<p>' . sprintf( __( '<b>Subscription expires</b>: %s' , 'wplg-subscription' ) , $expiration_date ) . '</p>';
		
		//Renewal fee
		
		$purchase_details .=  '<p>' . sprintf( __( '<b>Price</b>: %s' , 'wplg-subscription' ) , $product->output_renewal_price() ) . '</p>';
		
		
		//payment method
		//get_post_meta( $order->id, '_payment_method', true );
		$purchase_details .=  '<p>' . sprintf( __( '<b>Payment method</b>: %s' , 'wplg-subscription' ) , $order->payment_method ) . '</p>';
		//Subscription status
		$purchase_details .=  '<div class="wplg-edit-subscription-entry">' . sprintf( __( '<b>Subscription status</b>: %s' , 'wplg-subscription' ) , $product->display_subscription_status( $order_id, $customer_id ) ) . '</div>';
		
		
		/*
		 * Customer details
		*/
		$customer = get_userdata( $customer_id );
		$user_page_url = admin_url( 'user-edit.php?user_id='.$customer_id );
		$user_display= '<a target="_blank" href="' . $user_page_url . '">' . $customer->display_name . '</a>';
		
		
		$customer_details =  '<p>' . sprintf( __("<b>Customer's name</b>: %s") , $user_display ) . '</p>';
		$customer_details .=  '<p>' . sprintf( __("<b>Email address</b>: %s") , $customer->user_email ) . '</p>';
		global $woocommerce;
		
		$billing_fields = $woocommerce->countries->get_address_fields( $this->get_value('billing_country'), 'billing_' );
		foreach( $billing_fields as $field_key => $data ) {
				$value = get_user_meta( $customer_id, $field_key, true );
				if ( $value ) {
					$label = ( isset( $data['label'] )) ? $data['label']: $field_key;
					$customer_details .=  '<p><b>' . $label. '</b>: ' . $value . '</p>';					
				}

			}
		
		/*
		 * Edit subscription schedule
		 */
		$edit_schedule = '<form>';
		$edit_schedule .= '<input type="hidden" name="page" value="wplg_subscription_manage_subscription" />';
		$edit_schedule .= '<input type="hidden" name="action" value="update" />';
		$edit_schedule .= '<input type="hidden" name="order_id" value="' . $order_id . '" />';
		$edit_schedule .= '<input type="hidden" name="customer_id" value="' . $customer_id . '" />';
		$edit_schedule .= '<input type="hidden" name="subscription_id" value="' . $product->id . '" />';
		$edit_schedule .= '<input type="hidden" name="order_item_id" value="' . $order_item_id . '"  />';
		
		$edit_schedule .= '<label for="wplg-subscription-edit-date">' . __('Edit expiration date', 'wplg_subscription') . '</label>';
		$edit_schedule .= '<input id="wplg-subscription-edit-date" name="wplg-subscription-edit-date" value="'. $expiration_date .'" type="text"/>';
		
		//Close form
		$edit_schedule .= '<button class="button button-primary" type="submit">' . __( 'Save changes', 'wplg-subscription') . '</button></form>';
		
		
		
		/*
		 * Output all
		*/
		$side_menu = null;
		$content_container = null;
		$first = 1;	//after the first one, this will be set to zero, so the following one will not be displayed untile the section key is clicked
		foreach ( $page_sections as $section_id => $label ) {
				/* classes to add to the menu */
				$classes = array('dashicons-before');
				if ( isset( $page_sections_icons[$section_id] ) ) $classes[] = $page_sections_icons[$section_id];
				
				/* Only show the first one, and store it as currently displayed through data (used by the javascript) */
				$display = ($first) ? null : 'style="display:none;"';
				if ($first) $classes[] = 'wplg-edit-page-selected';
				
				$side_menu .= '<li class="' . implode( ' ', $classes ) . '" data-section-id="' . $section_id . '"><span>'. $label .'</span></li>';
				
				
				
				$content_container .= '<div id="wplg-edit-page-section-container-' . $section_id . '" '.$display.'>' . $$section_id . '</div>';
				$first = 0;
		}
		echo  '<div id="wplg-edit-subscription-container">'
				. '<ul id="wplg-edit-page-section-titles">'. $side_menu . '</ul>'
				. '<div id="wplg-edit-page-section-containers">'. $content_container . '</div>'
			.'</div>';
		
		
		//The script will add the datepicker for changing the schedule and the functionality to open /close the divs
		wp_enqueue_script( 'wplg-edit-subscription-js', plugins_url( '/js/wplg-edit-subscription.js', __FILE__ ));
		//The style will provide the graphical outlook for the side menu where to choose the section to show
		wp_enqueue_style( 'wplg-edit-subscription-css', plugins_url( '/css/wplg-edit-subscription-form.css', __FILE__ ));
	}
	
	public function delete_subscription() {
		if ( 'confirmed_delete' == $_GET['action'] ) {
			if ( wc_delete_order_item( intval( $_GET['order_item_id'] ) ) ) {
				/*
				* Stores the information about a deleted subscription
				* Mainly used to prevent to display deleted subscription in the standard WooCommerce "Available downloads" section
			   */
			   add_post_meta( intval( $_GET['order_id'] ) , $meta_key = 'subscription_deleted', $meta_value = time(), $unique = true);
				echo '<p>' . __( 'Subscription has been deleted', 'wplg-subscription' ) . '</p>';
			} else {
				echo '<p>' . __( '<b>Error!</b> It was not possible to delete the subscription', 'wplg-subscription' ) . '</p>';
			} 
			
		} else {
			
			?>
			<form>
				<input type="hidden" name="page" value="wplg_subscription_manage_subscription" />
				<input type="hidden" name="action" value="confirmed_delete" />
				<input type="hidden" name="order_item_id" value="<?php echo intval( $_GET['order_item_id'] ); ?>"  />
				<input type="hidden" name="order_id" value="<?php echo intval( $_GET['order_id'] ); ?>"  />
				<p><?php echo __( 'Are you sure you want to delete this subscription?', 'wplg-subscription' ); ?></p>
				<button type="submit"><?php echo __( 'Confirm', 'wplg-subscription' ); ?></button>
			</form>
			<?php
		}
	}
	
	/*
	 * Receives the data from the edit subscription form and updates the subscription accordingly
	*/
	public function update_subscription() {
		//order id + order item id
		$order_id = intval( $_GET['order_id'] );
		$order_item_id = intval( $_GET['order_item_id'] );
		
		$new_expiral_time = strtotime( $_GET['wplg-subscription-edit-date'] );
		
		
		
		$customer_id = intval( $_GET['customer_id'] );
		$subscription_id = intval( $_GET['subscription_id'] );
		$sub_id = $subscription_id . '__' . $customer_id;
		
		wc_update_order_item_meta( $order_item_id, '_wplg_sub_admin_expiral__'. $sub_id ,  $new_expiral_time );
		echo '<p>' . sprintf( __( "Subscription updated, new expiral time: %s", 'wplg-subscription'), date_i18n( 'F j,Y', $new_expiral_time)  ). '</p>';
	}
	
	
	/*
	 * Creates the form to add a subscription from the admin backend
	*/
	public function new_subscription() {
		
		
		if ( isset( $_POST['wplg-subscription-action'] ) && 'insert-subscription' == $_POST['wplg-subscription-action'] ) {
			//add_action( 'woocommerce_checkout_process', array( $this, 'insert_subscription') );
			$this->insert_subscription();
		} 
		
		/*
		 * Create lists for select fields
		*/
		$blogusers = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
		$users_list = null;
		foreach ( $blogusers as $user ) {
			$users_list .= '<option value="' . $user->ID . '">' . esc_html($user->display_name). '</option>';
		}
		
		/*
		 * List of WPLG Subscription product types
		*/
		$subscription_list = null;
		$query_args = array(
			'post_type' => 'product',
			'tax_query' => array(
				 array(
					 'taxonomy' => 'product_type',
					 'field'    => 'slug',
					 'terms'    => 'wplg_subscription', 
				 ),
			 ),
		  );
		
		$loop = new WP_Query( $query_args );

		while ( $loop->have_posts() ) {
			$loop->the_post(); 
			
			global $product; 
		
			$subscription_list .= '<option value="'.get_the_id().'">' . get_the_title() . '</option>';
		}
	
		wp_reset_query();
		
		/*
		 * List of statuses
		*/
		$order_statuses = null;
		foreach ( wc_get_order_statuses() as $slug ) {
			$order_statuses .= '<option value="' . $slug . '">'.  $slug . '</option>';
		}
		
		
		/*
		 * Prepare the fields for customer billing details
		*/
		global $woocommerce;
		$billing_form = null;
		$billing_fields = $woocommerce->countries->get_address_fields( $this->get_value('billing_country'), 'billing_' );
		foreach( $billing_fields as $field_key => $data ) {
				$label = isset( $data['label'] ) ? $data['label'] : $field_key;
				$billing_form .= '<p><label for="' . $field_key . '">'. $label .'</label>'
				. '<input type="text" class="wplg_subscription_billing_field" name="' . $field_key . '" id="wplg_subscription_new_details_' . $field_key . '" value="" /></p>' ;
			}
		

		
		echo '<form method="POST"><div id="new-subscription"><div id="select_data">'
		. '<input type="hidden" name="wplg-subscription-action" value="insert-subscription"/>'
		
		//Select user
		. '<p><label for="wplg-subscription-select-user">' . __('Select a customer', 'wplg-subscription') . '</label>'
		. '<select name="wplg-subscription-select-user" id="wplg-subscription-select-user">' . $users_list . '</select></p>'
		
		//Select subscription products
		. '<p><label for="wplg-subscription-select-subscription">' . __('Select a product', 'wplg-subscription') . '</label>'
		. '<select name="wplg-subscription-select-subscription">' . $subscription_list . '</select></p>'
		
		//Select status
		. '<p><label for="wplg-subscription-select-status">' . __('Subscription status', 'wplg-subscription') . '</label>'
		. '<select name="wplg-subscription-select-status">' . $order_statuses . '</select></p>'
		
		
		//Start and expiral data
		. '<p><label for="wplg-subscription-start-date">' . __('Start date', 'wplg-subscription') . '</label>'
		. '<input type="text" id="wplg-subscription-start-date" name="wplg-subscription-start-date" /></p>'
		
		. '<p><label for="wplg-subscription-expiral-date">' . __('Expiration date', 'wplg-subscription') . '</label>'
		. '<input type="text" id="wplg-subscription-expiral-date" name="wplg-subscription-expiral-date" /></p>'
		
		//Close selecting div
		. '</div>';
		
		//Add javascripts: datepicker and ajax to retriever customer billing details
		?>
		<script>
			jQuery(document).ready(function($) {
				$("#wplg-subscription-start-date").datepicker();
				$("#wplg-subscription-expiral-date").datepicker();
				
				
				
				$("#wplg-subscription-select-user").change(function() {
					var data = {
						'action': 'wplg_subscription_billing_details',
						'customer_id': $(this).val(),
					};
					
					//Clear previously selected user´s data
					$(".wplg_subscription_billing_field").val('');
					$("#wplg_subscription_loading_customer_details").show();
					
					jQuery.post(ajaxurl, data, function(response) {
						var billing_details = JSON.parse( response);
						console.log( billing_details );
						for ( var key in billing_details ) {
							if ( billing_details.hasOwnProperty(key)) {
							  var val = billing_details[key];
							  $("#wplg_subscription_new_details_" + key ).val( val );
							}
						}
						
						$("#wplg_subscription_loading_customer_details").hide();
					});
				});
				//Trigger on load
				$("#wplg-subscription-select-user").change();

			});
		</script><?php
		
		echo '<div id="billing_details">'
			. '<h3>' . __( 'Billing details', 'wplg-subscription') . '</h3>'
			. '<p>' . __( 'Select an user from the dropdown list to populate automatically the fields, if there are billing informations currently stored for that user', 'wplg-subscription') . '</p>'
			//This is displayed when the ajax call starts
			. '<p style="display:none;" id="wplg_subscription_loading_customer_details">' . __( 'Loading selected user data, please wait...', 'wplg-subscription') . '</p>'
			. $billing_form
			//close billing div
			. '</div>'
			//submit button and close everything
			. '<button type="submit">' . __( 'Create subscription', 'wplg-subscription') . '</button></div></form>';
		
	}
	
	/*
	 * Receives the data from a new subscription form and stored them
	*/
	public function insert_subscription() {
		
		if ( isset( $_POST['wplg-subscription-select-subscription'] ) && isset( $_POST['wplg-subscription-select-user'] ) ) {
			$product_id = intval( $_POST['wplg-subscription-select-subscription'] );
			$customer_id = intval( $_POST['wplg-subscription-select-user'] );
			
			//Check whether user already has subscription
			$product = wc_get_product( $product_id );
			if ( $product->get_user_expiral_time( $customer_id ) ) {
				echo '<p>' . __( '<b>Wrong data!</b> This user already has purchased this subscription' , 'wplg-subscription' ) . '</p>';
			} else {
			
				global $woocommerce;
				
				//Handle user's billing details
				$billing_address = array();
				$billing_fields = $woocommerce->countries->get_address_fields( $this->get_value('billing_country'), 'billing_' );
				
				foreach( $billing_fields as $field_key => $data ) {
						$posted_key = 'billing_' .$field_key;
						if ( isset( $_POST[ $posted_key ] ) ) {
							$billing_address[ $field_key ] = $_POST[ $posted_key ];
						}
					}			
				
				/*
				 * Prepare orders status using the posted value
				*/
				if ( isset( $_POST['wplg-subscription-select-status'] ) &&  in_array( $posted_status = $_POST['wplg-subscription-select-status'] ,  wc_get_order_statuses() ) ) {
					
					if (
						'Active' == $posted_status
						|| 'Expired' == $posted_status					
						) {
						//These statuses are handled programmatically depending on expiration time, set as completed instead
						$set_status = apply_filters( 'woocommerce_default_order_status', 'Completed' );
					} else {
						$set_status = apply_filters( 'woocommerce_default_order_status', $posted_status );
					}
						
				} else {
					//complete as default
					$set_status = apply_filters( 'woocommerce_default_order_status', 'Completed' );
				}
				
		
				$order_data = array(
					 'customer_id' => $customer_id
				);
				$new_order = wc_create_order($order_data);
				
				if ( !is_a($new_order, 'WC_Order') ) {
					echo sprintf( __( '<b>Error!</b> Unable to access WooCommerce to add subscription: %s', 'wplg-subscription' ), $new_order->get_error_message() );
					
				} else {
					$new_order->add_product( get_product( $product_id ), 1 );
		  
					$new_order->set_address($billing_address, 'billing');
					$new_order->set_address($billing_address, 'shipping');
					
					$new_order->calculate_totals();
					if ( $new_order->update_status( $set_status, 'Imported order', TRUE) ) {
						
						$start_time = ( isset( $_POST['wplg-subscription-start-date'] ) ) ? strtotime( $_POST['wplg-subscription-start-date'] ): null;
						
						$expiral_time = ( isset( $_POST['wplg-subscription-expiral-date'] ) ) ? strtotime( $_POST['wplg-subscription-expiral-date'] ): null;
						
						wplg_subscription_order_item_meta($new_order, $start_time, $expiral_time, $is_new_subscription = true );
						
						echo sprintf( __( 'Subscription successfully added with order status %s', 'wplg-subscription' ), $set_status );
					} else echo __( '<b>Error!</b> It was not possible to update subscription informations with your data', 'wplg-subscription' );
				}
			}

		} else {
			//Subscription or user id Missing
			echo __( '<b>Error!</b> Subscription and customer ID must be valid!', 'wplg-subscription');
		}
		

		
		//before the normal "new subscription" page starts
		echo '<h3>' . __('Add an other subscription' ,'wplg-subscription' ) . '</h3>';
	}
	
	
	
	/*
	 * Display a list of the user's subscription
	 * @param	int		$user_id	The id of the user we want to fetch the subscription for, or current use (default)
	*/
	public function customer_subscriptions( $user_id = null) {
		if (!$user_id) $user_id = get_current_user_id();
		
		
		// Get all customer orders
		$customer_orders = get_posts( array(
			'numberposts' => -1,
			'meta_key'    => '_customer_user',
			'meta_value'  => $user_id,
			'post_type'   => wc_get_order_types(),
			'post_status' => array_keys( wc_get_order_statuses() ),
		) );
		
		$rows = array();
		foreach ( $customer_orders as $post) {
			$order = new WC_Order( $post->ID );
			$items = $order->get_items();
			foreach ( $items as $item_id => $item ) {
				$product = $order->get_product_from_item( $item );
				/*
				* Filter subscriptions
				*/
				if (
						//Check that item is a subscription type
						$product->is_wplg_subscription
						//And that it has the custom meta which identifies a starting subscription
						&& $start_time = wc_get_order_item_meta( $item_id, '_wplg_subscription_start_time')
					)  {
					
					//Start time is displayed as meta_value for compatibility with fetch_subscription_data()
					$rows[] = array( 'order_id' => $post->ID, 'order_item_id' => $item_id, 'meta_value' => $start_time );	
				}
				
			}
		}
		
		echo '<h1>' . __( 'My Subscriptions', 'wplg-subscription') . '</h1>';
		
		/* Prepare table columns */
		 $columns = array(
            'product'			=>	__( 'File name', 'wplg-subscription'),
			'start_date'		=>	__( 'Purchase date', 'wplg-subscription'),
			'expire_date'		=>	__( 'Expires', 'wplg-subscription'),
			'renewal_string'	=>	__( 'Renewal price', 'wplg-subscription'),
			'customer_actions'	=>	__( 'Actions', 'wplg-subscription'),
			
        );
		 
		
			
		if ( is_admin() ) {
			$SubscriptionListTable = $this->prepare_subscription_list_table();
		
			$SubscriptionListTable->store_subscription_data(
						$this->prepare_subscription_data( $rows ),
						$this
						);
			
			
			$SubscriptionListTable->set_columns( $columns );
			$SubscriptionListTable->prepare_items();
			$SubscriptionListTable->output_table();
		} else {
			$this->standard_user_output_table( $this->prepare_subscription_data( $rows ),  $columns);
		}
	}
	
	/*
	 * Remove subscription relevant data from an order
	 * Used when an order is deleted
	 * @param 	object|int	$order			Either an order id or a WC_Order object
	*/
	public function delete_subscription_data( $order ) {
		if (!is_object( $order) ) {
			$order = new WC_Order( $order );
		}
		
		
		
		global $wpdb;
		$order_itemmeta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
		
		
		$items = $order->get_items();
		foreach ( $items as $item_id => $item ) {
			
			
			$product = $order->get_product_from_item( $item );
			if ( $product->is_wplg_subscription ) {
				$item_meta= array();
				
				$sub_id = $item_meta[ '_wplg_sub_expiral_id' ] = wc_get_order_item_meta( $item_id, '_wplg_sub_expiral_id' );
				$meta_to_fetch = array(
					'_wplg_sub_expiral__' . $sub_id,
					'_wplg_subscription_expiral_time',
					'_wplg_subscription_start_time',
					'_wplg_subscription_renewal_time',
									   );
				
				//Apply order item meta
				foreach ( $meta_to_fetch as $meta_key) {
					if ( $meta_value = wc_get_order_item_meta( $item_id, $meta_key ) ) {
						 //Create a "deleted" version, in case we want to store the data anyaway but make them not functional
						 //wc_add_order_item_meta( $item_id, '_deleted' . $meta_key, $meta_value, $unique = true );
						 $wpdb->delete( $order_itemmeta_table,
									   //Where
									   array(
											'order_item_id'		=> $item_id,
											'meta_key'			=> $meta_key,
											 ));
					}
				}
			}
		}
	}
	
	/*
	 * Sends one of the defined email types for a given subscription and user (if enabled )
	 * Note: this only checks whether the email is enabled or not, otherwise always send the email. It is the portion of the code that is *calling* this function that is responsible to figure out which email type is to be sent and when
	 * @param	string		$email_type 			One of the defined emails (welcome, expiration_reminder, etc.)
	 * @param	object		$subscription_object	The WPLG Subscription type product we are dealing with
	 * @param	int			$user_id				The id of the customer who purchased this subscription
	 * @param	object		$order					The order object to which this subscription belongs
	*/
	public function send_email( $email_type, $subscription_object, $user_id, $order ) {
		/* Check whether this email type is enabled */
		if ( !get_option( 'wplg_subscription_' . $email_type . '_email_enabled' ) ) {
			return ;
		}
		
		/* Get settings for this email */
		$email_subject = get_option( 'wplg_subscription_' . $email_type . '_email_subject' );
		$email_heading = get_option( 'wplg_subscription_' . $email_type . '_email_heading' );
		$email_content = get_option( 'wplg_subscription_' . $email_type . '_email_content' );
		$reminder_period = get_option( 'wplg_subscription_' . $email_type . '_days' );
		
		$customer = get_userdata( $user_id );
		$to = $customer->user_email;
		/* Associates each placeholder with the actual value */
		$parse = array(
			'{user_name}'			=> $customer->display_name,
			'{product_name}'		=> $subscription_object->post->post_title,
			'{item_download_link}'	=> $subscription_object->get_download_link(),
			'{site_title}'			=> get_bloginfo('name'),
			'{renew_product}'		=> '<a href="' . $subscription_object->renew_link() . '">' . $subscription_object->renew_link() . '</a>',
			'{reminder_period}'		=> $reminder_period,
			'{order_date}'			=> $order->order_date,
			);
		
		/* Some emails are dependent on expiration date */
		$expiral_date = $subscription_object->get_user_expiral_time( $user_id );
		
		/*
		* Replace placeholders
	    */
		$parsed_subject = $email_subject;
		$parsed_content = '<h1>' . $email_heading . '</h1>' . $email_content;
		foreach ( $parse as $key => $key_content ) {
			$parsed_content = str_replace( $key, $key_content , $parsed_content );
			$parsed_subject = str_replace( $key, $key_content, $parsed_subject);
		}
	   
	   /* Set body as html */
	   $headers = array('Content-Type: text/html; charset=UTF-8');
	   
	   /* Send email*/
	   $res = wp_mail( $to, $parsed_subject, $parsed_content, $headers );
	   
	}
	
	/*
	 * If requested in the settings, send the welcome email to the user with the download link of the subscriptions contained within an order
	* @param object|int	$order			Either an order id or a WC_Order object
	*/
	public function send_reminder_emails() {
		
		/*
		 * Fetch all orders containing a subscription
		*/
		$res = $this->fetch_subscriptions_data( array('order_id') );
		
		foreach ($res as $id => $object ) {
			$order = new WC_Order( $object->order_id );
			
			$user_id = $order->customer_user;
			
			$items = $order->get_items();
			
			foreach ( $items as $item_id => $item ) {
				
				$product = $order->get_product_from_item( $item );
				/* Loop over WPLG Subscription products */
				if ( $product->is_wplg_subscription ) foreach ( array_keys( $this->_options_groups ) as $email_type ){
					/*
					 * Reminder's email code must end with '_reminder'
					 * Other type of emails can be set too, but they must be called directly when needed
					*/
					if ( '_reminder' === substr($email_type, -strlen( '_reminder' ) ) ) {
						
						$expiration_time = $product->get_user_expiral_time( $user_id );
						$reminder_days = get_option( 'wplg_subscription_' . $email_type . '_days' );
						$day = 60 * 60 * 24;
						/* Each reminder type is sent at a different condition */
						$send = false;
						
						/* Track the reminders that have been already sent */
						$reminders_sent = (array) json_decode( get_option( 'wplg_subscription_' . $email_type . '_reminders_sent' ) );
						
						if ( 'expiration_reminder' == $email_type ) {
							if ( time() > ( $expiration_time - $reminder_days * $day ) && !in_array( $expiration_time - $reminder_days * $day , $reminders_sent ) ) {
								$send = true;
								$reminders_sent[] = $expiration_time - $reminder_days * $day;
							}
						} elseif ( 'expired_reminder' == $email_type ) {
							if ( time() > ( $expiration_time + $reminder_days * $day ) && !in_array( $expiration_time + $reminder_days * $day , $reminders_sent ) ) {
								$send = true;
								$reminders_sent[] = $expiration_time + $reminder_days * $day;
							}
						} elseif ( 'payment_reminder' == $email_type ) {
							/* Status to be used after final reminder */
							$final_status = get_option( 'wplg_subscription_payment_reminder_final_status' );
							/*
							 * Reminders to be sent only if order status is neither completed, or the final one
							*/
							if ( 'Completed' != wc_get_order_status_name( $order->get_status() ) && $final_status != wc_get_order_status_name( $order->get_status() ) ) {
								/*
								 * Payment reminder must be sent at given intervals of N day, for a certain max amount of times
								*/
								$max_times = get_option( 'wplg_subscription_payment_reminder_max_times' );
								$interval_found = false;
								
								$order_date = strtotime( $order->order_date );
								
								/*
								 * Do nothing unless the time treshold for the first interval is met
								*/
								if ( time() > $order_date + ( $reminder_days * $day ) ) {
									for ( $i = 1; $i <= $max_times; $i++ ) {
										/* Determine which time interval are we currently checking */
										$start_interval = $order_date + ( $i * $reminder_days * $day );
										$end_interval = $order_date + ( ( $i + 1 ) * $reminder_days * $day );
										if ( time() >= $start_interval && time() < $end_interval ) {
											/* Found, we are currently in this time interval, so send the i-th email reminder if not already sent */
											$interval_found = true;
											if ( !in_array( $start_interval , $reminders_sent ) ) {
												$send = true;
												$reminders_sent[] = $start_interval;
												break;
											}
										}
									}
									
									if ( !$interval_found ) {
										/* We looped over all the possible intervals and found nothing, so we are already over the last reminder: change order status */
										$order->update_status( $final_status );
									}	
								}
								

							}
							

						}
						
						
						if ( $send ) {
							/*
							 * Keep track of the sent reminders (which may have been updated )
							*/
							
							update_option( 'wplg_subscription_' . $email_type . '_reminders_sent', json_encode( $reminders_sent ) );
							$this->send_email( $email_type, $product, $user_id, $order );
						}
		
					} 
						
				}
			}
		}
		
		return;
	}
	
	/*
	 * If requested in the settings, send the welcome email to the user with the download link of the subscriptions contained within an order
	* @param object|int	$order			Either an order id or a WC_Order object
	*/
	public function send_welcome_email( $order ) {
		if (!is_object( $order) ) {
			$order = new WC_Order( $order );
		}
		
		$user_id = $order->customer_user;
		
		
		$items = $order->get_items();
		
		
		
		foreach ( $items as $item_id => $item ) {
			
			$product = $order->get_product_from_item( $item );
			/* Loop over WPLG Subscription products */
			if ( $product->is_wplg_subscription ) {
				$this->send_email( 'welcome', $product, $user_id );
			}
		}
	}
	
	/****************************************
	 * END OF MANAGE SUBSCRIPTIONS SECTION
	 ****************************************
	 ****************************************
	 ****************************************
	 ****************************************
	*/
	
	/****************************************
	 * ADMIN PAGES SECTION
	 ****************************************
	 * Here we find the functions that create the plugin pages 
	 ****************************************
	 ****************************************
	*/
	
	
	/*
	 * Associates each action passed to the admin section with the proper function
	*/
	public function admin_page_manage_subscription() {
		echo '<h1>' . $this->main_title . '</h1>';
		/*
		 * Associate each action with a function
		*/
		$actions = array(
			'edit'		=>	'edit_subscription',
			'new'		=>	'new_subscription',
			
			'update'	=>	'update_subscription',
			
			
			'delete'	=>	'delete_subscription',
			'confirmed_delete'	=>	'delete_subscription',
						 );
		/*
		 * Title of the page before the function is executed
		*/
		$action_labels = array(
			'edit'		=>	__( 'Edit subscription', 'wplg-subscription'),
			'new'		=>	__( 'New subscription', 'wplg-subscription'),
			
			'update'	=>	__( 'Update subscription', 'wplg-subscription'),
			
			'delete'	=>	__( 'Confirm', 'wplg-subscription'),
			'confirmed_delete'	=>	__( 'Delete subscription', 'wplg-subscription'),
						 );
		
		$action = ( isset( $_GET['action'] ) ) ? $_GET['action'] : null;
		if ( !$action ) $action = 'new';
		
		if ( in_array( $action, array_keys( $actions ) ) ) {
			echo '<h2>' . $action_labels[ $action ] . '</h2>';
			$this->$actions[ $action ]();		
		} else echo '<p>' . sprintf( __("Action %s is not recognized", 'wplg-subscription' ), $action ) . '</p>';
	}
	
    /*
     * Recognize admin pages and deals with them
     * Admin pages have the prefix 'admin_page_'
    */
    public function __call( $name, $args ) {
        if ( substr( $name, 0 , strlen('admin_page_') ) ) {
            //function name is achieved by removing the admin_page prefix
            $function = substr( $name, strlen('admin_page_') );
            
            if ( method_exists( $this, $function) ) {
                //echo title
                echo '<h1>' . $this->main_title . '</h1>';
                echo '<h2>' . $this->_menu_pages[ $function ] . '</h2>';
                //call the function that creates the page
                $this->$function();    
            }
            
        }
    }
	
	public function settings() {
		
        //Store settings
        do_settings_sections( 'wplg-subscription-option-group' );
        
		//Open form and apply some basic styling
		echo '<div class="wplg-subscription-options-wrap">'
                . '<form method="post" action="options.php">';
		settings_fields( 'wplg-subscription-option-group' );
		
		foreach ( $this->_options_groups as $prefix => $group_label ) {
			/*
			 * Grouped options
			*/
			
			/* Start the collapsible box for this group of options */
			echo '<div class="wplg-collapsible-box"><div class="wplg-collapsible-box-title">' . $group_label . '</div><div class="wplg-collapsible-box-content">';
		
			/*
			 * Checkbox to enable / disable this E-mail
			*/
			$checked = ( $this->_options[ $prefix .'_email_enabled' ] ) ? 'checked="checked"' : null;
			echo
			'<p>'
				.'<label for="wplg_subscription_' . $prefix . '_email_enabled">' . $this->_options_properties[  'email_enabled' ] . '</label>'
				.'<input type="checkbox" name="wplg_subscription_' . $prefix . '_email_enabled" value="1" ' .$checked  . '/>'
			. '</p>';
			
			/*
			 * Properties specific to this group (such as the settings for the days in reminder etc)
			*/
			
			$reminder_special_fields = null;
			if ( 'payment_reminder' == $prefix ) {
				
				/* Option for max number of reminders */
				$reminder_special_fields =  '<p>'
							.'<label for="wplg_subscription_payment_reminder_max_times">' . $this->_options_labels[ 'payment_reminder_max_times' ] . '</label>'
							. '<input type="text" name="wplg_subscription_payment_reminder_max_times" value="' . $this->_options[ 'payment_reminder_max_times' ] . '" />'
					. '</p>';
				
				/* Option for the status to set after final reminder */
				$final_status = $this->_options['payment_reminder_final_status'];
				$order_statuses = '<option value="' . $final_status . '">'.  $final_status . '</option>';
				foreach ( wc_get_order_statuses() as $slug ) {
					if ( wc_get_order_status_name( $final_status ) != $slug )
					$order_statuses .= '<option value="' . $slug . '">'.  $slug . '</option>';
				}
				$reminder_special_fields .= '<p>'
							.'<label for="wplg_subscription_payment_reminder_final_status">' . $this->_options_labels[ 'payment_reminder_final_status' ] . '</label>'
							. '<select name="wplg_subscription_payment_reminder_final_status">' . $order_statuses . '</select>'
					. '</p>';
				
				
			}
			/*
			 * Recurrent properties which are the same across all groups
			*/
			foreach ( $this->_options_properties as $property => $property_label ) {
				
				$internal_option_name = $prefix . '_' .$property;
				$full_option_name = 'wplg_subscription_' . $internal_option_name;
				
				if ( 'days' == $property ) {
					if ( '_reminder' === substr($prefix, -strlen( '_reminder' ) ) ) {
						//All reminders have the _days property to be set
						$full_option_name = 'wplg_subscription_' . $prefix . '_days';
						$internal_option_name = $prefix . '_days';
						
						echo '<p>'
							.'<label for="' . $full_option_name . '">' . $property_label . '</label>'
							. '<input type="text" name="' . $full_option_name . '" value="' . $this->_options[ $internal_option_name ] . '" />' . $this->_options_labels[ $internal_option_name ]
					. '</p>';
						/* After reminder period, if necessary, add special fields*/
						echo $reminder_special_fields;
					}
					
				/*
				 * Start other fields.
				 * Exclude the "enabled" property, as we did that already a the beginning of the box
				 */
				} elseif ('email_enabled' != $property ) {
					
					
					/* Textarea is used for content, others are input type="text */
					if ( 'email_content' == $property ) {
						$input_field = '<textarea name="' . $full_option_name . '">' . $this->_options[ $internal_option_name ] . '</textarea>';
					} else {
						$input_field = '<input type="text" name="' . $full_option_name . '" value="' . $this->_options[ $internal_option_name ] . '" />';
					}
					
					
					echo '<p>'
							.'<label for="' . $full_option_name . '">' . $property_label . '</label>'
							. $input_field
					. '</p>';
				}
			}
		
			/*
			 * Close the collapsable box 
			*/
			echo '</div></div>';
		}
		
        
		

		
		//Finalize the form
		echo submit_button() . '</form>';
		
		//Add graphics and functionality to collapsible boxes
		wp_enqueue_script( 'wplg-collapsible-boxes-js', plugins_url( '/js/wplg-collapsible-boxes.js', __FILE__ ));
		//The style will provide the graphical outlook for the side menu where to choose the section to show
		wp_enqueue_style( 'wplg-collapsible-boxes-css', plugins_url( '/css/wplg-collapsible-boxes.css', __FILE__ ));
	}
    
	
	
	/*
	 * Fetches a list of subscriptions, according to certain search principles (or, by default all subscriptions)
	 * Then uses an extension of WP_List_Table to display them, navigate through them and so on
	*/
    public function subscriptions( $where = null ) {
       
		/*
		 * First of all, prepare settings for pagination
		*/
		$per_page = 20;
		$total_subscriptions = $this->fetch_subscriptions_data( 'count', $where);
		$current_page = isset( $_GET['cpage'] ) ? abs( (int) $_GET['cpage'] ) : 1;
		$total_pages = ceil( $total_subscriptions / $per_page );
		
		$SubscriptionListTable = $this->prepare_subscription_list_table();
		
		$SubscriptionListTable->set_pagination_args( array(
            'total_items' => $total_subscriptions,
            'per_page'    => $per_page,
            'total_pages' => $total_pages 
        ) );
		$current_page = $SubscriptionListTable->get_pagenum();
		$first_element = ( $current_page - 1 ) * $per_page;
		
		
		
		/*
		 * Fetches a list of orders which contain WPLG subscriptions, and return the single elements
		*/
		
		$rows = $this->fetch_subscriptions_data( array( 'order_id, item.order_item_id, meta.meta_value' ), $where, $per_page, $first_element, ARRAY_A);
		
		
		//Fetch, prepare, sort, and filter our data...
		$SubscriptionListTable->store_subscription_data( $this->prepare_subscription_data( $rows), $this);
		
		
		/* Prepare table columns */
		 $columns = array(
            'cb'        	=> '<input type="checkbox" />', //Render a checkbox instead of text
			'id'			=>	__( 'Subscription ID', 'wplg-subscription'),
            'status'		=>	__( 'Status', 'wplg-subscription'),
			'product'		=>	__( 'Product', 'wplg-subscription'),
			'customer'		=>	__( 'Customer', 'wplg-subscription'),
			'total'			=>	__( 'Total', 'wplg-subscription'),
			'renewal_price'	=>	__( 'Renewal price', 'wplg-subscription'),
			'start_date'	=>	__( 'Start date', 'wplg-subscription'),
			'expire_date'	=>	__( 'Expires', 'wplg-subscription'),
        );
		$SubscriptionListTable->set_columns( $columns );
		
		/*
		 * Add and process bulk actions
		*/
		
        $actions = array(
            'delete'    => __( 'Delete' , 'wplg-subscription'),
        );
		$SubscriptionListTable->set_bulk_actions( $actions );
		$SubscriptionListTable->prepare_items();
		$SubscriptionListTable->output_table();
    }
	

    /****************************************
	 * SUBSCRIPTION'S TABLE
	 ****************************************
	 * Here we find the functions that handle the different tables to display subscriptions informations
	 ****************************************
	 ****************************************
	*/   
	
	
	
	/*
	 * Shortcut function for initializing Subscriptio_List_Table and load the data
	*/
	public function prepare_subscription_list_table() {
		
		
		
		/* To list the subscirption we will use an extension of the WP_List_Table class */
		/* Load base classes if necessary*/
		require_once(ABSPATH . 'wp-admin/includes/template.php' );

		if( ! class_exists('WP_Screen') ) {
			require_once( ABSPATH . 'wp-admin/includes/screen.php' );
			
		}
		
		if(!class_exists('WP_List_Table')){
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
		}
		/* And the extended one */
		require_once('class-wplg-subscription-list-table.php');
		
		//Create an instance of our package class...
		$SubscriptionListTable = new WPLG_Subscription_List_Table();
		
		return $SubscriptionListTable;
	}
	
	
	/*
     * Receives a list of rows containing subscription datas, and uses them to prepare the array used to display the table
    */
	public function prepare_subscription_data( $rows ) {
        
        $data = array();
        
        foreach($rows  as $a) {
			/*
			 * Prepare array for this element
			*/
			
			//fetch order and product
			$order = new WC_Order( $a['order_id'] );
			$items = $order->get_items();
			
		
			foreach ( $items as $item_id => $item ) {
				if ( $item_id == $a['order_item_id'] ) {
					$product = $order->get_product_from_item( $item );
					$current_item = $item;
				}
			}
			
			$user_id = get_post_meta( $a['order_id'], '_customer_user', true );
			
			
			
			$row = array(
				//Send this subscription item, order and product data, so they can be retrieved when dealing with the columns
				'order_data'	=>	$order,
				'item_data'		=> 	$current_item,
				'order_item_id'	=> 	$a['order_item_id'],
				
				/*
				 * Data pertaining specific columns
				*/
				'order_id'		=>	$a['order_id'],
				//'id'			=>	$a['order_id'],
				'status'		=>  $product->display_subscription_status( $a['order_id'], $user_id ),
				/* Send the whole product, the function column_product will deal with it */
				'product'		=>	$product,
				/* User data will be fetched and managed in column_customer() */
				'customer'		=>	$user_id,
				'total'			=>	$product->output_initial_price(),
				'renewal_price'	=>	$product->output_renewal_price(),
				'renewal_string'	=>	$product->output_renewal_string(),
				'start_date'	=>	date_i18n( 'F j, Y' , ( $a['meta_value'] ) ),
				'expire_date'	=>	date_i18n( 'F j, Y' , $product->get_user_expiral_time( $user_id ) ),
				'customer_actions'	=>	$product->output_customer_actions( $a['order_id']),
						 );
			
			
			$data[] = $row;
		}   
        
        return $data;
    }
	
	
	/**
	 * Generate row actions div
	 * Copied from class WP Table List to allow a compatible display of actions outside of admin section
	 *
	 * @param array $actions The list of actions
	 * @param bool $always_visible Whether the actions should be always visible
	 * @return string
	 */
	protected function row_actions( $actions, $always_visible = false ) {
		$action_count = count( $actions );
		$i = 0;

		if ( !$action_count )
			return '';

		$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
		foreach ( $actions as $action => $link ) {
			++$i;
			( $i == $action_count ) ? $sep = '' : $sep = ' | ';
			$out .= "<span class='$action'>$link$sep</span>";
		}
		$out .= '</div>';

		$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'wplg-subscription' ) . '</span></button>';

		return $out;
	}
	
	
	
	
	/*
	 * A less featured version of table, available to all users
	*/
	public function standard_user_output_table( $data,  $columns ) {
		$table = '<table class="wp-list-table widefat fixed striped subscriptions"><thead><tr>';
		foreach ( $columns as $column_key => $column_label ) {
			$table .= '<th id="' . $column_key . '" class="manage-column column-' . $column_key . '" scope="col">' . $column_label . '</th>';
		}
		$table .= '</tr></thead>';
		
		$table .= '<tbody id="the-list" data-wp-lists="list:subscription">';
			foreach ( $data as $item ) {
				$table .= '<tr>';
				foreach ( $columns as $column_key => $column_label ) {
					$function = 'column_' . $column_key;
					
					$column_content = method_exists($this, $function ) ? $this->$function( $item ) : $this->column_default( $item, $column_key );
					
					$table .= '<td class="' . $column_key . ' column-' . $column_key . ' has-row-actions column-primary" data-colname="' . $column_label . '">' . $column_content . '</td>';
				}
				$table .= '</tr>';
			}
		$table .= '</tbody></table>';
		?>
		<div class="wplg_subscription_wrap">
			
			<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
			<div id="subscription-table">
				<!-- Now we can render the completed list table -->
				<?php echo $table; ?>
			</div>
		</div>
		<?php
	}
	
	
	/*
	 * Defines how each column is displayed, depending on the subscription's informations
	*/
	
	
	/** ************************************************************************
     * Method that define the default value of columns where no specific function is defined
     * 
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
	
	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}
	
	/** ************************************************************************
     * Display the subscription id, along with edit / delete actions
     * 
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
	function column_id( $item ) {
		
		//Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=wplg_subscription_manage_subscription&action=%s&order_id=%d&order_item_id=%d">' . __('Edit' ,'wplg-subscription' ) . '</a>', 'edit' ,$item['order_id'], $item['order_item_id'] ),
            'delete'      => sprintf('<a href="?page=wplg_subscription_manage_subscription&action=%s&order_id=%d&order_item_id=%d">' . __( 'Delete' ,'wplg-subscription' ). '</a>','delete',$item['order_id'], $item['order_item_id'] ),
        );
        
        return sprintf('#%1$s %2$s',
            /*$1%s*/ $item['order_item_id'],
            /*$2%s*/ $this->row_actions($actions)
        );
	
	}
	
	/** ************************************************************************
     * Informations about the user and link to its profile
     * 
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
	function column_customer( $item ) {
		
		/*
		 * Retrieve user's info
		*/
		$user_id = $item['customer'];
		$user_data = get_userdata( $user_id );
		$user_page = admin_url( 'user-edit.php?user_id='.$user_id );
		
		return '<a target="_blank" href="' . $user_page . '">' . $user_data->display_name . '</a>';
    }
	
	/** ************************************************************************
     * Displays the subscription's name and link to its page
     * 
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
	
	function column_product( $item ) {
		
		/*
		 * Display product name with link to edit page
		*/
		$product = $item['product'];
		
		$edit_page = admin_url( 'post.php?action=edit&post=' . $product->id );
		$guid = $product->post->guid;
		return '<a target="_blank" href="' . $guid . '">' . $product->post->post_title . '</a>';
    }
	
	
    
} //End of class WPLG Subscription
