{include file="findInclude:common/header.tpl"}

<div class="nonfocal">
  <h2>
    {$category}: 
    {if $isToday}
      Today
    {else}
      {$current|date_format:"%a %b %e, %Y"}
    {/if}
  </h2>
</div>

{capture name="sideNav" assign="sideNav"}
  <div class="{block name='sideNavClass'}sidenav{/block}">
    <a href="{$prevURL}">
      &lt; {$prev|date_format:"%a %b %e"}
    </a> | 
    <a href="{$nextURL}">
      {$next|date_format:"%a %b %e"} &gt;
    </a>
  </div>
{/capture}

{$sideNav}

{block name="resultCount"}{/block}

{include file="findInclude:common/results.tpl" results=$events noResultsText="No Events Found"}

{$sideNav}

{include file="findInclude:common/footer.tpl"}
