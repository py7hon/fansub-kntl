<?php
if(!function_exists('add_filter')) exit;
function fansub_comment_wp_insert_comment($comment_id, $comment_object) {

}
add_action('wp_insert_comment', 'fansub_comment_wp_insert_comment', 10, 2);

function fansub_comment_transition_comment_status($new_status, $old_status, $comment) {
    if($old_status != $new_status) {
        if('approved' === $new_status) {
            do_action('fansub_comment_approved', $comment);
            $notify_me = get_comment_meta($comment->comment_ID, 'notify_me', true);
            if(!empty($notify_me)) {

            }
        }
        fansub_delete_transient('fansub_top_commenters');
        do_action('fansub_comment_status_changed', $comment);
    }
}
add_action('transition_comment_status', 'fansub_comment_transition_comment_status', 10, 3);

function fansub_comment_form_default_fields($fields) {
    $commenter = wp_get_current_commenter();
    $user = wp_get_current_user();
    $user_identity = $user->exists() ? $user->display_name : '';
    $format = current_theme_supports('html5', 'comment-form') ? 'html5' : 'xhtml';
    $format = apply_filters('fansub_comment_form_format', $format);
    $req = get_option('require_name_email');
    $aria_req = ($req ? "aria-required='true'" : '');
    $html_req = ($req ? "required='required'" : '');
    $required_html = '';
    fansub_add_string_with_space_before($required_html, $aria_req);
    fansub_add_string_with_space_before($required_html, $html_req);
    $html5 = 'html5' === $format;
    $fields = array(
        'author' => '<p class="comment-form-author">' . '<label for="author">' . __('Name', 'fansub') . ($req ? ' <span class="required">*</span>' : '') . '</label> ' .
            '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" ' . $required_html . ' /></p>',
        'email' => '<p class="comment-form-email"><label for="email">' . __('Email', 'fansub') . ($req ? ' <span class="required">*</span>' : '') . '</label> ' .
            '<input id="email" name="email" ' . ($html5 ? 'type="email"' : 'type="text"') . ' value="' . esc_attr($commenter['comment_author_email']) . '" size="30" aria-describedby="email-notes" ' . $required_html  . ' /></p>',
        'url' => '<p class="comment-form-url"><label for="url">' . __('Website', 'fansub') . '</label> ' .
            '<input id="url" name="url" ' . ($html5 ? 'type="url"' : 'type="text"') . ' value="' . esc_attr($commenter['comment_author_url']) . '" size="30" /></p>',
    );
    return $fields;
}
add_filter('comment_form_default_fields', 'fansub_comment_form_default_fields');

function fansub_comment_form_defaults($defaults) {
    $commenter = wp_get_current_commenter();
    $user = wp_get_current_user();
    $user_identity = $user->exists() ? $user->display_name : '';
    $format = current_theme_supports('html5', 'comment-form') ? 'html5' : 'xhtml';
    $format = apply_filters('fansub_comment_form_format', $format);
    $req = get_option('require_name_email');
    $aria_req = ($req ? " aria-required='true'" : '');
    $html_req = ($req ? " required='required'" : '');
    $required_text = sprintf(' ' . __('Required fields are marked %s', 'fansub'), '<span class="required">*</span>');
    $html5 = 'html5' === $format;
    $defaults = array(
        'comment_field' => '<p class="comment-form-comment"><label for="comment">' . _x('Comment', 'noun') . '</label> <textarea id="comment" name="comment" cols="45" rows="8"  aria-required="true" required="required"></textarea></p>',
        'must_log_in' => '<p class="must-log-in">' . sprintf(__('You must be <a href="%s">logged in</a> to post a comment.', 'fansub'), wp_login_url(apply_filters('the_permalink', get_permalink(get_the_ID())))) . '</p>',
        'logged_in_as' => '<p class="logged-in-as">' . sprintf(__('Logged in as <a href="%1$s">%2$s</a>. <a href="%3$s" title="Log out of this account">Log out?</a>', 'fansub'), get_edit_user_link(), $user_identity, wp_logout_url(apply_filters('the_permalink', get_permalink(get_the_ID())))) . '</p>',
        'comment_notes_before' => '<p class="comment-notes"><span id="email-notes">' . __('Your email address will not be published.', 'fansub') . '</span>'. ($req ? $required_text : '') . '</p>',
        'comment_notes_after' => '',
        'id_form' => 'commentform',
        'id_submit' => 'submit',
        'class_submit' => 'submit',
        'name_submit' => 'submit',
        'title_reply' => '<span class="title-text">' . __('Leave a Reply', 'fansub') . '</span>',
        'title_reply_to' => __('Leave a Reply to %s', 'fansub'),
        'cancel_reply_link' => __('Click here to cancel reply.', 'fansub'),
        'label_submit' => __('Post Comment', 'fansub'),
        'submit_button' => '<input name="%1$s" type="submit" id="%2$s" class="%3$s" value="%4$s" />',
        'submit_field' => '<p class="form-submit">%1$s %2$s</p>',
        'format' => 'html5'
    );
    return $defaults;
}
add_filter('comment_form_defaults', 'fansub_comment_form_defaults');

