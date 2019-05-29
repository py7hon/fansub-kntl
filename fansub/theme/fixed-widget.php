<script>
    (function($) {
        var $sidebar = $('.sidebar');
        if($sidebar.length) {
            var $window = $(window),
                window_top = $window.scrollTop(),
                $content_area = $sidebar.prev(),
                content_area_height = $content_area.height(),
                content_area_offset_top = $content_area.offset().top,
                $last_widget = $('.fansub .sidebar .widget:last'),
                widget_width = $last_widget.width(),
                widget_offset_top = $last_widget.offset().top,
                widget_height = $last_widget.height(),
                $admin_bar = $('#wpadminbar'),
                $site_footer = $('.site-footer'),
                site_footer_margin_top = parseInt($site_footer.css('margin-top').replace('px', '')),
                site_footer_height = $site_footer.height(),
                site_footer_offset_top = $site_footer.offset().top,
                last_scroll_top = 0;
            if(content_area_height < widget_height || 0 == widget_width || widget_width > 300 || $window.width() < 980) {
                return false;
            }
            if($admin_bar.length) {
                widget_offset_top -= $admin_bar.height();
            }
            if(window_top > widget_offset_top) {
                $last_widget.addClass('fixed');
            } else {
                $last_widget.removeClass('fixed');
            }
            $window.scroll(function() {
                if($window.width() < 980) {
                    return false;
                }
                window_top = $(this).scrollTop();
                var scroll_down = true;
                if(window_top > last_scroll_top) {
                    scroll_down = true;
                } else {
                    scroll_down = false;
                }
                last_scroll_top = window_top;
                content_area_height = $content_area.height();
                if(window_top > (content_area_height - content_area_offset_top + site_footer_height)) {
                    $last_widget.addClass('fixed-bottom');
                } else {
                    $last_widget.removeClass('fixed-bottom');
                    var p_top = 0;
                    if($('body').hasClass('admin-bar')) {
                        p_top = $('#wpadminbar').height() + 'px';
                    }
                    $last_widget.css({'top' : p_top, 'bottom' : 'auto'});
                }
                if(window_top > widget_offset_top) {
                    $last_widget.addClass('fixed');
                } else {
                    $last_widget.removeClass('fixed');
                }
                if($last_widget.hasClass('fixed-bottom')) {
                    var bottom = (site_footer_height + site_footer_margin_top),
                        white_space = site_footer_offset_top - window_top;
                    $last_widget.css({'bottom' : bottom + 'px', 'top' : 'auto'});
                }
            });
        }
    })(jQuery);
</script>