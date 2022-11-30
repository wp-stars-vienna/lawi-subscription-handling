<?php

namespace wps\lawi;

use \DateTime;
use \wps\lawi\permissions\PermissionService;

class Plugin
{

    public string $path = '';
    public string $subscriptionsJsonPath = '/config/subscriptions.json';
    public array $subscriptionsArray = [];

    public function __construct(string $path)
    {
        $this->path = $path;

        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        add_shortcode('epaper-landingpage-sc', [$this, 'epaper_landingpage_sc']);
        $this->setupPermissions();
    }

    public function setupPermissions(){
        if(file_exists($this->path . '/config/subscriptions.json')){
            $permissionService = new PermissionService($this->path . $this->subscriptionsJsonPath);
            $this->subscriptionsArray = $permissionService->getSubscriptionsArray();
        }
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
     *      add-to-cart-btn
     *  )
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

        // add to cart button
        $button  = $this->get_add_to_cart_btn( $product );

        // Build return string
        $string = '<div class="col-4">';

        $string .= '<h3>' . $name . '</h3>';
        $string .= wp_get_attachment_image($img_id);
        $string .= '<p>€' . $price . '</p>';
        $string .= $this->get_epaper_date_selector();
        $string .= $button;

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

        $config_day = 1;
        $date = new DateTime("now");
//        $date = new DateTime("01.12.2022");
        $today = $date->format('Y-m-d');

        $next_month = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, $config_day, date('Y')));
        $next_month_plus_one = date('Y-m-d', mktime(0, 0, 0, date('m') + 2, $config_day, date('Y')));
        $next_month_plus_two = date('Y-m-d', mktime(0, 0, 0, date('m') + 3, $config_day, date('Y')));

        $options = '';
        // show today only if its not the first of month
        if ($date->format('d') != '01') {
            $options .= '<option value="' . $today . '">' . $today . '</option>';
        }
        $options .= '<option value="' . $next_month . '">' . $next_month . '</option>';
        $options .= '<option value="' . $next_month_plus_one . '">' . $next_month_plus_one . '</option>';
        $options .= '<option value="' . $next_month_plus_two . '">' . $next_month_plus_two . '</option>';

        // Build return string
        $string = '<label for="cars">Startdatum wählen:</label>
            <div>
                <select name="epaper-startdate" id="epaper-startdate">
                 ' . $options . '
                </select>
            </div>';

        return $string;
    }

    /**
     * Description: returns button with different action
     * -> depends on user logged in status
     *
     * @param $product
     * @return string
     */
    public function get_add_to_cart_btn($product): string
    {
        // button for loggedin users
        $button = '<a href="' . $product->add_to_cart_url() . '" class="btn btn-primary">Add to cart</a>';

        // button for NOT logged in users
        if (!is_user_logged_in()) {
            $button = '<button type="button" class="btn btn-primary" data-toggle="modal" data-target="#lawiEpaperModal">Melden Sie sich an</button>';
            $button .= $this->get_registration_modal();
        }

        return $button;
    }

    public function get_registration_modal()
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
                            <button type="button" class="btn btn-primary">Save changes</button>
                          </div>
                        </div>
                      </div>
                    </div>';

        return $modal;
    }

}