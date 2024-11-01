<?php
/* @wordpress-plugin
 * Plugin Name:       Shipment & Tracking with APC Overnight
 * Plugin URI:        https://tricasol.com/apc-overnight-shipment
 * Description:       this plugin is develop to integrate WooCommerce store with APC Overnight and Logistic company's system in order to add new shipments and track shipments.
 * Version:           1.0
 * WC requires at least: 2.2
 * WC tested up to: 3.2
 * Requires at least: 5.0
 * Author:            Tricasol
 * Author URI: https://tricasol.com/
 * Text Domain:       apc-overnight-shipping
 * Developer: Asim Khadim
 * Copyright: © 2022 Tricasol.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html

 */

define('APCOVERNIGHT_URL', plugin_dir_url(__FILE__));

if (!defined('WPINC')) {
    die('security by preventing any direct access to your plugin file');
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    function apc_shipping_method()
    {
        if (!class_exists('apc_Shipping_Method')) {
            class apc_Shipping_Method extends WC_Shipping_Method
            {
                private $_instance = null;

                public static function getInstance() {
                    if (self::$_instance == null) {
                        self::$_instance = new apc_Shipping_Method();
                    }
                    return self::$_instance;
                }

                public function __construct()
                {
                    $this->id                 = 'apc';
                    $this->method_title       = __('APC Shipping', 'apc');
                    $this->method_description = __('APC Overnight Shipping Method for products (This service is available only in United Kingdom)', 'apc');
                    // Contreis availability
                    $this->availability = 'including';
                    $this->init();
                    $this->enabled   = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                    $this->email     = isset($this->settings['email']) ? $this->settings['email'] : 'test@test.com';
                    $this->password  = isset($this->settings['password']) ? $this->settings['password'] : 'demo123';
                    // $this->process   = isset($this->settings['process']) ? $this->settings['process'] : 'auto';
                    $this->services  = isset($this->settings['services']) ? $this->settings['services'] : 'ND09';
                    $this->ready_at  = isset($this->settings['ready_at']) ? $this->settings['ready_at'] : '09:00';
                    $this->closed_at = isset($this->settings['closed_at']) ? $this->settings['closed_at'] : '18:00';
                }
                /**
                Load the settings API
                 */
                function init()
                {
                    $this->init_form_fields();
                    $this->add_admin_scripts();
                    $this->init_settings();
                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                function add_admin_scripts()
                {
                    wp_enqueue_script('apc-label-script', APCOVERNIGHT_URL . 'js/apc-label.js', array('jquery'));
                    wp_localize_script('apc-label-script', 'apc_label_object', array('ajax_url' => admin_url('admin-ajax.php')));
                    wp_enqueue_style(
                        'label-css',
                        APCOVERNIGHT_URL . 'css/style.css',
                        null,
                        null, // example of no version number...
                        // ...and no CSS media type
                    );

                }

                function init_form_fields()
                {
                    $this->form_fields = array(
                        'enabled'   => array(
                            'title'   => __('Enable', 'apc'),
                            'type'    => 'checkbox',
                            'default' => 'yes',
                        ),
                        'email'     => array(
                            'title'   => __('Email', 'apc'),
                            'type'    => 'email',
                            'default' => 'test@test.com',
                        ),
                        'password'  => array(
                            'title'   => __('Password', 'apc'),
                            'type'    => 'password',
                            'default' => __('demo12', 'apc'),
                        ),
                        // 'process'   => array(
                        //     'title'    => __('Shipment Book', 'apc'),
                        //     'id'       => 'woocommerce_shipment_process',
                        //     'desc_tip' => __('Select the process option', 'apc'),
                        //     'default'  => 'auto',
                        //     'type'     => 'select',
                        //     'class'    => 'wc-enhanced-select',
                        //     'options'  => array(
                        //         'auto'   => __('Auto Process', 'apc'),
                        //         'manual' => __('Manual Process', 'apc'),
                        //     ),
                        // ),
                        'services'  => array(
                            'title'    => __('Service Product Codes', 'apc'),
                            'id'       => 'woocommerce_product_codes',
                            'desc_tip' => __('Select the service product code for apc shipment', 'apc'),
                            'default'  => 'ND16',
                            'type'     => 'select',
                            'class'    => 'wc-enhanced-select',
                            'options'  => $this->service_codes(),
                        ),
                        'ready_at'  => array(
                            'title'    => __('Order Ready At', 'apc'),
                            'type'     => 'text',
                            'desc_tip' => __('Add the time (hh:mm) for order ready at', 'apc'),
                            'default'  => '09:00',
                        ),
                        'closed_at' => array(
                            'title'    => __('Store Closed At', 'apc'),
                            'type'     => 'text',
                            'desc_tip' => __('Add the time (hh:mm) for store closed', 'apc'),
                            'default'  => '18:00',
                        ),
                    );
                }

                function service_codes()
                {
                    $codes = array(
                        'ND09' => 'NEXTDAY PARCEL BY 09.00',
                        'ND10' => 'NEXTDAY PARCEL BY 10.00',
                        'ND12' => 'NEXTDAY PARCEL BY 12.00',
                        'ND16' => 'NEXTDAY PARCEL BY 16.00',
                        'TDAY' => '2-5 DAY PARCEL',
                        'LW09' => 'NEXTDAY LIGHT WEIGHT BY 09.00',
                        'LW10' => 'NEXTDAY LIGHT WEIGHT BY 10.00',
                        'LW12' => 'NEXTDAY LIGHT WEIGHT BY 12.00',
                        'LW16' => 'NEXTDAY LIGHT WEIGHT BY 16.00',
                        'TDLW' => '2-5 DAY LIGHTWEIGHT',
                        'CP09' => 'NEXTDAY COURIER PACK BY 09.00',
                        'CP10' => 'NEXTDAY COURIER PACK BY 10.00',
                        'CP12' => 'NEXTDAY COURIER PACK BY 12.00',
                        'CP16' => 'NEXTDAY COURIER PACK BY 16.00',
                        'TDCP' => '2-5 DAY COURIER PACK',
                        'LP09' => 'LIQUID PRODUCT BY 09:00',
                        'LP10' => 'LIQUID PRODUCT BY 10.00',
                        'LP12' => 'LIQUID PRODUCT BY 12.00',
                        'LP16' => 'LIQUID PRODUCT BY 16.00',
                        'TDLP' => '2-5 DAY LIQUID PRODUCT',
                        'LQ09' => 'LIMITED QUANTITY BY 09.00',
                        'LQ10' => 'LIMITED QUANTITY BY 10.00',
                        'LQ12' => 'LIMITED QUANTITY BY 12.00',
                        'LQ16' => 'LIMITED QUANTITY BY 16.00',
                        'NC09' => 'NON-CONVEYABLE BY 09.00',
                        'NC10' => 'NON-CONVEYABLE BY 10.00',
                        'NC12' => 'NON-CONVEYABLE BY 12.00',
                        'NC16' => 'NON-CONVEYABLE BY 16.00',
                        'TDNC' => '2-5 DAY NON-CONVEYABLE',
                        'XS09' => 'EXCESS PARCEL BY 09.00',
                        'XS10' => 'EXCESS PARCEL BY 10.00',
                        'XS12' => 'EXCESS PARCEL BY 12.00',
                        'XS16' => 'EXCESS PARCEL BY 16.00',
                    );
                    return $codes;
                }
            }
        }
    }
    add_action('woocommerce_shipping_init', 'apc_shipping_method');

    function add_apc_shipping_method($methods)
    {
        $methods[] = 'apc_Shipping_Method';
        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'add_apc_shipping_method');

    function apc_place_order($order_id)
    {
        if( !get_post_meta( $order_id, '_thank_you', true ) ) {

            $apc_shipping_method = new apc_Shipping_Method();
            // get shipping settings

            $enabled          = $apc_shipping_method->settings['enabled'];
            $email            = $apc_shipping_method->settings['email'];
            $password         = $apc_shipping_method->settings['password'];
            $api_auth         = $email . ':' . $password;
            $shipping_process = $apc_shipping_method->settings['process'];
            $product_code     = $apc_shipping_method->settings['services'];
            $ready_at         = $apc_shipping_method->settings['ready_at'];
            $closed_at        = $apc_shipping_method->settings['closed_at'];

            if ($enabled == 'yes') {
                if (!$order_id) {
                    return;
                }
                $filename = '/home/imantkhy/test.imanbuilders.com/debug.txt';

                $order           = wc_get_order($order_id);
                $reference       = $order->get_order_key();
                $collection_date = $order->order_date;
                $collection_date = date("d/m/Y", strtotime($collection_date));

                //billing details
                $billing_company      = ($order->get_billing_company() == '' ? 'TruHair' : $order->get_billing_company());
                $billing_addressline1 = $order->get_billing_address_1();
                $billing_addressline2 = $order->get_billing_address_2();
                $billing_city         = $order->get_billing_city();
                $billing_postcode     = $order->get_billing_postcode();
                $billing_country      = $order->get_billing_country();
                $billing_first_name   = $order->get_billing_first_name();
                $billing_last_name    = $order->get_billing_last_name();
                $billing_phone        = $order->get_billing_phone();
                $billing_email        = $order->get_billing_email();

                //shipping details
                $shipping_company      = ($order->get_shipping_company() == '' ? 'TruHair' : $order->get_shipping_company());
                $shipping_addressline1 = $order->get_shipping_address_1();
                $shipping_addressline2 = $order->get_shipping_address_2();
                $shipping_city         = $order->get_shipping_city();
                $shipping_postcode     = $order->get_shipping_postcode();
                $shipping_country      = $order->get_shipping_country();
                $shipping_first_name   = $order->get_shipping_first_name();
                $shipping_last_name    = $order->get_shipping_last_name();

                $customer_note = $order->get_customer_note();
                $order_total   = $order->get_total();
                $item_count    = $order->get_item_count();
                $items         = array();

                //get order item detail
                foreach ($order->get_items() as $item_id => $item) {
                    $product_id   = $item->get_product_id();
                    $variation_id = $item->get_variation_id();
                    $product      = $item->get_product(); // see link above to get $product info
                    $product_name = $item->get_name();
                    $quantity     = $item->get_quantity();
                    $subtotal     = $item->get_subtotal();
                    $total        = $item->get_total();
                    $tax          = $item->get_subtotal_tax();
                    $allmeta      = $item->get_meta_data();
                    $length = (get_post_meta($product_id, '_length', true) ? get_post_meta($product_id, '_length', true) : 2);
                    $height = (get_post_meta($product_id, '_height', true) ? get_post_meta($product_id, '_height', true) : 5);
                    $width  = (get_post_meta($product_id, '_width', true) ? get_post_meta($product_id, '_width', true) : 5);
                    $weight = (get_post_meta($product_id, '_weight', true) ? get_post_meta($product_id, '_weight', true) : 1);
                    $items[]      = array(
                        'Type'      => 'ALL',
                        'Weight'    => $weight,
                        'Length'    => $length,
                        'Width'     => $width,
                        'Height'    => $height,
                        'Reference' => $product_name,
                    );
                }

                $response = array(
                    'Orders' => array(
                        'Order' => array(
                            'CollectionDate'  => $collection_date,
                            'ReadyAt'         => $ready_at,
                            'ClosedAt'        => $closed_at,
                            'ProductCode'     => $product_code,
                            'Reference'       => 'Order#' . $order_id,
                            'Collection'      => array(
                                'CompanyName'  => $billing_company,
                                'AddressLine1' => $billing_addressline1,
                                'AddressLine2' => $billing_addressline2,
                                'PostalCode'   => $billing_postcode,
                                'City'         => $billing_city,
                                'County'       => $billing_country,
                                'CountryCode'  => $billing_country,
                                'Contact'      => array(
                                    'PersonName'  => $billing_first_name . ' ' . $billing_last_name,
                                    'PhoneNumber' => $billing_phone,
                                    'Email'       => $billing_email,
                                ),
                            ),
                            'Delivery'        => array(
                                'CompanyName'  => $shipping_company,
                                'AddressLine1' => $shipping_addressline1,
                                'AddressLine2' => $shipping_addressline2,
                                'PostalCode'   => $shipping_postcode,
                                'City'         => $shipping_city,
                                'County'       => $shipping_country,
                                'CountryCode'  => $shipping_country,
                                'Contact'      => array(
                                    'PersonName'  => $shipping_first_name . ' ' . $shipping_last_name,
                                    'PhoneNumber' => $billing_phone,
                                    'Email'       => $billing_email,
                                ),
                                'Instructions' => $customer_note,
                            ),
                            'GoodsInfo'       => array(
                                'GoodsValue'         => $order_total,
                                'GoodsDescription'   => 'Send to APC Overnight for Shipment',
                                'Fragile'            => 'false',
                                'Security'           => 'false',
                                'IncreasedLiability' => 'false',
                            ),
                            'ShipmentDetails' => array(
                                "NumberOfPieces" => $item_count,
                                "Items"          => array(
                                    'Item' => $items,
                                ),
                            ),
                        ),
                    ),
                );

                $post_data = json_encode($response);

                $args = array(
                        'method' => 'POST',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'sslverify' => false,
                        'blocking' => false,
                        'headers' => array(
                            'remote-user: Basic ' . base64_encode($api_auth),
                            'Content-Type' => 'application/json',
                            'Accept' => 'application/json',
                        ),
                        'body' => $post_data,
                        'cookies' => array()
                );

                $request = wp_remote_post('https://apc.hypaship.com/api/3.0/Orders.json',$args);
                if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                    error_log( print_r( $request, true ) );
                }

                $result = wp_remote_retrieve_body( $request );

                add_post_meta($order_id, 'apc_response', $result, true);
                add_post_meta($order_id, '_thank_you', 'done', true);
                        // die();
          
            }

        }
    }
    add_action('woocommerce_thankyou', 'apc_place_order');

    function apc_ovn_register_shipment_order_status()
    {
        register_post_status('wc-shipment', array(
            'label'                     => 'Shipped',
            'public'                    => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list'    => true,
            'exclude_from_search'       => false,
            'label_count'               => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>'),
        ));
    }
    add_action('init', 'apc_ovn_register_shipment_order_status');
    function apc_ovn_add_awaiting_shipment_to_order_statuses($order_statuses)
    {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-shipment'] = 'Shipped';
            }
        }
        return $new_order_statuses;
    }
    add_filter('wc_order_statuses', 'apc_ovn_add_awaiting_shipment_to_order_statuses');

    // Display field value on the order edit page (not in custom fields metabox)
    add_action('woocommerce_admin_order_data_after_billing_address', 'apc_meta_field_display_admin_order_meta', 10, 1);
    function apc_meta_field_display_admin_order_meta($order)
    {
        $apc_response = get_post_meta($order->id, 'apc_response', true);
        if (!empty($apc_response)) {
            $apc_response = json_decode(get_post_meta($order->id, 'apc_response', true));
            $apc_billno   = $apc_response->Orders->Order->OrderNumber;
            $apc_waybill  = $apc_response->Orders->Order->WayBill;
            $apc_barcode  = $apc_response->Orders->Order->Barcode;
            echo esc_html('<p><strong>' . __("APC Bill No", "apc") . ':</strong> ' . $apc_billno . '<br><strong>' . __("APC WayBill", "apc") . ':</strong> ' . $apc_waybill . '<br><strong>' . __("APC Barcode", "apc") . ':</strong> ' . $apc_barcode . '</p>');
            
            echo esc_html('<button type="button" order_number=' . $apc_billno . ' class="print_label">Print Label</button><span class="wpcf7-spinner"></span>');
        }
    }

    add_action('wp_ajax_nopriv_apc_ovn_print_label', 'apc_ovn_print_label');
    add_action('wp_ajax_apc_ovn_print_label', 'apc_ovn_print_label');

    function apc_ovn_print_label(){

        $apc_options = get_option( 'woocommerce_apc_settings' );
        
        $email            = $apc_options['email'];;
        $password         = $apc_options['password'];
        $api_auth         = $email . ':' . $password;

        $order_number = sanitize_text_field($_POST['order_number']);

        $url = 'https://apc.hypaship.com/api/3.0/Orders/'.$order_number.'.json?labelformat=PNG';

        $args = array(
                        'method' => 'get',
                        'timeout' => 45,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'sslverify' => false,
                        'blocking' => false,
                        'headers' => array(
                            'Authorization' => 'Bearer {private token goes here!!!!}',
                            'Content-Type' => 'application/json',
                            'remote-user: Basic ' . base64_encode($api_auth),
                            'Accept' => 'application/json',
                        ),
                        'body' => $post_data,
                        'cookies' => array()
                );

                $request = wp_remote_get($url,$args);
                if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
                    error_log( print_r( $request, true ) );
                }

                $result = wp_remote_retrieve_body( $request );
                $png_encoded = json_decode($resp);
                $png_encoded = $png_encoded->Orders->Order->ShipmentDetails->Items->Item->Label->Content;
                echo esc_html($png_encoded);
       

 
          
       
    }

}
