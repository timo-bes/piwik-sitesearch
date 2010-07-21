function SiteSearch_ManipulateTable(selector) {
	
	$(selector + ' table.SiteSearch tbody tr')
	.css('cursor', 'pointer')
	.hover(function() {
		$(this).find('td').css('color', '#1D3256');
	}, function() {
		$(this).find('td').css('color', '');
	})
	.click(function() {
		var term = $(this).attr('searchterm');
		var request = function(followingVal, divId) {
			$('#'+divId).html('<div id="loadingPiwik">'
					+ '<img alt="" src="themes/default/images/loading-blue.gif"> '
					+ 'Loading data... </div>');
			
			$.post('index.php', {
				module: 'SiteSearch',
				action: 'pages',
				search_term: term,
				idSite: piwik.idSite,
				period: piwik.period,
				date: piwik.currentDateString,
				following: followingVal
			}, function(response){
				$('#'+divId).html(response);
			});
		}
		
		request(1, 'sitesearch_following_pages');
		request(0, 'sitesearch_previous_pages');
	});
	
}