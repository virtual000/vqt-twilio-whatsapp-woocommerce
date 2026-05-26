<?php
/*
Plugin Name: VQT Twilio WhatsApp for WooCommerce
Description: Advanced Twilio WhatsApp notifications for WooCommerce orders and account events with Twilio template support, dynamic variables, logs and test message.
Author: Virtual Qube Technologies
Author URI: https://vqubetech.com
Version: 2.1.0
Requires Plugins: woocommerce
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VQT_Twilio_WhatsApp_WooCommerce')) {

    class VQT_Twilio_WhatsApp_WooCommerce {

        private $log_option = 'vqt_wa_logs';

        public function __construct() {
            add_filter('woocommerce_settings_tabs_array', [$this, 'add_settings_tab'], 50);
            add_action('woocommerce_settings_tabs_vqt_whatsapp', [$this, 'render_settings_tab']);
            add_action('woocommerce_update_options_vqt_whatsapp', [$this, 'save_settings']);
            add_action('admin_init', [$this, 'handle_test_message']);

            add_action('woocommerce_new_order', [$this, 'handle_new_order_admin'], 20);
            add_action('woocommerce_new_order', [$this, 'handle_new_order_customer'], 21);
            add_action('woocommerce_order_status_cancelled', [$this, 'handle_cancelled_order'], 20);
            add_action('woocommerce_order_status_failed', [$this, 'handle_failed_order'], 20);
            add_action('woocommerce_order_status_processing', [$this, 'handle_processing_order'], 20);
            add_action('woocommerce_order_status_completed', [$this, 'handle_completed_order'], 20);
            add_action('user_register', [$this, 'handle_new_account'], 20);
        }

        public function add_settings_tab($tabs) {
            $tabs['vqt_whatsapp'] = 'WhatsApp';
            return $tabs;
        }

        public function render_settings_tab() {
            woocommerce_admin_fields($this->get_settings());

            echo '<h2 style="margin-top:30px;">Test WhatsApp Message</h2>';
            echo '<p>This test sends a normal free-text WhatsApp message. For live business initiated notifications, use approved Twilio Content SID templates.</p>';
            echo '<table class="form-table">';
            echo '<tr valign="top"><th scope="row"><label for="vqt_test_number">Test Number</label></th><td><input type="text" id="vqt_test_number" name="vqt_test_number" value="" placeholder="+919876543210" style="min-width:350px;" /><p class="description">Enter full WhatsApp number with country code.</p></td></tr>';
            echo '<tr valign="top"><th scope="row"><label for="vqt_test_message">Test Message</label></th><td><textarea id="vqt_test_message" name="vqt_test_message" rows="4" style="min-width:350px;">Test message from WooCommerce WhatsApp plugin.</textarea></td></tr>';
            echo '</table>';
            submit_button('Send Test WhatsApp', 'secondary', 'vqt_send_test_whatsapp');

            echo '<h2 style="margin-top:30px;">Recent Logs</h2>';
            $logs = get_option($this->log_option, []);
            if (!empty($logs) && is_array($logs)) {
                echo '<table class="widefat striped" style="max-width:1200px;">';
                echo '<thead><tr><th>Time</th><th>To</th><th>Type</th><th>Status</th><th>Message</th></tr></thead><tbody>';
                foreach (array_reverse(array_slice($logs, -30)) as $log) {
                    echo '<tr>';
                    echo '<td>' . esc_html($log['time']) . '</td>';
                    echo '<td>' . esc_html($log['to']) . '</td>';
                    echo '<td>' . esc_html($log['type']) . '</td>';
                    echo '<td>' . esc_html($log['status']) . '</td>';
                    echo '<td>' . esc_html($log['message']) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No logs yet.</p>';
            }
        }

        public function save_settings() {
            woocommerce_update_options($this->get_settings());
        }

        private function get_settings() {
            return [
                ['name' => 'Twilio WhatsApp Settings', 'type' => 'title', 'id' => 'vqt_whatsapp_settings', 'desc' => 'Configure Twilio WhatsApp notifications for WooCommerce.'],
                ['name' => 'Twilio SID', 'id' => 'vqt_twilio_sid', 'type' => 'text'],
                ['name' => 'Twilio Auth Token', 'id' => 'vqt_twilio_token', 'type' => 'password'],
                ['name' => 'Twilio WhatsApp Number', 'id' => 'vqt_twilio_from', 'type' => 'text', 'desc' => 'Example: +14155238886. Do not add whatsapp: prefix.'],
                ['name' => 'Admin WhatsApp Number', 'id' => 'vqt_admin_number', 'type' => 'text', 'desc' => 'Example: +919876543210'],
                ['name' => 'Default Country Code', 'id' => 'vqt_default_country_code', 'type' => 'text', 'desc' => 'Example: +91. Used when phone number has no country code.', 'default' => '+91'],

                ['name' => 'Enable New Order Admin', 'id' => 'vqt_enable_new_order_admin', 'type' => 'checkbox', 'default' => 'yes'],
                ['name' => 'Enable New Order Customer', 'id' => 'vqt_enable_new_order_customer', 'type' => 'checkbox', 'default' => 'no'],
                ['name' => 'Enable Processing Customer', 'id' => 'vqt_enable_processing', 'type' => 'checkbox', 'default' => 'yes'],
                ['name' => 'Enable Completed Customer', 'id' => 'vqt_enable_completed', 'type' => 'checkbox', 'default' => 'yes'],
                ['name' => 'Enable Cancelled Admin', 'id' => 'vqt_enable_cancelled', 'type' => 'checkbox', 'default' => 'yes'],
                ['name' => 'Enable Failed Admin', 'id' => 'vqt_enable_failed', 'type' => 'checkbox', 'default' => 'yes'],
                ['name' => 'Enable New Account Customer', 'id' => 'vqt_enable_account', 'type' => 'checkbox', 'default' => 'yes'],

                ['name' => 'New Order Admin Message', 'id' => 'vqt_msg_new_order_admin', 'type' => 'textarea', 'default' => "Hello Admin,\n\nA new WooCommerce order has been received.\n\nOrder number: {order_id}\nCustomer: {customer_name}\nOrder total: {order_total}\n\nPlease check the WooCommerce dashboard for full order details."],
                ['name' => 'New Order Customer Message', 'id' => 'vqt_msg_new_order_customer', 'type' => 'textarea', 'default' => "Hi {customer_name},\n\nYour order #{order_id} has been received.\n\nOrder total: {order_total}\n\nThank you for shopping with us."],
                ['name' => 'Processing Message', 'id' => 'vqt_msg_processing', 'type' => 'textarea', 'default' => "Hi {customer_name},\n\nYour order #{order_id} is now being processed.\n\nOrder total: {order_total}\n\nWe will notify you once it is completed."],
                ['name' => 'Completed Message', 'id' => 'vqt_msg_completed', 'type' => 'textarea', 'default' => "Hi {customer_name},\n\nYour order #{order_id} has been completed.\n\nOrder total: {order_total}\n\nThank you for shopping with us."],
                ['name' => 'Cancelled Message', 'id' => 'vqt_msg_cancelled', 'type' => 'textarea', 'default' => "Hello {customer_name},\n\nOrder #{order_id} has been cancelled.\n\nIf you have any questions, please contact support."],
                ['name' => 'Failed Message', 'id' => 'vqt_msg_failed', 'type' => 'textarea', 'default' => "Hello {customer_name},\n\nPayment for order #{order_id} was unsuccessful.\n\nPlease try again or contact support if you need assistance."],
                ['name' => 'New Account Message', 'id' => 'vqt_msg_account', 'type' => 'textarea', 'default' => "Welcome {customer_name},\n\nYour account has been created on {site_name}.\n\nYou can now log in and manage your orders and account details."],

                ['name' => 'New Order Admin Content SID', 'id' => 'vqt_content_sid_new_order_admin', 'type' => 'text', 'desc' => 'Template variables: {{1}} Admin, {{2}} order ID, {{3}} customer name, {{4}} order total.'],
                ['name' => 'New Order Customer Content SID', 'id' => 'vqt_content_sid_new_order_customer', 'type' => 'text', 'desc' => 'Template variables: {{1}} customer name, {{2}} order ID, {{3}} order total.'],
                ['name' => 'Processing Content SID', 'id' => 'vqt_content_sid_processing', 'type' => 'text', 'desc' => 'Template variables: {{1}} customer name, {{2}} order ID, {{3}} order total.'],
                ['name' => 'Completed Content SID', 'id' => 'vqt_content_sid_completed', 'type' => 'text', 'desc' => 'Template variables: {{1}} customer name, {{2}} order ID, {{3}} order total.'],
                ['name' => 'Cancelled Content SID', 'id' => 'vqt_content_sid_cancelled', 'type' => 'text', 'desc' => 'Template variables: {{1}} customer name, {{2}} order ID.'],
                ['name' => 'Failed Content SID', 'id' => 'vqt_content_sid_failed', 'type' => 'text', 'desc' => 'Template variables: {{1}} customer name, {{2}} order ID.'],
                ['name' => 'New Account Content SID', 'id' => 'vqt_content_sid_account', 'type' => 'text', 'desc' => 'Template variables: {{1}} customer name, {{2}} site name.'],
                ['type' => 'sectionend', 'id' => 'vqt_whatsapp_settings'],
            ];
        }

        public function handle_test_message() {
            if (!is_admin() || !isset($_POST['vqt_send_test_whatsapp']) || !current_user_can('manage_woocommerce')) {
                return;
            }

            $number = isset($_POST['vqt_test_number']) ? sanitize_text_field(wp_unslash($_POST['vqt_test_number'])) : '';
            $message = isset($_POST['vqt_test_message']) ? sanitize_textarea_field(wp_unslash($_POST['vqt_test_message'])) : '';

            if (empty($number) || empty($message)) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>Please enter test number and message.</p></div>';
                });
                return;
            }

            $this->send_whatsapp($number, $message, 'test_message');
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success"><p>Test WhatsApp request sent. Please check logs for result.</p></div>';
            });
        }

        private function is_enabled($key) {
            return get_option($key, 'no') === 'yes';
        }

        private function normalize_phone($phone) {
            $phone = trim((string) $phone);
            $phone = str_replace([' ', '-', '(', ')'], '', $phone);
            if (empty($phone)) {
                return '';
            }
            if (strpos($phone, 'whatsapp:') === 0) {
                $phone = str_replace('whatsapp:', '', $phone);
            }
            if (strpos($phone, '+') === 0) {
                return $phone;
            }
            $phone = ltrim($phone, '0');
            $country = trim((string) get_option('vqt_default_country_code', '+91'));
            if (strpos($country, '+') !== 0) {
                $country = '+' . ltrim($country, '+');
            }
            return $country . $phone;
        }

        private function clean_text($text) {
            $text = wp_strip_all_tags((string) $text);
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            $text = str_replace("\xc2\xa0", ' ', $text);
            return trim($text);
        }

        private function get_customer_name_from_order($order) {
            $name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            return $name ?: 'Customer';
        }

        private function get_order_total_text($order) {
            return $this->clean_text($order->get_formatted_order_total());
        }

        private function build_items_list($order) {
            $lines = [];
            if (!$order) {
                return '';
            }
            foreach ($order->get_items() as $item) {
                $lines[] = '• ' . $this->clean_text($item->get_name()) . ' x ' . (int) $item->get_quantity();
            }
            return implode("\n", $lines);
        }

        private function replace_order_vars($message, $order) {
            if (!$order) {
                return $message;
            }
            $replacements = [
                '{order_id}' => (string) $order->get_id(),
                '{customer_name}' => $this->get_customer_name_from_order($order),
                '{order_total}' => $this->get_order_total_text($order),
                '{order_status}' => wc_get_order_status_name($order->get_status()),
                '{site_name}' => get_bloginfo('name'),
                '{items_list}' => $this->build_items_list($order),
            ];
            return strtr($message, $replacements);
        }

        private function get_user_display_name($user_id) {
            $user = get_userdata($user_id);
            $name = trim(get_user_meta($user_id, 'billing_first_name', true) . ' ' . get_user_meta($user_id, 'billing_last_name', true));
            if (empty($name) && $user) {
                $name = $user->display_name;
            }
            return $name ?: 'Customer';
        }

        private function replace_user_vars($message, $user_id) {
            return strtr($message, [
                '{customer_name}' => $this->get_user_display_name($user_id),
                '{site_name}' => get_bloginfo('name'),
            ]);
        }

        private function add_log($to, $type, $status, $message) {
            $logs = get_option($this->log_option, []);
            if (!is_array($logs)) {
                $logs = [];
            }
            $logs[] = [
                'time' => current_time('mysql'),
                'to' => $to,
                'type' => $type,
                'status' => $status,
                'message' => mb_substr($this->clean_text($message), 0, 700),
            ];
            if (count($logs) > 150) {
                $logs = array_slice($logs, -150);
            }
            update_option($this->log_option, $logs, false);
        }

        private function send_whatsapp($to, $message, $type = 'generic', $content_sid = '', $content_variables = []) {
            $sid = trim((string) get_option('vqt_twilio_sid', ''));
            $token = trim((string) get_option('vqt_twilio_token', ''));
            $from = trim((string) get_option('vqt_twilio_from', ''));

            $to = $this->normalize_phone($to);
            $from = $this->normalize_phone($from);
            $message = $this->clean_text($message);

            if (empty($sid) || empty($token) || empty($from) || empty($to)) {
                $this->add_log($to, $type, 'failed_missing_config', $message);
                return false;
            }

            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
            $body = [
                'From' => 'whatsapp:' . $from,
                'To' => 'whatsapp:' . $to,
            ];

            if (!empty($content_sid)) {
                $body['ContentSid'] = $content_sid;
                if (!empty($content_variables)) {
                    $clean_variables = [];
                    foreach ($content_variables as $key => $value) {
                        $clean_variables[(string) $key] = $this->clean_text($value);
                    }
                    $body['ContentVariables'] = wp_json_encode($clean_variables);
                }
            } else {
                $body['Body'] = $message;
            }

            $response = wp_remote_post($url, [
                'timeout' => 30,
                'body' => $body,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($sid . ':' . $token),
                ],
            ]);

            if (is_wp_error($response)) {
                $this->add_log($to, $type, 'failed_wp_error', $message . ' | ' . $response->get_error_message());
                return false;
            }

            $code = wp_remote_retrieve_response_code($response);
            $resp_body = wp_remote_retrieve_body($response);

            if ($code >= 200 && $code < 300) {
                $this->add_log($to, $type, 'sent', $message);
                return true;
            }

            $this->add_log($to, $type, 'failed_http_' . $code, $message . ' | ' . $resp_body);
            return false;
        }

        public function handle_new_order_admin($order_id) {
            if (!$this->is_enabled('vqt_enable_new_order_admin')) return;
            $order = wc_get_order($order_id);
            if (!$order) return;
            $message = $this->replace_order_vars((string) get_option('vqt_msg_new_order_admin', ''), $order);
            $this->send_whatsapp(get_option('vqt_admin_number', ''), $message, 'new_order_admin', trim((string) get_option('vqt_content_sid_new_order_admin', '')), [
                '1' => 'Admin',
                '2' => (string) $order->get_id(),
                '3' => $this->get_customer_name_from_order($order),
                '4' => $this->get_order_total_text($order),
            ]);
        }

        public function handle_new_order_customer($order_id) {
            if (!$this->is_enabled('vqt_enable_new_order_customer')) return;
            $order = wc_get_order($order_id);
            if (!$order) return;
            $message = $this->replace_order_vars((string) get_option('vqt_msg_new_order_customer', ''), $order);
            $this->send_whatsapp($order->get_billing_phone(), $message, 'new_order_customer', trim((string) get_option('vqt_content_sid_new_order_customer', '')), [
                '1' => $this->get_customer_name_from_order($order),
                '2' => (string) $order->get_id(),
                '3' => $this->get_order_total_text($order),
            ]);
        }

        public function handle_processing_order($order_id) {
            if (!$this->is_enabled('vqt_enable_processing')) return;
            $order = wc_get_order($order_id);
            if (!$order) return;
            $message = $this->replace_order_vars((string) get_option('vqt_msg_processing', ''), $order);
            $this->send_whatsapp($order->get_billing_phone(), $message, 'processing', trim((string) get_option('vqt_content_sid_processing', '')), [
                '1' => $this->get_customer_name_from_order($order),
                '2' => (string) $order->get_id(),
                '3' => $this->get_order_total_text($order),
            ]);
        }

        public function handle_completed_order($order_id) {
            if (!$this->is_enabled('vqt_enable_completed')) return;
            $order = wc_get_order($order_id);
            if (!$order) return;
            $message = $this->replace_order_vars((string) get_option('vqt_msg_completed', ''), $order);
            $this->send_whatsapp($order->get_billing_phone(), $message, 'completed', trim((string) get_option('vqt_content_sid_completed', '')), [
                '1' => $this->get_customer_name_from_order($order),
                '2' => (string) $order->get_id(),
                '3' => $this->get_order_total_text($order),
            ]);
        }

        public function handle_cancelled_order($order_id) {
            if (!$this->is_enabled('vqt_enable_cancelled')) return;
            $order = wc_get_order($order_id);
            if (!$order) return;
            $message = $this->replace_order_vars((string) get_option('vqt_msg_cancelled', ''), $order);
            $this->send_whatsapp(get_option('vqt_admin_number', ''), $message, 'cancelled', trim((string) get_option('vqt_content_sid_cancelled', '')), [
                '1' => $this->get_customer_name_from_order($order),
                '2' => (string) $order->get_id(),
            ]);
        }

        public function handle_failed_order($order_id) {
            if (!$this->is_enabled('vqt_enable_failed')) return;
            $order = wc_get_order($order_id);
            if (!$order) return;
            $message = $this->replace_order_vars((string) get_option('vqt_msg_failed', ''), $order);
            $this->send_whatsapp(get_option('vqt_admin_number', ''), $message, 'failed', trim((string) get_option('vqt_content_sid_failed', '')), [
                '1' => $this->get_customer_name_from_order($order),
                '2' => (string) $order->get_id(),
            ]);
        }

        public function handle_new_account($user_id) {
            if (!$this->is_enabled('vqt_enable_account')) return;
            $phone = get_user_meta($user_id, 'billing_phone', true);
            if (empty($phone)) {
                $phone = get_user_meta($user_id, 'phone', true);
            }
            if (empty($phone)) return;
            $message = $this->replace_user_vars((string) get_option('vqt_msg_account', ''), $user_id);
            $this->send_whatsapp($phone, $message, 'new_account', trim((string) get_option('vqt_content_sid_account', '')), [
                '1' => $this->get_user_display_name($user_id),
                '2' => get_bloginfo('name'),
            ]);
        }
    }

    new VQT_Twilio_WhatsApp_WooCommerce();
}
