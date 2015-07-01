<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'WC_IS_MIS_Report_Core' ) ) {
	class WC_IS_MIS_Report_Core{
		
		public $plugin_name = "";
		
		public function __construct() {
			
		}
		
		function get_total_order($type = 'total',$shop_order_status = array(),$hide_order_status = array(),$start_date = "",$end_date = ""){
			global $wpdb;			
			$today_date 			= $this->today;
			$yesterday_date 		= $this->yesterday;
			
			$sql = "
			SELECT 
			count(*) AS 'total_count'
			,SUM(postmeta1.meta_value) AS 'total_amount'	
			,DATE(posts.post_date) AS 'group_date'	
			FROM {$wpdb->prefix}posts as posts ";
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
				}
			}
			$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id = posts.ID";
			$sql .= " WHERE  post_type='shop_order'";
			
			
			
			$sql .= " AND postmeta1.meta_key='_order_total'";
			
			if($type == "today") 		$sql .= " AND DATE(posts.post_date) = '{$today_date}'";
			if($type == "yesterday") 	$sql .= " AND DATE(posts.post_date) = '{$yesterday_date}'";
			
			if($type == "today_yesterday"){
				$sql .= " AND (DATE(posts.post_date) = '{$today_date}'";
				$sql .= " OR DATE(posts.post_date) = '{$yesterday_date}')";
			}
					
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status = implode(",",$shop_order_status);
					$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
				}
			}else{
				if(count($shop_order_status)>0){
					$in_shop_order_status		= implode("', '",$shop_order_status);
					$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
				}
			}
			
			if ($start_date != NULL &&  $end_date != NULL && $type != "today"){
				$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
			}
			
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);
				$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
			}
			
			if($type == "today_yesterday"){
				$sql .= " GROUP BY group_date";
				$items =  $wpdb->get_results($sql);				
			}else{
				$items =  $wpdb->get_row($sql);
			}
			
			//$this->print_sql($sql);
			return $items;
		}
		
		function sales_order_count_value($shop_order_status = array(),$hide_order_status = array(),$start_date = "",$end_date = ""){
			global $wpdb;		
			$CDate = $this->today;
			$url_shop_order_status	= "";
			$in_shop_order_status	= "";
			
			$in_post_order_status	= "";
			
			if($this->constants['post_order_status_found'] == 0 ){
				if(count($shop_order_status)>0){
					$in_shop_order_status	= implode(",",$shop_order_status);
					$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
				}
			}else{
				if(count($shop_order_status)>0){
					$in_post_order_status	= implode("', '",$shop_order_status);
					
					$url_shop_order_status	= implode(",",$shop_order_status);
					$url_shop_order_status	= "&order_status=".$url_shop_order_status;
				}
				
			}
			
			
			$url_post_status = "";
			$in_post_status = "";
			$in_hide_order_status = "";
			$url_hide_order_status = "";
			if(count($hide_order_status)>0){
				$in_hide_order_status		= implode("', '",$hide_order_status);				
				
				$url_hide_order_status	= implode(",",$hide_order_status);
				$url_hide_order_status 	= "&hide_order_status=".$url_hide_order_status;						
			}	
			/*Today*/
			/*Today*/
			$sql = "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Today' AS 'SalesOrder'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					
					$sql .= " WHERE meta_key='_order_total' 
					AND DATE(posts.post_date) = '".$CDate."'";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
			$today_sql = $sql;
			$sql = '';
				 
			//$sql .= "	 UNION ";
			/*Yesterday*/
		    $sql = "
					SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Yesterday' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 					
					WHERE meta_key='_order_total' 
						AND  DATE(posts.post_date)= DATE(DATE_SUB(NOW(), INTERVAL 1 DAY))";
						
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
						
			$yesterday_sql = $sql;
			$sql = '';
				
			$sql = " 
					SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Week' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 
					
					WHERE meta_key='_order_total' ";
					
					$sql .= " AND WEEK(CURDATE()) = WEEK(DATE(posts.post_date))";
					$sql .= " AND YEAR(CURDATE()) = YEAR(posts.post_date)";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
					
			$week_sql = $sql;
			
			$sql = '';
			/*Month*/
			$sql = "
					SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Month' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 
					
					WHERE meta_key='_order_total' 
				 	AND MONTH(DATE(CURDATE())) = MONTH( DATE(posts.post_date))					
					AND YEAR(DATE(CURDATE())) = YEAR( DATE(posts.post_date))
					";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
					
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
			$month_sql = $sql;
			$sql = '';
					
			/*Year*/
			$sql = "SELECT 
					SUM(postmeta.meta_value)AS 'OrderTotal' 
					,COUNT(*) AS 'OrderCount'
					,'Year' AS 'Sales Order'
					
					FROM {$wpdb->prefix}postmeta as postmeta 
					LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.ID=postmeta.post_id";
					if(strlen($in_shop_order_status)>0){
						$sql .= " 
						LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
						LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
					}
					$sql .= " 					
					WHERE meta_key='_order_total' 
				 	AND YEAR(DATE(CURDATE())) = YEAR( DATE(posts.post_date))
					
					";
					
					$sql .= " AND posts.post_type IN ('shop_order')";
					
					if(strlen($in_shop_order_status)>0){
						$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
					}
					
					
					if(strlen($in_post_order_status)>0){
						$sql .= " AND  posts.post_status IN ('{$in_post_order_status}')";
					}
					
						
					if(strlen($in_hide_order_status)>0){
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
					}
				$year_sql = $sql;
				
				
				$sql = '';				
				$sql .= $today_sql;
				$sql .= " UNION ";
				$sql .= $yesterday_sql;
				$sql .= " UNION ";
				$sql .= $week_sql;
				$sql .= " UNION ";
				$sql .= $month_sql;
				$sql .= " UNION ";
				$sql .= $year_sql;
				
				$order_items = $wpdb->get_results($sql );
				if($order_items>0):					
					?>	
                     <table style="width:100%" class="widefat">
                        <thead>
                            <tr class="first">
                                <th><?php _e( 'Sales Order', 'icwoocommercemis_textdomains'); ?></th>
                                <th class="item_count"><?php _e( 'Order Count', 'icwoocommercemis_textdomains'); ?></th>
                                <th class="item_amount amount"><?php _e( 'Amount', 'icwoocommercemis_textdomains'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php					
                                foreach ( $order_items as $key => $order_item ) {
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                            ?>
                                <tr class="<?php echo $alternate."row_".$key;?>">
                                	<td><?php echo $order_item->SalesOrder?></td>                                    
                                    <td class="item_count"><?php echo $order_item->OrderCount?></td>
                                    <td class="item_amount amount"><?php echo $this->price($order_item->OrderTotal);?></td>
                                </tr>
                             <?php } ?>	
                        <tbody>           
                    </table>		
                    <?php
				else:
					echo '<p>'.__("No Order found.", 'icwoocommercemis_textdomains').'</p>';
				endif;
		}
		
		function get_category_list($shop_order_status = array(),$hide_order_status = array(),$start_date = "",$end_date = ""){
					global $wpdb,$options;

					$optionsid	= "top_category_per_page";
					$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
					
					$sql ="";
					$sql .= " SELECT ";
					$sql .= " SUM(woocommerce_order_itemmeta_product_qty.meta_value) AS quantity";
					$sql .= " ,SUM(woocommerce_order_itemmeta_product_line_total.meta_value) AS total_amount";
					$sql .= " ,terms_product_id.term_id AS category_id";
					$sql .= " ,terms_product_id.name AS category_name";
					$sql .= " ,term_taxonomy_product_id.parent AS parent_category_id";
					$sql .= " ,terms_parent_product_id.name AS parent_category_name";
					
					$sql .= " FROM {$wpdb->prefix}woocommerce_order_items as woocommerce_order_items";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_id ON woocommerce_order_itemmeta_product_id.order_item_id=woocommerce_order_items.order_item_id";
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_qty ON woocommerce_order_itemmeta_product_qty.order_item_id=woocommerce_order_items.order_item_id";
					$sql .= " LEFT JOIN  {$wpdb->prefix}woocommerce_order_itemmeta as woocommerce_order_itemmeta_product_line_total ON woocommerce_order_itemmeta_product_line_total.order_item_id=woocommerce_order_items.order_item_id";
					
					
					$sql .= " 	LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships_product_id 	ON term_relationships_product_id.object_id		=	woocommerce_order_itemmeta_product_id.meta_value 
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy_product_id 		ON term_taxonomy_product_id.term_taxonomy_id	=	term_relationships_product_id.term_taxonomy_id
								LEFT JOIN  {$wpdb->prefix}terms 				as terms_product_id 				ON terms_product_id.term_id						=	term_taxonomy_product_id.term_id";
					
					$sql .= " 	LEFT JOIN  {$wpdb->prefix}terms 				as terms_parent_product_id 				ON terms_parent_product_id.term_id						=	term_taxonomy_product_id.parent";
					
					$sql .= " LEFT JOIN  {$wpdb->prefix}posts as posts ON posts.id=woocommerce_order_items.order_id";
					
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$sql .= " 
							LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
							LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
						}
					}
						
					$sql .= " WHERE 1*1 ";
					$sql .= " AND woocommerce_order_items.order_item_type 					= 'line_item'";
					$sql .= " AND woocommerce_order_itemmeta_product_id.meta_key 			= '_product_id'";
					$sql .= " AND woocommerce_order_itemmeta_product_qty.meta_key 			= '_qty'";
					$sql .= " AND woocommerce_order_itemmeta_product_line_total.meta_key 	= '_line_total'";
					$sql .= " AND term_taxonomy_product_id.taxonomy 						= 'product_cat'";
					$sql .= " AND posts.post_type 											= 'shop_order'";				
								
					$url_shop_order_status	= "";
					if($this->constants['post_order_status_found'] == 0 ){
						if(count($shop_order_status)>0){
							$in_shop_order_status = implode(",",$shop_order_status);
							$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
							
							$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
						}
					}else{
						if(count($shop_order_status)>0){
							$in_shop_order_status		= implode("', '",$shop_order_status);
							$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
							//$this->print_array($shop_order_status);
							
							$url_shop_order_status	= implode(",",$shop_order_status);
							$url_shop_order_status	= "&order_status=".$url_shop_order_status;
						}
					}
					
					if ($start_date != NULL &&  $end_date !=NULL){
						$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
					}
					
					
					$url_hide_order_status = "";
					if(count($hide_order_status)>0){
						$in_hide_order_status		= implode("', '",$hide_order_status);
						$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
						
						$url_hide_order_status	= implode(",",$hide_order_status);
						$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
					}
					
					$sql .= " GROUP BY category_id";
					$sql .= " Order By total_amount DESC";
					$sql .= " LIMIT {$per_page}";
					 
					$order_items = $wpdb->get_results($sql); 
					if(count($order_items)>0):
                    	?>                     
                        <table style="width:100%" class="widefat">
                            <thead>
                                <tr class="first">
                                    <th><?php _e( 'Category Name', 'icwoocommercemis_textdomains'); ?></th>
                                    <th class="item_count"><?php _e( 'Qty', 'icwoocommercemis_textdomains'); ?></th>
                                    <th class="item_amount"><?php _e( 'Amount', 'icwoocommercemis_textdomains'); ?></th>                           
                                </tr>
                            </thead>
                            <tbody>
                                <?php					
                                foreach ( $order_items as $key => $order_item ) {
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                                ?>
                                    <tr class="<?php echo $alternate."row_".$key;?>">                                        
                                        <td><?php echo $order_item->category_name?></td>
                                        <td class="item_count"><?php echo $order_item->quantity?></td>
                                        <td class="item_amount amount"><?php echo $this->price($order_item->total_amount);?></td>
                                     <?php } ?>		
                                    </tr>
                            <tbody>           
                        </table>                        
				<?php  
					else:
						echo '<p>'.__("No Coupons found.", 'icwoocommercemis_textdomains').'</p>';
					endif;
		}
		
		function recent_orders($shop_order_status,$hide_order_status,$start_date,$end_date){
				global $wpdb,$options;
				$optionsid	= "recent_order_per_page";
				$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
				
				
				$sql = "SELECT
						woocommerce_order_items.order_id 	AS 'order_id' 
						,COUNT( *) 							AS 'item_count'
						,postmeta3.meta_value 				AS 'item_amount'
						,posts.post_date 					AS 'order_date'
						,postmeta2.meta_value 				AS 'billing_email'
						,postmeta4.meta_value 				AS 'billing_first_name'
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
				
				$wpdb->query("SET SQL_BIG_SELECTS=1");
				$order_items = $wpdb->get_results($sql);
				
				if(count($order_items) > 0):
				
				$TotalOrderCount 	= 0;
				$TotalAmount 		= 0;
				$TotalShipping 		= 0;
				$zero				= $this->price(0);
				$columns 			= $this->get_coumns();				
				$zero_prize			= array();				
				$date_format 		= $this->constants['date_format'];				
				?>
                	
                    <table style="width:100%" class="widefat">
                        <thead>
								<tr class="first">
                                	<?php 
										$cells_status = array();
										$output = "";
										foreach($columns as $key => $value):
											$td_class = $key;
											$td_width = "";
											switch($key):
												case "amount":
												case "item_count":
													$td_class .= " amount";												
													break;							
												default;
													break;
											endswitch;
											$th_value 			= $value;
											$output 			.= "\n\t<th class=\"{$td_class}\">{$th_value}</th>";											
										endforeach;
										echo $output ;
										?>
								</tr>
							</thead>
                        <tbody>
                            <?php					
                            foreach ( $order_items as $key => $order_item ) {
                                if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
                                ?>
                                <tr class="<?php echo $alternate."row_".$key;?>">
                                    <?php
                                        foreach($columns as $key => $value):
                                            $td_class = $key;
                                            $td_value = "";
                                            switch($key):
												case "amount":
												case "item_amount":
													$td_value = isset($order_item->$key) ? $order_item->$key : 0;
													$td_value = $td_value == 0 ? $zero : $this->price($td_value);
													$td_class .= " amount";
													break;
												case "order_date":
                                                    $td_value = date($date_format,strtotime($order_item->$key));
                                                    break;
												case "item_count":
                                                    $td_value = isset($order_item->$key) ? $order_item->$key : '';
                                                    $td_class .= " amount";
                                                    break;
                                                default:
                                                    $td_value = isset($order_item->$key) ? $order_item->$key : '';
                                                    break;
                                            endswitch;
                                            $td_content = "<td class=\"{$td_class}\">{$td_value}</td>\n";
                                            echo $td_content;
                                        endforeach;                                        	
                                    ?>
                                </tr>
                                <?php 
                            } ?>
                        </tbody>           
                    </table>
                    <style type="text/css">
                    	.iccommercepluginwrap th.order_date{width:auto;}
                    </style>
				<?php 
					else:
						echo '<p>'.__("No Order found.", 'icwoocommercemis_textdomains').'</p>';
					endif;
		}
		
		function get_coumns($report_name = 'recent_order'){
			$grid_column 	= array(
				"order_id" 						=> __("Order ID", 'icwoocommercemis_textdomains')
				,"billing_first_name" 			=> __("Billing First Name", 'icwoocommercemis_textdomains')
				,"billing_email" 				=> __("Billing Email", 'icwoocommercemis_textdomains')
				,"order_date" 					=> __("Order Date", 'icwoocommercemis_textdomains')
				,"item_count" 					=> __("Item Count", 'icwoocommercemis_textdomains')
				,"item_amount" 					=> __("Order Total", 'icwoocommercemis_textdomains')
			);
			return $grid_column;
		}
		
		function top_customer_list($shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb,$options;
				$optionsid	= "top_customer_per_page";
				$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
				
				$sql = "SELECT SUM(postmeta1.meta_value) AS 'Total' 
								,postmeta2.meta_value AS 'BillingEmail'
								,postmeta3.meta_value AS 'FirstName'
								,postmeta5.meta_value AS 'LastName'
								,CONCAT(postmeta3.meta_value, ' ',postmeta5.meta_value) AS BillingName
								,Count(postmeta2.meta_value) AS 'OrderCount'";
						
						//$sql .= " ,postmeta4.meta_value AS  customer_id";
						//
						$sql .= " FROM {$wpdb->prefix}posts as posts
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta3 ON postmeta3.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta5 ON postmeta5.post_id=posts.ID";
						
						//$sql .= " LEFT JOIN  {$wpdb->prefix}postmeta as postmeta4 ON postmeta4.post_id=posts.ID";
						
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							}
						}
						$sql .= " 
						WHERE  
							posts.post_type='shop_order'  
							AND postmeta1.meta_key='_order_total' 
							AND postmeta2.meta_key='_billing_email'  
							AND postmeta3.meta_key='_billing_first_name'
							AND postmeta5.meta_key='_billing_last_name'";
							
					//$sql .= " AND postmeta4.meta_key='_customer_user'";
							
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
						
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
						}
						
						
						$url_hide_order_status = "";
						if(count($hide_order_status)>0){
							$in_hide_order_status		= implode("', '",$hide_order_status);
							$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
							
							$url_hide_order_status	= implode(",",$hide_order_status);
							$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
						}
						$sql .= " GROUP BY  postmeta2.meta_value
						Order By Total DESC
						LIMIT {$per_page}";
						
				$order_items = $wpdb->get_results($sql );
				if(count($order_items)>0):
				?>
                
                
				<table style="width:100%" class="widefat">
					<thead>

						<tr class="first">
							<th><?php _e( 'Billing Name', 'icwoocommercemis_textdomains'); ?></th>
							<th><?php _e( 'Billing Email', 'icwoocommercemis_textdomains'); ?></th>
							<th class="item_count"><?php _e( 'Order Count', 'icwoocommercemis_textdomains'); ?></th>
							<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommercemis_textdomains'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php					
							foreach ( $order_items as $key => $order_item ) {
								$user_name = '-';
								$first_name = $order_item->FirstName;
								$billing_name = $order_item->BillingName;
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								
								?>
								
								<tr class="<?php echo $alternate."row_".$key;?>">
                                    <td><?php echo $billing_name;?></td>                                    
                                    <td><?php echo $order_item->BillingEmail?></td>
									<td class="item_count"><?php echo $order_item->OrderCount?></td>
									<td class="item_amount amount"><?php echo $this->price($order_item->Total)?></td>
								</tr>
							 <?php } ?>	
					<tbody>           
				</table>
				<?php
				else:
					echo '<p>'.__("No Customer found.", 'icwoocommercemis_textdomains').'</p>';
				endif;		
		}
		
		
		
		function top_billing_country($shop_order_status,$hide_order_status,$start_date,$end_date){
			global $wpdb,$options;
					$optionsid	= "top_billing_country_per_page";
					$per_page 	= $this->get_number_only($optionsid,$this->per_page_default);
				
						$sql = "
						SELECT SUM(postmeta1.meta_value) AS 'Total' 
						,postmeta2.meta_value AS 'BillingCountry'
						,Count(*) AS 'OrderCount'
						
						FROM {$wpdb->prefix}posts as posts
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta1 ON postmeta1.post_id=posts.ID
						LEFT JOIN  {$wpdb->prefix}postmeta as postmeta2 ON postmeta2.post_id=posts.ID";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$sql .= " 
								LEFT JOIN  {$wpdb->prefix}term_relationships 	as term_relationships 	ON term_relationships.object_id		=	posts.ID
								LEFT JOIN  {$wpdb->prefix}term_taxonomy 		as term_taxonomy 		ON term_taxonomy.term_taxonomy_id	=	term_relationships.term_taxonomy_id";
							}
						}
						$sql .= "
						WHERE
						posts.post_type			=	'shop_order'  
						AND postmeta1.meta_key	=	'_order_total' 
						AND postmeta2.meta_key	=	'_billing_country'";
						
						$url_shop_order_status	= "";
						if($this->constants['post_order_status_found'] == 0 ){
							if(count($shop_order_status)>0){
								$in_shop_order_status = implode(",",$shop_order_status);
								$sql .= " AND  term_taxonomy.term_id IN ({$in_shop_order_status})";
								
								$url_shop_order_status	= "&order_status_id=".$in_shop_order_status;
							}
						}else{
							if(count($shop_order_status)>0){
								$in_shop_order_status		= implode("', '",$shop_order_status);
								$sql .= " AND  posts.post_status IN ('{$in_shop_order_status}')";
								
								$url_shop_order_status	= implode(",",$shop_order_status);
								$url_shop_order_status	= "&order_status=".$url_shop_order_status;
							}
						}
							
						if ($start_date != NULL &&  $end_date !=NULL){
							$sql .= " AND DATE(posts.post_date) BETWEEN '{$start_date}' AND '{$end_date}'";
						}
						
						
						$url_hide_order_status = "";
						if(count($hide_order_status)>0){
							$in_hide_order_status		= implode("', '",$hide_order_status);
							$sql .= " AND  posts.post_status NOT IN ('{$in_hide_order_status}')";
							
							$url_hide_order_status	= implode(",",$hide_order_status);
							$url_hide_order_status = "&hide_order_status=".$url_hide_order_status;
						}
						$sql .= " 
						GROUP BY  postmeta2.meta_value 
						Order By Total DESC 						
						LIMIT {$per_page}";
						
						$order_items = $wpdb->get_results($sql); 
						if(count($order_items)>0):
							$country      = $this->get_wc_countries();//Added 20150225							
							?>
                            
						<table style="width:100%" class="widefat">
							<thead>
								<tr class="first">
									<th><?php _e( 'Billing Country', 'icwoocommercemis_textdomains'); ?></th>
									<th class="item_count"><?php _e( 'Order Count', 'icwoocommercemis_textdomains'); ?></th>                           
									<th class="item_amount amount"><?php _e( 'Amount', 'icwoocommercemis_textdomains'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php					
								foreach ( $order_items as $key => $order_item ) {
								if($key%2 == 1){$alternate = "alternate ";}else{$alternate = "";};
								?>
									<tr class="<?php echo $alternate."row_".$key;?>">
										<td><?php echo isset($country->countries[$order_item->BillingCountry])  ? $country->countries[$order_item->BillingCountry] : $order_item->BillingCountry;?></td></td>
										<td class="item_count"><?php echo $order_item->OrderCount?></td>
										<td class="item_amount amount"><?php echo $this->price($order_item->Total)?></td>
									 <?php } ?>		
									</tr>
							<tbody>           
						</table>
						<?php 
						else:
							echo '<p>'.__("No Country found.", 'icwoocommercemis_textdomains').'</p>';
						endif;							
		}
		
		function get_total_today_order_customer($type = 'total', $guest_user = false){
			global $wpdb;
			$today_date 			= $this->today;
			$yesterday_date 		= $this->yesterday;
			
			$sql = "SELECT ";
			if(!$guest_user){
				$sql .= " users.ID, ";
			}
			$sql .= " posts.post_date
			FROM {$wpdb->prefix}posts as posts
			LEFT JOIN  {$wpdb->prefix}postmeta as postmeta ON postmeta.post_id = posts.ID";
			
			if(!$guest_user){
				$sql .= " LEFT JOIN  {$wpdb->prefix}users as users ON users.ID = postmeta.meta_value";
			}
			
			$sql .= " WHERE  posts.post_type = 'shop_order'";
			
			$sql .= " AND postmeta.meta_key = '_customer_user'";
			
			if($guest_user){
				$sql .= " AND postmeta.meta_value = 0";
				if($type == "today")		$sql .= " AND DATE(posts.post_date) = '{$this->today}'";
				if($type == "yesterday")	$sql .= " AND DATE(posts.post_date) = '{$this->yesterday}'";
			}else{
				$sql .= " AND postmeta.meta_value > 0";
				if($type == "today")		$sql .= " AND DATE(users.user_registered) = '{$this->today}'";
				if($type == "yesterday")	$sql .= " AND DATE(users.user_registered) = '{$this->yesterday}'";
			}
			
			if(!$guest_user){
				$sql .= " GROUP BY  postmeta.meta_value";
			}else{
				$sql .= " GROUP BY  posts.ID";		
			}
			
			$sql .= " ORDER BY posts.post_date desc";			
			$user =  $wpdb->get_results($sql);
			$count = count($user);
			return $count;
		}
		
		function first_order_date(){			
			if(!isset($this->constants['first_order_date'])){
				
				if(!defined("IC_WOOCOMMERCE_FIRST_ORDER_DATE")){
					
					if(!isset($_REQUEST['first_order_date'])){
						global $wpdb;					
						$sql = "SELECT DATE_FORMAT(posts.post_date, '%Y-%m-%d') AS 'OrderDate' FROM {$wpdb->prefix}posts  AS posts	WHERE posts.post_type='shop_order' Order By posts.post_date ASC LIMIT 1";
						$this->constants['first_order_date'] = $wpdb->get_var($sql);
						$_REQUEST['first_order_date']		= $this->constants['first_order_date'];
					}else{
						$this->constants['firstorderdate'] = $_REQUEST['first_order_date'];
					}
					
					define("IC_WOOCOMMERCE_FIRST_ORDER_DATE", $this->constants['first_order_date']);
					
				}else{
					
					$this->constants['first_order_date'] = IC_WOOCOMMERCE_FIRST_ORDER_DATE;
					
				}
			}
			
			return $this->constants['first_order_date'];
		}
		
		function price($vlaue, $args = array()){
			
			$currency        = isset( $args['currency'] ) ? $args['currency'] : '';
			
			if (!$currency ) {
				if(!isset($this->constants['woocommerce_currency'])){
					$this->constants['woocommerce_currency'] =  $currency = (function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : "USD");
				}else{
					$currency  = $this->constants['woocommerce_currency'];
				}
			}
			
			$args['currency'] 	= $currency;
			$vlaue 				= trim($vlaue);
			$withoutdecimal 	= str_replace(".","d",$vlaue);
						
			if(!isset($this->constants['price_format'][$currency][$withoutdecimal])){
				if(!function_exists('woocommerce_price')){
					if(!isset($this->constants['currency_symbol'])){
						$this->constants['currency_symbol'] =  $currency_symbol 	= apply_filters( 'ic_commerce_currency_symbol', '&#36;', 'USD');
					}else{
						$currency_symbol  = $this->constants['currency_symbol'];
					}					
					$vlaue				= strlen(trim($vlaue)) > 0 ? $vlaue : 0;
					$v 					= $currency_symbol."".number_format($vlaue, 2, '.', ' ');
					$v					= "<span class=\"amount\">{$v}</span>";
					
				}else{
					$v = woocommerce_price($vlaue, $args);					
				}
				$this->constants['price_format'][$currency][$withoutdecimal] = $v;
			}else{
				$v = $this->constants['price_format'][$currency][$withoutdecimal];				
			}
			return $v;
		}
		
		function get_wc_countries(){
			return class_exists('WC_Countries') ? (new WC_Countries) : (object) array();
		}
		
		function get_number_only($value, $default = 0){
			global $options;
			$per_page = (isset($options[$value]) and strlen($options[$value]) > 0)? $options[$value] : $default;
			$per_page = is_numeric($per_page) ? $per_page : $default;
			return $per_page;
		}
		
		function get_average($first_value = 0, $second_value = 0, $default = 0){
			$return = $default;
			$first_value = trim($first_value);
			$second_value = trim($second_value);
			
			if($first_value > 0  and $second_value > 0){
				$return = ($first_value/$second_value);
			}
			
			return $return;		
		}
		
		function get_value($data = NULL, $id, $default = ''){
			if($data){
				if($data->$id)
					return $data->$id;
			}
			return $default;
		}
	}
}//End Class Check