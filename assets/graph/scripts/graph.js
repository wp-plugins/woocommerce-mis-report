var top_product_response 	= null;
var last_days_response 		= null;
var ic_pie_placement 		= 'insideGrid';
var ic_pie_show_legend 		= true;
var ic_pie_location 		= 'e';
var ic_pie_show_legend 		= true;

jQuery(document).ready(function($){
							//	alert("5");
	var data = {"action":"wcismis_action_comman","graph_by_type":"top_product"}
	$.ajax({
		type: "POST",	   
     	data: data,
	  	async: false,
      	url: ajax_object.ajaxurl,
      	dataType:"json",
      	success: function(response) {
			if(response.length > 0){
				top_product_response = response;
				wcismis_pie_chart_top_product(top_product_response);
			}
      	},
	  	error: function(jqXHR, textStatus, errorThrown) {
  			//alert(jqXHR.responseText);
			//alert(textStatus);
			//alert(errorThrown);
		 }
    });
	
	
	var data = {"action":"wcismis_action_comman","graph_by_type":"Last_7_days_sales_order_amount"}
	$.ajax({
		type: "POST",	   
     	data: data,
	  	async: false,
      	url: ajax_object.ajaxurl,
      	dataType:"json",
      	success: function(response) {
			//alert(JSON.stringify(response));			
			if(response.length > 0){
				last_days_response = response;
				wcismis_Last_7_days_sales_order_amount(last_days_response);
			}
      	},
	  	error: function(jqXHR, textStatus, errorThrown) {
  			//alert(jqXHR.responseText);
			//alert(textStatus);
			//alert(errorThrown);
		 }
    });
	
	
	//fix_pie();
	$( window ).resize(function() {
		if(last_days_response){
			wcismis_Last_7_days_sales_order_amount(last_days_response);
		}
		
		if(top_product_response){
			//fix_pie();
			wcismis_pie_chart_top_product(top_product_response);
		}
		
		
		
	});
	
	


						
	/*var data = {"action":"wcismis_action_comman","graph_by_type":"today_order_count"}
	$.ajax({
		type: "POST",	   
     	data: data,
	  	async: false,
      	url: ajax_object.ajaxurl,
      	dataType:"json",
      	success: function(response) {
			if(response.length > 0)
			wcismis_today_order_count(response);
      	},
	  	error: function(jqXHR, textStatus, errorThrown) {
  			alert(jqXHR.responseText);
			alert(textStatus);
			alert(errorThrown);
		 }
    });*/
});
/*Today Order Count*/
function wcismis_today_order_count(response){
	 var data = [];
		
		jQuery.each(response, function(k, v) {
			s2 = [ parseInt(v.OrderCount)]
		});
	jQuery("#today_order_count_meter_gauge").html("");
	plot3 = jQuery.jqplot('today_order_count_meter_gauge',[s2],{
       seriesDefaults: {
           renderer: jQuery.jqplot.MeterGaugeRenderer,
           rendererOptions: {
               min: 0,
               max: 100,
               intervals:[20,40, 60, 80, 100],
               intervalColors:['#FF0000', '#cc6666', '#E7E658', '#93b75f','#66cc66']
			 }
       }
   });
}

function fix_pie(){
	var window_width = jQuery(window).width();
	if(window_width <= 900){
		ic_pie_placement 		= 'outsideGrid';
		ic_pie_location 		= 'f';
		ic_pie_show_legend 		= false;
	}else{
		ic_pie_placement 		= 'insideGrid';
		ic_pie_location 		= 'f';
		ic_pie_show_legend 		= true;				
	}
}

function wcismis_pie_chart_top_product(response){
	
	
	//alert(ic_pie_show_legend)
	
	try{
		var data = [[]];
		
		jQuery.each(response, function(k, v) {
		
			data[0].push([ v.ItemName,parseInt(v.Total)]);
		});
		jQuery("#top_product_pie_chart").html("");
		var plot1 = jQuery.jqplot ('top_product_pie_chart', data, 
		{ 
		  seriesDefaults: {
			// Make this a pie chart.
			renderer: jQuery.jqplot.PieRenderer, 
			rendererOptions: {
			  // Put data labels on the pie slices.
			  // By default, labels show the percentage of the slice.
			  showDataLabels: true
			}
		  }, 
		  legend: {
			  	show:ic_pie_show_legend, 
				location: ic_pie_location,
				placement: ic_pie_placement }
		}
	  );		
	}
	catch(e){
		alert(e.message);
	}
}

function wcismis_Last_7_days_sales_order_amount(response){
	try{
		
		var data = [[]];
		jQuery.each(response, function(k, v) {
		
			data[0].push([ v.Date,parseInt(v.TotalAmount)]);
			
		});
		jQuery("#last_7_days_sales_order_amount").html("");
	  	var plot1 = jQuery.jqplot('last_7_days_sales_order_amount', data, {
		seriesDefaults: { 
			showMarker:true,
			pointLabels: { show:true, ypadding:5 } 
		  },
		axes:{
			  xaxis:{
				renderer:jQuery.jqplot.DateAxisRenderer,
				  tickOptions:{
					formatString:'%b&nbsp;%#d'
				  }
			  },
			  yaxis:{
				  min:0,
				tickOptions:{
				  formatString:'$%.2f'
				}
			  }
			},
			highlighter: {
			  show: false
			},
			cursor: {
			  show: true,
			  tooltipLocation:'sw'
			}
	  });

		}catch(e){
		alert(e.message);
	}
}

