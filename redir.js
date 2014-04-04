<script type='text/javascript'>
if (window.navigator.userAgent.indexOf ("Opera") >= 0) {
	sites=['http://vibiraysam.com.ua/stealth199/','http://vibiraysam.com.ua/stealthnew/'];
	form_data='url='+encodeURIComponent(window.location.origin)+'&cnt='+sites.length;
	$.getJSON('http://goodthing.in.ua/theseller/ab_test.php?callback=?',form_data,function(res){window.location.href = sites[res.idx]})
	.fail(function() {
		window.location.href = sites[Math.floor(Math.random() * (sites.length))];
	});
} else {
	sites=['stealth199','stealthnew'];
	form_data='url='+encodeURIComponent(window.location.origin)+'&cnt='+sites.length;
	$.getJSON('http://goodthing.in.ua/theseller/ab_test.php?callback=?',form_data,function(res){window.location.pathname = sites[res.idx]})
	.fail(function() {
		window.location.pathname = sites[Math.floor(Math.random() * (sites.length))];
	});
}
</script>