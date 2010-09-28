{assign var=showSitesSelection value=false}
{assign var=showPeriodSelection value=false}
{include file="CoreAdminHome/templates/header.tpl"}

<h2>{'SiteSearch_SiteSearch'|translate}</h2>
<p>
	{'SiteSearch_AdminDescription1'|translate}
</p>
<p>
	{'SiteSearch_AdminDescription2'|translate}<br />
	<b>{'SiteSearch_SearchURL'|translate}:</b>
	{'SiteSearch_SearchURLDescription'|translate}<br />
	<b>{'SiteSearch_SearchParameter'|translate}:</b>
	{'SiteSearch_SearchParameterDescription'|translate}<br />
</p> 
<p>
	{'SiteSearch_AdminDescription3'|translate}<br />
	{'SiteSearch_AdminDescription4'|translate}
</p>


<form method="POST" action="{url module=SiteSearch action=admin}">
<table class="adminTable adminTableNoBorder" style="width: 800px; margin: 15px 0">
	<thead>
		<tr>
			<td><b>{'SiteSearch_Website'|translate}</b></td>
			<td><b>{'SiteSearch_SearchURL'|translate}</b></td>
			<td><b>{'SiteSearch_SearchParameter'|translate}</b></td>
			<td></td>
		</tr>
	</thead>
	<tbody>
		{foreach from=$sitesList item=site}
		<tr>
			<td>{$site.name}</td>
			<td><input name="SiteSearch_Data[{$site.idsite}][url]" value="{$site.sitesearch_url}" style="width: 200px"/></td>
			<td><input name="SiteSearch_Data[{$site.idsite}][parameter]" value="{$site.sitesearch_parameter}" style="width: 100px"/></td>
			<td><input name="SiteSearch_Data[{$site.idsite}][analyze]" value="1" type="checkbox" id="SiteSearch_Data_{$site.idsite}"/>&nbsp;&nbsp;<label for="SiteSearch_Data_{$site.idsite}">{'SiteSearch_AnalyzeURLsNow'|translate}</label></td>
		</tr>
		{/foreach}
	</tbody>
</table>

<input type="hidden" value="{$token_auth}" name="token_auth" />
<input type="submit" value="{'SiteSearch_Save'|translate}" name="submit" class="submit" />
</form>

{include file="CoreAdminHome/templates/footer.tpl"}
