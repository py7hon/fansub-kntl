

window.wp = window.wp || {};
window.fansub = window.fansub || {};

if(typeof jQuery === 'undefined') {
    throw new Error(fansub.i18n.jquery_undefined_error)
}

jQuery(document).ready(function($) {
    'use strict';

    var version = $.fn.jquery.split(' ')[0].split('.');
    if((version[0] < 2 && version[1] < 9) || (version[0] == 1 && version[1] == 9 && version[2] < 1)) {
        throw new Error(fansub.i18n.jquery_version_error)
    }
});

fansub.media_frame = null;
fansub.media_items = {};

jQuery(document).ready(function($) {
    'use strict';
    var $body = $('body');

    fansub.getParamByName = function(url, name) {
        name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
            results = regex.exec(url);
        return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
    };

    fansub.receiveSelectedMediaItems = function(file_frame) {
        return file_frame.state().get('selection');
    };

    fansub.receiveSelectedMediaItem = function(file_frame) {
        var items = fansub.receiveSelectedMediaItems(file_frame);
        return items.first().toJSON();
    };

    fansub.isImageUrl = function(url) {
        if(!$.trim(url)) {
            return false;
        }
        var result = true,
            extension = url.slice(-4);
        if(extension != '.png' && extension != '.jpg' && extension != '.gif' && extension != '.bmp' && extension != 'jpeg') {
            if(extension != '.ico') {
                result = false;
            }
        }
        return result;
    };

    fansub.getTagName = function($tag) {
        if($tag.length) {
            return $tag.get(0).tagName;
        }
        return '';
    };

    fansub.isUrl = function(text) {
        var url_regex = new RegExp('^(http:\/\/www.|https:\/\/www.|ftp:\/\/www.|www.){1}([0-9A-Za-z]+\.)');
        return url_regex.test(text);
    };

    fansub.isArray = function(variable){
        return (Object.prototype.toString.call(variable) === '[object Array]');
    };

    fansub.getFirstMediaItemJSON = function(media_items) {
        return media_items.first().toJSON();
    };

    fansub.createImageHTML = function(args) {
        args = args || {};
        var alt = args.alt || '',
            id = args.id || 0,
            src = args.src || '',
            $element = args.element || null;
        if($.isNumeric(id) && id > 0) {
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_sanitize_media_value',
                    url: src,
                    id: id
                },
                success: function(response){
                    if(!response.is_image) {
                        src = response.type_icon;
                    }
                    if($element.length) {
                        $element.html('<img src="' + src + '" alt="' + alt + '">');
                    }
                }
            });
        } else {
            if($.trim(src)) {
                return '<img src="' + src + '" alt="' + alt + '">';
            }
        }
    };

    fansub.autoReloadPageNoActive = function(reload_time, delay_time) {
        reload_time = reload_time || 60000;
        delay_time = delay_time || 10000;
        var time = new Date().getTime();
        $(document.body).bind('mousemove keypress', function() {
            time = new Date().getTime();
        });
        function refresh() {
            if(new Date().getTime() - time >= reload_time) {
                window.location.reload(true);
            } else {
                setTimeout(refresh, delay_time);
            }
        }
        setTimeout(refresh, delay_time);
    };

    fansub.autoReloadPage = function(delay_time) {
        delay_time = delay_time || 2000;
        var time = new Date().getTime();
        function refresh() {
            if(new Date().getTime() - time >= delay_time) {
                window.location.reload(true);
            } else {
                setTimeout(refresh, 1000);
            }
        }
        setTimeout(refresh, 1000);
    };

    fansub.debugLog = function(object) {
        var data = JSON.stringify(object);
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: fansub.ajax_url,
            data: {
                action: 'fansub_debug_log',
                object: data
            },
            success: function(response){

            }
        });
    };

    fansub.limitUploadFile = function($element) {
        $element.on('change', function() {
            var count_file = $element.get(0).files.length,
                max_file = parseInt($element.attr('data-max')),
                object = this,
                $image_preview = $element.next();
            if(!$.isNumeric(max_file)) {
                max_file = -1;
            }
            if(max_file > 0 && count_file > max_file) {
                alert('Bạn không được chọn quá ' + max_file + ' tập tin.');
                $element.val('');
                return false;
            }
            if($image_preview.length) {
                $image_preview.empty();
                if(typeof (FileReader) != "undefined") {
                    for(var i = 0; i < count_file; i++) {
                        var reader = new FileReader(),
                            file_name = object.files.item(i).name;
                        reader.onload = function(e) {
                            var $image = $('<img>', {
                                src: e.target.result,
                                class: 'thumb-image',
                                alt: ''
                            }).attr('data-file-name', file_name);
                            $image.appendTo($image_preview);
                        };
                        $image_preview.show();
                        reader.readAsDataURL($element.get(0).files[i]);
                    }

                }
            }
        });
    };

    fansub.setCookie = function(cname, cvalue, exmin) {
        var d = new Date();
        d.setTime(d.getTime() + (exmin * 60 * 1000));
        var expires = "expires=" + d.toGMTString(),
            my_cookies;
        my_cookies = cname + "=" + cvalue + "; " + expires + "; path=/";
        document.cookie = my_cookies;
    };

    fansub.iconChangeCaptchaExecute = function() {
        var $icon_refresh_captcha = $('img.fansub-captcha-reload'),
            $captcha_image = $('img.fansub-captcha-image');
        if(!$captcha_image.length) {
            return false;
        }
        $captcha_image.css({'cursor' : 'text'});
        $icon_refresh_captcha.css({'opacity' : '0.75'});
        $icon_refresh_captcha.on('mouseover', function(e) {
            e.preventDefault();
            $(this).css({'opacity' : '1'});
        });
        $icon_refresh_captcha.on('mouseout mouseleave', function(e) {
            e.preventDefault();
            $(this).css({'opacity' : '0.75'});
        });
        $icon_refresh_captcha.on('click', function(e) {
            e.preventDefault();
            var $element = $(this),
                $container = $element.parent(),
                $image = $container.find('img.fansub-captcha-image');
            $element.css({'opacity' : '0.25', 'pointer-events' : 'none'});
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_change_captcha_image'
                },
                success: function(response){
                    if(response.success) {
                        $image.attr('src', response.captcha_image_url);
                    } else {
                        alert(response.message);
                    }
                    $element.css({'opacity' : '0.75', 'pointer-events' : 'inherit'});
                }
            });
        });
    };

    fansub.addDefaultQuicktagButton = function() {
        var $quicktags_toolbar = $body.find('.quicktags-toolbar');
        if(!$body.hasClass('front-end') && $quicktags_toolbar.length && $quicktags_toolbar.attr('id') == 'ed_toolbar') {
            QTags.addButton('hr', 'hr', '\n<hr>\n', '', 'h', 'Horizontal rule line', 30);
            QTags.addButton('dl', 'dl', '<dl>\n', '</dl>\n\n', 'd', 'HTML Description List Element', 100);
            QTags.addButton('dt', 'dt', '\t<dt>', '</dt>\n', '', 'HTML Definition Term Element', 101);
            QTags.addButton('dd', 'dd', '\t<dd>', '</dd>\n', '', 'HTML Description Element', 102);
        }
    };

    fansub.formatNumber = function(number, separator, currency) {
        currency = currency || ' ₫';
        separator = separator || ',';
        var number_string = number.toString(),
            decimal = '.',
            numbers = number_string.split('.'),
            result = '';
        if(!fansub.isArray(numbers)) {
            numbers = number_string.split(',');
            decimal = ',';
        }
        if(fansub.isArray(numbers)) {
            number_string = numbers[0];
        }
        var number_len = parseInt(number_string.length);
        var last = number_string.slice(-3);
        if(number_len > 3) {
            result += separator + last;
        } else {
            result += last;
        }

        while(number_len > 3) {
            number_len -= 3;
            number_string = number_string.slice(0, number_len);
            last = number_string.slice(-3);

            if(number_len <= 3) {
                result = last + result;
            } else {
                result = separator + last + result;
            }
        }
        if(fansub.isArray(numbers) && $.isNumeric(numbers[1])) {
            result += decimal + numbers[1];
        }
        result += currency;
        result = $.trim(result);
        return result;
    };

    fansub.scrollToPosition = function(pos, time) {
        time = time || 1000;
        $('html, body').stop().animate({scrollTop: pos}, time);
    };

    fansub.goToTop = function() {
        fansub.scrollToPosition(0);
        return false;
    };

    fansub.scrollToTop = function() {
        fansub.goToTop();
    };

    fansub.isEmail = function(email) {
        return this.test(email, '^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+@[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$');
    };

    fansub.isEmpty = function(text) {
        return text.trim();
    };

    fansub.switcherAjax = function() {
        $('.fansub-switcher-ajax .icon-circle').on('click', function(e) {
            e.preventDefault();
            var $element = $(this),
                opacity = '0.5';
            if($element.hasClass('icon-circle-success')) {
                opacity = '0.25';
            }
            $element.css({'opacity' : opacity});
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_switcher_ajax',
                    post_id: $element.attr('data-id'),
                    value: $element.attr('data-value'),
                    key: $element.attr('data-key')
                },
                success: function(response){
                    if(response.success) {
                        $element.toggleClass('icon-circle-success');
                    }
                    $element.css({'opacity' : '1'});
                }
            });
        });
    };

    fansub.chosenSelectUpdated = function(el) {
        var $element = el,
            values = $element.chosen().val();
        var $parent = $element.parent(),
            $result = $parent.find('.chosen-result');
        if(null == values) {
            $result.val('');
            return;
        }
        var new_value = [],
            taxonomy = null,
            $option = null,
            i = 0,
            count_value = values.length,
            is_term = false;
        for(i; i <= count_value; i++) {
            var current_value = values[i],
                new_item = {value: current_value};
            $option = $parent.find('option[value="' + current_value + '"]');
            taxonomy = $option.attr('data-taxonomy');
            if($.trim(taxonomy)) {
                new_item.taxonomy = taxonomy;
                is_term = true;
            }
            new_value.push(new_item);
        }
        $result.val(JSON.stringify(new_value));
    };

    fansub.mediaRemove = function(upload, remove, preview, url, id) {
        preview.html('');
        url.val('');
        id.val('');
        remove.addClass('hidden');
        upload.removeClass('hidden');
    };

    fansub.mediaChange = function(upload, remove, preview, url, id) {
        if(fansub.isImageUrl(url.val())) {
            preview.html(fansub.createImageHTML({src: url.val(), id: id.val(), element: preview}));
        } else {
            preview.html('');
        }
        id.val('');
    };

    fansub.mediaUpload = function(button, options) {
        var defaults = {
            title: fansub.i18n.insert_media_title,
            button_text: null,
            multiple: false,
            remove: false,
            change: false
        };
        options = options || {};
        options = $.extend({}, defaults, options);
        var $container = button.parent();
        var $url = $container.find('input.media-url'),
            $id = $container.find('input.media-id'),
            $remove = $container.find('.btn-remove'),
            $preview = $container.find('.media-preview'),
            media_frame = null;
        if(!options.remove && !options.change) {
            if(button.hasClass('selecting')) {
                return;
            }
            if(!options.button_text) {
                if(options.multiple) {
                    options.button_text = fansub.i18n.insert_media_button_texts;
                } else {
                    options.button_text = fansub.i18n.insert_media_button_text;
                }
            }
            button.addClass('selecting');
            if(media_frame) {
                media_frame.open();
                return;
            }
            media_frame = wp.media({
                title: options.title,
                button: {
                    text: options.button_text
                },
                multiple: options.multiple
            });
            media_frame.on('select', function() {
                var media_items = fansub.receiveSelectedMediaItems(media_frame);
                if(!options.multiple) {
                    var media_item = fansub.getFirstMediaItemJSON(media_items);
                    if(media_item.id) {
                        $id.val(media_item.id);
                    }
                    if(media_item.url) {
                        $url.val(media_item.url);
                        $preview.html(fansub.createImageHTML({src: media_item.url, id: media_item.id, element: $preview}));
                        button.addClass('hidden');
                        $remove.removeClass('hidden');
                    }
                }
                button.removeClass('selecting');
            });
            media_frame.on('escape', function() {
                button.removeClass('selecting');
            });
            media_frame.open();
        } else {
            if(options.remove) {
                fansub.mediaRemove(button, $remove, $preview, $url, $id);
            }
        }

        if(options.change) {
            fansub.mediaChange(button, $remove, $preview, $url, $id);
        }

        $url.on('change input', function(e) {
            e.preventDefault();
            fansub.mediaChange(button, $remove, $preview, $url, $id);
        });

        $remove.on('click', function(e) {
            e.preventDefault();
            fansub.mediaRemove(button, $remove, $preview, $url, $id);
        });
    };

    fansub.sortableTermStop = function(container) {
        var $input_result = container.find('.input-result'),
            $sortable_result = container.find('.connected-result'),
            value = [];
        $sortable_result.find('li').each(function(index, el) {
            var $element = $(el),
                item = {
                    id: $element.attr('data-id'),
                    taxonomy: $element.attr('data-taxonomy')
                };
            value.push(item);
        });
        value = JSON.stringify(value);
        $input_result.val(value);
        return value;
    };

    fansub.sortablePostTypeStop = function(container) {
        var $input_result = container.find('.input-result'),
            $sortable_result = container.find('.connected-result'),
            value = [];
        $sortable_result.find('li').each(function(index, el) {
            var $element = $(el),
                item = {
                    id: $element.attr('data-id')
                };
            value.push(item);
        });
        value = JSON.stringify(value);
        $input_result.val(value);
        return value;
    };

    fansub.sortableTaxonomyStop = function(container) {
        var $input_result = container.find('.input-result'),
            $sortable_result = container.find('.connected-result'),
            value = [];
        $sortable_result.find('li').each(function(index, el) {
            var $element = $(el),
                item = {
                    id: $element.attr('data-id')
                };
            value.push(item);
        });
        value = JSON.stringify(value);
        $input_result.val(value);
        return value;
    };

    fansub.sortableStop = function($element, $container) {
        var $input_result = $container.find('.input-result'),
            value = [];
        $element.find('li').each(function(index, el) {
            var $element = $(el),
                taxonomy = $element.attr('data-taxonomy'),
                item = {
                    id: $element.attr('data-id')
                };
            if(typeof taxonomy !== typeof undefined && taxonomy !== false) {
                item.taxonomy = taxonomy;
            }
            value.push(item);
        });
        value = JSON.stringify(value);
        $input_result.val(value);
        return value;
    };

    fansub.administrativeBoundaries = function($element, child_name, $container) {
        $container = $container || $element.closest('form');
        var $form = $container,
            $child = $form.find('select[name=' + child_name + ']'),
            $default = $child.find('option[value=0]'),
            element_name = $element.attr('name');
        if($child.length) {
            if(!$default.length) {
                $default = $child.find('option[value=""]')
            }
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_fetch_administrative_boundaries',
                    parent: $element.val(),
                    taxonomy: 'category',
                    type: element_name,
                    default: $default.prop('outerHTML')
                },
                success: function(response){
                    $child.html(response.html_data);
                }
            });
        }
    };
});

