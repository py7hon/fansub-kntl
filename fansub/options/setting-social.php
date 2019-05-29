<?php
if(!function_exists('add_filter')) exit;
$parent_slug = 'options-general.php';

$option_social = new FANSUB_Option(__('Socials', 'fansub'), 'fansub_option_social');
$option_social->set_parent_slug($parent_slug);
$option_social->add_section(array('id' => 'account', 'title' => __('Account', 'fansub'), 'description' => __('Your social accounts to config API on website.', 'fansub')));
$option_social->add_section(array('id' => 'facebook', 'title' => __('Facebook', 'fansub'), 'description' => __('All information about Facebook account and Facebook Insights Admins.', 'fansub')));
$option_social->add_section(array('id' => 'google', 'title' => __('Google', 'fansub'), 'description' => __('All information about Google account and Google console.', 'fansub')));
$option_social->add_field(array('id' => 'facebook_site', 'title' => __('Facebook page URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('facebook_site')));
$twitter_account = fansub_get_wpseo_social_value('twitter_site');
if(!empty($twitter_account) && !fansub_url_valid($twitter_account)) {
	$twitter_account = 'http://twitter.com/' . $twitter_account;
}
$option_social->add_field(array('id' => 'twitter_site', 'title' => __('Twitter URL', 'fansub'), 'value' => $twitter_account));
$option_social->add_field(array('id' => 'instagram_url', 'title' => __('Instagram URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('instagram_url')));
$option_social->add_field(array('id' => 'linkedin_url', 'title' => __('LinkedIn URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('linkedin_url')));
$option_social->add_field(array('id' => 'myspace_url', 'title' => __('Myspace URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('myspace_url')));
$option_social->add_field(array('id' => 'pinterest_url', 'title' => __('Pinterest URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('pinterest_url')));
$option_social->add_field(array('id' => 'youtube_url', 'title' => __('YouTube URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('youtube_url')));
$option_social->add_field(array('id' => 'google_plus_url', 'title' => __('Google+ URL', 'fansub'), 'value' => fansub_get_wpseo_social_value('google_plus_url')));
$option_social->add_field(array('id' => 'rss_url', 'title' => __('RSS URL', 'fansub')));
$option_social->add_field(array('id' => 'addthis_id', 'title' => __('AddThis ID', 'fansub'), 'section' => 'account'));
$option_social->add_field(array('id' => 'fbadminapp', 'title' => __('Facebook App ID', 'fansub'), 'section' => 'facebook', 'value' => fansub_get_wpseo_social_value('fbadminapp')));
$option_social->add_field(array('id' => 'google_api_key', 'title' => __('Google API Key', 'fansub'), 'section' => 'google'));
$option_social->init();
fansub_option_add_object_to_list($option_social);

function fansub_option_social_update($input) {
	$key = 'facebook_site';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'twitter_site';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'instagram_url';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'linkedin_url';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'myspace_url';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'pinterest_url';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'youtube_url';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'google_plus_url';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
	$key = 'fbadminapp';
	if(isset($input[$key])) {
		fansub_update_wpseo_social($key, $input[$key]);
	}
}
add_action('fansub_sanitize_' . $option_social->get_option_name_no_prefix() . '_option', 'fansub_option_social_update');

function fansub_addthis_script($args = array()) {
	$id = isset($args['id']) ? $args['id'] : '';
	if(empty($id)) {
		$id = fansub_option_get_value('option_social', 'addthis_id');
	}
	if(empty($id)) {
		$use_default_addthis_id = apply_filters('fansub_use_default_addthis_id', false);
		if($use_default_addthis_id) {
			$id = 'ra-4e8109ea4780ac8d';
		}
	}
	$id = apply_filters('fansub_addthis_id', $id);
	if(empty($id)) {
		return;
	}
	?>
	<!-- Go to www.addthis.com/dashboard to customize your tools -->
	<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=<?php echo $id; ?>" async="async"></script>
	<?php
}

function fansub_addthis_toolbox($args = array()) {
	$post_id = isset($args['post_id']) ? $args['post_id'] : get_the_ID();
	$class = isset($args['class']) ? $args['class'] : 'addthis_native_toolbox';
	$class = apply_filters('fansub_addthis_toolbox_class', $class);
	$url = isset($args['url']) ? $args['url'] : get_the_permalink();
	$title = isset($args['title']) ? $args['title'] : get_the_title();
	?>
	<!-- Go to www.addthis.com/dashboard to customize your tools -->
	<div class="<?php echo $class; ?>" data-url="<?php echo $url; ?>" data-title="<?php echo fansub_wpseo_get_post_title($post_id); ?>"></div>
	<?php
}