{wrap field=name}
  {label for=repositoryName required=yes}{lang}Name{/lang}{/label}
  {text_field name='repository[name]' value=$repository_data.name id=repositoryName class='title required'}
{/wrap}

{wrap field=url aid="$aid_url"}
  {label for=repositoryUrl required=yes}{lang}Repository URL or directory:{/lang}{/label}
  {text_field name='repository[url]' value=$repository_data.url id=repositoryUrl class='title required'}
{/wrap}

<div class="col">
{wrap field=username}
  {label for=repositoryUsername}{lang}Username{/lang}{/label}
  {text_field name='repository[username]' style='width:250px' value=$repository_data.username id=repositoryUsername class='title'}
{/wrap}
</div>

<div class="col">
{wrap field=password}
  {label for=repositoryPassword}{lang}Password{/lang}{/label}
  {password_field name='repository[password]' value=$repository_data.password id=repositoryPassword}
{/wrap}
</div>
<div class="clear"></div>
{if $logged_user->canSeePrivate()}
  {wrap field=visibility}
    {label for=repositoryVisibility}Visibility{/label}
    {select_visibility name=repository[visibility] value=$repository_data.visibility project=$active_project}
  {/wrap}
{else}
  <input type="hidden" name="repository[visibility]" value="1"/>
{/if}