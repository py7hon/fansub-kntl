

jQuery(document).ready(function($) {
    var $body = $('body');

    (function() {
        $('.sf-menu, .fansub-superfish-menu > ul').each(function() {
            var $element = $(this),
                options = {
                    hoverClass: 'sf-hover',
                    delay: 100,
                    cssArrows: false,
                    dropShadows: false
                };
            if(!$element.hasClass('sf-menu')) {
                $element.addClass('sf-menu');
            }
            if($element.hasClass('slide')) {
                options.animation = {
                    height: 'show',
                    marginTop: 'show',
                    marginBottom: 'show',
                    paddingTop: 'show',
                    paddingBottom: 'show'
                };
                options.animationOut = {
                    height: 'hide',
                    marginTop: 'hide',
                    marginBottom: 'hide',
                    paddingTop: 'hide',
                    paddingBottom: 'hide'
                };
            }
            if($element.hasClass('arrow')) {
                options.cssArrows = true;
            }
            $element.superfish(options);
        });
    })();

    (function() {
        $('.fansub-go-top').fansubScrollTop();
    })();

    (function() {
        $('input[type="file"].fansub-field-upload').each(function() {
            fansub.limitUploadFile($(this));
        });
    })();

    (function() {
        $('.fansub .comment-tools .comment-likes').on('click', function(e) {
            e.preventDefault();
            var $element = $(this),
                $container = $element.closest('.comment'),
                $count = $element.find('.count'),
                comment_id = parseInt($container.attr('data-comment-id')),
                likes = parseInt($element.attr('data-likes'));
            $element.addClass('disabled');
            $element.css({'text-decoration' : 'none'});
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_comment_likes',
                    comment_id: comment_id,
                    likes: likes
                },
                success: function(response){
                    likes++;
                    $element.attr('data-likes', likes);
                    $count.html(response.likes);
                }
            });
            return false;
        });

        $('.fansub .comment-tools .comment-report').on('click', function(e) {
            e.preventDefault();
            var $element = $(this),
                $container = $element.closest('.comment'),
                comment_id = parseInt($container.attr('data-comment-id'));
            $element.addClass('disabled');
            $element.css({'text-decoration' : 'none'});
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_comment_report',
                    comment_id: comment_id
                },
                success: function(response){

                }
            });
            return false;
        });

        $('.fansub .comment-tools .comment-share').on('click', function(e) {
            e.preventDefault();
            var $element = $(this);
            $element.css({'text-decoration' : 'none'});
            $element.toggleClass('active');
            return false;
        });

        $('.fansub .comment-tools .comment-share .list-share .fa').on('click', function(e) {
            e.preventDefault();
            var $element = $(this);
            $element.css({'text-decoration' : 'none'});
            window.open($element.attr('data-url'), 'ShareWindow', 'height=450, width=550, toolbar=0, location=0, menubar=0, directories=0, scrollbars=0');
        });
    })();

    (function() {
        fansub.iconChangeCaptchaExecute();
    })();

    (function() {
        $('.fansub.fansub-google-maps .fansub-field-maps').fansubGoogleMaps();
    })();

    (function() {
        $('.vote .vote-post').on('click', function(e) {
            e.preventDefault();
            var $element = $(this),
                $parent = $element.parent(),
                vote_type = $element.attr('data-vote-type'),
                post_id = $parent.attr('data-post-id');
            $element.addClass('disabled');
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_vote_post',
                    post_id: post_id,
                    vote_type: vote_type,
                    value: $element.attr('data-vote')
                },
                success: function(response){
                    if(response.success) {
                        $element.attr('data-vote', response.value);
                        $parent.addClass('disabled');
                    }
                }
            });
        });
    })();

    (function() {
        var $cart_preview = $('#fansubCart');
        if($cart_preview.length) {
            $cart_preview.on('click', '.fansub-post .fa-remove', function(e) {
                e.preventDefault();
                var $element = $(this),
                    post_id = $element.attr('data-id'),
                    $item = $element.closest('.fansub-post'),
                    $cart_contents = $element.closest('.fansub-cart-contents');
                $element.addClass('disabled');
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: fansub.ajax_url,
                    data: {
                        action: 'fansub_wc_remove_cart_item',
                        post_id: post_id
                    },
                    success: function(response){
                        $item.fadeOut();
                        $item.remove();
                        if(response.updated) {
                            $cart_contents.html(response.cart_contents);
                        }
                    }
                });
            });
        }
    })();

    // Tab widget
    (function() {
        var $tabber_widgets = $('.fansub-tabber-widget');
        if($tabber_widgets.length) {
            $tabber_widgets.each(function() {
                var $element = $(this),
                    $list_tabs = $element.find('ul.nav-tabs');
                $element.find('.tab-item').each(function() {
                    var widget = $(this).attr('id');
                    $(this).find('a.tab-title').attr('href', '#' + widget).wrap('<li></li>').parent().detach().appendTo($list_tabs);
                });
                $list_tabs.find('li:first').addClass('active');
                $list_tabs.fadeIn();
                $element.find('.tab-pane:first').addClass('active');
            });
            $tabber_widgets.on('click', '.nav-tabs li a', function(e) {
                e.preventDefault();
                var $element = $(this),
                    id = $element.attr('href').replace('#', ''),
                    $widget = $element.closest('.fansub-tabber-widget'),
                    $pane = $widget.find('div[id^="' + id + '"]');
                $widget.find('.tab-pane').removeClass('active');
                $pane.addClass('active');
            });
        }
    })();

    // Product fast buy
    (function() {
        var $modal = $('.single-product.woocommerce .modal.product-fast-buy');
        if($modal.length) {
            $modal.on('click', '.customer-info form button', function(e) {
                e.preventDefault();
                var $element = $(this),
                    $modal_body = $element.closest('.modal-body'),
                    $attributes_form = $modal_body.find('.attributes-form'),
                    attributes = [],
                    $form = $element.closest('form'),
                    $full_name = $form.find('.full-name'),
                    $phone = $form.find('.phone'),
                    $email = $form.find('.email'),
                    $address = $form.find('.address'),
                    $message = $form.find('.message');
                if($full_name.prop('required') && !$.trim($full_name.val())) {
                    $full_name.focus();
                } else if($phone.prop('required') && !$.trim($phone.val())) {
                    $phone.focus();
                } else if($email.prop('required') && !$.trim($email.val())) {
                    $email.focus();
                } else if($address.prop('required') && !$.trim($address.val())) {
                    $address.focus();
                } else if($message.prop('required') && !$.trim($message.val())) {
                    $message.focus();
                } else {
                    $element.addClass('disabled');
                    if($attributes_form.length) {
                        $attributes_form.find('select').each(function() {
                            var $select = $(this),
                                attribute = {name: $select.attr('data-attribute_name'), value: $select.val()};
                            attributes.push(attribute);
                        });
                    }
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: fansub.ajax_url,
                        data: {
                            action: 'fansub_wc_order_item',
                            post_id: $element.attr('data-id'),
                            name: $full_name.val(),
                            phone: $phone.val(),
                            email: $email.val(),
                            message: $message.val(),
                            address: $address.val(),
                            attributes: attributes
                        },
                        success: function(response){
                            if($.trim(response.html_data)) {
                                $modal_body.html(response.html_data);
                            }
                        }
                    });
                }
            });
        }
    })();

    // User subscribe widget
    (function() {
        var $fansub_widget_subscribe = $('.fansub-subscribe-widget');
        if($fansub_widget_subscribe.length) {
            $fansub_widget_subscribe.find('.fansub-subscribe-form').on('submit', function(e) {
                e.preventDefault();
                var $element = $(this),
                    $messages = $element.find('.messages'),
                    use_captcha = $element.attr('data-captcha'),
                    register = $element.attr('data-register'),
                    $submit = $element.find('input[type="submit"]'),
                    $email = $element.find('.input-email'),
                    $name = $element.find('.input-name'),
                    $phone = $element.find('.input-phone'),
                    $captcha = $element.find('.fansub-captcha-code'),
                    captcha = '';
                if($name.length && $name.prop('required') && !$.trim($name.val())) {
                    $name.focus();
                } else if($phone.length && $phone.prop('required') && !$.trim($phone.val())) {
                    $phone.focus();
                } else if($email.length && $email.prop('required') && !$.trim($email.val())) {
                    $email.focus();
                } else if($captcha.length && $captcha.prop('required') && !$.trim($captcha.val())) {
                    $captcha.focus();
                } else {
                    if($captcha.length) {
                        captcha = $captcha.val();
                    }
                    $submit.addClass('disabled');
                    $element.find('.img-loading').show();
                    $.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: fansub.ajax_url,
                        data: {
                            action: 'fansub_widget_subscribe',
                            name: $name.val(),
                            phone: $phone.val(),
                            email: $email.val(),
                            use_captcha: use_captcha,
                            captcha: captcha,
                            register: register
                        },
                        success: function(response){
                            $element.find('.img-loading').hide();
                            $captcha.next().next().trigger('click');
                            $messages.html(response.message);
                            if(response.success) {

                            } else {
                                $submit.removeClass('disabled');
                            }
                        }
                    });
                }
            });
        }
    })();

    (function() {
        $('.fansub').on('click', '.save-post, .favorite-post, .interest-post, .love-post', function(e) {
            e.preventDefault();
            if($body.hasClass('fansub-user')) {
                var $element = $(this),
                    post_id = $element.attr('data-post-id');
                $element.addClass('disabled');
                $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: fansub.ajax_url,
                    data: {
                        action: 'fansub_favorite_post',
                        post_id: post_id
                    },
                    success: function(response){
                        if(response.success) {
                            $element.html(response.html_data);
                            $element.removeClass('disabled');
                        }
                    }
                });
            } else {
                window.location.href = fansub.login_url;
            }
        });
    })();
});