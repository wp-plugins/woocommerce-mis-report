<?php
/**
Plugin Name: WooCommerce Sales MIS Report 
Plugin URI: http://plugins.infosofttech.com
Description: Woocommerce Sales Reporter shows you all key sales information in one main Dashboard in very intuitive, easy to understand format which gives a quick overview of your business and helps make smart decisions
Version: 2.0
Author: Infosoft Consultant 
Author URI: http://www.infosofttech.com
License: A  "Slug" license name e.g. GPL2

Tested WooCommerce Version: 2.3.11
Tested Wordpress Version: 4.2.2

Last Update Date:01 July, 2015
**/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'WC_IS_MIS_Report' ) ) {
	require_once("ic-woocommerce-mis-report-core.php");
	class WC_IS_MIS_Report extends WC_IS_MIS_Report_Core{
		
		public $plugin_name = "";
		
		public $constants = "";
		
		public function __construct() {
			global $options;
			$this->plugin_name = __("WooCommerce Advance Sales Report Plugin",'icwoocommercemis_textdomains');
			
			$this->constants['post_order_status_found']	= 1;//1 mean woocommerce status replaced with post status
			$this->constants['plugin_key']	= "icwoocommercemis";			
			 
			if(is_admin()){				
				add_action('admin_menu', array(&$this, 'wcismis_add_page'));
				
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));	
				add_action('wp_ajax_wcismis_action_comman', array($this, 'wcismis_action_comman'));
				
				add_filter( 'plugin_action_links_woocommerce-mis-report/woocommerce_ic_mis_report.php', array( $this, 'plugin_action_links' ), 9, 2 );
				
				if(isset($_GET['page']) && $_GET['page'] == "wcismis_page"){
					add_action('admin_footer',  array( &$this, 'admin_footer'));
					$this->per_page = get_option('wcismis_per_page',5);				
					$this->define_constant();
				}
			}
		}
		function wcismis_add_page(){
			$main_page = add_menu_page($this->plugin_name, __('MIS Report','icwoocommercemis_textdomains'), 'manage_options', 'wcismis_page', array($this, 'wcismis_page'), plugins_url( 'woocommerce-mis-report/assets/images/menu_icons.png' ), '56.01' );
		}
		function admin_footer() {
			
			wp_enqueue_style( 'wcismis_admin_styles', WC_IS_MIS_URL . '/assets/css/admin.css' );
			/*Graph Style Sheet*/
			wp_enqueue_style( 'wcismis_admin_graph_css', WC_IS_MIS_URL . '/assets/graph/css/jquery.jqplot.min.css');
			/*Don't Touch This JqPlot Lib*/
			wp_enqueue_script( 'wcismis_admin_graph_script_pie_lib', WC_IS_MIS_URL . '/assets/graph/scripts/jquery.jqplot.min.js');
			
			/*Don't Touch This (Pie Chart Lib)*/
			wp_enqueue_script( 'wcismis_admin_graph_script_pie_chart', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.pieRenderer.min.js');	
			
			/*Don't Touch This (point Labels)*/
			wp_enqueue_script( 'wcismis_admin_graph_script_line_chart_1', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.pointLabels.min.js');	
			
			/*Don't Touch This (Date Lib)*/
			wp_enqueue_script( 'wcismis_admin_graph_script_pointLabels', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.dateAxisRenderer.min.js');	
			
			// in javascript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
			wp_localize_script( 'ajax-script', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); // setting ajaxurl
			
		}
		
		function define_constant(){
			if(!defined('WC_IS_MIS_FILE_PATH')) define( 'WC_IS_MIS_FILE_PATH', dirname( __FILE__ ) );
			if(!defined('WC_IS_MIS_DIR_NAME')) 	define( 'WC_IS_MIS_DIR_NAME', basename( WC_IS_MIS_FILE_PATH ) );
			if(!defined('WC_IS_MIS_FOLDER')) 	define( 'WC_IS_MIS_FOLDER', dirname( plugin_basename( __FILE__ ) ) );
			if(!defined('WC_IS_MIS_NAME')) 		define(	'WC_IS_MIS_NAME', plugin_basename(__FILE__) );
			if(!defined('WC_IS_MIS_URL')) 		define( 'WC_IS_MIS_URL', WP_CONTENT_URL . '/plugins/' . WC_IS_MIS_FOLDER );
			$this->constants['plugin_url'] 		= WC_IS_MIS_URL;
		}
		
		function admin_enqueue_scripts($hook) {
				if( 'toplevel_page_wcismis_page' != $hook ) {
					//return;		
				}
				
				if(isset($_GET['page']) && $_GET['page'] == "wcismis_page"){}else{ return false;}
				
				wp_enqueue_script('wcismis_ajax_script', plugins_url( '/assets/graph/scripts/graph.js', __FILE__ ), array('jquery'));
				wp_localize_script('wcismis_ajax_script', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php' ))); // setting ajaxurl			
		}
		
		function wcismis_action_comman() {
			if(isset($_POST['action']) && $_POST['action'] == "wcismis_action_comman"){
				
				if(isset($_POST['graph_by_type']) && $_POST['graph_by_type'] == "top_product"){
					$this->wcismis_pie_chart_top_product();					
				}
				//
				if(isset($_POST['graph_by_type']) && $_POST['graph_by_type'] == "today_order_count"){
					$this->wcismis_today_order_count();					
				}
				if(isset($_POST['graph_by_type']) && $_POST['graph_by_type'] == "Last_7_days_sales_order_amount"){
					$this->wcismis_Last_7_days_sales_order_amount();					
				}
			}
			die(); // this is required to return a proper result
			exit;
		}
		
		
		
		function wcismis_page(){
			global $options;
			
			$this->constants['date_format'] = isset($this->constants['date_format']) ? $this->constants['date_format'] : get_option( 'date_format', "Y-m-d" );//New Change ID 20150209
			$options				= array();
			$date_format			= $this->constants['date_format'];
			$this->today			= date_i18n("Y-m-d");
			$this->yesterday 		= date("Y-m-d",strtotime("-1 day",strtotime($this->today)));
			$this->per_page_default	= 5;
			
			$shop_order_status		= array();	
			$hide_order_status 		= array();
			$start_date 			= $this->first_order_date();
			$end_date 				= $this->today;
			$summary_start_date 	= $start_date;
			$summary_end_date 		= $end_date;
			
			$_total_orders 			= $this->get_total_order('total',$shop_order_status,$hide_order_status,$start_date,$end_date);
			$total_orders 			= $this->get_value($_total_orders,'total_count',0);
			$total_sales 			= $this->get_value($_total_orders,'total_amount',0);
			$total_sales_avg		= $this->get_average($total_sales,$total_orders);
			
			$users_of_blog 			= count_users();
			$total_customer 		= isset($users_of_blog['avail_roles']['customer']) ? $users_of_blog['avail_roles']['customer'] : 0;
			
			$total_guest_customer 	= $this->get_total_today_order_customer('total',true);
			
			$total_categories  	=	$this->wcismis_get_total_categories_count();
			$total_products  	=	$this->wcismis_get_total_products_count();
			
			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 100);
			
			
			
			
			
			?>
            	 <div class="wrap iccommercepluginwrap">                 	
                    <h2><?php _e('Dashboard - WooCommerce Advance Salse Report (FREE Version)','icwoocommercemis_textdomains') ?></h2> 
                    
                     <?php $this->premium_gold();?>
                                       
                     <div id="poststuff" class="woo_cr-reports-wrap">
                        <div class="woo_cr-reports-top">
                            <div class="row">
                                <div class="icpostbox">
                                    <h3><span><?php _e( 'Summary', 'icwoocommercemis_textdomains'); ?></span></h3>
                                    
                                    <div class="clearfix"></div>
                                    <div class="SubTitle"><span><?php echo sprintf(__('Summary From %1$s To %2$s', 'icwoocommercemis_textdomains'), date($date_format, strtotime($summary_start_date)),date($date_format, strtotime($summary_end_date))); ?></span></div>
                                    <div class="clearfix"></div>
                                    
                                    <div class="ic_dashboard_summary_box">
                                    
                                        <div class="ic_block ic_block-orange">
                                            <div class="ic_block-content">
                                                <h2><span><?php _e( 'Total Sales', 'icwoocommercemis_textdomains'); ?></span></h2>
                                                <div class="ic_stat_content">
                                                    <p class="ic_stat">
                                                        <?php if ( $total_sales > 0 ) echo $this->price($total_sales); else _e( '0', 'icwoocommercemis_textdomains'); ?>
                                                        <span class="ic_count">#<?php if ( $total_orders > 0 ) echo $total_orders; else _e( '0', 'icwoocommercemis_textdomains'); ?></span>
                                                    </p>
                                                    <img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/sales-icon.png" alt="" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="ic_block ic_block-green">
                                            <div class="ic_block-content">
                                                <h2><span><?php _e( 'Average Sales Per Order', 'icwoocommercemis_textdomains'); ?></span></h2>
                                                <div class="ic_stat_content">
                                                    <p class="ic_stat"><?php if ( $total_sales_avg > 0 ) echo $this->price($total_sales_avg); else _e( '0', 'icwoocommercemis_textdomains'); ?></p>
                                                    <img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/average-icon.png" alt="" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="ic_block ic_block-light-green">
                                            <div class="ic_block-content">
                                                <h2 class="small-size"><?php _e( 'Total Registered Customers', 'icwoocommerce_textdomains'); ?></span></h2>
                                                <div class="ic_stat_content">
                                                    <p class="ic_stat">#<?php if ( $total_customer > 0 ) echo $total_customer; else _e( '0', 'icwoocommerce_textdomains'); ?></p>
                                                    <img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/customers-icon.png" alt="" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="ic_block ic_block-brown">
                                            <div class="ic_block-content">
                                                <h2><?php _e( 'Total Guest Customers', 'icwoocommerce_textdomains'); ?></span></h2>
                                                <div class="ic_stat_content">
                                                    <p class="ic_stat">#<?php if ( $total_guest_customer > 0 ) echo $total_guest_customer; else _e( '0', 'icwoocommerce_textdomains'); ?></p>
                                                    <img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/customers-icon.png" alt="" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                         <div class="ic_block ic_block-blue">
                                            <div class="ic_block-content">
                                                <h2><?php _e( 'Total Products', 'icwoocommerce_textdomains'); ?></span></h2>
                                                <div class="ic_stat_content">
                                                    <p class="ic_stat">#<?php if ( $total_products > 0 ) echo $total_products; else _e( '0', 'icwoocommerce_textdomains'); ?></p>
                                                    <img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/product-icon.png" alt="" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                         <div class="ic_block ic_block-purple">
                                            <div class="ic_block-content">
                                                <h2><?php _e( 'Total Category', 'icwoocommerce_textdomains'); ?></span></h2>
                                                <div class="ic_stat_content">
                                                    <p class="ic_stat">#<?php if ( $total_guest_customer > 0 ) echo $total_guest_customer; else _e( '0', 'icwoocommerce_textdomains'); ?></p>
                                                    <img src="<?php echo $this->constants['plugin_url']?>/assets/images/icons/category-icon.png" alt="" />
                                                </div>
                                            </div>
                                        </div>
                                        
                                    <div class="clearfix"></div>
                                </div>
                            </div>
                        </div>
                    	</div>
                     </div>
                     
                     <div class="row">                     	
                        
                        
                        <div class="col-md-6">
                        	<div class="icpostbox">
                                <h3>
                                    <span class="title"><?php _e( 'Last 7 days Sales', 'icwoocommercemis_textdomains'); ?></span>           	
                                </h3>
                                <div class="ic_inside Overflow">                            
                                    <div id="last_7_days_sales_order_amount" class="example-chart" style="width:98%; margin-left:3px;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                        	<div class="icpostbox">
                                <h3>
                                    <span class="title"><?php _e( 'Top 5 Products', 'icwoocommercemis_textdomains'); ?></span>           	
                                </h3>
                                <div class="ic_inside Overflow">                            
                                    <div id="top_product_pie_chart" class="example-chart"></div>
                                </div>
                            </div>
                        </div>
                        
                    </div><!--End Row--> 
                    
                    <div class="row ThreeCol_Boxes">
                        <div class="col-md-6">
                            <div class="icpostbox">
                                <h3>
                                    <span class="title"><?php _e( 'Order Summary', 'icwoocommercemis_textdomains'); ?></span>
                                    <span class="progress_status"></span>
                                </h3>
                                <div class="ic_inside Overflow" id="sales_order_count_value">
                                    <div class="grid"><?php $this->sales_order_count_value($shop_order_status,$hide_order_status,$start_date,$end_date);?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="icpostbox">
                                <h3>
									<span class="title"><?php echo sprintf(__( 'Top %s Category','icwoocommercemis_textdomains' ),$this->get_number_only('top_product_per_page',$this->per_page_default)); ?></span>
                                </h3>                                
                               
                                <div class="ic_inside Overflow" id="top_category_status">
                                	<div class="chart_parent">
                                    	<div class="chart" id="top_category_status_chart"></div>
                                    </div>
                                    <div class="grid"><?php $this->get_category_list($shop_order_status,$hide_order_status,$start_date,$end_date);?></div>
                                </div>
                            </div>                    	
                        </div>
                    </div>
                     
                  
                    
                    <div class="row ThreeCol_Boxes">
                        <div class="col-md-6">
                            <div class="icpostbox">
                                <h3>
									<span class="title"><?php echo sprintf(__( 'Top %s Billing Country','icwoocommercemis_textdomains' ),$this->get_number_only('top_billing_country_per_page',$this->per_page_default)); ?></span>
                                </h3>
                                <div class="ic_inside Overflow" id="top_billing_country">
                                	<div class="chart_parent">
                                    	<div class="chart" id="top_billing_country_chart"></div>
                                    </div>
                                    <div class="grid"><?php $this->top_billing_country($shop_order_status,$hide_order_status,$start_date,$end_date);//New Change ID 20140918?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="icpostbox">
                                <h3>
									<span class="title"><?php echo sprintf(__( 'Top %s Customers','icwoocommercemis_textdomains' ),$this->get_number_only('top_customer_per_page',$this->per_page_default)); ?></span>
                                </h3>
                                <div class="ic_inside Overflow" id="top_customer_list">
                                	<div class="chart_parent">
                                    	<div class="chart" id="top_customer_list_chart"></div>
                                    </div>
                                    <div class="grid"><?php $this->top_customer_list($shop_order_status,$hide_order_status,$start_date,$end_date);?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                     <div class="row">
                        <div class="icpostbox">
                            <h3>
								<span class="title"><?php echo sprintf(__( 'Recent %s Orders','icwoocommercemis_textdomains' ),$this->get_number_only('recent_order_per_page',$this->per_page_default)); ?></span>                        	
                            </h3>
                            <div class="ic_inside Overflow">                            
                                <div class="grid"><?php $this->recent_orders($shop_order_status,$hide_order_status,$start_date,$end_date);?></div>
                            </div>
                        </div>
                    </div>
                    
                    
                 </div><!--End Wrap-->
            <?php
		}
		
		function premium_gold(){
			?>
				<div class="row">
                    <div class="icpostbox">
                        <h3>
                            <span class="title"><?php echo sprintf(__( 'Upgrade to Advance Sales Report (Premium Gold Version)' ,'icwoocommercemis_textdomains'),$this->get_number_only('recent_order_per_page',$this->per_page_default)); ?></span>                        	
                        </h3>
                        <div class="ic_inside Overflow">                            
                            <div>
                                <div class="Notes">
                                        <ul style="margin-right:15px;">
                                            <li>Improvised Dashboard, More summaries</li>
                                            <li>Projected Vs Actual Sales</li>
                                            <li>Top n States, Category wise sales summary</li>
                                            <li>8 Different Crosstab Reports</li>
                                            
                                            <li>Tax Reporting </li>
                                            <li>Coupon based Reporting</li>
                                            <li>Customize Columns</li>
                                            <li>Export to Excel, CSV</li>
                                            
                                            <li>Auto Email Reports</li>
                                            <li>Advance Variation Filters</li>
                                            <li>Monthly Sales Reports (Tax, Coupon, Total  Orders, Customer)</li>
                                            <li>Online PDF Generation</li>
                                            
                                            <li>Many More Features</li>
                                        </ul>
                                        
                                        <div class="clearfix"></div>
                                        
                                        <div class="footer_buttons"> 
                                            <div class="footer_left">
                                                <div class="footer_note">
                                                    <p>Enquiries, Suggestions mail us at - <a href="mailto:sales@infosofttech.com">sales@infosofttech.com</a></p>
                                                    <p>Website: <a href="http://plugins.infosofttech.com" target="_blank">plugins.infosofttech.com</a></p>
                                                </div>
                                            </div> 
                                            
                                            <div class="footer_left footer_left1">
                                                <a href="http://plugins.infosofttech.com/woogoldpremdemo/wp-admin/admin.php?page=icwoocommercepremiumgold_page&login_action=goldpremdemo_login&user_login=demouser&user_pass=demouser" target="_blank" class="ViewDemo">View Demo</a>                                            
                                            </div>
                                            
                                            <div class="clearfix"></div>                                               
                                        </div>
                                        <div class="clearfix"></div>
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php			
		}
		
		function wcismis_Last_7_days_sales_order_amount()
		{
			global $wpdb,$sql,$Limit;

			$weekarray = array();
			$timestamp = time();
			for ($i = 0 ; $i < 7 ; $i++) {
				$weekarray[] =  date('Y-m-d', $timestamp);
				$timestamp -= 24 * 3600;
			}
			
			$sql = " SELECT    
				DATE(posts.post_date) AS 'Date' ,
				sum(meta_value) AS 'TotalAmount'
				
				FROM {$wpdb->prefix}posts as posts 
				
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id
				
				
				WHERE  post_type='shop_order' AND meta_key='_order_total' AND (posts.post_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY))
				GROUP BY  DATE(posts.post_date)
				";
				$order_items = $wpdb->get_results($sql);
				
				$item_dates = array();
				$item_data = array();
				
				foreach($order_items as $item)
				{
					$item_dates[] = trim($item->Date);
					$item_data[$item->Date]	= $item->TotalAmount;
				}
				$new_data = array();
				foreach($weekarray as $date)
				{	if(in_array($date, $item_dates))
					{
						
						$new_data[$date] = $item_data[$date];
					}
					else
					{
						$new_data[$date] = 0;
					}
				}
				
				$new_data2 = array();
				$i = 0;
				foreach($new_data as $key => $value)
				{
					$new_data2[$i]["Date"]	= $key;
					$new_data2[$i]["TotalAmount"]	= $value;
					
					$i++;
					
				}				
				if(isset($_POST['graph_by_type']) && $_POST['graph_by_type'] == "Last_7_days_sales_order_amount"){
					echo	json_encode($new_data2);
				}
				else
				{
					return $order_items;
				}		
				
		}
		
		/*To 5 Products*/
		function wcismis_pie_chart_top_product()
		{
			global $wpdb,$sql,$Limit;
			$Limit = 5;
			
			/*Order ID, Order Product Name */
				$sql = "SELECT  
						woocommerce_order_items.order_item_name AS 'ItemName'
						,woocommerce_order_items.order_item_id
						,SUM(woocommerce_order_itemmeta.meta_value) AS 'Qty'
						,SUM(woocommerce_order_itemmeta6.meta_value) AS 'Total'
						
						FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
						LEFT JOIN    {$wpdb->prefix}posts                        as posts                         ON posts.ID                                        =    woocommerce_order_items.order_id
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id						
						LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id=woocommerce_order_items.order_item_id						
						LEFT JOIN    {$wpdb->prefix}woocommerce_order_itemmeta     as woocommerce_order_itemmeta3    ON woocommerce_order_itemmeta3.order_item_id    =    woocommerce_order_items.order_item_id
					
						WHERE woocommerce_order_itemmeta.meta_key='_qty' AND woocommerce_order_itemmeta6.meta_key='_line_total'
						AND posts.post_type          =    'shop_order'
						AND woocommerce_order_itemmeta3.meta_key        =    '_product_id'
						GROUP BY  woocommerce_order_itemmeta3.meta_value
						Order By Total DESC
						LIMIT 5
			";
			$order_items = $wpdb->get_results($sql);
			if(isset($_POST['graph_by_type']) && $_POST['graph_by_type'] == "top_product"){
				echo	json_encode($order_items);
				//echo "anzar";
			}
			else
			{
				return $order_items;
			}		
			
		}
		
		function wcismis_get_total_categories_count(){
			global $wpdb;
			$sql = "SELECT COUNT(*) As 'category_count' FROM {$wpdb->prefix}term_taxonomy as term_taxonomy  
					LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id
			WHERE taxonomy ='product_cat'";
			return $wpdb->get_var($sql);
		}
		
		function wcismis_get_total_products_count(){
			global $wpdb;
			$sql = "SELECT COUNT(*) AS 'product_count'  FROM {$wpdb->prefix}posts as posts WHERE  post_type='product' AND post_status = 'publish'";
			return $wpdb->get_var($sql);		
		}
		
		function admin_footer_text($footer_text = ""){
			//$footer_text = __( 'Thank You for using our WooCommerce Sales Report Plug-in.', 'icwoocommercemis_textdomains' );
			
			$footer_text = __( 'Website: <a href="http://plugins.infosofttech.com" target="_blank">plugins.infosofttech.com</a><br /> Email: <a href="mailto:sales@infosofttech.com">sales@infosofttech.com</a>', 'icwoocommercemis_textdomains' );
			
			return $footer_text;
		}
		
		function plugin_action_links($plugin_links, $file){
			if ( $file == "woocommerce-mis-report/woocommerce_ic_mis_report.php") {
				$settings_link = array();
				$plugin_links[]		= '<a href="'.admin_url('admin.php?page=wcismis_page').'" title="'.__('Report', 	'icwoocommercemis_textdomains').'">'.__('Report', 'icwoocommercemis_textdomains').'</a>';
			}		
			return $plugin_links;
		}
	}
}

if(!function_exists("plugins_loaded_ic_woocommerce_mis_report")){
	function plugins_loaded_ic_woocommerce_mis_report(){
		if(!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {	
			
			if(!defined('WC_IS_MIS_WC_ACITVE')) define( 'WC_IS_MIS_WC_ACITVE', FALSE );
			
			function wcismis_admin_notices(){
				$message = "";
				$message .= '<div class="error">';
				$message .= '<p>' . sprintf( __('WooCommerce MIS Report depends on <a href="%s">WooCommerce</a> to work!' , 'icwoocommercemis_textdomains' ), 'http://wordpress.org/extend/plugins/woocommerce/' ) . '</p>';
				$message .= '</div>';
				echo  $message;
			}
			
			add_action( 'admin_notices', 'wcismis_admin_notices');
			
			$WC_IS_MIS_Report = new WC_IS_MIS_Report();
			
		}else{
			
			if(!defined('WC_IS_MIS_WC_ACITVE')) define( 'WC_IS_MIS_WC_ACITVE', TRUE );
			
			$WC_IS_MIS_Report = new WC_IS_MIS_Report();
		}
	}
}

add_action("plugins_loaded","plugins_loaded_ic_woocommerce_mis_report", 20);