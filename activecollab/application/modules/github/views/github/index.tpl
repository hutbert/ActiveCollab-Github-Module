{title}Github Version Control{/title}
{add_bread_crumb}{lang}Github{/lang}{/add_bread_crumb}

  <div id="repository_index" class="repository_listing">
  {if is_foreachable($repositories)}
    <table>
      <tr>
        <th></th>
        <th>{lang}Repository Name{/lang}</th>
		<th>{lang}Last Tag{/lang}</th>
        <th>{lang}Last Commit{/lang}</th>
		<th></th>
		<th></th>
      </tr>
    {foreach from=$repositories item=repository}
      <tr class="{cycle values='odd,even'}">
        <td class="star">{object_star object=$repository user=$logged_user}</td>
        <td class="name">
          <strong>{object_link object=$repository}</strong>
          <span class="block details">
             <a href="{$repository->getUrl()|clean}">{$repository->getUrl()|clean}</a>
           </span>
        </td>
		<td>
          {if is_null($repository->last_tag)}
           	-
          {else}
			<a href="https://github.com/{$repository->getUserName()}/{$repository->getRepoName()}/tree/{$repository->last_tag}">
				{$repository->last_tag}
			</a>
		  {/if}
		</td>
        <td class="last_commit" style="text-align:left;">
          {if is_null($repository->last_commit)}
            -
          {else}
            <a href="https://github.com{$repository->last_commit->url}">
				{$repository->last_commit->id}
			</a> 
			<br />{$repository->last_commit->author->name} on {$repository->last_commit->committed_date|date}
			<br />
			{$repository->last_commit->message}
          {/if}          
        </td>
        <td class="star">{object_subscription object=$repository user=$logged_user}</td>
        <td class="visibility">{object_visibility object=$repository user=$logged_user}</td>
      </tr>
    {/foreach}
    </table>
  {else}
    <p class="empty_page">{lang add_url=$add_repository_url}There are no repositories added. Would you like to <a href=":add_url">create one</a>{/lang}?</p>
  {/if}
  </div>