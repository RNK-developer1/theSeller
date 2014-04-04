<head>
    <meta charset="utf-8">
    <title>theSeller</title>
    <meta name="description" content="theSeller">
    <meta name="author" content="EugeneL.">

    <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js"></script>
    <script src="assets/bootstrap.min.js"></script>
	<script src="assets/bootstrap-datetimepicker.min.js"></script>
	<script src="assets/jquery.chained.min.js"></script>
	<script src="assets/jquery.mask.min.js"></script>
	<script src="assets/bootstrap-datetimepicker.ru.js" charset='UTF-8'></script>
    <link href="assets/bootstrap.min.css" rel="stylesheet" media="screen">
	<link href="assets/datetimepicker.css" rel="stylesheet" media="screen">
	<link href="assets/table.css" rel="stylesheet" media="screen">
	
    <style type="text/css">
        .center { display: block; margin: 0 auto; }
		body {
			padding-top: 60px;
			color: red;
		}
		.container {
			color: black;
			max-width: 99% !important;
		}
		.navbar-brand {
			max-width: 400px;
		}
		.datetimepicker {
			z-index: 9999 !important;
			color: black;
		}
		.bold_red {
			font-weight: bold;
			color: red;
		}	
		table .header-fixed {
		  position: fixed;
		  top: 40px;
		  z-index: 1020; /* 10 less than .navbar-fixed to prevent any overlap */
		  border-bottom: 1px solid #d5d5d5;
		  -webkit-border-radius: 0;
			 -moz-border-radius: 0;
				  border-radius: 0;
		  -webkit-box-shadow: inset 0 1px 0 #fff, 0 1px 5px rgba(0,0,0,.1);
			 -moz-box-shadow: inset 0 1px 0 #fff, 0 1px 5px rgba(0,0,0,.1);
				  box-shadow: inset 0 1px 0 #fff, 0 1px 5px rgba(0,0,0,.1);
		  filter: progid:DXImageTransform.Microsoft.gradient(enabled=false); /* IE6-9 */
		}
		.table-bordered>tbody>tr:hover>td {
			border: 1px solid darkgray !important;
			border-top-width: 2px !important;
			border-bottom-width: 2px !important;
		}
		.short_form label {
			width: 45%;
			display: inline-block;
			margin-bottom: 10px;
		}
		.short_form .form-control {
			display: inline-block;
			width: 45%;
			height: 30px;
			padding: 0 12px;
		}
		.short_form textarea.form-control {
			height: 45px;
		}
		.btn-success_light {
			color: rgb(255, 255, 255);
			background-color: lightgreen;
			border-color: lightgreen;
			color: black;
		}
		
		.btn-in_work {
			background-color: lightgray;
			color: black;
		}
		
		.form-inline .form-group {
			display: inline-block;
			margin-bottom: 0;
			vertical-align: middle;
		}
		.navbar-nav .form-inline {
			padding-top: 5px;
		}
		
		textarea.form-control[name="comment"] {
			width: 535px;
			height: 100px;
		}
		
		textarea.form-control[name="comment2"] {
			width: 535px;
			height: 100px;
		}
		
		select.form-control[name="whs_ref"] {
			width: 488px;
		}
		
		.navbar-fixed-top select {
			padding: 0;
		}
    </style>
	
	<script type='text/javascript'>
		(function($) {
		 
			$.fn.fixedHeader = function (options) {
			 var config = {
			   topOffset: 40,
			   bgColor: '#EEEEEE'
			 };
			 if (options){ $.extend(config, options); }
			 
			 return this.each( function() {
			  var o = $(this);
			 
			  var $win = $(window)
				, $head = $('thead.header', o)
				, isFixed = 0;
			  var headTop = $head.length && $head.offset().top - config.topOffset;
			 
			  function processScroll() {
				if (!o.is(':visible')) return;
				if ($('thead.header-copy').size()) {
					$('thead.header-copy').width($('thead.header').width());
					var i, scrollTop = $win.scrollTop();
				}
				var t = $head.length && $head.offset().top - config.topOffset;
				if (!isFixed && headTop != t) { headTop = t; }
				if (scrollTop >= headTop && !isFixed) {
				  isFixed = 1;
				} else if (scrollTop <= headTop && isFixed) {
				  isFixed = 0;
				}
				isFixed ? $('thead.header-copy', o).offset({ left: $head.offset().left }).removeClass('hide')
						: $('thead.header-copy', o).addClass('hide');
			  }
			  $win.on('scroll', processScroll);
			 
			  // hack sad times - holdover until rewrite for 2.1
			  $head.on('click', function () {
				if (!isFixed) setTimeout(function () {  $win.scrollTop($win.scrollTop() - 47) }, 10);
			  })
			 
			  $head.clone().removeClass('header').addClass('header-copy header-fixed').appendTo(o);
			  var ww = [];
			  o.find('thead.header > tr:first > th').each(function (i, h){
				ww.push($(h).outerWidth());
			  });
			  $.each(ww, function (i, w){
				o.find('thead.header > tr > th:eq('+i+'), thead.header-copy > tr > th:eq('+i+')').css({width: w});
			  });
			 
			  o.find('thead.header-copy').css({ margin:'0 auto',
												width: o.outerWidth(),
											   'background-color':config.bgColor });
			  processScroll();
			 });
			};
		 
		})(jQuery);
	</script>
</head>