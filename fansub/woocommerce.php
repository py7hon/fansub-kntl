<?php
if(!function_exists('add_filter')) exit;
$lang = fansub_get_language();

function fansub_get_wc_version() {
    if(defined('WOOCOMMERCE_VERSION')) {
        return WOOCOMMERCE_VERSION;
    }
    return '';
}

function fansub_wc_installed() {
    return defined('WOOCOMMERCE_VERSION');
}

function fansub_wc_get_product_price($post_id = null) {
    if(!fansub_id_number_valid($post_id)) {
        $post_id = get_the_ID();
    }
    global $product;
    $h_product = $product;
    if(!is_a($h_product, 'WC_Product')) {
        $h_product = new WC_Product($post_id);
    }
    return $h_product->get_price();
}

function fansub_wc_product_price($post_id = null) {
    $price = fansub_wc_get_product_price($post_id);
    echo fansub_wc_format_price($price);
}

function fansub_wc_format_price($price) {
    return wc_price($price);
}

function fansub_wc_get_product_total_sales($post_id = null) {
    if(!fansub_id_number_valid($post_id)) {
        $post_id = get_the_ID();
    }
    return absint(fansub_get_post_meta('total_sales', $post_id));
}

function fansub_wc_get_shop_page() {
    $id = get_option('woocommerce_shop_page_id');
    return get_post($id);
}

function fansub_wc_get_cart_url() {
    global $woocommerce;
    return $woocommerce->cart->get_cart_url();
}

function fansub_wc_get_checkout_url() {
    return wc_get_checkout_url();
}

function fansub_wc_count_cart() {
    global $woocommerce;
    return $woocommerce->cart->cart_contents_count;
}

function fansub_wc_get_cart_total_formatted() {
    global $woocommerce;
    return $woocommerce->cart->get_cart_total();
}

function fansub_wc_get_cart_total() {
    global $woocommerce;
    return $woocommerce->cart->total;
}

function fansub_wc_is_variable($product) {
    return $product->is_type('variable');
}

function fansub_wc_get_cart_items() {
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    return $items;
}

function fansub_wc_get_add_to_cart($args = array()) {
    $post_id = isset($args['post_id']) ? absint($args['post_id']) : get_the_ID();
    $product = wc_get_product($post_id);
    if(!$product->is_type('simple')) {
        return '';
    }
    $sku = isset($args['sku']) ? $args['sku'] : $product->get_sku();
    $style = isset($args['style']) ? $args['style'] : '';
    $price = $product->get_price();
    $container_class = isset($args['container_class']) ? $args['container_class'] : '';
    fansub_add_string_with_space_before($container_class, 'custom-add-to-cart fansub-add-to-cart');
    if(0 == $price) {
        fansub_add_string_with_space_before($container_class, 'please-call');
    }
    $field_class = isset($args['field_class']) ? $args['field_class'] : '';
    $quantity = isset($args['quantity']) ? absint($args['quantity']) : 1;
    $show_price = isset($args['show_price']) ? (bool)$args['show_price'] : true;
    $show_price = ($show_price) ? 'true' : 'false';
    $shortcode = do_shortcode('[add_to_cart id="' . $post_id . '" sku="' . $sku . '" style="' . $style . '" class="' . $field_class . '" show_price="' . $show_price . '" quantity="' . $quantity . '"]');
    return '<div class="'. $container_class .'">' . $shortcode . '</div>';
}

function fansub_wc_add_to_cart($args = array()) {
    echo fansub_wc_get_add_to_cart($args);
}

