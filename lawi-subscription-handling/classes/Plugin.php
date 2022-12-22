<?php

namespace wps\lawi;

use \DateTime;
use \DateTimeZone;
use \wps\lawi\permissions\PermissionService;
use \wps\lawi\permissions\SubscriptionWatcher;
use \wps\lawi\SubscriptionService;

class Plugin
{

    private static $instance;

    public string $path = '';
    public string $subscriptionsJsonPath = '/config/subscriptions.json';
    public $permissionService = null;

    public static function get_instance(string $path='')
    {
        if (null === self::$instance) {
            if($path != ''){
                self::$instance = new self($path);
            }
        }

        return self::$instance;
    }

    public function __construct(string $path)
    {
        $this->path = $path;
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'register_scripts'] );
//        add_action('wp_enqueue_style', [$this, 'register_styles'] );
        add_action( 'wp_ajax_nopriv_addToCartExtraData', [$this, 'addToCartExtraData'] );
        add_action( 'wp_ajax_addToCartExtraData', [$this, 'addToCartExtraData'] );
        add_action('acf/init', [$this, 'acfInit']);

        // start watching for subscription changes
        new SubscriptionWatcher();
        new SubscriptionService();

        // checkout modifications
        new Checkout();
    }

    public function init(): void
    {
        $this->setupPermissions();
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'wps_add_custom_field_item_data'), 10, 4 );
        add_filter( 'woocommerce_get_item_data', array($this, 'add_epaper_start_date_to_cart'), 10 ,4);
        add_action( 'woocommerce_checkout_update_order_meta', array($this, 'wps_update_order_meta'));

        add_action('login_redirect', [$this, 'user_login_filter']);
        add_shortcode('epaper-landingpage-sc', [$this, 'epaper_landingpage_sc']);

        $this->checkAndCleanCart();

    }

    public function register_scripts(): void
    {
        $pluginsUrl = plugins_url() . '/lawi-subscription-handling';


        // load script for ajax handling
        wp_enqueue_script( 'wp-util' );
        wp_enqueue_style( 'epaper-styles', $pluginsUrl . '/assets/css/style.css', [], null);

        wp_enqueue_script( 'bootstrap-js', $pluginsUrl . '/assets/js/bootstrap.min.js', ['jquery'], null, true );
        wp_enqueue_script( 'lawi-subscription-handling-js', $pluginsUrl . '/assets/js/script.js', ['bootstrap-js'], null, true );
    }

    public function checkAndCleanCart(){
        $cart = WC()->cart;
        if(!!$cart){
            $cartItems = WC()->cart->get_cart();
            if(is_array($cartItems) && count($cartItems)>1){
                foreach ( $cartItems as $cart_item_key => $cart_item ) {
                    if(isset($cart_item['data'])){
                        $product = $cart_item['data'];
                        if( $product instanceof  \WC_Product_Subscription){
                            WC()->cart->set_quantity($cart_item_key,'0');
                        }
                     }
                }
            }
        }
    }

    public function acfInit(){
        $this->addAcfOptionspage();
    }

    /**
     * create acf Optionspage
     * @return void
     */
    public function addAcfOptionspage(){

        if( function_exists('acf_add_options_page') ) {

            // Register options page.
            acf_add_options_page(array(
                'page_title'    => __('Landwirt ePaper Subscriptions'),
                'menu_title'    => __('ePaper Subscriptions'),
                'menu_slug'     => 'lawi-epaper-subscriptions',
                'parent_slug' => 'options-general.php',
                'capability'    => 'edit_posts',
                'redirect'      => false
            ));

            // load acf fieldgroup from php
            include_once $this->path . '/assets/fieldgroups/subscriptionProducts.php';
        }
    }

    /**
     * Setup all the permissions
     * @return void
     */
    public function setupPermissions(): void
    {
        if(file_exists($this->path . '/config/subscriptions.json')){
            $this->permissionService = new PermissionService($this->path . $this->subscriptionsJsonPath);
            $this->subscriptionsArray = $this->permissionService->getSubscriptionsArray();
        }

    }

    // save product data in the session to add it after login procedure
    public function addToCartExtraData(){

        if(isset($_SESSION)){
            $_SESSION['epaperCartExtraData'] = [
                'startDate' => $_POST['date'],
                'productID' => $_POST['id'],
            ];
        }

        //echo json_encode(['is_logged_in_user' => is_user_logged_in()]);
        wp_die();
    }

    /**
     * Description: Used on the epaper landingpage
     *
     * Return String contains:
     * - grid-container
     * - grid-item
     * - product-data (
     *      img,
     *      titel,
     *      price,
     *      select-start-date,
     *      add-to-cart-btn )
     *
     * @param $atts wc-product-id
     * @param $content
     * @param $tag
     * @return string
     */
    public function epaper_landingpage_sc($atts = [], $content = NULL, $tag = ''): string
    {

        if(!isset($atts['id']) || $atts['id'] == '') return __('product ID not found', '');

        $product_ids = explode(",", $atts['id']);


        $subscriptionProducts = Plugin::get_instance()->permissionService->getSubscriptionsArray()['ePaperSubscriptions'];

        $products = [];
        foreach ($subscriptionProducts as $key => $subscriptionProduct){

            $product_id = (int) $key;
            $product = wc_get_product($product_id);

            $product = [
                'id' => $product_id,
                'country' => $subscriptionProduct['meta']['country'],
                'type' => $subscriptionProduct['meta']['type'],
            ];


            $products[] = $product;
        }

        //1.  sort subscriptionproducts
        //  single + bio
        //  kombi + bio
        // bio should be the last item

        $productsSingle = [];
        foreach ($products as $product) {
            if ($product['type'] == 'single') {
                $productsSingle[] = $product['id'];
            }
        }

        foreach ($products as $product) {
            if ($product['type'] == 'bio') {
                $productsSingle[] = $product['id'];
            }
        }

        $productsKombi = [];
        foreach ($products as $product) {
            if ($product['type'] == 'kombi') {
                $productsKombi[] = $product['id'];
            }
        }
        foreach ($products as $product) {
            if ($product['type'] == 'bio') {
                $productsKombi[] = $product['id'];
            }
        }

        // 2. show epapers in 2 rows
        $string = '<div class="row epaper single">';
        foreach ($productsSingle as $id) {
            $string .= $this->get_epaper_grid_item($id);
        }
        $string .= '</div>';

        $string .= '<div class="row epaper kombi">';
        foreach ($productsKombi as $id) {
            $string .= $this->get_epaper_grid_item($id);
        }
        $string .= '</div>';

        return $string;
    }

    public function get_epaper_grid_item($product_id): string|null
    {
        $product = wc_get_product($product_id);

        // return if product not found
        if($product === false || $product === null) return null;

        $user = wp_get_current_user();
        $isSubscriber = false;

        if(isset($user)){
            $bannedStatis = ['active', 'pending-cancel', 'on-hold', ];
            foreach ($bannedStatis as $status){
                if(true === wcs_user_has_subscription( $user->ID, $product_id, $status)){
                    $isSubscriber = true;
                    break;
                }
            }
        }

        // get product data
        $name = $product->get_name();
        $price = $product->get_price();
        $postExcerpt = get_post( $product_id )->post_content;

        // epaper form
        $form  = $this->get_epaper_product_form( $product, $price );

        if($isSubscriber === true){
            $form = '<button type="button" class="btn btn-secondary already-subscribed" disabled="true">Bereits abonniert</button>';
        }

        // Build return string
        $string = '<div class="col-4"><div class="epaperCard">';

        $string .= '<h3>' . $name . '</h3>';
        $string .= '<p>' . $postExcerpt . '</p>';
        $string .=  '<div class="epaper-form my-2">' . $form . '</div>';
        $string .= '</div></div>';

        return $string;
    }

    /**
     * @param int $productID
     * @return string
     * @throws \Exception
     */
    public function get_epaper_date_selector(int $productID): string
    {
        // array of available dates
        $dates = [
            new DateTime('today', new DateTimeZone('europe/vienna')),
            new DateTime('first day of next month', new DateTimeZone('europe/vienna')),
            new DateTime('first day of next month + 1 month', new DateTimeZone('europe/vienna')),
            new DateTime('first day of next month + 2 month', new DateTimeZone('europe/vienna'))
        ];

        $options = '';
        $options .= '<option value="" selected>' .  __('Startdatum auswählen', '') . '</option>';
        foreach ($dates as $key => $date){
            $options .= '<option value="' . $date->format('Y-m-d') . '">' . ($key == 0 ? __('Heute', '') : $date->format('d.m.Y')) . '</option>';
        }

        // Build return string
        $html = '<div>';
//        $html .= '<label for="epaper-startdate-'.$productID.'">' . __('Startdatum:', '') . '</label><br>';
        $html .= '<select name="epaper-startdate" id="epaper-startdate-'.$productID.'" class="form-select form-select-lg mb-3 dateSelect" required>';
        $html .= $options;
        $html .= '</select>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Description: returns epaper_product_form with different action
     * -> depends on user logged in status
     *
     * @param $product
     * @return string
     */
    public function get_epaper_product_form($product , $price ): string|null
    {
        $wc_cart_url = wc_get_cart_url();
        $action = $wc_cart_url . $product->add_to_cart_url();
        $product_id = $product->get_id();
        $cart = WC()->cart;

        if($cart === null) return null;

        $cartEmpty = $cart->get_cart_contents_count() == 0 ? 'true' : 'false';

        $button = '<button type="submit" class="btn btn-primary subscribe-now" data-modal="false">Abonnieren</button>';
        $modal = '';

        if (!is_user_logged_in()){
            //$button = '<button type="button" class="btn btn-primary" name="login" data-toggle="modal" data-target="#lawiEpaperModal">Melden Sie sich an</button>';
            $button = '<button type="submit" class="btn btn-primary subscribe-now" data-modal="login">Einloggen/Registrieren</button>';
            $modal = $this->get_login_modal();
        }

        //render button
        $form = '<form id="epaper-id-'. $product_id .'" action="'. $action . '" method="post">';
        $form .= '<hr>';
        $form .= $this->get_epaper_date_selector($product_id);
        $form .= '<hr>';
        $form .= '<div class="price"><div class="big">' . $price . '€</div> / Monat</div>';
        $form .= '<input type="hidden" name="productID" value="'.$product_id.'" class="productSelect"/>';
        $form .= '<input type="hidden" name="CartEmpty" value="' . $cartEmpty . '" class="CartEmpty"/>';
        $form .= $button;
        $form .= '</form>';
        $form .= $modal;
        $form .= $this->get_cartNotEmpty_modal();

        return $form;
    }

    public function get_login_modal():string
    {
        ob_start();
        wp_login_form();
        $loginform = ob_get_contents();
        ob_end_clean();

        $modal = '<div class="modal fade" id="lawiEpaperModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5>LANDWIRT Anmeldung / Registrierung</h5>
                            </div>
                
                            <div class="modal-body">
                            <!-- Nav tabs -->
                                <ul class="nav nav-tabs" id="epaperModalTab" role="tablist">
                                  <li class="nav-item">
                                    <a class="nav-link active" id="anmelden-tab" data-toggle="tab" href="#anmelden" role="tab" aria-controls="anmelden" aria-selected="true">Anmelden</a>
                                  </li>
                                  <li class="nav-item">
                                    <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab" aria-controls="register" aria-selected="false">Registrieren</a>
                                  </li>
                                </ul>
                                <!-- Tab panes -->
                                <div class="tab-content">
                                    <div class="tab-pane active" id="anmelden" role="tabpanel" aria-labelledby="anmelden-tab">
                                        ' . $loginform . '
                                        <p>Falls Sie Ihr Passwort vergessen haben, <a href="/passwort-zuruecksetzen"/>klicken Sie hier!</a></p>
                                    </div>
                                    <div class="tab-pane" id="register" role="tabpanel" aria-labelledby="register-tab">
                                        <p>Sie können sich bei Landwirt-media.com registrieren und erhalten zugang zu Landwirt Inhalten.</p>
                                        <a class="btn btn-primary" href="/registrieren/">Zur Registrierung</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Schließen</button>
                            </div>
                        </div>
                    </div>
                </div>';

        return $modal;
    }


    public function get_cartNotEmpty_modal():string
    {
        return '<div class="modal fade" id="lawiEpaperCleanCartModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="lawiEpaperModalLabel">Ihr Warenkorb ist nicht leer</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            Abos können nicht zusammen mit anderen Produkten bestellt werden. Ihr Warenkorb wird daher geleert.
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                            <button type="button" class="btn btn-primary emptyCart">Ok</button>
                          </div>
                        </div>
                      </div>
                    </div>';
    }

    /**
     * Add the date of field 'epaper-startdate' as item data to the cart object
     *
     * @param array $cart_item_meta_data
     * @param int $product_id
     * @param  $variation_id
     * @param bool $quantity
     *
     * @return array
     * @since 1.0.0
     */
    public function wps_add_custom_field_item_data($cart_item_meta_data, $product_id,  $variation_id, $quantity ): array {

        if( ! empty( $_POST['epaper-startdate'] ) ) {
            // Add the item data
            $cart_item_meta_data['epaper-startdate'] = $_POST['epaper-startdate'];
            $_SESSION['epaper-startdate'] = $_POST['epaper-startdate'];
            return $cart_item_meta_data;
        }

        if (!session_id()) {
            session_start();
        }

        if(isset($_SESSION['epaperCartExtraData'])) {
            $data = $_SESSION['epaperCartExtraData'];
            $productID = (int) $data['productID'];
            $startDate = $data['startDate'];
            $cart_item_meta_data['epaper-startdate'] = $startDate;
            unset($_SESSION['epaperCartExtraData']);
        }

        return $cart_item_meta_data;
    }

    /**
     * Display custom item data in the cart
     */
    function add_epaper_start_date_to_cart( $item_data, $cart_item_data ) {
        if( isset( $cart_item_data['epaper-startdate'] ) ) {

            $date = new DateTime($cart_item_data['epaper-startdate']);

            $item_data[] = array(
                'key' => __( 'Abo Startdatum', 'lawi_epaper' ),
                'value' => $date->format('d.m.Y')
            );
        }
        return $item_data;
    }

    /**
    * Save start date as order meta
    */
    public function wps_update_order_meta($order_id) {
        if (!empty($_SESSION['epaper-startdate'])) {
            update_post_meta($order_id, '_epaper_startdate', $_SESSION['epaper-startdate']);
        }
    }

    public function user_login_filter() {

        if (!session_id()) {
            session_start();
        }

        if(isset($_SESSION['epaperCartExtraData'])){

            $data = $_SESSION['epaperCartExtraData'];
            $productID = (int) $data['productID'];

            $cart = WC()->cart;
            $cart->empty_cart();
            $cart->add_to_cart( $productID, 1 );

            $checkout_url = wc_get_checkout_url();

            unset($_SESSION['epaperCartExtraData']);

            // return directly to the checkout
            return $checkout_url;
        }
    }
}