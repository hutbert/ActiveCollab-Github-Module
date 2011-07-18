<?php

  /**
   * Github module initialization file
   * 
   * @package activeCollab.modules.project_exporter
   */
  
  define('GITHUB_MODULE', 'github');
  define('GITHUB_MODULE_PATH', APPLICATION_PATH . '/modules/github');
  
  require_once GITHUB_MODULE_PATH.'/models/GithubRepository.class.php';
  require_once GITHUB_MODULE_PATH.'/models/GithubRepositories.class.php';
  
  /**
   * Return section URL
   *
   * @param Project $project
   * @param array $additional_params
   * @return string
   */
  function github_module_url($project, $additional_params = null) {
    $params = array('project_id' => $project->getId());
    return assemble_url('github', $params);
  }
  
  /**
   * Get the URL to add a repository
   *
   * @param object $project
   * @return string
   */
  function github_module_add_repository_url($project) {
    return assemble_url('github_add',array('project_id'=>$project->getId()));
  } // add a repository URL
  
?>