jQuery(function($) {
    'use strict';

    var setEnabled = function (elem, enabled) {
        var inner = elem.find('.js-2fa-inner')
        inner.html('<input type="checkbox">')
        inner.find('input').prop('checked', enabled)

        inner.find('input').click(function (event) {
            event.preventDefault()

            $.ajax({
                method: 'POST',
                url: ajaxurl,
                data: {
                    action: '2fa_network_site_toggle',
                    nonce: elem.find('.nonce').val(),
                    blog_id: elem.find('.blog_id').val(),
                    enabled: !enabled ? 'yes' : 'no',
                },
            })
                        .done(function (data) {
                            if (data.success) {
                                setEnabled(elem, !enabled)
                            } else {
                                alert('error5. TODO')
                            }
                        })
                        .fail(function () {
                                alert('error6. TODO')
                        })
        })
    }

    $('.js-2fa-toggle').each(function () {
        var $this = $(this)
        var inner = $this.find('.js-2fa-inner')

        setEnabled($this, $this.find('.enabled').val() === '1')
    })
})
