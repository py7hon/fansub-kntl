jQuery(document).ready(function ($) {
    (function () {
        $('.chooseable').fansubChosenSelect();
    })();

    (function () {
        var $publish = $('#publish'),
            client = new ZeroClipboard($publish);

        client.on('ready', function (readyEvent) {
            var shortlink = '';
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_ph_get_shortlink',
                    post_id: $('#post_ID').val()
                },
                success: function (response) {
                    if (response.success) {
                        shortlink = response.shortlink;
                        $publish.attr('data-shortlink', shortlink);
                    }
                }
            });
            client.on('copy', function (event) {
                if ($.trim($publish.attr('data-shortlink'))) {
                    shortlink = $publish.attr('data-shortlink');
                } else {
                    var $shortlink = $('#shortlink');
                    if ($shortlink.length) {
                        shortlink = $shortlink.val();
                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            url: fansub.ajax_url,
                            data: {
                                action: 'fansub_ph_get_shortlink',
                                post_id: $('#post_ID').val()
                            },
                            success: function (response) {
                                if (response.success) {
                                    shortlink = response.shortlink;
                                    event.clipboardData.setData('text/plain', shortlink);
                                }
                            }
                        });
                    }
                }
                event.clipboardData.setData('text/plain', shortlink);
            });

            if ($.trim(shortlink)) {
                $('#go_shortlink').attr('value', shortlink);
            }

            client.on('aftercopy', function (event) {

            });
        });
    })();

    (function () {
        var $postbox = $('.postbox');
        if ($postbox.length && false) {
            $postbox.on('click', 'h2 span', function (e) {
                e.preventDefault();
                var $element = $(this);
                var html = '<input value="' + $element.text() + '"><button>Change</button>';
                $element.html(html);
            });

            $postbox.on('mouseover', 'h2 span', function (e) {
                $(this).css({cursor: 'pointer'});
            });
        }
    })();
});