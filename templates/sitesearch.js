function SiteSearch_ManipulateTable(selector) {
	
	$(selector + ' table.SiteSearch tbody tr')
	.css('cursor', 'pointer')
	.hover(function() {
		$(this).find('td').css('color', 'black');
	}, function() {
		$(this).find('td').css('color', '');
	})
	.click(function() {
		var term = $(this).attr('searchterm');
		var request = function(method, followingVal, divId) {
			$('#'+divId).html('<div id="loadingPiwik">'
					+ '<img alt="" src="themes/default/images/loading-blue.gif"> '
					+ 'Loading data... </div>');
			
			$.post('index.php', {
				module: 'SiteSearch',
				action: method,
				search_term: term,
				idSite: piwik.idSite,
				period: piwik.period,
				date: piwik.currentDateString,
				following: followingVal
			}, function(response){
				$('#'+divId).html(response);
			});
		}
		
		request('pages', 1, 'sitesearch_following_pages');
		request('pages', 0, 'sitesearch_previous_pages');
		request('evolution', false, 'sitesearch_evolution');
        request('getRefinements', false, 'sitesearch_refinements');
	});
	
}