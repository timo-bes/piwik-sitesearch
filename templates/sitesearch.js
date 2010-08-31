function SiteSearch_ManipulateTable(selector) {
	
	$(selector + ' table.SiteSearch tbody tr')
	.css('cursor', 'pointer')
	.click(function() {
		var $this = $(this);
		var term = $this.attr('searchterm');
		var request = function(method, followingVal, divId) {
			$('#'+divId).html('<div id="loadingPiwik">'
					+ '<img alt="" src="themes/default/images/loading-blue.gif"> '
					+ 'Loading data... </div>');
			
			$.post('index.php', {
				module: 'SiteSearch',
				action: method,
				searchTerm: term,
				idSite: piwik.idSite,
				period: piwik.period,
				date: piwik.currentDateString,
				following: followingVal,
				idSearch: $this.attr('id_search')
			}, function(response){
				$('#'+divId).html(response);
			});
		}
		
		request('pages', 1, 'sitesearch_following_pages');
		request('pages', 0, 'sitesearch_previous_pages');
		request('evolution', false, 'sitesearch_evolution');
        request('getRefinements', false, 'sitesearch_refinements');
		
		$('#sitesearch_hide').show();
		
	    var first = term.charAt(0).toUpperCase();
	    term = first + term.substr(1);
		$('#sitesearch_head').html(term).parent().show();
	});
	
}

$(document).ready(function() {
	$('h2.sitesearch_hasdescription').live('mouseover', function() {
		var $this = $(this);
		var $link = $(document.createElement('span'))
				.addClass('sitesearch_description_button')
				.html('(?)');
		$this.append($link);
	}).live('mouseout', function() {
		$(this).find('span').remove();
	}).live('click', function() {
		$(this).next().toggle();
	});
});
