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
		
		var request = function(followingVal, divId) {
			$('#'+divId).html('<div id="loadingPiwik">'
					+ '<img alt="" src="themes/default/images/loading-blue.gif"> '
					+ 'Loading data... </div>');
			
			$.post('index.php', {
				module: 'SiteSearch',
				action: 'pages',
				idaction: id,
				idSite: piwik.idSite,
				following: followingVal
			}, function(response){
				$('#'+divId).html(response);
			});
		}
		
		request(1, 'sitesearch_following_pages');
		request(0, 'sitesearch_previous_pages');
	});
});