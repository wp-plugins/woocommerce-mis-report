/*Top Customer*/
function wcismis_pie_chart_top_product(response){
	try{
		var data = [[]];
		
		jQuery.each(response, function(k, v) {
		
			data[0].push([ v.ItemName,parseInt(v.Total)]);
		});
		
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
		  legend: { show:true, location: 'e' }
		}
	  );		
	}
	catch(e){
	alert(e.message);
	}
}

/*Top  Customer*/
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
			if(response.length > 0)
			wcismis_pie_chart_top_product(response);
      	},
	  	error: function(jqXHR, textStatus, errorThrown) {
  			alert(jqXHR.responseText);
			alert(textStatus);
			alert(errorThrown);
		 }
    });
});

/*Today Order Count*/
jQuery(document).ready(function($){
						
	var data = {"action":"wcismis_action_comman","graph_by_type":"today_order_count"}
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
    });
});
/*Today Order Count*/
function wcismis_today_order_count(response){
	 var data = [];
		
		jQuery.each(response, function(k, v) {
			s2 = [ parseInt(v.OrderCount)]
		});
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
/*Last 7 Days Sales Order*/
jQuery(document).ready(function($){
	
	var data = {"action":"wcismis_action_comman","graph_by_type":"Last_7_days_sales_order_amount"}
	$.ajax({
		type: "POST",	   
     	data: data,
	  	async: false,
      	url: ajax_object.ajaxurl,
      	dataType:"json",
      	success: function(response) {
			//alert("a1");
			//alert(JSON.stringify(response));
			if(response.length > 0)
			wcismis_Last_7_days_sales_order_amount(response);
      	},
	  	error: function(jqXHR, textStatus, errorThrown) {
  			alert(jqXHR.responseText);
			alert(textStatus);
			alert(errorThrown);
		 }
    });
});
/*Last 7 Days Sales Order*/
function wcismis_Last_7_days_sales_order_amount(response){
	try{
		
		var data = [[]];
		jQuery.each(response, function(k, v) {
		
			data[0].push([ v.Date,parseInt(v.TotalAmount)]);
			
		});
	  var plot1 = jQuery.jqplot('last_7_days_sales_order_amount', data, {
		title:'Last 7 days Sales Amount',
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
