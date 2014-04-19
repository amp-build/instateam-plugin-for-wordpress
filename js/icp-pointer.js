jQuery(document).ready( function($) {
    icp_open_pointer(0);
    function icp_open_pointer(i) {
        pointer = icpPointer.pointers[i];
        options = $.extend( pointer.options, {
            close: function() {
                $.post( ajaxurl, {
                    pointer: pointer.pointer_id,
                    action: 'dismiss-wp-pointer'
                });
            }
        });
 
        $(pointer.target).pointer( options ).pointer('open');
    }
});