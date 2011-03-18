<?php

/**
 * Piwik - Open source web analytics
 * SiteSearch Plugin
 * German Translation
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
	'SiteSearch_PluginDescription' => 'Dieses Plugin analysiert die interne Suche der Webseiten.',
	'SiteSearch_SiteSearch' => 'Interne Suche',
	'SiteSearch_AdminDescription1' => 'Dieses Plugin analysiert die interne Suche der Webseiten.',
	'SiteSearch_AdminDescription2' => 'Bitte gib die folgenden Werte an:',
	'SiteSearch_AdminDescription3' => 'Wenn &quot;URLs jetzt analysieren&quot; angew&auml;hlt ist, werden alle vorhandenen URLs erneut nach dem dem Suchparameter durchsucht.',
	'SiteSearch_AdminDescription4' => 'Bei gro&szlig;en Datenbanken kann das einige Zeit in Anspruch nehmen.',
	'SiteSearch_Website' => 'Webseite',
	'SiteSearch_SearchURL' => 'Such-URL',
	'SiteSearch_SearchURLDescription' => 'Der Teil der URL, mit dem die Suchergebnisseite identifiziert werden kann.<br />Es ist sinnvoll, die URL so genau wie m&ouml;glich zu w&auml;hlen, es reicht aber z.B. auch /, dann werden<br />alle URLs auf das Vorkommen des Parameters untersucht (die Performance leidet darunter allerdings).<br />Beispiel: Suchergebnisseite ist http://www.example.com/suche.php?q=searchterm => Gute Such-URL ist /suche.php',
	'SiteSearch_SearchParameter' => 'Suchparameter',
	'SiteSearch_SearchParameterDescription' => 'Der Name des GET Parameters, der den Suchbegriff enth&auml;lt.',
	'SiteSearch_AnalyzeURLsNow' => 'URLs jetzt analysieren',
	'SiteSearch_Save' => 'Speichern',
	'SiteSearch_Keyword' => 'Suchbegriff',
	'SiteSearch_Hits' => 'Aufrufe',
	'SiteSearch_UniqueHits' => 'Eindeutige Aufrufe',
	'SiteSearch_Results' => 'Ergebnisse',
	'SiteSearch_Page' => 'Seiten-URL',
	'SiteSearch_VisitsWithSearches' => 'Besuche mit Suche',
	'SiteSearch_TotalSearches' => 'Anzahl Suchen',
	'SiteSearch_TableNoData' => 'Es stehen keine Daten f&uuml;r diesen Bericht zur Verf&uuml;ging.',
	'SiteSearch_SearchUserPercentage' => '% der Benutzer, die die Suche genutzt haben',
	
	'SiteSearch_Keywords' => 'Suchbegriffe',
	'SiteSearch_SearchesWithoutResults' => 'Suchen ohne Ergebnisse',
	'SiteSearch_PercentageOfSearchUsers' => 'Anteil der Suchnutzer',
	'SiteSearch_Evolution' => 'Entwicklung',
	'SiteSearch_FollowingPages' => 'Anschlie&szlig;e Seite',
	'SiteSearch_PreviousPages' => 'Vorherige Seite',
	'SiteSearch_AssociatedKeywords' => 'Verwandte Suchbegriffe',
	
	'SiteSearch_InternalSearchEvolution' => 'Entwicklung der internen Suche',
	'SiteSearch_MostPopularInternalSearches' => 'Popul&auml;rste interne Suchbegriffe',
	
	// TODO: finish german translation
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