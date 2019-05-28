jQuery(document).ready(function ($) {
    var $body = $('body');

    (function () {
        var $lazy_image = $('.wp-post-image.lazyload');
        if ($lazy_image.length) {
            $lazy_image.lazyload({
                event: "scrollstop"
            }).removeClass('lazyload').addClass('lazyloaded');
        }
    })();

    (function () {
        /*
         var $promo_video = $('.fansub-new-release.fansub-single .series-releases .video-box .module-body'),
         $fancy_video = $('.fansub-new-release.single-box .video-box .fansub-post a.post-thumbnail');
         if($promo_video.length) {
         $promo_video.bxSlider({
         slideWidth: 260,
         minSlides: 2,
         maxSlides: 5,
         moveSlides: 1,
         slideMargin: 10
         });
         }

         if($fancy_video.length) {
         $fancy_video.fancybox({
         autoScale: true,
         padding: 0,
         scrolling: 'no',
         autoDimensions: true,
         width: 640,
         height: 480,
         minHeight: 480,
         maxHeight: 480,
         maxWidth: "90%",
         minWidth: "80%"
         });
         }
         */
    })();

    (function () {
        var url = fansub.ajax_url + "?action=fansub_search_autocomplete",
            $autocomplete_field = $('.fansub-new-release.advanced-search .search-form .search-field, .fansub-box.fansub-release .search-form .search-field');
        if ($autocomplete_field.length) {
            $autocomplete_field.autocomplete({
                source: url,
                delay: 500,
                minLength: 1,
                select: function (event, ui) {
                    window.location.href = ui.item.link;
                }
            }).autocomplete("instance")._renderItem = function (ul, item) {
                return $("<li>").append(item.html).appendTo(ul);
            };
        }
    })();

    (function () {
        var $video_box = $('.single-box .series-releases .video-box');
        if ($video_box.length) {
            var $module_body = $video_box.find('.module-body');
            $module_body.addClass('ajax-loading');
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_kntl_video_list',
                    post_id: parseInt($video_box.attr('data-post-id')),
                    jwplayer: 0
                },
                success: function (response) {
                    $module_body.removeClass('ajax-loading');
                    if (response.have_posts) {
                        $module_body.html(response.html_data);
                        var $promo_video = $module_body,
                            $fancy_video = $('.fansub-new-release.single-box .video-box .fansub-post a.post-thumbnail, .fansub-new-release.single-box .video-box .fansub-post a.fancy-link');
                        if ($promo_video.length) {
                            $promo_video.bxSlider({
                                slideWidth: 260,
                                minSlides: 2,
                                maxSlides: 5,
                                moveSlides: 1,
                                slideMargin: 10
                            });
                        }

                        $video_box.find('.module-body').children('.fansub-post').each(function () {
                            var $element = $(this),
                                jwplayer = parseInt($element.attr('data-jwplayer')),
                                post_id = parseInt($element.attr('data-id'));
                            if (1 == jwplayer) {
                                $element.addClass('ajax-loading');
                                $.ajax({
                                    type: 'POST',
                                    dataType: 'json',
                                    url: fansub.ajax_url,
                                    data: {
                                        action: 'fansub_kntl_video_list',
                                        post_id: post_id,
                                        jwplayer: 1
                                    },
                                    success: function (response) {
                                        $element.removeClass('ajax-loading');
                                        var $video_fancy_box = $element.find('.video-fancy-box');
                                        if ($video_fancy_box.length && response.has_data) {
                                            $video_fancy_box.html(response.html_data);
                                        }
                                        $element.find('a.post-thumbnail, a.fancy-link').fancybox({
                                            autoScale: true,
                                            padding: 0,
                                            scrolling: 'no',
                                            autoDimensions: true,
                                            width: 640,
                                            height: 480,
                                            minHeight: 480,
                                            maxHeight: 480,
                                            maxWidth: "90%",
                                            minWidth: "80%"
                                        });
                                    }
                                });
                            }
                        });

                        if ($fancy_video.length) {
                            $fancy_video.each(function () {
                                var $element = $(this),
                                    $fansub_post = $element.closest('.fansub-post');
                                if (!$fansub_post.hasClass('ajax-loading')) {
                                    $element.fancybox({
                                        autoScale: true,
                                        padding: 0,
                                        scrolling: 'no',
                                        autoDimensions: true,
                                        width: 640,
                                        height: 480,
                                        minHeight: 480,
                                        maxHeight: 480,
                                        maxWidth: "90%",
                                        minWidth: "80%"
                                    });
                                }
                            });
                        }
                    }
                }
            });
        }
    })();

    (function () {
        $('.anime-list .pagination.ajax .link-item').on('click', function (e) {
            e.preventDefault();
            var $element = $(this),
                paged = parseInt($element.attr('data-paged')),
                $pagination = $element.closest('.pagination'),
                $container = $pagination.parent(),
                $another = $container.find('.pagination').not($pagination),
                $next_item = $pagination.find('.next-item'),
                $prev_item = $pagination.find('.previous-item'),
                $first_item = $pagination.find('a[data-paged="1"]'),
                $list_post = $container.find('ul');
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_kntl_pagination',
                    paged: paged,
                    query_vars: $pagination.attr('data-query-vars')
                },
                success: function (response) {
                    if (0 == paged) {
                        $first_item.trigger('click');
                    } else {
                        var $current_element = $pagination.find("[data-paged='" + paged + "']").not('.last-item, .next-item, .first-item, .previous-item'),
                            $another_current_element = $another.find("[data-paged='" + paged + "']").not('.last-item, .next-item, .first-item, .previous-item');
                        $pagination.find('.current-item').removeClass('current-item');
                        $another.find('.current-item').removeClass('current-item');
                        if (response.have_posts) {
                            $list_post.html(response.html);
                            if ($element.hasClass('next-item')) {
                                $element.attr('data-paged', paged + 1);
                            } else if ($element.hasClass('previous-item')) {
                                if (paged < 1) {
                                    paged = 2;
                                }
                                $element.attr('data-paged', paged - 1);
                            } else {
                                $next_item.attr('data-paged', paged + 1);
                                $prev_item.attr('data-paged', paged - 1);
                            }
                        } else {
                            if ($element.hasClass('next-item') || $element.hasClass('previous-item')) {
                                $element.attr('data-paged', 1);
                            }
                            $first_item.trigger('click');
                        }
                        $current_element.addClass('current-item');
                        $another_current_element.addClass('current-item');
                        var $lazy_image = $list_post.find('.wp-post-image.lazyload');
                        if ($lazy_image.length) {
                            $lazy_image.lazyload({
                                event: "scrollstop"
                            }).removeClass('lazyload').addClass('lazyloaded');
                        }
                    }
                }
            });
        });
    })();

    (function () {
        $('.fansub-new-release .quality-item .quality').live('click', function (e) {
            e.preventDefault();
            var $element = $(this),
                $quality_item = $element.parent(),
                $list_qualities = $quality_item.parent(),
                $list_servers = $quality_item.find('.list-servers'),
                $fansub_post = $element.closest('.fansub-post'),
                list_server_height = 0;
            if (!$element.hasClass('has-link')) {
                return false;
            }
            $list_qualities.find('.quality-item').not($quality_item).removeClass('current-quality').find('.list-servers').hide();
            $quality_item.toggleClass('current-quality');
            if ($quality_item.hasClass('current-quality')) {
                $fansub_post.addClass('active');
                $list_servers.slideDown();
                list_server_height = $list_servers.height();
                if (list_server_height > 25) {
                    $fansub_post.css({'margin-bottom': list_server_height + 'px'});
                } else {
                    list_server_height = 25;
                    $fansub_post.css({'margin-bottom': list_server_height + 'px'});
                }
            } else {
                $list_servers.hide();
                $fansub_post.removeClass('active');
                $fansub_post.css({'margin-bottom': '0'});
            }
        });
    })();

    (function () {
        $('.fansub-new-release').each(function (index, el) {
            var $element = $(el);
            if (!$element.hasClass('single-box')) {
                var $fansub_post = $element.closest('.fansub-post');
                $fansub_post.find('.entry-title').each(function () {
                    var $this = $(this);
                    if ($this.html().replace(/\s|&nbsp;/g, '').length == 0) {
                        $this.parent().remove();
                    }
                });
            }
        });
    })();

    (function () {
        $('.fansub-new-release .search-form:not(.advanced-search-form)').on('submit', function (e) {
            e.preventDefault();
            var $element = $(this),
                $search_field = $element.find('.search-field'),
                search_term = $search_field.val(),
                $fansub_new_release = $element.closest('.box-content'),
                $list_releases = $fansub_new_release.find('.list-releases'),
                $query_vars = $fansub_new_release.find('.query-vars'),
                $dashicons = $element.find('.dashicons').not('.dashicons-lock'),
                $options_data = $fansub_new_release.find('.options-data');
            $list_releases.fadeOut();
            if (!$.trim(search_term)) {
                $('.fansub-new-release .search-form .refreshbutton').trigger('click');
                return false;
            }
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_kntl_search_post',
                    search: $search_field.val(),
                    query_vars: $query_vars.val(),
                    options_data: $options_data.val(),
                    box_type: $fansub_new_release.attr('data-type'),
                    is_search: 1
                },
                success: function (response) {
                    $dashicons.removeClass('dashicons-update');
                    $dashicons.addClass('dashicons-dismiss');
                    $dashicons.attr('title', $dashicons.attr('data-clear-text'));
                    if (response.success) {
                        $list_releases.html(response.html);
                    } else {
                        $list_releases.html(response.no_post_msg);
                    }
                    $query_vars.val(response.query_vars);
                    $list_releases.fadeIn();
                }
            });
        });

        $('.fansub-new-release .search-form .refreshbutton').live('click', function (e) {
            e.preventDefault();
            var $element = $(this).closest('.search-form'),
                $fansub_new_release = $element.closest('.box-content'),
                $btn_more = $fansub_new_release.find('.btn-more'),
                $dashicons = $fansub_new_release.find('.dashicons').not('.dashicons-lock'),
                $search_field = $fansub_new_release.find('.search-field'),
                $list_releases = $fansub_new_release.find('.list-releases'),
                $query_vars = $fansub_new_release.find('.query-vars'),
                $default_query_vars = $fansub_new_release.find('.default-query-vars'),
                default_query_vars = $default_query_vars.val(),
                $single_box = $element.closest('.single-box'),
                is_single = 0,
                $options_data = $fansub_new_release.find('.options-data');
            if (!$dashicons.hasClass('dashicons-dismiss')) {
                //default_query_vars = '';
            }
            if ($single_box.length) {
                is_single = 1;
            }
            $list_releases.fadeOut();
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_kntl_search_post',
                    default_query_vars: default_query_vars,
                    options_data: $options_data.val(),
                    refresh: 1,
                    box_type: $fansub_new_release.attr('data-type'),
                    single: is_single
                },
                success: function (response) {
                    if ($dashicons.hasClass('dashicons-dismiss')) {
                        $dashicons.removeClass('dashicons-dismiss');
                        $dashicons.addClass('dashicons-update');
                        $dashicons.attr('title', $dashicons.attr('data-refresh-text'));
                        $search_field.val('');
                    }
                    if (response.success) {
                        $list_releases.html(response.html);
                    }
                    $query_vars.val($query_vars.attr('data-default'));
                    $list_releases.fadeIn();
                    $btn_more.css({cursor: 'pointer'});
                    $btn_more.removeClass('no-more-post');
                    $btn_more.html($btn_more.attr('data-text'));
                }
            });
        });

        $('.fansub-new-release .btn-more').live('click', function (e) {
            e.preventDefault();
            var $element = $(this),
                $fansub_new_release = $element.closest('.box-content'),
                $list_releases = $fansub_new_release.find('.list-releases'),
                $query_vars = $fansub_new_release.find('.query-vars'),
                $dashicons = $fansub_new_release.find('.dashicons').not('.dashicons-lock'),
                $options_data = $fansub_new_release.find('.options-data');
            if ($element.hasClass('no-more-post')) {
                return false;
            }
            $element.html($element.attr('data-loading-text'));
            //$list_releases.fadeOut();
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_kntl_search_post',
                    query_vars: $query_vars.val(),
                    options_data: $options_data.val(),
                    box_type: $fansub_new_release.attr('data-type'),
                    load_more: 1
                },
                success: function (response) {
                    if (response.have_posts) {
                        $dashicons.removeClass('dashicons-update');
                        $dashicons.addClass('dashicons-dismiss');
                        $dashicons.attr('title', $dashicons.attr('data-clear-text'));
                        $list_releases.append(response.html);
                    }
                    $query_vars.val(response.query_vars);
                    if (!response.more_post) {
                        $element.addClass('no-more-post');
                        $element.html($element.attr('data-reached-end-text'));
                        $element.css({cursor: 'text'});
                    } else {
                        $element.html($element.attr('data-text'));
                    }

                    $list_releases.fadeIn();
                }
            });
        });
    })();

    (function () {
        $body.on('click', '.server-item a', function (e) {
            var $element = $(this),
                $list_servers = $element.closest('.list-servers'),
                $private_item = $list_servers.find('.private-item');
            if ($private_item.length) {
                e.preventDefault();
                if ($private_item.hasClass('password-required')) {
                    var unlocked = parseInt($private_item.attr('data-unlocked'));
                    if (1 != unlocked) {
                        if ($element.hasClass('private-link')) {
                            var password = prompt('Enter password');
                            $.ajax({
                                type: 'POST',
                                dataType: 'json',
                                url: fansub.ajax_url,
                                data: {
                                    action: 'fansub_ph_check_post_password',
                                    post_id: $private_item.attr('data-id'),
                                    password: password
                                },
                                success: function (response) {
                                    if (response.success) {
                                        if (!$element.hasClass('private-link')) {
                                            window.open($element.attr('data-href'), '_blank');
                                        } else {
                                            $private_item.attr('data-unlocked', 1);
                                            $element.find('.dashicons').addClass('dashicons-unlock');
                                        }
                                    } else {
                                        if ($.trim(password)) {
                                            alert(response.message);
                                        }
                                    }
                                }
                            });
                        }
                    } else {
                        if (!$element.hasClass('private-link')) {
                            window.open($element.attr('data-href'), '_blank');
                        }
                    }
                } else {
                    alert('Private content');
                }
            }
        });
    })();
});