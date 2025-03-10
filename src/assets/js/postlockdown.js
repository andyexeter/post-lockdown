(function($) {
    'use strict';

    $(function() {
        $('.pl-posts-container').each(function() {
            var selectedKey = $(this)
                    .find('.pl-posts-selected .pl-multiselect')
                    .data('key'),
                inputName = $(this)
                    .find('.pl-posts-selected .pl-multiselect')
                    .data('input_name'),
                $table = $(this).closest('table.form-table');

            $(this).plmultiselect({
                ajaxAction: $table.data('ajax_action'),
                ajaxNonce: $table.data('ajax_nonce'),
                inputName: inputName,
                inputSearch: $(this).find('.pl-autocomplete'),
                ulAvailable: $(this).find(
                    '.pl-posts-available .pl-multiselect'
                ),
                ulSelected: $(this).find('.pl-posts-selected .pl-multiselect'),
                selected: window.postlockdown[selectedKey] || [],
                spinner: $(this).find('.spinner')
            });
        });
    });
})(jQuery);
