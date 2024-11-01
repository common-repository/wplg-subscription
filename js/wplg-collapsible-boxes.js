jQuery(document).ready(function($) {
    $(".wplg-collapsible-box-title").each( function() {
        
        
            var open_button = $('<button/>',{
                text: '+',
                click: function (e) {
                    e.preventDefault();
                    /*Show div*/
                    $(this).parent().siblings('.wplg-collapsible-box-content').slideDown();
                    /*Change the button*/
                    $(this).hide();
                    $(this).siblings('.wplg-collapsible-box-close').show();
                }
                }).addClass('wplg-collapsible-box-open').hide().appendTo($(this));
             var close_button = $('<button/>',{
                text: '-',
                click: function (e) {
                    e.preventDefault();
                    /* Close content */
                    $(this).parent().siblings('.wplg-collapsible-box-content').slideUp();
                    /*Change the button*/
                    $(this).hide();
                    $(this).siblings('.wplg-collapsible-box-open').show();
                }
                }).addClass('wplg-collapsible-box-close').appendTo($(this));
    });
});