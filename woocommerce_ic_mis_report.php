<?php
/**
 * Plugin Name: WooCommerce Sales MIS Report 
 * Plugin URI: http://www.plugin.infosofttech.com
 * Description: Woocommerce Sales Reporter shows you all key sales information in one main Dashboard in very intuitive, easy to understand format which gives a quick overview of your business and helps make smart decisions
 * Version: 1.2 
 * Author: Infosoft Consultant 
 * Author URI: http://www.infosofttech.com/deepak.aspx
 * License: A  "Slug" license name e.g. GPL2
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'WC_IS_MIS_Report' ) ) {
	class WC_IS_MIS_Report{
		
		public $plugin_name = "";
		
		public function __construct() {
			global $options;
			$this->plugin_name = "WooCommerce MIS Report";
			
			if(is_admin()){				
				add_action('admin_menu', array(&$this, 'wcismis_add_page'));
				
				add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));	
				add_action('wp_ajax_wcismis_action_comman', array($this, 'wcismis_action_comman'));
				
				if(isset($_GET['page']) && $_GET['page'] == "wcismis_page"){
					add_action('admin_footer',  array( &$this, 'admin_footer'));
					$this->per_page = get_option('wcismis_per_page',5);				
					$this->define_constant();
				}
			}
		}
		function wcismis_add_page(){
			$main_page = add_menu_page($this->plugin_name, 'MIS Report', 'manage_options', 'wcismis_page', array($this, 'wcismis_page'), plugins_url( 'woocommerce_ic_mis_report/assets/images/menu_icons.png' ), '57.5' );
		}
		function admin_footer() {
			
			wp_enqueue_style( 'wcismis_admin_styles', WC_IS_MIS_URL . '/assets/css/admin.css' );
			/*Graph Style Sheet*/
			wp_enqueue_style( 'wcismis_admin_graph_css', WC_IS_MIS_URL . '/assets/graph/css/jquery.jqplot.min.css');
			/*Don't Touch This JqPlot Lib*/
			wp_enqueue_script( 'wcismis_admin_graph_script_pie_lib', WC_IS_MIS_URL . '/assets/graph/scripts/jquery.jqplot.min.js');
			
			/*Don't Touch This (Pie Chart Lib)*/
			wp_enqueue_script( 'wcismis_admin_graph_script_pie_chart', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.pieRenderer.min.js');	
			
			/*Don't Touch This (Meter Gauge Chart Lib)*/
			wp_enqueue_script( 'wcismis_admin_graph_script_meter_gauge', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.meterGaugeRenderer.min.js');	
			
			/*Don't Touch This (Line Chart Lib)*/
			//wp_enqueue_script( 'wcismis_admin_graph_script_line_chart', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.canvasTextRenderer.min.js');	
			//wp_enqueue_script( 'wcismis_admin_graph_script_line_chart_1', WC_IS_MIS_URL . '/assets/graph/scripts/jqplot.canvasAxisLabelRenderer.min.js');	
			
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
			$total_orders 		=	$this->wcismis_get_total_order_count();
			$total_sales  		=	$this->wcismis_get_total_order_amount();			
			$total_customer  	=	$this->wcismis_get_total_customer_count();
			$total_categories  	=	$this->wcismis_get_total_categories_count();
			$total_products  	=	$this->wcismis_get_total_products_count();
			?>
            	 <div class="wrap ic_mis_report wcismis_wrap">
                    <div class="icon32" id="icon-options-general"><br /></div>
                    <h2><?php _e('Dashboard','wcismis') ?></h2>
                    	  <div id="poststuff" class="woo_cr-reports-wrap">
                          		<div class="woo_cr-reports-top">					
                                    <div class="postbox left">
                                        <h3><span><?php _e( 'Total Orders', 'wcismis' ); ?></span></h3>
                                        <div class="inside">
                                          <p class="stat"><?php echo $total_orders; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="postbox left">
                                        <h3><span><?php _e( 'Total Sales', 'wcismis' ); ?></span></h3>
                                        <div class="inside">
                                            <p class="stat"><?php echo wcismis_price($total_sales); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="postbox left">
                                        <h3><span><?php _e( 'Total Customers', 'wcismis' ); ?></span></h3>
                                        <div class="inside">
                                            <p class="stat"><?php echo $total_customer; ?></p>
                                        </div>
                                    </div>
									
									<div class="postbox left">
                                        <h3><span><?php _e( 'Total Products', 'wcismis' ); ?></span></h3>
                                        <div class="inside">
                                            <p class="stat"><?php echo $total_products; ?></p>
                                        </div>
                                    </div>
									
									<div class="postbox left">
                                        <h3><span><?php _e( 'Total Categories', 'wcismis' ); ?></span></h3>
                                        <div class="inside">
                                            <p class="stat"><?php echo $total_categories; ?></p>
                                        </div>
                                    </div>
									
                                    
                                    <div class="clearfix"></div>
                                </div>
                    			
                                 <div class="ThreeCol_Boxes">
                                    <div class="postbox">
                                        <h3>
                                            <span><?php _e( 'Today Order Count', 'wcismis' ); ?></span>
                                        </h3>
                                        <div class="inside">
                                             <div id="today_order_count_meter_gauge" class="example-chart"></div>
                                        </div>
                                    </div>
                                </div>
                                 <div class="ThreeCol_Boxes">
                                    <div class="postbox">
                                        <h3>
                                            <span><?php _e( 'Top Products', 'wcismis' ); ?></span>
                                        </h3>
                                        <div class="inside">
                                             <div id="top_product_pie_chart" class="example-chart"></div>	
                                        </div>
                                    </div>
                                </div>
                                 <div class="ThreeCol_Boxes LastBox_Margin">
                                    <div class="postbox">
                                        <h3>
                                            <span><?php _e( 'Last 7 days Sales Amount', 'wcismis' ); ?></span>
                                        </h3>
                                        <div class="inside">
                                             <div id="last_7_days_sales_order_amount" class="example-chart" style="width:90%"></div>	
                                        </div>
                                    </div>
                                </div>
                    			<div class="clearfix"></div>
                                <div class="postbox">
                                    <h3><span><?php _e( 'Sales Order Summary', 'wcismis' ); ?></span></h3>
                                    <div class="inside">
                                        <?php $this->sales_order_count_value()?>
                                    </div>
                                </div>
                                
                                 <div class="postbox">
                                    <h3><span><?php _e( 'Recent Orders', 'wcismis' ); ?></span></h3>
                                    <div class="inside">                            
                                        <?php $this->recent_orders();?>
                                    </div>
                                </div>  
                                
                                 <div class="postbox">
                                    <h3><span><?php _e( 'Top Billing Countries', 'wcismis' ); ?></span></h3>
                                    <div class="inside">
                                         <?php $this->top_billing_country()?>
                                    </div>
                                </div> 
                                <div class="postbox">
                                    <h3><span><?php _e( 'Top Customers', 'wcismis' ); ?></span></h3>
                                    <div class="inside">
                                        <?php $this->top_customer_list();?>
                                    </div>
                                </div>
                                
                                <div class="NotesSec">
                                	<div class="postbox">
                                    	<h3><span>Pro Version Features</span></h3>
                                        <div class="Notes">
                                            <ul style="margin-right:15px;">
                                                <li>Recent Orders</li>
                                                <li>Top n Products, Summary, Details</li>
                                                <li>Top n Country, Summary, Details</li>
                                                <li>Top n Payment Gateway, Summary, Details</li>
                                                <li>Sales Order Status</li>
                                                <li>Export to CSV</li>
                                            </ul>
                                            
                                            <ul>
                                                <li>Sales Order Summary</li>
                                                <li>Top n Customers, Summary, Details</li>
                                                <li>Top n Coupons, Summary, Details</li>
                                                <li>Day Wise Summary/Detail, Today, Yesterday, This Week, This Month, This Year</li>
                                                <li>Graphical Representaion of sales data</li>
                                            </ul>
                                            <div class="clearfix"></div>
                                            <a href="http://plugins.infosofttech.com/" target="_blank" class="BuyNow"></a>
                                            <a href="http://plugins.infosofttech.com/demo/" target="_blank" class="ViewDemo"></a>
                                        </div>
                                    </div>
                                </div>
                          </div>
                 </div>
            <?php
		}
		/*13-Feb-2014*/
		/*this week sales order */
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
		/*Graph Start From Here*/
		function wcismis_today_order_count()
		{
			global $wpdb,$sql,$Limit;
			$sql = "SELECT 
						SUM(postmeta.meta_value)AS 'OrderTotal' 
						,COUNT(*) AS 'OrderCount'
						,'Today' AS 'SalesOrder'
						
						FROM {$wpdb->prefix}postmeta as postmeta 
						LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
						
						WHERE meta_key='_order_total' 
						AND DATE(posts.post_date) = DATE(NOW())";
			$order_items = $wpdb->get_results($sql);
			
			if(isset($_POST['graph_by_type']) && $_POST['graph_by_type'] == "today_order_count"){
				echo	json_encode($order_items);
				
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
					 
					 LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta ON woocommerce_order_itemmeta.order_item_id=woocommerce_order_items.order_item_id
					 
					  LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta6 ON woocommerce_order_itemmeta6.order_item_id=woocommerce_order_items.order_item_id
					 
					
					WHERE woocommerce_order_itemmeta.meta_key='_qty' AND woocommerce_order_itemmeta6.meta_key='_line_total'
					GROUP BY  woocommerce_order_items.order_item_name
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
		/*14-Feb-2014*/
		function wcismis_get_total_categories_count()
		{
			global $wpdb,$sql,$Limit;
			$sql = "SELECT COUNT(*) As 'category_count' FROM {$wpdb->prefix}term_taxonomy as term_taxonomy  
					LEFT JOIN  {$wpdb->prefix}terms as terms ON terms.term_id=term_taxonomy.term_id
			WHERE taxonomy ='product_cat'";
			return $wpdb->get_var($sql);
			//print_array($order_items);	
			
		
		}
		function wcismis_get_total_products_count()
		{
			global $wpdb,$sql,$Limit;
			$sql = "SELECT COUNT(*) AS 'product_count'  FROM {$wpdb->prefix}posts as posts WHERE  post_type='product' AND post_status = 'publish'";
			return $wpdb->get_var($sql);
		
		}
		/*13-Feb-2014*/
		/*total order count*/
		function wcismis_get_total_order_count(){
			global $wpdb;
			$sql = " SELECT count(*) AS 'total_order_count'		
			FROM {$wpdb->prefix}posts as posts 
			WHERE  post_type='shop_order'";
			
			return $wpdb->get_var($sql);
		}
		/*total order amount*/
		function wcismis_get_total_order_amount(){
				global $wpdb;
				$sql = "SELECT			
				SUM(meta_value) AS 'total_order_amount'			
				FROM {$wpdb->prefix}posts as posts			
				LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON posts.ID=postmeta.post_id
				WHERE  post_type='shop_order' AND meta_key='_order_total'";
				
				return $wpdb->get_var($sql);
		}
		/*Total Customer Count*/
		function wcismis_get_total_customer_count(){
			$user_query = new WP_User_Query( array( 'role' => 'Customer' ) );
			return $user_query->total_users;
		}
		/*13-Feb-2014*/
		function sales_order_count_value(){
			global $wpdb;		
			/*Today*/
			/*Today*/
		$sql = "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Today' AS 'SalesOrder'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
					
					WHERE meta_key='_order_total' 
					AND DATE(posts.post_date) = DATE(NOW())";
				 
			$sql .= "	 UNION ";
			/*Yesterday*/
		    $sql .= "	 SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Yesterday' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
					
					WHERE meta_key='_order_total' 
						AND  DATE(posts.post_date)= DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))
						
						";
						
			$sql .= "	 UNION ";	
			/*Week*/		
			$sql .= " SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Week' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
					
					WHERE meta_key='_order_total' 
						
				 	AND WEEK(DATE(CURDATE())) = WEEK( DATE(posts.post_date))
					";
			/*Month*/	
			$sql .= "	 UNION ";		
			
			$sql .= "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Month' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
					
					WHERE meta_key='_order_total' 
				 	AND MONTH(DATE(CURDATE())) = MONTH( DATE(posts.post_date))
					
					AND YEAR(DATE(CURDATE())) = YEAR( DATE(posts.post_date))
					";
					
					
			/*Year*/		
			$sql .= "	 UNION ";	
			
			$sql .= "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Year' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
					
					WHERE meta_key='_order_total' 
				 	AND YEAR(DATE(CURDATE())) = YEAR( DATE(posts.post_date))
					
					";
			

				$order_items = $wpdb->get_results($sql ); 
				?>	
			 <table style="width:100%" class="widefat">
				<thead>
					<tr class="first">
						<th>Sales</th>
						<th>Order Count</th>
						<th class="amount">Amount</th>
					</tr>
				</thead>
				<tbody>
					<?php					
						foreach ( $order_items as $key => $order_item ) {
						if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
					?>
						<tr class="<?php echo $alternate."row_".$key;?>">
							<td><?php echo $order_item->SalesOrder?></td>
							<td><?php echo $order_item->OrderCount?></td>
							<td class="amount"><?php echo wcismis_price($order_item->OrderTotal);?></td>
						</tr>
					 <?php } ?>	
				<tbody>           
			</table>		
			<?php
		}
		
		
		
		function top_billing_country(){
			global $wpdb;
					$per_page = apply_filters( 'wcismispro_top_billing_country_per_page', $this->per_page);
					$sql = "SELECT SUM(postmeta.meta_value) AS 'Total' 
							,postmeta5.meta_value AS 'BillingCountry'
							,Count(*) AS 'OrderCount'
					FROM {$wpdb->prefix}postmeta as postmeta 
					
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=postmeta.post_id
					
					
					WHERE  postmeta.meta_key='_order_total' AND  postmeta5.meta_key='_billing_country' 
			 		
					GROUP BY  postmeta5.meta_value 
					Order By OrderCount DESC 
					
					LIMIT {$per_page}";
					$order_items = $wpdb->get_results($sql); 
					if(count($order_items)>0):
					 $country      = new WC_Countries;
					?>
                    <table style="width:100%" class="widefat">
                        <thead>
                            <tr class="first">
                                <th>Billing Country</th>
                                <th>Order Count</th>                           
                                <th class="amount">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php					
                            foreach ( $order_items as $key => $order_item ) {
                            if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                            ?>
                                <tr class="<?php echo $alternate."row_".$key;?>">
                                    <td><?php echo $country->countries[$order_item->BillingCountry];?></td>
                                    <td><?php echo $order_item->OrderCount?></td>
                                    <td class="amount"><?php echo wcismis_price($order_item->Total)?></td>
                                 <?php } ?>		
                                </tr>
                        <tbody>           
                    </table>
					<style type="text/css">
						td.amount{ text-align:right; width:100px;}
						th.amount{ text-align:right; width:100px;}
					</style>	
					<?php 
					else:
						echo '<p>No order found.</p>';
					endif;
		}
		
		public function recent_orders(){	
			global $wpdb;			
			$per_page = apply_filters( 'wcismispro_recent_orders_per_page', $this->per_page);
			
			$sql = "SELECT
						woocommerce_order_items.order_id As 'OrderID' 
						,COUNT( *) AS 'ItemCount'
						,postmeta3.meta_value As 'OrderTotal'
						,posts.post_date AS 'OrderDate'
						,postmeta2.meta_value As 'BillingEmail'
						,postmeta4.meta_value As 'FirstName'
						FROM 
					{$wpdb->prefix}woocommerce_order_items as woocommerce_order_items
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=woocommerce_order_items.order_id
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=woocommerce_order_items.order_id
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=woocommerce_order_items.order_id
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=woocommerce_order_items.order_id
					
					WHERE 
					postmeta2.meta_key='_billing_email'
					AND postmeta3.meta_key='_order_total'
					AND posts.post_type='shop_order'
					AND postmeta4.meta_key='_billing_first_name'
						
					GROUP BY woocommerce_order_items.order_id
					
					Order By posts.post_date DESC 
					LIMIT {$per_page}
					";					
					$order_items = $wpdb->get_results($sql );
					if(count($order_items)>0):
				?>
				 <table style="width:100%" class="widefat">
                    <thead>
                        <tr class="first">
                            <th>Order ID</th>
                            <th>Order Date</th>                           
                            <th>Billing Email</th>
                            <th>First Name</th>
                            <th>Item Count</th>
                            <th class="amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php					
                            foreach ( $order_items as $key => $order_item ) {
                            if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                        ?>
                        <tr class="<?php echo $alternate."row_".$key;?>">
                            <td><?php echo $order_item->OrderID?></td>
                            <td><?php echo $order_item->OrderDate?></td>
                            <td><?php echo $order_item->BillingEmail?></td>
                            <td><?php echo $order_item->FirstName?></td>
                            <td><?php echo $order_item->ItemCount?></td>
                            <td class="amount"><?php echo wcismis_price($order_item->OrderTotal)?></td>
                        </tr>
                         <?php } ?>
                    <tbody>           
				</table>
		<?php 
				else:
					echo '<p>No recent order found.</p>';
				endif;
		}
		
		function top_customer_list(){
			global $wpdb;
			$per_page = apply_filters( 'wcismispro_top_customer_per_page', $this->per_page);
			$sql = "SELECT SUM(postmeta.meta_value) AS 'Total' 
							,postmeta3.meta_value AS 'BillingEmail'
							,postmeta2.meta_value AS 'BillingFirstName'
							,Count(*) AS 'OrderCount'
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=postmeta.post_id
					LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=postmeta.post_id
					WHERE  postmeta.meta_key='_order_total' AND  postmeta3.meta_key='_billing_email'  AND postmeta2.meta_key='_billing_first_name' 
			 		GROUP BY  postmeta3.meta_value 
					Order By Total DESC 					
					LIMIT {$per_page}";
			$order_items = $wpdb->get_results($sql );
			if(count($order_items)>0):?>
            <table style="width:100%" class="widefat">
                <thead>
                    <tr class="first">
                        <th>Billing First Name</th>
                        <th>Billing Email</th>                           
                        <th>Order Count</th>
                        <th class="amount">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php					
                        foreach ( $order_items as $key => $order_item ) {
                            if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                            ?>
                            <tr class="<?php echo $alternate."row_".$key;?>">
                                <td><?php echo $order_item->BillingFirstName?></td>
                                <td><?php echo $order_item->BillingEmail?></td>
                                <td><?php echo $order_item->OrderCount?></td>
                                <td class="amount"><?php echo wcismis_price($order_item->Total)?></td>
                            </tr>
                         <?php } ?>	
                <tbody>           
            </table>	
			<?php
			else:
				echo '<p>No orders found.</p>';
			endif;
		}
	}
}	  
if(!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {	
	if(!defined('WC_IS_MIS_WC_ACITVE')) define( 'WC_IS_MIS_WC_ACITVE', FALSE );
	function wcismis_admin_notices(){
		$message = "";
		$message .= '<div class="error">';
		$message .= '<p>' . sprintf( __('WooCommerce MIS Report depends on <a href="%s">WooCommerce</a> to work!' , 'wcismis' ), 'http://wordpress.org/extend/plugins/woocommerce/' ) . '</p>';
		$message .= '</div>';
		echo  $message;
	}	
	add_action( 'admin_notices', 'wcismis_admin_notices');
}else{
	$WC_IS_MIS_Report = new WC_IS_MIS_Report();
	if(!defined('WC_IS_MIS_WC_ACITVE')) define( 'WC_IS_MIS_WC_ACITVE', TRUE );
	if(!function_exists('wcismis_price')){
		function wcismis_price($vlaue){
			if(!function_exists('woocommerce_price') || WC_IS_MIS_WC_ACITVE == false){
				return apply_filters( 'wcismis_currency_symbol', '&#36;', 'USD').$vlaue;
			}else{
				return woocommerce_price($vlaue);
			}	
		}
	}
}