function fansub_wc_insert_order($data) {
    $post_id = fansub_get_value_by_key($data, 'post_id');
    if(fansub_id_number_valid($post_id)) {
        $post = get_post($post_id);
        if(is_a($post, 'WP_Post') && 'product' == $post->post_type) {
            $product = wc_get_product($post_id);
            $variable_product = new WC_Product_Variable($product);
            $variations = $variable_product->get_available_variations();
            $variation_args = array();
            $variation_id = null;
            foreach($variations as $variation) {
                $variation_id = $variation['variation_id'];
                $variation_args['variation'] = $variation['attributes'];
            }
            $name = fansub_get_value_by_key($data, 'name');
            $phone = fansub_get_value_by_key($data, 'phone');
            $email = fansub_get_value_by_key($data, 'email');
            $address = fansub_get_value_by_key($data, 'address');
            $message = fansub_get_value_by_key($data, 'message');
            $name = fansub_sanitize_first_and_last_name($name);
            $attributes = fansub_get_value_by_key($data, 'attributes');
            $addresses = array(
                'first_name' => $name['first_name'],
                'last_name' => $name['last_name'],
                'email' => $email,
                'phone' => $phone,
                'address_1' => $address
            );
            $args = array(
                'customer_note' => $message,
                'created_via' => 'programmatically'
            );
            if(is_user_logged_in()) {
                $current = wp_get_current_user();
                $args['customer_id'] = $current->ID;
            }
            $order = wc_create_order($args);
            $gateway = WC_Payment_Gateways::instance();
            $gateways = $gateway->get_available_payment_gateways();
            if(fansub_array_has_value($gateways)) {
                $gateway = current($gateways);
                $order->set_payment_method($gateway);
            }
            $order->set_address($addresses);
            $order->set_address($addresses, 'shipping');

            if(fansub_array_has_value($attributes) && fansub_id_number_valid($variation_id)) {
                foreach($attributes as $attribute) {
                    $attribute_name = fansub_get_value_by_key($attribute, 'name');
                    $attribute_value = fansub_get_value_by_key($attribute, 'value');
                    if(!empty($attribute_name) && !empty($attribute_value)) {
                        if(isset($variation_args['variation'][$attribute_name])) {
                            $variation_args['variation'][$attribute_name] = $attribute_value;
                        }
                    }
                }
                $variation_product = new WC_Product_Variation($variation_id);
                $order->add_product($variation_product, 1, $variation_args);
            } else {
                $order->add_product($product);
            }
            $order->record_product_sales();
            $order->calculate_totals();
            $order->payment_complete();
            return $order;
        }
    }
    return false;
}

function fansub_wc_get_cart_preview_html() {
    $cart_preview = '';
    $cart_items = fansub_wc_get_cart_items();
    $cart_preview .= '<ul class="cart-preview list-unstyled">';
    $cart_preview .= '<li class="title">Giỏ hàng của bạn</li>';
    if(fansub_array_has_value($cart_items)) {
        $cart_preview .= '<li class="cart-items"><ul class="list-unstyled list-products">';
        foreach($cart_items as $item) {
            $post_id = fansub_get_value_by_key($item, 'product_id');
            if(!fansub_id_number_valid($post_id)) {
                continue;
            }
            $quantity = absint(fansub_get_value_by_key($item, 'quantity'));
            $data = fansub_get_value_by_key($item, 'data');
            $data = fansub_object_to_array($data);
            $price = floatval(fansub_get_value_by_key($data, 'price'));
            $li = new FANSUB_HTML('li');
            $li->set_class(fansub_get_post_class($post_id, 'clearfix'));
            ob_start();
            fansub_post_thumbnail(array('width' => 44, 'height' => 44, 'post_id' => $post_id));
            fansub_post_title_link(array('title' => get_the_title($post_id), 'permalink' => get_permalink($post_id)));
            echo '<p class="info">';
            echo '<span class="price">Đơn giá: ' . fansub_wc_format_price($price) . '</span>';
            echo '<span class="quantity">Số lượng: ' . number_format($quantity) . '</span>';
            echo '</p>';
            echo '<i class="fa fa-remove" data-id="' . $post_id . '"></i>';
            $li_html = ob_get_clean();
            $li->set_text($li_html);
            $cart_preview .= $li->build();
        }
        $cart_preview .= '</ul></li>';
        $cart_preview .= '<li class="bottom">';
        $cart_preview .= '<span class="total">Tổng cộng: <strong>' . fansub_wc_get_cart_total_formatted() . '</strong></span>';
        $cart_preview .= '<a class="btn-clickable orange go-page" href="' . fansub_wc_get_checkout_url() . '">Thanh toán</a>';
        $cart_preview .= '</li>';
    } else {
        $cart_preview .= '<li class="no-item-message">Hiện chưa có sản phẩm nào trong giỏ hàng của bạn.</li>';
    }
    $cart_preview .= '</ul>';
    $cart_preview = apply_filters('fansub_wc_cart_preview_html', $cart_preview);
    return $cart_preview;
}

