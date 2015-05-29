jQuery(function($) {
    'use strict';

    $('.js-2fa-deactivate').click(function () {
        var $this = $(this)
          , user = $this.attr('data-user')
          , nonce = $this.attr('data-nonce')

        if (confirm('Are you sure?')) {
            $.ajax({
                method: 'POST',
                url: ajaxurl,
                data: {
                    action: '2fa_deactivate',
                    user: user,
                    nonce: nonce,
                },
            })
                        .done(function (data) {
                            if (data.success) {
                                $this.parents('.column-2fa').find('.2fa-status').text(data.new_status)
                            } else {
                                alert('Error. Please try again.')
                            }
                        })
                        .fail(function () {
                            alert('Error. Please try again.')
                        })
        }
    })
})
