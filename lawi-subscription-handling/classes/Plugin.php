<?php

namespace wps\lawi;

use \DateTime;
class Plugin
{

    public string $path = '';

    public function __construct(string $path)
    {
        $this->path = $path;

        add_action('init', [$this, 'init']);

    }

    public function init()
    {
        add_shortcode('epaper-landingpage-sc', [$this, 'epaper_landingpage_sc']);

        add_filter('woocommerce_add_cart_item_data', array($this, 'wps_add_custom_field_item_data'), 10, 4 );
        add_filter( 'woocommerce_get_item_data', array($this, 'add_epaper_start_date_to_cart'), 10 ,4);

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
     * Description: returns start date selector
     * - today
     *   - show only if not equal to first day of month
     * - first of next,
     * - next+1
     * - next+2 month
     *
     * @return string
     */
    public function get_epaper_date_selector(): string
    {
        // create selectable dates
        // Todo: add empty select

        $config_day = 1;
        $date = new DateTime("now");
//        $date = new DateTime("01.12.2022");
        $today = $date->format('Y-m-d');

        $next_month = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, $config_day, date('Y')));
        $next_month_pretty = date('d.m.Y', mktime(0, 0, 0, date('m') + 1, $config_day, date('Y')));
        $next_month_plus_one = date('Y-m-d', mktime(0, 0, 0, date('m') + 2, $config_day, date('Y')));
        $next_month_plus_one_pretty = date('d.m.Y', mktime(0, 0, 0, date('m') + 2, $config_day, date('Y')));
        $next_month_plus_two = date('Y-m-d', mktime(0, 0, 0, date('m') + 3, $config_day, date('Y')));
        $next_month_plus_two_pretty = date('d.m.Y', mktime(0, 0, 0, date('m') + 3, $config_day, date('Y')));

        $options = '';
        // show today only if its not the first of month
        if ($date->format('d') != '01') {
            $options .= '<option value="' . $today . '">Heute</option>';
        }
        $options .= '<option value="' . $next_month . '">' . $next_month_pretty . '</option>';
        $options .= '<option value="' . $next_month_plus_one . '">' . $next_month_plus_one_pretty . '</option>';
        $options .= '<option value="' . $next_month_plus_two . '">' . $next_month_plus_two_pretty . '</option>';

        // Build return string
        $string = '<label for="epaper-startdate">Startdatum wählen:</label>
            <div>
                <select name="epaper-startdate" id="epaper-startdate">
                 ' . $options . '
                </select>
            </div>';

        return $string;
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

        // button for loggedin users
        $form = '<form action="'. $action . '" method="post">';
        $form .= $this->get_epaper_date_selector();
        $form .= '<input type="submit" name="submit" value="Add to cart" class="btn btn-primary"/>';
        $form .= '</form>';

        // button for NOT logged in users
        if (!is_user_logged_in()) {

            $form = '<form action="'. $action . '" method="post">';
            $form .= $this->get_epaper_date_selector();
            $form .= '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#lawiEpaperModal">Melden Sie sich an</button>';
            $form .= '</form>';

            $form .= $this->get_login_modal();
        }

        return $form;
    }

    public function get_login_modal():string
    {
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
                             ' . ob_start() . wp_login_form() . ob_get_clean().'
                            <div><p>Falls Sie Ihr Passwort vergessen haben, <a href="/passwort-zuruecksetzen"/>klicken Sie hier!</a></p></div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
        }

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

}