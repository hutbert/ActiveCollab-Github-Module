{add_bread_crumb}{$active_repository->getName()} - Commit History{/add_bread_crumb}
<div id="repository_history">
	<p class="pagination top">
		<span class="inner_pagination">
			{lang}Page{/lang}: {$page} - 
			{if !is_null($prev_page)}
				<a href="{$active_repository->getHistoryUrl()}&amp;page={$prev_page}&amp;branch_tag={$branch_tag}">Prev</a>, 
			{/if}
			<a href="{$active_repository->getHistoryUrl()}&amp;page={$next_page}&amp;branch_tag={$branch_tag}">Next</a>
		</span>
	</p>
	<form action="{$active_repository->getHistoryUrl()}" method="get">
		<p class="top" style="float:right;-moz-box-shadow: 1px 1px 1px #DEDEDE;border: 1px solid #DEDEDE;border-radius:5px;padding:5px 20px;">
				<span style="color:#333;">
							<strong>Branch/Tag: </strong>
							<select style="font-size:10px;margin-left:7px;" name="branch_tag">
								<optgroup label="Branches">
									{if !is_null($branches)}
										{foreach from=$branches item=hash key=name}
										<option value="{$name}" {if $name == $branch_tag}selected="selected"{/if}>{$name}</option>
										{/foreach}
									{/if}
								</optgroup>
								<optgroup label="Tags">
									{if !is_null($tags)}
										{foreach from=$tags item=tag key=name}
										<option value="{$name}" {if $name == $branch_tag}selected="selected"{/if}>{$name}</option>
										{/foreach}
									{/if}
								</optgroup>
							</select>
							<input type="hidden" name="path_info" value="{$path_info}" />
							<input type="submit" value="Change" style="width:auto;font-size:9px;background-color:#f7f7f7" />
				</span>
		</p>
	</form>
	<div class="grouped_commits">
  {foreach from=$commits item=commits_day key=date}
    <div class="date_slip">
      <span>{$date}</span>
    </div>
    <table class="commit_history_table common_table">
      {foreach from=$commits_day name=commit_list item=commit}
      <tr class="commit {cycle values='odd,even'}" data-pk="{$commit->id}">
        <td class="revision_number">
          <a href="https://github.com/{$user}/{$repo}/commit/{$commit->id}">
							{$commit->short_id}
					</a>
        </td>
        <td class="revision_user">
        	{if $commit->author->login == ''}
						{$commit->author->name}
					{else}
						{$commit->author->login}
					{/if}
        </td>
        <td class="revision_details">
          <div class="commit_message">
            {$commit->message|nl2br}
          </div>
					<div class="commit_files"></div>
        </td>
				<td class="file_toggle">
					<a>Show Changes</a>
				</td>
      </tr>
      {/foreach}
    </table>
	{foreachelse}
		<p style="text-align:center;padding-top:25px;font-size:120%;color:#333;font-weight:bold">
			There are no commits for this period in the repository.
		</p>
  {/foreach}
  </div>
</div>
{literal}

{/literal}