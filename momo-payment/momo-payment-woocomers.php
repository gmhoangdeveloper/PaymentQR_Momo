<?php

/**
 * Plugin Name: Momo Payment for Woocommerce
 * Plugin URI: https://github.com/gmhoangdeveloper/PaymentQR_Momo
 * Author Name: Giang Minh Hoàng
 * Author URI: https://www.facebook.com/giangminhhoang.ro
 * Description: Plugin thanh toán trực tuyến Quét QR Momo hàng đầu Việt Nam
 * Version: 0.1.0
 * License: 0.1.0
 * License URL: https://github.com/gmhoangdeveloper/PaymentQR_Momo/blob/master/README.md
 * text-domain: momos-pay-woo
 */

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) return;
add_action('plugins_loaded', 'momo_payment_init', 11);

function momo_payment_init()
{
    if (class_exists('WC_Payment_Gateway')) {
        class WC_Momo_pay_Gateway extends WC_Payment_Gateway
        {
            public function __construct()
            {
                // Phần Admin setting trong woocommerce
                $this->id   = 'momo_payment';
                $this->icon = apply_filters('woocommerce_momo_icon', plugins_url('/assets/logo-momo.png', __FILE__));
                $this->has_fields = false;
                $this->method_title = __('Momo Payment Gateway', 'momo-pay-woo');
                $this->method_description = __('Momo doanh nghiệp QR', 'momo-pay-woo');
                // Phần hiện lên phần front-end
                // Phần title là hiện nút tên from get option là lấy thông tin trong title
                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                // get option như lấy phần description trong íntruction
                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            }
            public function admin_scripts()
            {
                wp_enqueue_script('wc_baokim_payment', plugins_url('assets/js/momo-payment.js', __FILE__));
            }
            public function init_form_fields()
            {
                $this->form_fields = apply_filters('woo_momo_pay_fields', array(
                    'enabled' => array(
                        'title' => __('Enable/Disable', 'momo-pay-woo'),
                        'type' => 'checkbox',
                        'label' => __('Enable or Disable Momo thanh toán', 'momo-pay-woo'),
                        'default' => 'no'
                    ),
                    'title' => array(
                        'title' => __('Tên phương thức', 'momo-pay-woo'),
                        'type' => 'text',
                        'default' => __('Ví điện tử momo', 'momo-pay-woo'),
                        'desc_tip' => true,
                        'description' => __('Tên phương thức thanh toán (khi khách hàng chọn phương thức thanh toán) ', 'momo-pay-woo')
                    ),
                    'description' => array(
                        'title' => __('Mô tả ', 'momo-pay-woo'),
                        'type' => 'textarea',
                        'default' => __('Ví thanh toán trực tuyến hàng đầu Việt Nam', 'momo-pay-woo'),
                        'desc_tip' => true,
                        'description' => __('Mô tả phương thức thanh toán ', 'momo-pay-woo')
                    ),
                    'api_PARTNER_CODE' => array(
                        'title' => __('PARTNER CODE', 'momo-pay-woo'),
                        'type' => 'text',
                        'description' => __('Thông tin PARTNER CODE do momo cung cấp', 'momo-pay-woo'),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'api_ACCESS_KEY' => array(
                        'title' => __('ACCESS KEY', 'momo-pay-woo'),
                        'type' => 'password',
                        'description' => __('Thông tin ACCESS KEY do momo cung cấp', 'momo-pay-woo'),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'api_SECRET_KEY' => array(
                        'title' => __('SECRET KEY', 'momo-pay-woo'),
                        'type' => 'password',
                        'description' => __('Thông tin SECRET KEY do momo cung cấp', 'momo-pay-woo'),
                        'default' => '',
                        'desc_tip' => true,
                    ),
                    'api_API_ENDPOINT' => array(
                        'title' => __('API ENDPOINT', 'momo-pay-woo'),
                        'type' => 'text',
                        'description' => __('Thông tin API ENDPOINT do momo cung cấp', 'momo-pay-woo'),
                        'default' => '',
                        'desc_tip' => true,
                    ),

                ));
            }

            public function process_payment($order_id)
            {
                $order = wc_get_order($order_id);

                $partnerCode = $this->get_option('api_PARTNER_CODE');
                $accessKey = $this->get_option('api_ACCESS_KEY');
                $orderInfo = "Thanh toán qua MoMo";
                $amount = $order->total;
                $orderId =
                    $order->id . "";
                $returnUrl = $this->get_return_url($order);
                $notifyurl =  $_SERVER['HTTP_REFERER'];
                // Lưu ý: link notifyUrl không phải là dạng localhost
                $extraData = "merchantName=Goat White Payment Momo";
                $requestId = time() . "";
                $requestType = "captureMoMoWallet";
                // Trước tiên phải hash cái mã ra 256
                $rawHash =
                    "partnerCode=" . $partnerCode .
                    "&accessKey=" . $accessKey .
                    "&requestId=" . $requestId .
                    "&amount=" . $amount .
                    "&orderId=" . $orderId .
                    "&orderInfo=" . $orderInfo .
                    "&returnUrl=" . $returnUrl .
                    "&notifyUrl=" . $notifyurl .
                    "&extraData=" . $extraData;
                $serectkey =
                    $this->get_option('api_SECRET_KEY');
                $signature = hash_hmac("sha256", $rawHash, $serectkey);
                $data = array(
                    'partnerCode' => $partnerCode,
                    'accessKey' => $accessKey,
                    'requestId' => $requestId,
                    'amount' => $amount,
                    'orderId' => $orderId,
                    'orderInfo' => $orderInfo,
                    'returnUrl' => $returnUrl,
                    'notifyUrl' => $notifyurl,
                    'extraData' => $extraData,
                    'requestType' => $requestType,
                    'signature' => $signature
                );


                $headers = array(
                    // 'Authorization' => $basicauth,
                    'Content-type' => 'application/json',
                    // 'Content-length' => $contentlen
                );

                $url  = $this->get_option('api_API_ENDPOINT');
                $pload = array(
                    'method' => 'POST',
                    'timeout' => 30,
                    'redirection' => 5,
                    'httpversion' => '1.0',
                    'blocking' => true,
                    'headers' => $headers,
                    'body' => json_encode($data),
                    // 'cookies' => array()
                );
                $response = wp_remote_post($url, $pload);
                $responsepayUrl = json_decode($response['body'], true);

                $order->update_status('on-hold');
                return array(
                    'result'   => 'success',
                    'redirect' => $responsepayUrl['payUrl'],
                );
            }
            public function thankyou_page($order_id)
            {
                $order = new WC_Order($order_id);
                $order->update_status('processing');
            }
        }
    }
}

add_filter('woocommerce_payment_gateways', 'add_to_woo_momo_payment_gateway');

function add_to_woo_momo_payment_gateway($gateways)
{
    $gateways[] = 'WC_Momo_pay_Gateway';
    return $gateways;
}