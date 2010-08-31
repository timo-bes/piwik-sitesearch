<div id="{$properties.uniqueId}">
	<div class="{if isset($javascriptVariablesToSet.idSubtable)&& $javascriptVariablesToSet.idSubtable!=0}sub{/if}{if $javascriptVariablesToSet.viewDataTable=='tableAllColumns'}dataTableAllColumnsWrapper{elseif $javascriptVariablesToSet.viewDataTable=='tableGoals'}dataTableAllColumnsWrapper{else}dataTableWrapper{/if}">
	{if isset($arrayDataTable.result) and $arrayDataTable.result == 'error'}
		{$arrayDataTable.message} 
	{else}
		{if count($arrayDataTable) == 0}
		<div class="pk-emptyDataTable">{'SiteSearch_TableNoData'|translate}</div>
		{else}
			<a name="{$properties.uniqueId}"></a>
			<table cellspacing="0" class="dataTable SiteSearch"> 
			<thead>
			<tr>
			{foreach from=$dataTableColumns item=column name=head}
				<th class="sortable {if $smarty.foreach.head.first}first{elseif $smarty.foreach.head.last}last{/if}" id="{$column}"><div id="thDIV">{$columnTranslations[$column]}</div></th>
			{/foreach}
			</tr>
			</thead>
			
			<tbody>
			{foreach from=$arrayDataTable item=row}
			<tr>
{foreach from=$dataTableColumns item=column}
<td>
{if !$row.idsubdatatable && $column==8 && !empty($row.metadata.url)}<span class="urlLink">{$row.metadata.url}</span>{/if}
{if $column==8}{logoHtml metadata=$row.metadata alt=$row.columns.label}{/if}
{if isset($row.columns[$column])}{$row.columns[$column]}{else}{$defaultWhenColumnValueNotDefined}{/if}
</td>
{/foreach}
			</tr>
			{/foreach}
			</tbody>
			</table>
		{/if}
		
		{if $properties.show_footer}
			{include file="CoreHome/templates/datatable_footer.tpl"}
		{/if}
		{include file="CoreHome/templates/datatable_js.tpl"}
	{/if}
	</div>
</div>