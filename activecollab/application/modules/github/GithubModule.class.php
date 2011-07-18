<?php

  /**
   * Github module definition
   *
   * @package activeCollab.modules.github
   * @subpackage models
   */
  class GithubModule extends Module {
    
    /**
     * Plain module name
     *
     * @var string
     */
    var $name = 'github';
    
    /**
     * Is system module flag
     *
     * @var boolean
     */
    var $is_system = false;
    
    /**
     * Module version
     *
     * @var string
     */
    var $version = '1.0';
    
    // ---------------------------------------------------
    //  Events and Routes
    // ---------------------------------------------------
    
    /**
     * Define module routes
     *
     * @param Router $r
     * @return null
     */
    function defineRoutes(&$router) {
      $router->map( 'github', 
                    'projects/:project_id/github', 
                    array('controller' => 'github', 'action' => 'index'), 
                    array('project_id' => '\d+'));
      $router->map( 'github_add', 
                    'project/:project_id/github_add', 
                    array('controller' => 'github', 'action' => 'add'), 
                    array('project_id' => '\d+'));
      $router->map( 'github_repository_history', 
                    '/projects/:project_id/github_repositories/:github_repository_id/history', 
                    array('controller' => 'github', 'action'=>'history'), 
                    array('project_id' => '\d+', 'github_repository_id' => '\d+'));
      $router->map( 'github_commit_filepaths',
                    'projects/:project_id/github_repositories/:github_repository_id/commit/',
                    array('controller' => 'github', 'action' => 'commit_filepaths'),
                    array('project_id' => '\d+', 'github_repository_id' => '\d+'));
    } // defineRoutes
    
    /**
     * Define event handlers
     *
     * @param EventsManager $events
     * @return null
     */
    function defineHandlers(&$events) {
      //$events->listen('on_project_options', 'on_project_options');
      $events->listen('on_project_tabs', 'on_project_tabs');
    } // defineHandlers
    
    /**
     * Install this module
     *
     * @param void
     * @return boolean
     */
    function install() {
      mkdir(WORK_PATH.'/export', 0777);
      return parent::install();  
    } // install
    
    // ---------------------------------------------------
    //  Names
    // ---------------------------------------------------
    
    /**
     * Get module display name
     *
     * @return string
     */
    function getDisplayName() {
      return lang('Github');
    } // getDisplayName
    
    /**
     * Return module description
     *
     * @param void
     * @return string
     */
    function getDescription() {
      return lang('Github repository integration');
    } // getDescription
    
    /**
     * Return module uninstallation message
     *
     * @param void
     * @return string
     */
    function getUninstallMessage() {
      return lang('Module will be deactivated. Files created with this module will not be deleted');
    } // getUninstallMessage
    
  }

?>