jQuery(document).ready(function($) {
    function MediaUpload(element, options) {
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, MediaUpload.DEFAULTS, options);
        this.items = null;
        this.$container = this.$element.parent();
        this.$id = this.$container.find('input.media-id');
        this.$url = this.$container.find('input.media-url');
        this.$preview = this.$container.find('.media-preview');
        this.$remove = this.$container.find('.btn-remove');
        //this.$th = this.$container.prev();
        this._defaults = MediaUpload.DEFAULTS;
        this._name = MediaUpload.NAME;
        this.frame = null;

        this.init();

        this.$element.on('click', $.proxy(this.add, this));
        this.$url.on('change input', $.proxy(this.change, this));
        this.$remove.on('click', $.proxy(this.remove, this));
    }

    MediaUpload.NAME = 'fansub.mediaUpload';

    MediaUpload.DEFAULTS = {
        title: fansub.i18n.insert_media_title,
        button_text: null,
        multiple: false
    };

    MediaUpload.prototype.init = function() {
        if(!this.options.button_text) {
            if(this.options.multiple) {
                this.options.button_text = fansub.i18n.insert_media_button_texts;
            } else {
                this.options.button_text = fansub.i18n.insert_media_button_text;
            }
        }
    };

    MediaUpload.prototype.selected = function() {
        this.items = fansub.receiveSelectedMediaItems(this.frame);
        if(!this.options.multiple) {
            var media_item = fansub.getFirstMediaItemJSON(this.items);
            if(media_item.id) {
                this.$id.val(media_item.id);
            }
            if(media_item.url) {
                this.$url.val(media_item.url);
                this.$preview.html(fansub.createImageHTML({src: media_item.url, id: media_item.id, element: this.$preview}));
                this.$element.addClass('hidden');
                this.$remove.removeClass('hidden');
            }
        }
        this.$element.removeClass('selecting');
    };

    MediaUpload.prototype.remove = function(e) {
        e.preventDefault();
        this.$preview.html('');
        this.$url.val('');
        this.$id.val('');
        this.$remove.addClass('hidden');
        this.$element.removeClass('hidden');
    };

    MediaUpload.prototype.add = function(e) {
        e.preventDefault();
        var $element = this.$element;
        if(this.$element.hasClass('selecting')) {
            return;
        }
        this.$element.addClass('selecting');
        if(this.frame) {
            this.frame.open();
            return;
        }
        this.frame = wp.media({
            title: this.options.title,
            button: {
                text: this.options.button_text
            },
            multiple: this.options.multiple
        });
        this.frame.on('select', $.proxy(this.selected, this));
        this.frame.on('escape', function() {
            $element.removeClass('selecting');
        });
        this.frame.open();
    };

    MediaUpload.prototype.change = function(e) {
        e.preventDefault();
        if(fansub.isImageUrl(this.$url.val())) {
            this.$preview.html(fansub.createImageHTML({src: this.$url.val(), id: this.$id.val(), element: this.$preview}));
        } else {
            this.$preview.html('');
        }
        this.$id.val('');
    };

    $.fn.fansubMediaUpload = function(options) {
        return this.each(function() {
            if(!$.data(this, MediaUpload.NAME)) {
                $.data(this, MediaUpload.NAME, new MediaUpload(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    function ScrollTop(element, options) {
        var $window = $(window),
            current_pos = $window.scrollTop();
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, ScrollTop.DEFAULTS, options);
        this._defaults = ScrollTop.DEFAULTS;
        this._name = ScrollTop.NAME;

        this.init();

        var pos_to_show = this.options.posToShow,
            $element = this.$element;

        if(current_pos >= pos_to_show) {
            $element.fadeIn();
        }

        $window.scroll(function() {
            if($(this).scrollTop() >= pos_to_show) {
                $element.fadeIn();
            } else {
                $element.fadeOut();
            }
        });

        $element.on('click', $.proxy(this.click, this));
    }

    ScrollTop.NAME = 'fansub.scrollTop';

    ScrollTop.DEFAULTS = {
        posToShow: 100
    };

    ScrollTop.prototype.init = function() {

    };

    ScrollTop.prototype.click = function(e) {
        e.preventDefault();
        fansub.scrollToTop();
    };

    $.fn.fansubScrollTop = function(options) {
        return this.each(function() {
            if(!$.data(this, ScrollTop.NAME)) {
                $.data(this, ScrollTop.NAME, new ScrollTop(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    function SortableList(element, options) {
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, SortableList.DEFAULTS, options);
        this._defaults = SortableList.DEFAULTS;
        this._name = SortableList.NAME;
        if(this.$element.hasClass('manage-column')) {
            return;
        }
        this.init();
        var $element = this.$element,
            $container = $element.parent(),
            $sortable_result = $element.next(),
            sortable_options = {
                placeholder: 'ui-state-highlight',
                sort: function(event, ui) {
                    var that = $(this),
                        ui_state_highlight = that.find('.ui-state-highlight');
                    ui_state_highlight.css({'height': ui.item.height()});
                    if(that.hasClass('display-inline')) {
                        ui_state_highlight.css({'width': ui.item.width()});
                    }
                },
                stop: function() {
                    var $sortable_result = $container.find('.connected-result');
                    if($sortable_result.length) {
                        if($sortable_result.hasClass('term-sortable')) {
                            fansub.sortableTermStop($container);
                        } else if($sortable_result.hasClass('post-type-sortable')) {
                            fansub.sortablePostTypeStop($container);
                        } else if($sortable_result.hasClass('taxonomy-sortable')) {
                            fansub.sortableTaxonomyStop($container);
                        }
                    } else {
                        fansub.sortableStop($element, $container);
                    }
                }
            };
        if($sortable_result.length && $sortable_result.hasClass('sortable')) {
            var element_height = $element.height(),
                sortable_result_height = $sortable_result.height();
            if(element_height > sortable_result_height) {
                $sortable_result.css({'height': element_height});
            }
        }
        if($element.hasClass('connected-list')) {
            sortable_options.connectWith = '.connected-list';
        }
        $element.sortable(sortable_options).disableSelection();
    }

    SortableList.NAME = 'fansub.sortableList';

    SortableList.DEFAULTS = {};

    SortableList.prototype.init = function() {

    };

    $.fn.fansubSortable = function(options) {
        return this.each(function() {
            if(!$.data(this, SortableList.NAME)) {
                $.data(this, SortableList.NAME, new SortableList(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    function MobileMenu(element, options) {
        var $window = $(window),
            $body = $('body'),
            current_width = $window.width();
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, MobileMenu.DEFAULTS, options);
        this._defaults = MobileMenu.DEFAULTS;
        this._name = MobileMenu.NAME;
        this.init();
        var $element = this.$element,
            $menu_parent = $element.parent(),
            $mobile_menu_button = $menu_parent.find('.mobile-menu-button'),
            $search_form = $menu_parent.find('.search-form'),
            display_width = parseFloat(this.options.displayWidth),
            height = parseInt(this.options.height),
            body_height = $body.height(),
            force_search_form = this.options.forceSearchForm,
            search_form_added = false;
        this.element_class = $element.attr('class');
        this.html = $element.html();
        var html = this.html,
            menu_class = this.element_class,
            position = this.options.position,
            window_resized = false;
        function fansub_update_mobile_menu() {
            $element.removeClass('sf-menu sf-js-enabled');
            $element.find('li.menu-item-has-children').not('.appended').addClass('appended').append('<i class="fa fa-plus"></i>');
            $element.css({height: body_height});
            $element.show();
            $element.addClass(position);
            $element.addClass('fansub-mobile-menu');
            if(!$mobile_menu_button.length) {
                $menu_parent.append(fansub.mobile_menu_icon);
                $mobile_menu_button = $menu_parent.find('.mobile-menu-button');
                $mobile_menu_button.attr('aria-controls', $element.attr('id'))
            }
            if(!search_form_added && (!$search_form.length || force_search_form)) {
                if(!search_form_added && (!$element.find('li.search-item').length || force_search_form)) {
                    $element.prepend('<li class="search-item menu-item" style="overflow: hidden">' + fansub.search_form + '</li>');
                    search_form_added = true;
                }
            }
            $mobile_menu_button.css({'line-height' : height + 'px'});
            $mobile_menu_button.show();

            $menu_parent.off('click', '.mobile-menu-button').on('click', '.mobile-menu-button', function(e) {
                e.stopPropagation();
                $element.toggleClass('active');
            });

            $body.on('click', function() {
                $element.removeClass('active');
            });

            $menu_parent.off('click', '.fansub-mobile-menu').on('click', '.fansub-mobile-menu', function(e) {
                e.stopPropagation();
                if(e.target == this) {
                    $element.toggleClass('active');
                }
            });

            $element.find('.search-field').on('click', function(e) {
                e.preventDefault();
            });

            if($body.hasClass('jquery-mobile')) {
                $menu_parent.on('swipeleft', '.fansub-mobile-menu', function(e) {
                    e.preventDefault();
                    $element.removeClass('active');
                });
            }

            $element.find('li.menu-item-has-children .fa').off('click').on('click', function(e) {
                e.preventDefault();
                var $this = $(this),
                    $current_li = $this.parent(),
                    $sub_menu = $current_li.children('.sub-menu');
                if($this.hasClass('active')) {
                    $sub_menu.stop(true, false, true).slideUp();
                    $this.removeClass('fa-minus');
                    $this.addClass('fa-plus');
                    $current_li.find('.fa-minus').each(function() {
                        $(this).removeClass('fa-minus active').addClass('fa-plus');
                    });
                    $current_li.find('.sub-menu').not($sub_menu).hide();
                } else {
                    $this.removeClass('fa-plus');
                    $this.addClass('fa-minus');
                    $sub_menu.stop(true, false, true).slideDown();
                }
                $this.toggleClass('active');
            });

            $window.scroll(function() {
                var pos = $(this).scrollTop(),
                    $admin_bar = $('#wpadminbar'),
                    admin_bar_height = 0;
                if($admin_bar.length) {
                    admin_bar_height = $admin_bar.height();
                }
                if(pos < 100) {
                    pos = admin_bar_height;
                }
                if(pos == admin_bar_height) {
                    $element.css({'top' : pos + 'px'});
                } else {
                    $element.css({'top' : '-' + pos + 'px'});
                }
            });
        }
        if(current_width > display_width) {
            if(!window_resized) {
                $window.on('resize', function() {
                    window_resized = true;
                    current_width = $window.width();
                    if(current_width > display_width) {
                        if($element.hasClass('fansub-mobile-menu')) {
                            $element.attr('class', menu_class);
                            $element.attr('style', '');
                            $element.html(html);
                            window.location.href = window.location.href;
                        }
                    } else {
                        fansub_update_mobile_menu();
                    }
                });
            }
            return this;
        }

        if(current_width <= display_width) {
            fansub_update_mobile_menu();
        }

        if(!window_resized) {
            $window.on('resize', function() {
                window_resized = true;
                current_width = $window.width();
                if(current_width > display_width) {
                    if($element.hasClass('fansub-mobile-menu')) {
                        $element.attr('class', menu_class);
                        $element.attr('style', '');
                        $element.html(html);
                        window.location.href = window.location.href;
                    }
                } else {
                    fansub_update_mobile_menu();
                }
            });
        }
    }

    MobileMenu.NAME = 'fansub.mobileMenu';

    MobileMenu.DEFAULTS = {
        displayWidth: 980,
        position: 'left',
        height: 30,
        forceSearchForm: false
    };

    MobileMenu.prototype.init = function() {
        if(!this.$element.is('ul')) {
            this.$element = this.$element.find('ul');
        }
    };

    MobileMenu.prototype.click = function(e) {
        e.preventDefault();
        fansub.scrollToTop();
    };

    $.fn.fansubMobileMenu = function(options) {
        return this.each(function() {
            if(!$.data(this, MobileMenu.NAME)) {
                $.data(this, MobileMenu.NAME, new MobileMenu(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    function ChosenSelect(element, options) {
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, ChosenSelect.DEFAULTS, options);
        this._defaults = ChosenSelect.DEFAULTS;
        this._name = ChosenSelect.NAME;
        this.multiple = this.$element.attr('multiple');
        this.init();
        var $element = this.$element,
            loaded = parseInt(this.$element.attr('data-loaded')),
            chosen_params = {
                width: this.options.width || '100%'
        };
        if(1 == loaded) {
            this.$element.parent().find('.chosen-container').remove();
        }
        if('multiple' == this.multiple) {
            this.$element.chosen(chosen_params).on('change', function() {
                fansub.chosenSelectUpdated($element);
            });
        } else {
            this.$element.chosen(chosen_params);
        }
        this.$element.parent().find('.chosen-container').show();
    }

    ChosenSelect.NAME = 'fansub.chosenSelect';

    ChosenSelect.DEFAULTS = {
        displayWidth: 980,
        position: 'left'
    };

    ChosenSelect.prototype.init = function() {
        var $element_parent = this.$element.parent(),
            $next_element = $element_parent.next();
        if($next_element.hasClass('chosen-container')) {
            $next_element.remove();
        }
        this.$element.addClass('fansub-chosen-select');
        this.$element.attr('data-loaded', 1);
    };

    $.fn.fansubChosenSelect = function(options) {
        return this.each(function() {
            if(!$.data(this, ChosenSelect.NAME)) {
                $.data(this, ChosenSelect.NAME, new ChosenSelect(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    function PostRating(element, options) {
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, PostRating.DEFAULTS, options);
        this._defaults = PostRating.DEFAULTS;
        this._name = PostRating.NAME;
        this.multiple = this.$element.attr('multiple');
        this.init();
        var $element = this.$element;
        $element.raty(this.options);
    }

    PostRating.NAME = 'fansub.postRating';

    PostRating.DEFAULTS = {
        score: function() {
            return $(this).attr('data-score');
        },
        path: function() {
            return this.getAttribute('data-path');
        },
        number: parseInt($(this).attr('data-number')),
        numberMax: parseInt($(this).attr('data-number-max')),
        readOnly: function() {
            var readonly = parseInt($(this).attr('data-readonly'));
            return readonly == 1;
        },
        click: function(score, e) {
            var $element = $(this),
                post_id = parseInt(this.getAttribute('data-id'));
            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: fansub.ajax_url,
                data: {
                    action: 'fansub_rate_post',
                    post_id: post_id,
                    score: score,
                    number: $element.attr('data-number'),
                    number_max: $element.attr('data-number-max')
                },
                success: function(response){
                    if(response.success) {
                        var refresh = parseInt($element.attr('data-refresh'));
                        if(1 == refresh) {
                            $element.attr('data-score', response.score);
                        } else {
                            $element.attr('data-score', score);
                        }
                        $element.attr('data-readonly', 1);
                        $element.raty(options);
                    }
                }
            });
        }
    };

    PostRating.prototype.init = function() {

    };

    $.fn.fansubPostRating = function(options) {
        return this.each(function() {
            if(!$.data(this, PostRating.NAME)) {
                $.data(this, PostRating.NAME, new PostRating(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    var $body = $('body');
    function GoogleMaps(element, options) {
        this.self = this;
        this.$element = $(element);
        if(!this.$element.length || !$body.hasClass('fansub-google-maps')) {
            return this;
        }
        this.element = element;
        this.options = $.extend({}, GoogleMaps.DEFAULTS, options);
        this._defaults = GoogleMaps.DEFAULTS;
        this._name = GoogleMaps.NAME;
        this.init();
        var $element = this.$element,
            $google_maps = $body.find('#google_maps'),
            $geo_address = $body.find('.fansub-geo-address'),
            $province = $body.find('select[name=province]'),
            $category_list = $('.classifieds.fansub-google-maps #categorychecklist'),
            lat_long = new google.maps.LatLng($element.attr('data-lat'), $element.attr('data-long')),
            map_options = {
                zoom: parseInt($element.attr('data-zoom')),
                center: lat_long,
                scrollwheel: $element.attr('data-scrollwheel')
            },
            map = new google.maps.Map(document.getElementById($element.attr('id')), map_options),
            marker = new google.maps.Marker({
                position: lat_long,
                map: map,
                draggable: true,
                title: $element.attr('data-marker-title')
            }),
            point = marker.getPosition();
        google.maps.event.addListener(marker, 'dragend', function(event) {
            point = marker.getPosition();
            map.panTo(point);
            if($google_maps.length) {
                $google_maps.val(JSON.stringify(point));
            }
            $element.attr('data-lat', point.lat);
            $element.attr('data-long', point.lng);
        });
        var geocoder = new google.maps.Geocoder();
        if($geo_address.length) {
            $geo_address.on('change', function(e) {
                e.preventDefault();
                if($.trim($geo_address.val())) {
                    if(geocoder == null) {
                        geocoder = new google.maps.Geocoder();
                    }
                    geocoder.geocode({address: $geo_address.val()}, function(results, status) {
                        if(status == google.maps.GeocoderStatus.OK) {
                            var bounds = results[0].geometry.bounds;
                            if(bounds) {
                                map.fitBounds(bounds);
                                map.setZoom(14);
                                lat_long = new google.maps.LatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng());
                                marker.setPosition(lat_long);
                                point = marker.getPosition();
                                if($google_maps.length) {
                                    $google_maps.val(JSON.stringify(point));
                                }
                                map.setCenter(point);
                                google.maps.event.addListener(marker, 'dragend', function(event) {
                                    point = marker.getPosition();
                                    map.panTo(point);
                                    if($google_maps.length) {
                                        $google_maps.val(JSON.stringify(point));
                                    }
                                    $element.attr('data-lat', point.lat);
                                    $element.attr('data-long', point.lng);
                                });
                            }
                        }
                    });
                }
            });
        }
        if($category_list.length) {
            $category_list.find('input[type="checkbox"]').on('change', function(e) {
                e.preventDefault();
                var $input_category = $(this);
                if($input_category.is(':checked')) {
                    if(geocoder == null) {
                        geocoder = new google.maps.Geocoder();
                    }
                    if(!$.trim($geo_address.val())) {
                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            url: fansub.ajax_url,
                            data: {
                                action: 'fansub_get_term_administrative_boundaries_address',
                                term_id: $input_category.val(),
                                taxonomy: 'category'
                            },
                            success: function(response){
                                if($.trim(response.address)) {
                                    geocoder.geocode({address: response.address}, function(results, status) {
                                        if(status == google.maps.GeocoderStatus.OK) {
                                            var bounds = results[0].geometry.bounds;
                                            if(bounds) {
                                                map.fitBounds(bounds);
                                                map.setZoom(14);
                                                lat_long = new google.maps.LatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng());
                                                marker.setPosition(lat_long);
                                                point = marker.getPosition();
                                                if($google_maps.length) {
                                                    $google_maps.val(JSON.stringify(point));
                                                }
                                                map.setCenter(point);
                                                google.maps.event.addListener(marker, 'dragend', function(event) {
                                                    point = marker.getPosition();
                                                    map.panTo(point);
                                                    if($google_maps.length) {
                                                        $google_maps.val(JSON.stringify(point));
                                                    }
                                                    $element.attr('data-lat', point.lat);
                                                    $element.attr('data-long', point.lng);
                                                });
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            });
        }
        if($province.length) {
            var $district = $body.find('select[name=district]'),
                $ward = $body.find('select[name=ward]'),
                $street = $body.find('select[name=street]');
            $province.add($district).add($ward).add($street).on('change', function(e) {
                e.preventDefault();
                var term_id = $(this).val();
                if($.isNumeric(term_id) && term_id > 0) {
                    if(geocoder == null) {
                        geocoder = new google.maps.Geocoder();
                    }
                    if(!$.trim($geo_address.val())) {
                        $.ajax({
                            type: 'POST',
                            dataType: 'json',
                            url: fansub.ajax_url,
                            data: {
                                action: 'fansub_get_term_administrative_boundaries_address',
                                term_id: term_id,
                                taxonomy: 'category'
                            },
                            success: function(response){
                                if($.trim(response.address)) {
                                    geocoder.geocode({address: response.address}, function(results, status) {
                                        if(status == google.maps.GeocoderStatus.OK) {
                                            var bounds = results[0].geometry.bounds;
                                            if(bounds) {
                                                map.fitBounds(bounds);
                                                map.setZoom(14);
                                                lat_long = new google.maps.LatLng(results[0].geometry.location.lat(), results[0].geometry.location.lng());
                                                marker.setPosition(lat_long);
                                                point = marker.getPosition();
                                                if($google_maps.length) {
                                                    $google_maps.val(JSON.stringify(point));
                                                }
                                                map.setCenter(point);
                                                google.maps.event.addListener(marker, 'dragend', function(event) {
                                                    point = marker.getPosition();
                                                    map.panTo(point);
                                                    if($google_maps.length) {
                                                        $google_maps.val(JSON.stringify(point));
                                                    }
                                                    $element.attr('data-lat', point.lat);
                                                    $element.attr('data-long', point.lng);
                                                });
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            });
        }
    }

    GoogleMaps.NAME = 'fansub.googleMaps';

    GoogleMaps.DEFAULTS = {

    };

    GoogleMaps.prototype.init = function() {

    };

    $.fn.fansubGoogleMaps = function(options) {
        return this.each(function() {
            if(!$.data(this, GoogleMaps.NAME)) {
                $.data(this, GoogleMaps.NAME, new GoogleMaps(this, options));
            }
        });
    };
});

jQuery(document).ready(function($) {
    $.fn.fansubShow = function(show, fade) {
        var that = $(this);
        fade = fade || false;
        if(show) {
            if(fade) {
                that.addClass('active').fadeIn();
            } else {
                that.addClass('active').show();
            }
        } else {
            if(fade) {
                that.removeClass('active').fadeOut();
            } else {
                that.removeClass('active').hide();
            }
        }
    };

    $.fn.fansubExternalLinkFilter = function() {
        var that = $(this);
        that.filter(function() {
            return this.hostname && this.hostname !== location.hostname;
        }).addClass('external');
    };
});

jQuery(document).ready(function($) {
    (function() {
        $('.btn-insert-media').fansubMediaUpload();
    })();

    (function() {
        $('.fansub-geo-address').on('input', function() {
            $(this).addClass('user-type-address');
            $(this).attr('data-user-type', 1);
        });
    })();
});