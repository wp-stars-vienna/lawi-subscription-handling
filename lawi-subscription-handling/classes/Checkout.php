<?php

namespace wps\lawi;

use \DateTime;
use \DateTimeZone;

class Checkout
{

    public function __construct(){

        add_action( 'woocommerce_checkout_before_customer_details', [$this, 'extra_checkbox']);
        add_action( 'woocommerce_checkout_process', [$this, 'validate_extra_checkbox']);
        add_action( 'woocommerce_checkout_update_order_meta', [$this, 'store_extra_checkbox_value'], 10, 1);
        add_action( 'woocommerce_admin_order_data_after_billing_address', [$this, 'display_extra_checkbox_value'], 10, 1);
        add_action( 'woocommerce_coupons_enabled', [$this, 'woocommerce_checkout_coupon_form'], 9999, 1 );
        add_action( 'woocommerce_thankyou', [$this, 'new_order_action'], 10, 2 );
        add_action('woocommerce_add_to_cart', [$this, 'add_to_cart']);

        add_filter( 'woocommerce_coupon_is_valid_for_cart', [$this, 'exclude_product_from_subscription_products'], 9999, 2);
        add_filter('woocommerce_add_to_cart_redirect', [$this, 'add_to_cart_redirect'], 10, 2);
    }


    public function add_to_cart(){

        if(isset($_GET['add-to-cart']) && !!$_GET['add-to-cart']){
            $addedProductID = (int) $_GET['add-to-cart'];
            $subscriptionProducts = Plugin::get_instance()->permissionService->getSubscriptionsArray()['ePaperSubscriptions'];
            $cartItems = WC()->cart->cart_contents;

            if(isset($subscriptionProducts[$addedProductID])){
                $subs = [];
                foreach ($cartItems as $key => $item){
                    if ($cartItems[$key]['product_id'] != $addedProductID) {
                        WC()->cart->set_quantity($key,'0');
                    }else{
                        // collect the subscription products
                        $subs[] = $key;
                    }
                }

                // if there are more subscription products in the cart, remove all but not the last
                if(count($subs)>1){
                    array_pop($subs);
                    foreach ($subs as $sub){
                        WC()->cart->set_quantity($sub,'0');
                    }
                }
            }else{
                // remove subscription product if you add a normal product and there is a subscription in your cart
                foreach ($cartItems as $key => $item){
                    if( isset($item['data']) && $item['data'] instanceof  \WC_Product_Subscription){
                        WC()->cart->set_quantity($key,'0');
                    }
                }
            }
        }
    }

    /**
     * Important! the second parameter $product is null when you add a product to cart via ajax!
     * @param string $url
     * @param $product
     * @return string
     */
    public function add_to_cart_redirect(string $url, $product=null): string
    {
        if(null !== $product){
            return $product->get_type() == 'subscription' ? wc_get_checkout_url() : $url;
        }

        return $url;
    }

    public function needWafeOfwithdrawal(){
        $cart = WC()->cart->get_cart();
        if(is_array($cart) && count($cart) === 1){
            $cartProduct = $cart[array_key_first($cart)];

            if( isset($cartProduct['data']) && $cartProduct['data'] instanceof  \WC_Product_Subscription){
                if(isset($cartProduct['epaper-startdate'])){

                    $todayInTwoWeeks = new DateTime('today + 14 days', new DateTimeZone('europe/vienna'));
                    $subscriptionStartDate = new DateTime($cartProduct['epaper-startdate'], new DateTimeZone('europe/vienna'));

                    return $subscriptionStartDate < $todayInTwoWeeks;
                }
            }
        }

        return false;
    }

    public function isSubscription(){

        if (is_checkout() === false && is_cart() === false ) return;

        $cart = WC()->cart->get_cart();
        if(is_array($cart) && count($cart) === 1){
            $cartProduct = $cart[array_key_first($cart)];
            if( isset($cartProduct['data']) && $cartProduct['data'] instanceof  \WC_Product_Subscription){
                if(isset($cartProduct['epaper-startdate'])){
                    return true;
                }
            }
        }

        return false;
    }

    public function woocommerce_checkout_coupon_form(bool $enabled){
        return !$this->isSubscription();
    }

    /**
     * // Add custom checkout field: woocommerce_review_order_before_submit
     * @return void
     */
    public function extra_checkbox(){

        if(false === $this->needWafeOfwithdrawal()) return '';

        $html = '';

        $html .= '<div class="alert alert-primary" role="alert">';
        $html .= '<h2>' . __('Ihr Abo beginnt innerhalb von 14 Tagen') . '</h2>';
        $html .= '<p>' . __('Das ist nur in Kombination mit einem Verzicht auf Ihr Widerrufsrecht m??glich.') . '</p>';

        $html .= '<style>
            input#waive_of_withdrawal {
                    position: relative !important;
                }
        </style>';

        $html .= woocommerce_form_field( 'waive_of_withdrawal', array(
            'type'      => 'checkbox',
            //'class'     => array('input-checkbox'),
            'label'     => __('Ich verzichte freiwillig auf mein Widerrufsrecht.'),
            'required'  => true,
            'return' => true
        ),  WC()->checkout->get_value( 'waive_of_withdrawal' ) );

        $html .= '</div>';

        echo $html;
    }

    public function validate_extra_checkbox(){
        if (!$_POST['waive_of_withdrawal'] && true === $this->needWafeOfwithdrawal() )
            wc_add_notice( __( 'Bei einem Abostart innerhalb von 14 Tagen m??ssen Sie den Widerrufsverzicht akzeptieren' ), 'error' );
    }

    /**
     * // Save the custom checkout field in the order meta, when checkbox has been checked
     * @return void
     */
    public function store_extra_checkbox_value($order_id){
        if(isset($_POST['waive_of_withdrawal']) && $_POST['waive_of_withdrawal'] == true){
            update_post_meta( $order_id, 'waive_of_withdrawal', true );
        }
    }

    /**
     * // Display the custom field result on the order edit page (backend) when checkbox has been checked
     * @return void
     */
    public function display_extra_checkbox_value($order){
        $html = '';

        $waiveOfWithdrawal = get_post_meta( $order->get_id(), 'waive_of_withdrawal', true );
        $style = 'color: green; display: flex;flex-direction: row;align-items: center;';
        $checkIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22" style="margin-right: 5px;">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>';

        if( $waiveOfWithdrawal == true ){
            $html .= '<div>';
            $html .= '<strong>'.__('Verzichtet auf sein Widerrufsrecht<br>(Abostart innerhalb von 14 Tagen)', '').': </strong>';
            $html .= '<div style="' . $style . '">' . $checkIcon . '<div>' . __('Einverstanden', '').'</div></div>';
            $html .= '</div>';
        }

        echo $html;
    }

    public function exclude_product_from_subscription_products($is_type, $that){
        if($this->isSubscription() === true){
            return false;
        }

        return $is_type;
    }

    public function new_order_action($order_id)
    {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );
        $items = $order->get_items();

        if(!!$items && is_array($items) && count($items) === 1){
            $item = $items[array_key_first($items)];
            if(isset($item) && !!$item){
                $productId = $item->get_product_id();
                $product = wc_get_product( $productId );
                if ( class_exists(\WC_Subscriptions_Product::class) && \WC_Subscriptions_Product::is_subscription( $product ) ) {
                   $order->update_status( 'completed' );
                }
            }
        }
    }
}