{$titleTruncate = $titleTruncate|default:500}
{if isset($item['label'])}
  {if $boldLabels}
    <strong>
  {/if}
    {$item['label']}{if $labelColon|default:true}:&nbsp;{/if}
  {if $boldLabels}
    </strong>
  {/if}
{/if}
{block name="itemLink"}
  {if $item['url']}
    <a href="{$item['url']}" class="{$item['class']|default:''}"{if $linkTarget || $item['linkTarget']} target="{if $item['linkTarget']}{$item['linkTarget']}{else}{$linkTarget}{/if}"{/if}>
  {/if}
    {if $item['img']}
      <img src="{$item['img']}" alt="{$item['title']}"{if $item['imgWidth']}
        width="{$item['imgWidth']}"{/if}{if $item['imgHeight']}
        height="{$item['imgHeight']}"{/if}{if $item['imgAlt']}
        alt="{$item['imgAlt']}"{/if} />
    {/if}
    {$item['title']|truncate:$titleTruncate}
    {if $item['subtitle']}
      {if $subTitleNewline|default:true}<div{else}&nbsp;<span{/if} class="smallprint">
        {$item['subtitle']}
      {if $subTitleNewline|default:true}</div>{else}</span>{/if}
    {/if}
    {if $item['badge']}
      <span class="badge">{$item['badge']}</span>
    {/if}
  {if $item['url']}
    </a>
  {/if}
{/block}
