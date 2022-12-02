<?php

namespace wps\lawi;

use \DateTime;
use \DateTimeZone;
use \wps\lawi\permissions\PermissionService;
use \wps\lawi\permissions\LawiRole;

class Plugin
{

    public string $path = '';
    public string $subscriptionsJsonPath = '/config/subscriptions.json';
    public $permissionService = null;

    public function __construct(string $path)
    {
        $this->path = $path;

        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'register_scripts'] );

        add_action( 'wp_ajax_nopriv_addToCartExtraData', [$this, 'addToCartExtraData'] );
        add_action( 'wp_ajax_addToCartExtraData', [$this, 'addToCartExtraData'] );

    }

    public function init(): void
    {
        $this->setupPermissions();
        add_filter('woocommerce_add_cart_item_data', array($this, 'wps_add_custom_field_item_data'), 10, 4 );
        add_filter( 'woocommerce_get_item_data', array($this, 'add_epaper_start_date_to_cart'), 10 ,4);
        add_action('wp_login', [$this, 'user_login_filter']);
        add_shortcode('epaper-landingpage-sc', [$this, 'epaper_landingpage_sc']);
    }

    public function register_scripts(): void
    {
        $pluginsUrl = plugins_url() . '/lawi-subscription-handling';

        // load script for ajax handling
        wp_enqueue_script( 'wp-util' );
        wp_enqueue_script( 'lawi-subscription-handling-js', $pluginsUrl . '/assets/js/script.js', ['jquery'], null, true );
    }

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

        echo json_encode(['is_logged_in_user' => is_user_logged_in()]);
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

        $product_ids = explode(",", $atts['id']);

        // get grid data per product
        $string = '<div class="row">';
        foreach ($product_ids as $id) {
            $string .= $this->get_epaper_grid_item($id);
        }
        $string .= '</div>';

        return $string;
    }

    public function get_epaper_grid_item($product_id): string
    {
        $product = wc_get_product($product_id);

        // get product data
        $img_id = $product->get_image_id();
        $name = $product->get_name();
        $price = $product->get_price();

        // epaper form
        $form  = $this->get_epaper_product_form( $product );

        // Build return string
        $string = '<div class="col-4">';

        $string .= '<h3>' . $name . '</h3>';
        $string .= wp_get_attachment_image($img_id);
        $string .= '<p>€' . $price . '</p>';
        $string .=  '<div class="epaper-form my-2">' . $form . '</div>';
        $string .= '</div>';

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
            new DateTime('today', new \DateTimeZone('europe/vienna')),
            new DateTime('first day of next month', new \DateTimeZone('europe/vienna')),
            new DateTime('first day of next month + 1 month', new \DateTimeZone('europe/vienna')),
            new DateTime('first day of next month + 2 month', new \DateTimeZone('europe/vienna'))
        ];

        $options = '';
        $options .= '<option value="" selected>' .  __('auswählen', '') . '</option>';
        foreach ($dates as $key => $date){
            $options .= '<option value="' . $date->format('Y-m-d') . '">' . ($key == 0 ? __('Heute', '') : $date->format('d.m.Y')) . '</option>';
        }

        // Build return string
        $html = '<div>';
        $html .= '<label for="epaper-startdate-'.$productID.'">' . __('Startdatum:', '') . '</label><br>';
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
    public function get_epaper_product_form($product): string
    {
        $wc_cart_url = wc_get_cart_url();
        $action = $wc_cart_url . $product->add_to_cart_url();
        $product_id = $product->get_id();

        $button = '<input type="submit" name="submit" value="Add to cart" class="btn btn-primary"/>';
        $modal = '';

        if (!is_user_logged_in()){
            //$button = '<button type="button" class="btn btn-primary" name="login" data-toggle="modal" data-target="#lawiEpaperModal">Melden Sie sich an</button>';
            $button = '<input type="submit" name="submit" value="Einloggen/Registrieren" class="btn btn-primary"/>';
            $modal = $this->get_login_modal();
        }

        //render button
        $form = '<form id="epaper-id-'. $product_id .'" action="'. $action . '" method="post">';
        $form .= $this->get_epaper_date_selector($product_id);
        $form .= '<input type="hidden" name="productID" value="'.$product_id.'" class="productSelect"/>';
        $form .= $button;
        $form .= '</form>';
        $form .= $modal;

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
                            <h5 class="modal-title" id="lawiEpaperModalLabel">Anmelden</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                             ' . $loginform .'
                            <div><p>Falls Sie Ihr Passwort vergessen haben, <a href="/passwort-zuruecksetzen"/>klicken Sie hier!</a></p></div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Schließen</button>
                          </div>
                        </div>
                      </div>
                    </div>';

        return $modal;
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
        }
        session_start();
        $_SESSION['paulsessionDings'] = "hi PLaul";


        return $cart_item_meta_data;
    }


    /**
     * Display custom item data in the cart
     */
    function add_epaper_start_date_to_cart( $item_data, $cart_item_data ) {
        if( isset( $cart_item_data['epaper-startdate'] ) ) {

            $date = new DateTime($cart_item_data['epaper-startdate']);
            $date_pretty = $date->format('d.m.Y');


            $item_data[] = array(
                'key' => __( 'Abo Startdatum', 'lawi_epaper' ),
                'value' => $date_pretty
            );
        }
        return $item_data;
    }


    public function user_login_filter() {
        // store data in session



        print_r($_SESSION);

die();

        // redirect to cart if
        // -> user = XX
        // -> Session data contains Product ID  and todays date
    }


}