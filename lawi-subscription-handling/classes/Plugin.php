<?php

namespace wps\lawi;

class Plugin
{

    public string $path = '';

    public function __construct(string $path){
        $this->path = $path;

        add_action('init', array( $this, 'init') );

    }

    public function init()
    {
        add_shortcode('epaper-landingpage-sc', array( $this, 'epaper_landingpage_sc'));
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
    public function epaper_landingpage_sc($atts = array(), $content = null, $tag = '' ): string
    {
        $product_ids = explode(",",$atts['id']);

        // get grid data per product
        $string = '<div class="row">';
        foreach ($product_ids as $id ) {
            $string .= $this->get_epaper_grid_item( $id );
        }
        $string .= '</div>';

        return $string;
    }

    public function get_epaper_grid_item( $product_id ): string
    {
        $product = wc_get_product( $product_id );

        // get product data
        $img_id = $product->get_image_id();
        $name = $product->get_name();
        $price = $product->get_price();

        // add to cart button
        $button = '<a href="' . $product->add_to_cart_url() . '">add to cart</a>';

        // Build return string
        $string = '<div class="col-4">';

        $string .= '<h3>' . $name . '</h3>';
        $string .=  wp_get_attachment_image( $img_id );
        $string .=  '<p>€' . $price . '</p>';
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
        $date = new \DateTime("now");
//        $date = new \DateTime("01.12.2022");
        $today = $date->format('Y-m-d');

        $next_month = date('Y-m-d', mktime(0, 0, 0, date('m') + 1,  $config_day, date('Y')));
        $next_month_plus_one = date('Y-m-d', mktime(0, 0, 0, date('m') + 2,  $config_day, date('Y')));
        $next_month_plus_two = date('Y-m-d', mktime(0, 0, 0, date('m') + 3,  $config_day, date('Y')));

        $options = '';
        // show today only if its not the first of month
        if( $date->format('d') != '01' ) {
            $options .= '<option value="'. $today . '">' . $today . '</option>';
        }
        $options .= '<option value="'. $next_month . '">' . $next_month . '</option>';
        $options .= '<option value="'. $next_month_plus_one . '">' . $next_month_plus_one . '</option>';
        $options .= '<option value="'. $next_month_plus_two . '">' . $next_month_plus_two . '</option>';

        // Build return string
        $string = '<label for="cars">Startdatum wählen:</label>
            <div>
                <select name="epaper-startdate" id="epaper-startdate">
                 ' . $options . '
                </select>
            </div>';

        return $string;
    }
}