function fansub_wc_get_cart($args = array()) {
    $lang = fansub_get_language();
    $title = isset($args['title']) ? $args['title'] : (('vi' == $lang) ? 'Thông tin giỏ hàng' : __('View your shopping cart', 'fansub'));
    $show_item = isset($args['show_item']) ? (bool)$args['show_item'] : true;
    $show_price = isset($args['show_price']) ? (bool)$args['show_price'] : true;
    $show_icon = isset($args['show_icon']) ? (bool)$args['show_icon'] : true;
    $show_preview = isset($args['show_preview']) ? (bool)$args['show_preview'] : true;
    $cart = '<div class="fansub-cart-contents">';
    $cart .= '<a class="cart-content" href="' . fansub_wc_get_cart_url() . '" title="' . $title . '">';
    if($show_icon) {
        $cart .= '<i class="fa fa-shopping-cart icon-left"></i>';
    }
    if($show_item) {
        $count_cart = fansub_wc_count_cart();
        $item_text = $count_cart . ' sản phẩm';
        if('vi' != $lang) {
            $item_text = sprintf(_n('%d item', '%d items', $count_cart, 'fansub'), $count_cart);
        }
        $cart .= '<span class="product-number">' . $item_text . '</span>';
        if($show_price) {
            $cart .= '<span class="sep"> - </span>';
        }
    }
    if($show_price) {
        $cart .= fansub_wc_get_cart_total_formatted();
    }
    if($show_preview) {
        $cart .= '<i class="fa fa-angle-down icon-right"></i>';
    }
    $cart .= '</a>';
    if($show_preview) {
        $cart .= fansub_wc_get_cart_preview_html();
    }
    $cart .= '</div>';
    return apply_filters('fansub_wc_cart', $cart, $args);
}

function fansub_wc_cart($args = array()) {
    $before = fansub_get_value_by_key($args, 'before');
    echo $before;
    do_action('fansub_wc_cart_before');
    echo fansub_wc_get_cart($args);
    do_action('fansub_wc_cart_after');
    if(!empty($before)) {
        $after = fansub_get_value_by_key($args, 'after');
        echo $after;
    }
}

function fansub_wc_the_cart($args = array()) {
    echo '<div id="fansubCart" class="fansub-cart wc-cart">';
    fansub_wc_cart($args);
    echo '</div>';
}

function fansub_wc_get_content_single_product() {
    wc_get_template_part('content', 'single-product');
}

function fansub_wc_use_fast_buy_button() {
    $use = apply_filters('fansub_wc_use_fast_buy_button', true);
    return $use;
}

$fansub_shop_site = apply_filters('fansub_shop_site', false);

if(!(bool)$fansub_shop_site) {
    return;
}

function fansub_wc_after_single_product_title_hook() {
    do_action('fansub_wc_after_single_product_title');
}
add_action('woocommerce_single_product_summary', 'fansub_wc_after_single_product_title_hook', 6);

function fansub_wc_after_single_product_add_to_cart_button() {
    do_action('fansub_wc_after_single_product_add_to_cart_button');
}
add_action('woocommerce_single_product_summary', 'fansub_wc_after_single_product_add_to_cart_button', 31);

