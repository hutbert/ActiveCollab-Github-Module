<?php

  /**
   * Github control module on_project_tabs event handler
   *
   * @package activeCollab.modules.github
   * @subpackage handlers
   */
  
  /**
   * Handle on prepare project overview event
   *
   * @param NamedList $tabs
   * @param User $logged_user
   * @param Project $project
   * @return null
   */
  function github_handle_on_project_tabs(&$tabs, &$logged_user, &$project) {
    $tabs->add('github', array(
      'text' => lang('Github'),
      'url' => github_module_url($project)
    ));
  } // github_handle_on_project_tabs

?>