<?php



/*******************************************************************************
 * Create a new list table package that extends the core WP_List_Table class.
 * WP_List_Table contains most of the framework for generating the table, but we
 * need to define and override some methods so that our data can be displayed
 * exactly the way we need it to be.
 * 
 * This is used to display the subscriptions' list in the Admin side
 * On the user side (i.e. page "My downloads"), this class won't be available, so we will use a simpler version of the tables instead
 */
class WPLG_Subscription_List_Table extends WP_List_Table {
    
    
	var $columns = array(), $bulk_actions = array();
	
	//Reference to plugin's main class
	var $wplg_subscription_class;
	
    /** ************************************************************************
     * REQUIRED. Set up a constructor that references the parent constructor. We 
     * use the parent reference to set some default configs.
     ***************************************************************************/
    function __construct(){
        global $status, $page;
                
        //Set parent defaults
        parent::__construct( array(
            'singular'  => __( 'Subscription', 'wplg-subscription'),     //singular name of the listed records
            'plural'    => __( 'Subscriptions', 'wplg-subscription'),    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }


    /** ************************************************************************
     * Method that define the default value of columns where no specific function is defined
     * 
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @param array $column_name The name/slug of the column to be processed
     * @return string Text or HTML to be placed inside the column <td>
     **************************************************************************/
    function column_default($item, $column_name){
		$function = 'column_' . $column_name;
		$column_content = method_exists($this->wplg_subscription_class, $function ) ? $this->wplg_subscription_class->$function( $item ) : $this->wplg_subscription_class->column_default( $item, $column_name );
		return $column_content;
		
    }
	
	
    /** ************************************************************************
     * Checkboxes for using bulk actions! The 'cb' column
     * is given special treatment when columns are processed.
     * 
     * @param array $item A singular item (one full row's worth of data)
     * @return string Text to be placed inside the column <td>
     **************************************************************************/
    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //"subscription"
            /*$2%s*/ $item['order_item_id']                //The value of the checkbox should be the record's id
        );
    }


    /** ************************************************************************
     * Prepares the columns of this table
     * Will return an array where the key is the column slug (and class) and the value 
     * is the column's title text. 
     * 
     * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function set_columns( $columns ){
       
		$this->columns = $columns;
        return $this;
    }
	
	function get_columns() { return $this->columns; }
	


    /** ************************************************************************
     * Define bulk actions for this table
     * 
     * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
     **************************************************************************/
    function set_bulk_actions( $actions ) {
		
		$this->bulk_actions = $actions;
        return $this;
    }
	
	function get_bulk_actions() {
		return $this->bulk_actions;
	}

	
    function process_bulk_action() {
		
        //Detect when a bulk action is being triggered...
        if( 'delete'=== $this->current_action() ) {
			
			if ( isset( $_GET['subscription'] ) && !empty ( $_GET['subscription'] ) ) {
				/*
				 * Keeps count of the deleted (or failed ) subscriptions
				*/
				$n_success = 0;
				$n_failed = 0;
				$id_success = array();
				$id_failed = array();
				
				foreach ( $_GET['subscription'] as $sub_id ) {
					if ( wc_delete_order_item( $sub_id ) )  {
						$n_success++;
						$id_success[] = $sub_id;
					} else {
						$n_failed++;
						$id_failed[] = $sub_id;
					} 
				}
				/*
				 * Displays the outcomes
				*/
				if ( $n_failed ) {
					printf( esc_html( _n( 'Failed to delete %d subscription. Still existing subscription: %s', 'Failed to delete %d subscriptions. Still existing subscriptions: %s', $n_failed, 'wplg-subscription'  ) ), $n_failed, implode(', ', $id_failed ) );
				}
				
				if ( $n_success ) {
					printf( esc_html( _n( '%d subscription deleted. Deleted subscription: %s', '%d subscriptions deleted. Deleted subscriptions: %s', $n_success, 'wplg-subscription'  ) ), $n_success, implode(', ', $id_success ) );
				}
			}
        }
        
    }

	protected $data;
    
	/*
	 * Used by the class WPLG_Subscription to provide the subscriptions' details
	 * @param	array	$data			The array with the details of every subscription fetched
	 * @param	array	$main_class		Reference to the active instance of WPLG_Subscription, in ordet to provide access to its methods (which are used to determine how to create the columns)
	*/
	public function store_subscription_data( $data, $main_class ) {
		$this->items = $data;
		$this->wplg_subscription_class = $main_class;
	}
	
	/* Output the generated table */
	public function output_table() {
		
		?>
		<div class="wplg_subscription_wrap">
			
			<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
			<form id="subscription-table" method="get">
				<!-- For plugins, we also need to ensure that the form posts back to our current page -->
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<!-- Now we can render the completed list table -->
				<?php $this->display() ?>
			</form>
		</div>
		<?php
	}
	
    

    /** ************************************************************************
     * Prepares the table for display by setting columns, headers, bulk actions
     **************************************************************************/
    function prepare_items( ) {
		
        /**
         * Define our column headers. This includes a complete
         * array of columns to be displayed (slugs & titles), a list of columns
         * to keep hidden, and a list of columns that are sortable. Each of these
         * can be defined in another method (as we've done here) before being
         * used to build the value for our _column_headers property.
         */
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        
        /**
         * Build an array to be used by the class for column 
         * headers. The $this->_column_headers property takes an array which contains
         * 3 other arrays. One for all columns, one for hidden columns, and one
         * for sortable columns.
         */
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->process_bulk_action();
        
    }

}



