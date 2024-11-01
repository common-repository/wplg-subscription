jQuery( document ).ready( function() {
    /*
     * Regaining the standard price fields for a WPLG_Subscription product type
     */
    jQuery( '.options_group.pricing' ).addClass( 'show_if_wplg_subscription' ).show();
    
    /*
     * Apply same settings as virtual / downloadable files
    */
    jQuery( '.options_group.show_if_downloadable' ).addClass( 'show_if_wplg_subscription' );
    jQuery( '.hide_if_downloadable' ).addClass( 'hide_if_wplg_subscription' );
    
   
    
    // product type specific options
    jQuery( 'body' ).on( 'woocommerce-product-type-change', function( event, select_val, select ) {

        if ( select_val == 'wplg_subscription' ) {
            jQuery( '.show_if_wplg_subscription' ).show();
            jQuery( '.hide_if_wplg_subscription' ).hide();
            
            /*
             * The virtual and downloadble options are not shown for subscription, as they are always virtual and downloadable
             * However, by default they are not checked, so turn them on
            */
            jQuery( '#_virtual' ).attr( 'checked' , true );
            jQuery( '#_downloadable' ).attr( 'checked' , true );
            
        } else {
            jQuery( '.show_if_wplg_subscription' ).hide();
            jQuery( '.hide_if_wplg_subscription' ).show();
            
        }
    });
    
});