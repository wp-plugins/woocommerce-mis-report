<?php
/**
 * Plugin Name: WooCommerce MIS Report
 * Plugin URI: http://www.plugin.infosofttech.com
 * Description: This report provides the sales summary (Day, yesterday, monthly and yearly) in term of amount and order count. Currently the report provide the order summary, recent 5 order, top five countries, top 5 customers.This report helps to monitor the sales status for any business.
 * Version: 0.0.1
 * Author: Infosoft Consultant
 * Author URI: http://www.infosofttech.com/deepak.aspx
 * License: A "Slug" license name e.g. GPL2
 */
 
 
 class WC_IS_MIS_Report{
		
		public $plugin_name = "";
		
		public function __construct() {
			global $options;
			$this->plugin_name = "WooCommerce MIS Report";
			
			if(is_admin()){
				add_action('admin_menu', array(&$this, 'wcismis_add_page'));
				add_action('admin_footer',  array( &$this, 'admin_footer'));
				$this->per_page = get_option('wcismis_per_page',5);
			}
		}
		
		function wcismis_add_page(){
			$main_page = add_menu_page($this->plugin_name, 'MIS Report', 'manage_options', 'wcismis_page', array($this, 'wcismis_page'), plugins_url( 'woocommerce_ic_mis_report/assets/images/menu_icons.png' ), '57.5' );
		}
		
		function wcismis_page(){
			?>
            	 <div class="wrap ic_mis_report wcismis_wrap">
                    <div class="icon32" id="icon-options-general"><br /></div>
                    <h2><?php _e('Dashboard','wcismis') ?></h2>
                    	  <div id="poststuff" class="woo_cr-reports-wrap">
                         		<div class="postbox">
                                    <h3><span><?php _e( 'Sales Order Summary', 'wcismispro' ); ?></span></h3>
                                    <div class="inside">
                                        <?php $this->sales_order_count_value()?>
                                    </div>
                                </div>
                                
                                 <div class="postbox">
                                    <h3><span><?php _e( 'Recent orders', 'wcismispro' ); ?></span></h3>
                                    <div class="inside">                            
                                        <?php $this->recent_orders();?>
                                    </div>
                                </div>  
                                
                                 <div class="postbox">
                                    <h3><span><?php _e( 'Top Billing Country', 'wcismispro' ); ?></span></h3>
                                    <div class="inside">
                                         <?php $this->top_billing_country()?>
                                    </div>
                                </div> 
                                
                                                             
                                
                                
                                <div class="postbox">
                                    <h3><span><?php _e( 'Top Customers', 'wcismispro' ); ?></span></h3>
                                    <div class="inside">                            
                                        <?php $this->top_customer_list();?>
                                    </div>
                                </div>
                          </div>
                 </div>
            <?php
		}
		
		function sales_order_count_value(){
			global $wpdb;		
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
							AND  DATE(posts.post_date) = DATE(NOW())-1";
							
				$sql .= "	 UNION ";	
				/*Week*/		
				$sql .= " SELECT 
						SUM(postmeta.meta_value)AS 'OrderTotal' 
						,COUNT(*) AS 'OrderCount'
						,'Week' AS 'Sales Order'
						
						FROM {$wpdb->prefix}postmeta as postmeta 
						LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id
						
						WHERE meta_key='_order_total' 
							
						AND WEEK(posts.post_date) = WEEK(CURDATE())
						AND 
						MONTH(posts.post_date) = MONTH(CURDATE())
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
						AND MONTH(posts.post_date) = MONTH(CURDATE())
						AND 
						YEAR(posts.post_date) = YEAR(CURDATE())
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
						AND YEAR(posts.post_date) = YEAR(CURDATE())";
			

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
		
		function admin_footer() {	
			global $woocommerce;
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.minified';
			wp_enqueue_style( 'woo_cr_admin_styles', WOO_CR_URL . '/assets/css/admin.css' );
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
                                    <td class="amount"><?php echo wcismispro_price($order_item->Total)?></td>
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
                            <td class="amount"><?php echo wcismispro_price($order_item->OrderTotal)?></td>
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
                                <td class="amount"><?php echo wcismispro_price($order_item->Total)?></td>
                            </tr>
                         <?php } ?>	
                <tbody>           
            </table>	
			<?php
			else:
				echo '<p>No customer found.</p>';
			endif;
		}
		
 }
 
if(!in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {	
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
	function wcismis_price($vlaue){
		if(!function_exists('woocommerce_price') || WC_IS_MIS_WC_ACITVE == false){
			return apply_filters( 'wcismis_currency_symbol', '&#36;', 'USD').$vlaue;
		}else{
			return woocommerce_price($vlaue);
		}	
	}
}