<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * English Translation
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @author Timo Besenreuther, EZdesign.de
 *
 * @category Piwik_Plugins
 * @package Piwik_SiteSearch
 */

$translations = array(
	'SiteSearch_PluginDescription' => 'This plugin analyzes the internal search of the websites.',
	'SiteSearch_SiteSearch' => 'Site Search',
	'SiteSearch_AdminDescription1' => 'This plugin analyzes the internal website search.',
	'SiteSearch_AdminDescription2' => 'Please specify the following values:',
	'SiteSearch_AdminDescription3' => 'If &quot;Analyze URLs now&quot; is checked, all URLs will be scanned for the search parameter again.',
	'SiteSearch_AdminDescription4' => 'Depending on the size of your database, this might take a while.',
	'SiteSearch_Website' => 'Website',
	'SiteSearch_SearchURL' => 'Search URL',
	'SiteSearch_SearchURLDescription' => 'The part of the URL, that identifies the search results page.<br />For performance reasons, you should choose the search url as specific as possible.<br />Nevertheless, you may use / to check all urls for the occurence of the search parameter.<br />Example: The search results page is http://www.example.com/search.php?q=searchterm => a good search url is /search.php',
	'SiteSearch_SearchParameter' => 'Search Parameter',
	'SiteSearch_SearchParameterDescription' => 'The name of the GET parameter, that holds the search term.',
	'SiteSearch_AnalyzeURLsNow' => 'Analyze URLs now',
	'SiteSearch_Save' => 'Save',
	'SiteSearch_Keyword' => 'Keyword',
	'SiteSearch_Hits' => 'Hits',
	'SiteSearch_UniqueHits' => 'Unique Hits',
	'SiteSearch_Results' => 'Results',
	'SiteSearch_Page' => 'Page',
	'SiteSearch_VisitsWithSearches' => 'Visits with Searches',
	'SiteSearch_TotalSearches' => 'Total Searches',
	'SiteSearch_TableNoData' => 'There is no data for this report.',
	'SiteSearch_SearchUserPercentage' => '% of users using the search',
	
	'SiteSearch_Keywords' => 'Keywords',
	'SiteSearch_SearchesWithoutResults' => 'Searches without results',
	'SiteSearch_PercentageOfSearchUsers' => 'Percentage of search users',
	'SiteSearch_Evolution' => 'Evolution',
	'SiteSearch_FollowingPages' => 'Following pages',
	'SiteSearch_PreviousPages' => 'Previous pages',
	'SiteSearch_AssociatedKeywords' => 'Associated Keywords',
	
	'SiteSearch_InternalSearchEvolution' => 'Internal search evolution',
	'SiteSearch_MostPopularInternalSearches' => 'Most popular internal searches',
	
	'SiteSearch_KeywordsAreaDescription' => 'This table shows the keywords, that users searched for in the given period. Click on a row to see details.',
	'SiteSearch_SearchesWithoutResultsAreaDescription' => 'This table shows the keywords, that users searched for in the given period and had no reults. Click on a row to see details.',
	'SiteSearch_KeywordColumnDescription' => 'The entire searchterm, that was entered.',
	'SiteSearch_HitsColumnDescription' => 'The number of times, the searchterm was entered.',
	'SiteSearch_UniqueHitsColumnDescription' => 'The number of visits, that included a search for the keyword.',
	'SiteSearch_ResultsColumnDescription' => 'The number of search results for the keyword (passed to the Piwik tracker, only works if set up properly).',
	
	'SiteSearch_EvolutionAreaDescription' => 'This graph shows the evolution of searches over time.',
	'SiteSearch_EvolutionAreaDescription2' => 'At first, the graph shows statistics for the search in general, when a keyword is selected, it displays only information for the particular keyword.',
	
	'SiteSearch_FollowingPagesAreaDescription' => 'This table shows the pages, that were visited directly after searching for the keyword. They are likely to be the ones, the user was looking for.',
	'SiteSearch_PreviousPagesAreaDescription' => 'This table shows the pages, that were visited directly before searching for the keyword. This may contain clues about where users tend to get lost on the website.',
	'SiteSearch_PageColumnDescription' => 'The URL of the site. Click on it to go to the page.',
	'SiteSearch_PageHitsColumnDescription' => 'The number of times, users that searched for the keyword visited the page.',
	
	'SiteSearch_AssociatedKeywordsAreaDescription' => 'The keywords shown in this table have been searched for by the users, that also searched for the selected keyword.',
	'SiteSearch_AssociatedHitsColumnDescription' => 'The number of visitors, that searched for both the selected keyword and the keyword displayed in the table row.',
	
	'SiteSearch_SearchUserPercentageAreaDescription' => 'This graph shows the percentage of users, that were at least using the search once during their visit.'
);

?>
