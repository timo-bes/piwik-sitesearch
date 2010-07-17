$(document).ready(function() {
	
	$('#SiteSearch_Keywords tbody tr')
	.css('cursor', 'pointer')
	.hover(function() {
		$(this).find('td').css('color', '#1D3256');
	}, function() {
		$(this).find('td').css('color', '');
	})
	.click(function() {
		var id = $(this).attr('id');
		id = id.substring(6, id.length);
		$.post('index.php', {
			module: 'SiteSearch',
			action: 'pages',
			idaction: id,
			idSite: piwik.idSite
		}, function(response) {
			$('#sitesearch_pages').html(response);
		});
	});
});