function fansub_wc_single_product_fast_buy_button() {
    $use = fansub_wc_use_fast_buy_button();
    if($use) {
        global $product;
        $button_text = apply_filters('fansub_wc_fast_buy_button_text', __('Mua hàng nhanh', 'fansub'));
        $button_description = apply_filters('fansub_wc_fast_buy_button_description', __('Đặt hàng nhanh, không cần thêm sản phẩm vào giỏ hàng.', 'fansub'));
        ?>
        <button data-target="#productBuy<?php the_ID(); ?>" data-toggle="modal" class="btn-clickable orange fast-buy" type="button">
            <?php
            echo $button_text;
            if(!empty($button_description)) {
                $button_description = fansub_wrap_tag($button_description, 'span');
                echo $button_description;
            }
            ?>
        </button>
        <div id="productBuy<?php the_ID(); ?>" role="dialog" tabindex="-1"  class="modal fade product-fast-buy">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button aria-label="Close" data-dismiss="modal" class="close" type="button"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title">Đặt hàng nhanh</h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-xs-12 col-md-6 info-column">
                                <div class="product-info">
                                    <?php
                                    fansub_post_thumbnail(array('bfi_thumb' => false, 'loop' => false));
                                    fansub_post_title_single(array('tag' => 'h2'));
                                    $get_variations = sizeof( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
                                    $attributes = array();
                                    if(fansub_wc_is_variable($product)) {
                                        $attributes = $product->get_variation_attributes();
                                    }
                                    $attribute_keys = array_keys( $attributes );
                                    //$selected_attributes = $product->get_variation_default_attributes();
                                    $available_variations = false;
                                    if(fansub_wc_is_variable($product)) {
                                        $available_variations = $get_variations ? $product->get_available_variations() : false;
                                    }
                                    if ( empty( $available_variations ) && false !== $available_variations ) : ?>
                                        <p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce' ); ?></p>
                                    <?php else : ?>
                                        <form class="variations_form cart attributes-form" method="post">
                                            <table class="variations" cellspacing="0">
                                                <tbody>
                                                <?php foreach ( $attributes as $attribute_name => $options ) : ?>
                                                    <tr>
                                                        <td class="label"><label for="<?php echo sanitize_title( $attribute_name ); ?>"><?php echo wc_attribute_label( $attribute_name ); ?></label></td>
                                                        <td class="value">
                                                            <?php
                                                            $selected = isset( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) ? wc_clean( $_REQUEST[ 'attribute_' . sanitize_title( $attribute_name ) ] ) : $product->get_variation_default_attribute( $attribute_name );
                                                            wc_dropdown_variation_attribute_options( array( 'options' => $options, 'attribute' => $attribute_name, 'product' => $product, 'selected' => $selected ) );
                                                            echo end( $attribute_keys ) === $attribute_name ? apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . __( 'Clear', 'woocommerce' ) . '</a>' ) : '';
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;?>
                                                </tbody>
                                            </table>
                                        </form>
                                    <?php endif;
                                    fansub_wc_product_price();
                                    ?>
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-6 customer-column">
                                <div class="customer-info">
                                    <?php
                                    $name = '';
                                    $email = '';
                                    $phone = '';
                                    $address = '';
                                    if(is_user_logged_in()) {
                                        $current = wp_get_current_user();
                                        $name = get_user_meta($current->ID, 'billing_first_name', true);
                                        $last_name = get_user_meta($current->ID, 'billing_last_name', true);
                                        if(!empty($last_name)) {
                                            $name = $last_name . ' ' . $name;
                                        }
                                        $name = trim($name);
                                        if(empty($name)) {
                                            $name = $current->display_name;
                                        }
                                        $email = get_user_meta($current->ID, 'billing_email', true);
                                        if(!is_email($email)) {
                                            $email = $current->user_email;
                                        }
                                        $phone = get_user_meta($current->ID, 'billing_phone', true);
                                        $address = get_user_meta($current->ID, 'billing_address_1', true);
                                    }
                                    ?>
                                    <form class="order-form" method="post">
                                        <div class="form-group">
                                            <p>Thông tin bắt buộc phải nhập <?php echo FANSUB_REQUIRED_HTML; ?> vào</p>
                                        </div>
                                        <div class="form-group">
                                            <input type="text" required aria-required="true" value="<?php echo $name; ?>" class="full-name form-control" placeholder="Họ và tên *" name="fullname">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="phone form-control" value="<?php echo $phone; ?>" placeholder="Điện thoại" name="phone">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" required aria-required="true" value="<?php echo $email; ?>" class="email form-control" placeholder="Email *" name="email">
                                        </div>
                                        <div class="form-group">
                                            <input type="text" class="address form-control" value="<?php echo $address; ?>" placeholder="Địa chỉ" name="address">
                                        </div>
                                        <div class="form-group">
                                            <label for="message">Ghi chú:</label>
                                            <textarea id="message" name="message" class="message form-control"></textarea>
                                        </div>
                                        <div class="form-group">
                                            <button class="btn-clickable orange" data-id="<?php the_ID(); ?>">Đặt hàng</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
add_action('fansub_wc_after_single_product_add_to_cart_button', 'fansub_wc_single_product_fast_buy_button');

function fansub_wc_after_single_product_summary() {
    do_action('fansub_wc_after_single_product_summary');
}
add_action('woocommerce_after_single_product_summary', 'fansub_wc_after_single_product_summary', 0);

function fansub_wc_add_vietnam_dong_currency($currencies) {
    $currencies['VNDU'] = __('Việt Nam Đồng', 'fansub');
    return $currencies;
}
if('vi' == $lang) add_filter('woocommerce_currencies', 'fansub_wc_add_vietnam_dong_currency');

function fansub_wc_vietnam_dong_currency_symbol($currency_symbol, $currency) {
    switch($currency) {
        case 'VNDU':
            $currency_symbol = 'Đ';
            break;
    }
    return $currency_symbol;
}
if('vi' == $lang) add_filter('woocommerce_currency_symbol', 'fansub_wc_vietnam_dong_currency_symbol', 10, 2);

function fansub_wc_single_add_to_cart_button_text() {
    $text = 'Thêm sản phẩm vào giỏ hàng';
    $text = apply_filters('fansub_wc_single_add_to_cart_button_text', $text);
    return $text;
}
if('vi' == $lang) add_filter('woocommerce_product_single_add_to_cart_text', 'fansub_wc_single_add_to_cart_button_text', 99);

function fansub_wc_product_add_to_cart_text() {
    $text = 'Thêm vào giỏ';
    $text = apply_filters('fansub_wc_add_to_cart_button_text', $text);
    return $text;
}
if('vi' == $lang) add_filter('woocommerce_product_add_to_cart_text', 'fansub_wc_product_add_to_cart_text');

add_filter('fansub_track_user_viewed_posts', '__return_true');

function fansub_wc_add_to_cart_fragments($fragments) {
    $args = apply_filters('fansub_wc_cart_content_ajax_args', array(), $fragments);
    ob_start();
    fansub_wc_cart($args);
    $cart_contents = ob_get_clean();
    $cart_contents = apply_filters('fansub_wc_cart_content_ajax', $cart_contents, $fragments);
    $fragments['div.fansub-cart-contents'] = $cart_contents;
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'fansub_wc_add_to_cart_fragments');

function fansub_wc_remove_cart_item_ajax_callback() {
    $result = array(
        'updated' => false
    );
    $post_id = fansub_get_method_value('post_id');
    if(fansub_id_number_valid($post_id)) {
        $WC = WC();
        $updated = false;
        foreach($WC->cart->get_cart() as $cart_item_key => $cart_item) {
            $prod_id = $cart_item['product_id'];
            if($post_id == $prod_id) {
                $WC->cart->set_quantity($cart_item_key, 0, true);
                $updated = true;
                break;
            }
        }
        if($updated) {
            $cart_contents = fansub_wc_add_to_cart_fragments(array());
            $cart_contents = fansub_get_value_by_key($cart_contents, 'div.fansub-cart-contents');
            if(empty($cart_contents)) {
                $updated = false;
            } else {
                $result['cart_contents'] = $cart_contents;
            }
        }
        $result['updated'] = $updated;
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_wc_remove_cart_item', 'fansub_wc_remove_cart_item_ajax_callback');
add_action('wp_ajax_nopriv_fansub_wc_remove_cart_item', 'fansub_wc_remove_cart_item_ajax_callback');

function fansub_wc_order_item_ajax_callback() {
    $result = array(
        'success' => false,
        'html_data' => '<p class="alert alert-danger">Đã có lỗi xảy ra, xin vui lòng thử lại sau.</p>'
    );
    $post_id = fansub_get_method_value('post_id');
    if(fansub_id_number_valid($post_id)) {
        $post = get_post($post_id);
        if(is_a($post, 'WP_Post') && 'product' == $post->post_type) {
            $name = fansub_get_method_value('name');
            $phone = fansub_get_method_value('phone');
            $email = fansub_get_method_value('email');
            $address = fansub_get_method_value('address');
            $message = fansub_get_method_value('message');
            $attributes = fansub_get_method_value('attributes');
            $order = fansub_wc_insert_order(array(
                'post_id' => $post_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'message' => $message,
                'attributes' => $attributes
            ));
            if(false !== $order) {
                $result['success'] = true;
                $result['html_data'] = '<p class="alert alert-success">Đơn hàng của bạn đã được lưu thành công, chúng tôi sẽ liên hệ lại với bạn trong thời gian sớm nhất.</p>';
            }
        }
    }
    wp_send_json($result);
}
add_action('wp_ajax_fansub_wc_order_item', 'fansub_wc_order_item_ajax_callback');
add_action('wp_ajax_nopriv_fansub_wc_order_item', 'fansub_wc_order_item_ajax_callback');

function fansub_wc_after_cart_table() {
    $page = fansub_wc_get_shop_page();
    $permalink = apply_filters('fansub_return_shop_url', get_permalink($page));
    ?>
    <a title="" href="<?php echo $permalink; ?>" class="btn-grey fansub-button return-shop"><i class="fa fa-angle-left icon-left"></i> <?php fansub_text('Tiếp tục mua hàng', __('Continue shopping', 'fansub')); ?></a>
    <?php
    do_action('fansub_wc_after_return_shop_button');
}
add_action('woocommerce_after_cart_table', 'fansub_wc_after_cart_table');

function fansub_wc_after_single_product_related() {
    do_action('fansub_wc_after_single_product_related');
}
add_action('woocommerce_after_single_product_summary', 'fansub_wc_after_single_product_related', 21);

function fansub_wc_checkout_fields($fields) {
    if('vi' == fansub_get_language()) {
        unset($fields['billing']['billing_postcode']);
        unset($fields['billing']['billing_country']);
        unset($fields['billing']['billing_company']);
        unset($fields['billing']['billing_address_2']);
        unset($fields['billing']['billing_city']);
    }
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'fansub_wc_checkout_fields');

function fansub_wc_before_single_variation_quantity() {
    do_action('fansub_wc_before_single_variation_quantity');
}
add_action('woocommerce_single_variation', 'fansub_wc_before_single_variation_quantity', 19);

function fansub_wc_after_single_product_meta() {
    do_action('fansub_wc_after_single_product_meta');
}
add_action('woocommerce_single_product_summary', 'fansub_wc_after_single_product_meta', 41);