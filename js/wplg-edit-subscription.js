jQuery(document).ready(function($) {
    
    /*Date picker to change the subscription schedule*/
	$("#wplg-subscription-edit-date").datepicker();
    
    /*
     * When clicking on the section name, the content will be shown
    */
    $("#wplg-edit-page-section-titles li").click( function() {
            /*Change selected section*/
            $("#wplg-edit-page-section-titles li").removeClass('wplg-edit-page-selected');
            $(this).addClass('wplg-edit-page-selected');
            
            var section_id = $(this).data('section-id');
            var id_to_show = "wplg-edit-page-section-container-" + section_id;
            /*Hide all*/
            $( "#wplg-edit-page-section-containers" ).children().hide();
            console.log( id_to_show);
            console.log(section_id);
            console.log($(this));
            $( "#" + id_to_show ).show();
        });
});