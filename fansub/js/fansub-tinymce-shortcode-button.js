
window.fansub = window.fansub || {};

jQuery(document).ready(function($) {
    tinymce.create('tinymce.plugins.fansub_shortcode_plugin', {
        init: function(ed, url) {
            ed.addCommand('fansub_insert_shortcode', function() {
                var selected = tinyMCE.activeEditor.selection.getContent(),
                    content = '';
            });
            var shortcode_values = [],
                shortcodes_button = fansub.shortcodes;
            $.each(shortcodes_button, function(key, i) {
                shortcode_values.push({text: key, value: key});
            });
            ed.addButton('fansub_shortcode', {
                type: 'listbox',
                text: 'Shortcodes',
                title: 'Insert shortcode',
                cmd: 'fansub_insert_shortcode',
                onselect: function(e) {
                    var selected = tinyMCE.activeEditor.selection.getContent(),
                        shortcode = e.control.settings.value;
                    tinyMCE.activeEditor.selection.setContent('[' + shortcode + ']' + selected + '[/' + shortcode + ']');
                },
                values: shortcode_values
            });
        }
    });
    tinymce.PluginManager.add('fansub_shortcode', tinymce.plugins.fansub_shortcode_plugin);
});