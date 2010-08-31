<div style="min-width: 930px">
	
	<div id="leftcolumn">
		<h2 class="sitesearch_hasdescription">{'SiteSearch_Keywords'|translate}</h2>
		<div class="sitesearch_description_wrapper">
			<div class="sitesearch_description">
				<p class="sitesearch_main_description">
					{'SiteSearch_KeywordsAreaDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_Keyword'|translate}: </b>
					{'SiteSearch_KeywordColumnDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_Hits'|translate}: </b>
					{'SiteSearch_HitsColumnDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_UniqueHits'|translate}: </b>
					{'SiteSearch_UniqueHitsColumnDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_Results'|translate}: </b>
					{'SiteSearch_ResultsColumnDescription'|translate}
				</p>
			</div>
		</div>
		{$keywords}
		
		<h2 class="sitesearch_hasdescription">{'SiteSearch_SearchesWithoutResults'|translate}</h2>
		<div class="sitesearch_description_wrapper">
			<div class="sitesearch_description">
				<p class="sitesearch_main_description">
					{'SiteSearch_SearchesWithoutResultsAreaDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_Keyword'|translate}: </b>
					{'SiteSearch_KeywordColumnDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_Hits'|translate}: </b>
					{'SiteSearch_HitsColumnDescription'|translate}
				</p>
				<p>
					<b>{'SiteSearch_UniqueHits'|translate}: </b>
					{'SiteSearch_UniqueHitsColumnDescription'|translate}
				</p>
			</div>
		</div>
		{$noResults}
		
		<h2 class="sitesearch_hasdescription">{'SiteSearch_PercentageOfSearchUsers'|translate}</h2>
		<div class="sitesearch_description_wrapper">
			<div class="sitesearch_description">
				<p class="sitesearch_main_description">
					{'SiteSearch_SearchUserPercentageAreaDescription'|translate}
				</p>
			</div>
		</div>
		{$searchPercentage}
	</div>
	
	<div id="rightcolumn">
		
		<h1 style="display:none">
			<span id="sitesearch_head"></span> 
			({$period})
		</h1>
		
		<h2 class="sitesearch_hasdescription">{'SiteSearch_Evolution'|translate}</h2>
		<div class="sitesearch_description_wrapper">
			<div class="sitesearch_description">
				<p class="sitesearch_main_description">
					{'SiteSearch_EvolutionAreaDescription'|translate}
				</p>
				<p>
					{'SiteSearch_EvolutionAreaDescription2'|translate}
				</p>
			</div>
		</div>
		<div id="sitesearch_evolution">
			{$evolution}
		</div>
		
		<div id="sitesearch_hide" style="display:none">
			
			<h2 class="sitesearch_hasdescription">{'SiteSearch_FollowingPages'|translate}</h2>
			<div class="sitesearch_description_wrapper">
				<div class="sitesearch_description">
					<p class="sitesearch_main_description">
						{'SiteSearch_FollowingPagesAreaDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_Page'|translate}: </b>
						{'SiteSearch_PageColumnDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_Hits'|translate}: </b>
						{'SiteSearch_PageHitsColumnDescription'|translate}
					</p>
				</div>
			</div>
			<div id="sitesearch_following_pages"></div>
			
			<h2 class="sitesearch_hasdescription">{'SiteSearch_PreviousPages'|translate}</h2>
			<div class="sitesearch_description_wrapper">
				<div class="sitesearch_description">
					<p class="sitesearch_main_description">
						{'SiteSearch_PreviousPagesAreaDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_Page'|translate}: </b>
						{'SiteSearch_PageColumnDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_Hits'|translate}: </b>
						{'SiteSearch_PageHitsColumnDescription'|translate}
					</p>
				</div>
			</div>
			<div id="sitesearch_previous_pages"></div>
		
		    <h2 class="sitesearch_hasdescription">{'SiteSearch_AssociatedKeywords'|translate}</h2>
			<div class="sitesearch_description_wrapper">
				<div class="sitesearch_description">
					<p class="sitesearch_main_description">
						{'SiteSearch_AssociatedKeywordsAreaDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_Keyword'|translate}: </b>
						{'SiteSearch_KeywordColumnDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_UniqueHits'|translate}: </b>
						{'SiteSearch_AssociatedHitsColumnDescription'|translate}
					</p>
					<p>
						<b>{'SiteSearch_Results'|translate}: </b>
						{'SiteSearch_ResultsColumnDescription'|translate}
					</p>
				</div>
			</div>
		    <div id="sitesearch_refinements"></div>
			
		</div>
		
	</div>

</div>