function fansub_wp_list_comments_args($args) {
    $args['reply_text'] = '<i class="fa fa-reply"></i><span class="text">' . __('Reply', 'fansub') . '</span>';
    return $args;
}
add_filter('wp_list_comments_args', 'fansub_wp_list_comments_args', 10);

function fansub_get_comment_likes($comment_id) {
    $result = get_comment_meta($comment_id, 'likes', true);
    $result = absint($result);
    return $result;
}

function fansub_facebook_comment($args = array()) {
    $args = apply_filters('fansub_facebook_comment_args', $args);
    $colorscheme = isset($args['colorscheme']) ? $args['colorscheme'] : 'light';
    $colorscheme = apply_filters('fansub_facebook_comment_colorscheme', $colorscheme, $args);
    $href = isset($args['href']) ? $args['href'] : '';
    if(empty($href)) {
        if(is_single() || is_page() || is_singular()) {
            $href = get_the_permalink();
        }
    }
    if(empty($href)) {
        $href = $this->get_current_url();
    }
    $href = apply_filters('fansub_facebook_comment_href', $href, $args);
    $mobile = isset($args['mobile']) ? $args['mobile'] : '';
    $num_posts = isset($args['num_posts']) ? $args['num_posts'] : 10;
    $num_posts = apply_filters('fansub_facebook_comment_num_posts', $num_posts, $args);
    $order_by = isset($args['order_by']) ? $args['order_by'] : 'social';
    $width = isset($args['width']) ? $args['width'] : '100%';
    $width = apply_filters('fansub_facebook_comment_width', $width, $args);
    $loading_text = fansub_get_value_by_key($args, 'loading_text', __('Loading...', 'fansub'));
    $div = new FANSUB_HTML('div');
    $div->set_class('fb-comments');
    $atts = array(
        'data-colorscheme' => $colorscheme,
        'data-href' => $href,
        'data-mobile' => $mobile,
        'data-numposts' => $num_posts,
        'data-order-by' => $order_by,
        'data-width' => $width
    );
    $atts = apply_filters('fansub_facebook_comment_attributes', $atts, $args);
    $div->set_attribute_array($atts);
    $div->set_text($loading_text);
    $div->output();
}

function fansub_google_comment() {
    ?>
    <script src="https://apis.google.com/js/plusone.js"></script>
    <div id="google_comments"><?php _e('Loading...', 'fansub'); ?></div>
    <script>
        gapi.comments.render('google_comments', {
            href: window.location,
            width: '624',
            first_party_property: 'BLOGGER',
            view_type: 'FILTERED_POSTMOD'
        });
    </script>
    <?php
}

function fansub_get_top_commenters($number = 5, $time = 'all', $condition = '') {
    $transient_name = 'fansub_top_commenters_' . $time . '_' . $number;
    $transient_name = fansub_sanitize_id($transient_name);
    if(false === ($results = get_transient($transient_name))) {
        global $wpdb;
        $sql = 'SELECT COUNT(comment_author_email) AS comments_count, comment_author_email, comment_author, comment_author_url, user_id FROM ' . $wpdb->comments . '
                    WHERE comment_author_email != "" AND comment_type = "" AND comment_approved = 1';
        $expires = HOUR_IN_SECONDS;
        switch($time) {
            case 'today':
                $sql .= ' AND DAY(comment_date) = DAY(CURDATE()) AND MONTH(comment_date) = MONTH(CURDATE()) AND YEAR(comment_date) = YEAR(CURDATE())';
                break;
            case 'week':
            case 'this_week':
                $sql .= ' AND YEARWEEK(comment_date) = YEARWEEK(NOW())';
                $expires = 12 * HOUR_IN_SECONDS;
                break;
            case 'month':
            case 'this_month':
                $sql .= ' AND MONTH(comment_date) = MONTH(CURDATE()) AND YEAR(comment_date) = YEAR(CURDATE())';
                $expires = DAY_IN_SECONDS;
                break;
            case 'year':
            case 'this_year':
                $sql .= ' AND YEAR(comment_date) = YEAR(CURDATE())';
                $expires = 2 * DAY_IN_SECONDS;
                break;
        }
        $condition = trim($condition);
        if(!empty($condition)) {
            $sql .= ' ' . $condition;
        }
        $sql .= ' GROUP BY comment_author_email ORDER BY comments_count DESC, comment_author ASC LIMIT ' . $number;
        $results = $wpdb->get_results($sql);
        set_transient($transient_name, $results, $expires);
    }
    return $results;
}

function fansub_comment_inserted_hook($id, $comment) {
    fansub_delete_transient('fansub_top_commenters');
}
add_action('wp_insert_comment', 'fansub_comment_inserted_hook', 10, 2);