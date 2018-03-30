<?php
/*
Plugin Name: AgeChecked WooCommerce AddOn
Description: This plugin is used to perform age checks on customers during the WooCommerce checkout process.
Version: 1.3
Author: AgeChecked
Author URI: http://www.agechecked.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Loads the plugin
add_action('plugins_loaded', 'agechecked_woocommerce_addon_init', 0);

function agechecked_woocommerce_addon_init()
{	
	//Preparatory checks
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) || !class_exists('WC_Settings_API') || class_exists('AgeChecked_WC_Addon'))
		return;
    
	//Plugin class
    class AgeChecked_WC_Addon extends WC_Settings_API
    {
        private static $instance;
	
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		
		public static function get_title() {
			return __("AgeChecked", 'agechecked-wc-addon');
		}
		
        // Setup our plugin's id, description and other values
        function __construct()
        {
            // The global ID for this Payment method
            $this->id = "agechecked_woocommerce_addon";
            
            // The Title shown on the top of the settings page
            $this->method_title = self::get_title();
            
            // The description, shown on the actual Payment options page on the backend
            $this->method_description = __("AgeChecked Plug-in for WooCommerce", 'agechecked-wc-addon');
            
            // The title to be used for the vertical tabs that can be ordered top to bottom
            $this->title = __("AgeChecked", 'agechecked-wc-addon');
            
            // If you want to show an image next to the plugin name on the frontend, enter a URL to an image.
            $this->icon = null;
            
            // This basically defines your settings which are then loaded with init_settings()
            $this->init_form_fields();
            
			$this->init_settings();
			
            // Turn these settings into variables we can use
            foreach ($this->settings as $setting_key => $value) {
                $this->$setting_key = is_string($value) ? trim($value) : $value;
            }
			
			// Save settings
            if (is_admin()) {
                // Versions over 2.0
                // Save our administration options. Since we are not going to be doing anything special
                // we have not defined 'process_admin_options' in this class so the method in the parent
                // class will be used instead
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
                    $this,
                    'process_admin_options',
                ));
            }
			
			add_action('wp_enqueue_scripts', array($this, 'agechecked_woocommerce_addon_enqueue_scripts'));
			
			add_action('woocommerce_checkout_before_customer_details', array($this, 'agechecked_woocommerce_addon_ui'));
    
			//validating
			add_action('woocommerce_checkout_process', array($this, 'agechecked_woocommerce_addon_verify'));
			
			// Saving data
			add_action('woocommerce_checkout_update_order_meta', array($this, 'agechecked_woocommerce_addon_checkout_update_order_meta'));
			
			//showing data in admin
			add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'agechecked_woocommerce_addon_admin_order_data_after_billing_address'), 10, 1);
			
			load_plugin_textdomain('agechecked-wc-addon', false, dirname(plugin_basename(__FILE__)) . '/lang/');
            
        }
		
		function WC_compat($order, $old, $new = null, $old_is_property = true, $new_is_property = false) {
			$method_property_name = !$new ? 'get_'.$old : $new;
			if (!$new_is_property) {
				return method_exists($order, $method_property_name) ? $order->$method_property_name() : ($old_is_property ? $order->$old : $order->$old());
			} else {
				return property_exists($order, $method_property_name) ? $order->$method_property_name : ($old_is_property ? $order->$old : $order->$old());
			}
		}
		
		/**
		 * Output plugin settings to WC Checkout tab.
		 */
		public function admin_options() {
			echo '<h2>' . esc_html( $this->method_title ) . '</h2>';
			echo wp_kses_post( wpautop( $this->method_description ) );
			parent::admin_options();
			$this->checks();
		}
		
		//Plugin settings
        function init_form_fields()
        {
			$this->form_fields = array(
                'enabled' => array(
                    'id' => 'agechecked_wc_settings_enabled',
                    'title' => __('Enable/Disable', 'agechecked-wc-addon'),
					'label' => __('Enable age checks on checkout', 'agechecked-wc-addon'),
                    'desc_tip' => __('Select to enable age checks.', 'agechecked-wc-addon'),
                    'type' => 'checkbox',
                    'default' => 'no',
                ),
                'url' => array(
                    'title' => __('API URL', 'agechecked-wc-addon'),
                    'desc_tip' => __('Please insert the API URL provided by your agechecked.com account manager.', 'agechecked-wc-addon'),
                    'id' => 'agechecked_wc_settings_url',
                    'type' => 'text',
                    'css' => 'min-width:300px;',
                    'default' => 'https://staging.agechecked.com/api', // WC >= 2.0
					'placeholder' => 'e.g. https://staging.agechecked.com/api',
                    'description' => __('', 'agechecked-wc-addon')
                ),
                'public_key' => array(
                    'title' => __('Public Key', 'agechecked-wc-addon'),
                    'desc_tip' => __('Please insert the public key provided by your agechecked.com account manager.', 'agechecked-wc-addon'),
                    'id' => 'agechecked_wc_settings_public_key',
                    'type' => 'text',
                    'css' => 'min-width:600px;',
                    'default' => '', // WC >= 2.0
                    'description' => __('', 'agechecked-wc-addon')
                ),
                'private_key' => array(
                    'title' => __('Private Key', 'agechecked-wc-addon'),
                    'desc_tip' => __('Please insert the private key provided by your agechecked.com account manager.', 'agechecked-wc-addon'),
                    'id' => 'agechecked_wc_settings_private_key',
                    'type' => 'text',
                    'css' => 'min-width:600px;',
                    'default' => '', // WC >= 2.0
                    'description' => __('', 'agechecked-wc-addon')
                ),
                "product_category" => array(
                    'title' => __('Product Category', 'agechecked-wc-addon'),
                    'desc_tip' => __('Please enter the product category that should be age checked. Ensure category exists.', 'agechecked-wc-addon'),
                    'id' => 'agechecked_wc_settings_product_category',
                    'type' => 'text',
                    'default' => 'AgeChecked',
					'placeholder' => 'Limit by category (e.g. AgeChecked)',
                    'css' => 'min-width:300px;',
                    'description' => __('Allocates age checks to the specified category of products. Leave empty to age check all products.', 'agechecked-wc-addon')
                )
            );
        }
		
		// Check if SSL is enabled and notify the user.
		public function checks() {
			if ( 'no' == $this->enabled ) {
				return false;
			}

			// PHP Version
			if ( version_compare( phpversion(), '5.3', '<' ) ) {
				echo '<div class="error"><p>' . sprintf( __( 'AgeChecked Error: AgeChecked requires PHP 5.3 and above. You are using version %s.', 'agechecked-wc-addon' ), phpversion() ) . '</p></div>';
				return false;
			}
			
			// Check required fields
			if (!$this->url) {
				echo '<div class="error"><p>' . __( 'AgeChecked Error: Please enter your API URL', 'transactium-wc-addon' ) . '</p></div>';
				return false;
			}
			
			// Check required fields
			if ( ! $this->public_key || ! $this->private_key ) {
				echo '<div class="error"><p>' . __( 'AgeChecked Error: Please enter your public and private keys', 'transactium-wc-addon' ) . '</p></div>';
				return false;
			}
			
			return true;
		}

		/**
		 * Disabling payment gateway functionality as we only require this for settings in WC Checkout tab
		 *
		 * @return bool
		 */
		public function is_available() { return false; }
		
		//Checking if product category specified in plugin settings matches the given product category
		function check_agechecked_product_category($product_category)
		{
			$agechecked_product_category_name = $this->product_category;
			
			if ($product_category->name === $agechecked_product_category_name)
				return true;
			
			return false;
		}
		
		function agechecked_woocommerce_addon_check_if_required()
		{
			if($this->enabled === "no" || !$this->checks()) return false;
			
			global $woocommerce;
			
			$agechecked_product_category_name = $this->product_category;
			
			//If no category is set then all categories are age checked
			if (empty($agechecked_product_category_name))
				return true; 
			
			foreach ($woocommerce->cart->get_cart() as $cart_item_key => $values) {
				$_product = $values['data'];
				$terms    = get_the_terms($this->WC_compat($_product, 'id'), 'product_cat');
				
				if (!empty($terms))
				{
					// second level loop search, in case some items have several categories
					foreach ($terms as $term) {
						if ($this->check_agechecked_product_category($term)) {
							return true;
						}
					}
				}
			}
			
			return false;
		}
		
		function agechecked_woocommerce_addon_enqueue_scripts()
		{
			if (!$this->agechecked_woocommerce_addon_check_if_required())
				return false;
			
			$public_key = $this->public_key;
			$url        = $this->url;
			
			// Enqueues required JS files
			wp_enqueue_script('jqhack', plugins_url('agechecked-woocommerce-addon/assets/js/jqhack.js'), array('jquery'));
			$url = add_query_arg(array('merchantkey' => $public_key, 'version' => '1.0'), $url . '/jsapi/getjavascript');
			wp_enqueue_script('agechecked', $url, array('jquery', 'jqhack'), null);
			wp_enqueue_style('agechecked', plugins_url('agechecked-woocommerce-addon/assets/css/style.css'), array());
		}
		
		//Loading AgeChecked UI on checkout
		function agechecked_woocommerce_addon_ui($checkout)
		{
			
			if (!$this->agechecked_woocommerce_addon_check_if_required())
				return false;
			
			$checkout = WC()->checkout();
			
			?>
			<input type='hidden' id='agechecked_agecheckid' name='agechecked_agecheckid'/>
			<input type='hidden' id='agechecked_ageverifiedid' name='agechecked_ageverifiedid'/>
			<script>
				if (typeof Agechecked !== "undefined" && Agechecked.API.isloaded()) {
				   Agechecked.API.registerreturn(function(d){
					   Agechecked.API.modalclose();
					   //alert(d.data);
					   var msg = JSON.parse(d.data);
					   if (msg.status == 6 || msg.status == 7) {
							 jQuery('#agechecked_agecheckid').val(msg.agecheckid);
							 jQuery('#agechecked_ageverifiedid').val(msg.ageverifiedid);
					   }
					   else {
						 if (msg.agecheckedpopupurl) {
							Agechecked.API.modalopen(msg.agecheckedpopupurl)
						 }
					   }
				   });
				   Agechecked.API.createagecheckjson({
						mode: 'javascript',
						avtype: 'agechecked',
				   }).done(function(json){
						Agechecked.API.modalopen(json.agecheckurl);
				   });
				}
				else
				  alert("agechecked.com service not loaded.");
			</script>
			<?php
		}
		
		//Running Agecheck Verification
		function agechecked_woocommerce_addon_verify()
		{
			
			if (!$this->agechecked_woocommerce_addon_check_if_required())
				return false;
			
			$private_key = $this->private_key;
			$url         = $this->url;
			
			$url         = add_query_arg(array('merchantkey' => $private_key, 'agecheckid' => $_POST['agechecked_agecheckid']), $url . "/jsonapi/getagecheck");
			$resp        = wp_remote_get($url);
			$jd          = (object) json_decode(wp_remote_retrieve_body($resp), true);
			if (($jd->status != 6 && $jd->status != 7) || $jd->agecheckid != $_POST['agechecked_agecheckid'] || $jd->ageverifiedid != $_POST['agechecked_ageverifiedid'])
				wc_add_notice(__('Could not age check you', 'agechecked-wc-addon'), 'error');
			
		}
		
		//Adding result to order meta
		function agechecked_woocommerce_addon_checkout_update_order_meta($order_id)
		{
			if (!empty($_POST['agechecked_ageverifiedid'])) {
				update_post_meta($order_id, 'ageverifiedid', sanitize_text_field($_POST['agechecked_ageverifiedid']));
			}
		}
		
		//Showing result out to user on order completion
		function agechecked_woocommerce_addon_admin_order_data_after_billing_address($order)
		{
			echo '<p><strong>' . __('AgeVerifiedId', 'agechecked-wc-addon') . ':</strong> ' . get_post_meta($this->WC_compat($order, 'id'), 'ageverifiedid', true) . '</p>';
		}
		
    }
	
    add_filter('woocommerce_payment_gateways', 'agechecked_woocommerce_addon_gateway');
    function agechecked_woocommerce_addon_gateway($methods)
    {
        $methods[] = AgeChecked_WC_Addon::get_instance();
        return $methods;
    }
    
}

// Add custom action links
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'agechecked_woocommerce_addon_action_links');
function agechecked_woocommerce_addon_action_links($links)
{
	$plugin_links = array(
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=agechecked_woocommerce_addon') . '">' . __('Settings', 'agechecked-wc-addon') . '</a>'
	);
	
	// Merge our new link with the default ones
	return array_merge($plugin_links, $links);
}

?>