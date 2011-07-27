<?php

  // We need project controller
  use_controller('project', SYSTEM_MODULE);

  /**
   * Github controller
   *
   * @package activeCollab.modules.github
   * @subpackage controllers
   */
  class GithubController extends ProjectController {
    
    /**
     * Controller name
     *
     * @var string
     */
    var $controller_name = 'github';
    
    /**
     * Active module
     *
     * @var string
     */
    var $active_module = GITHUB_MODULE;
    
    /**
     * Active repository
     *
     * @var Repository
     */
    var $active_repository = null;
    
    /**
     * Constructor
     *
     * @param Request $request
     * @return TicketsController
     */
    function __construct($request) {
      parent::__construct($request);
      
      if(!$this->logged_user->isAdministrator() && !$this->logged_user->isProjectLeader($this->active_project) && !$this->logged_user->isProjectManager()) {
        $this->httpError(HTTP_ERR_FORBIDDEN);
      } // if
      
      $repository_id = $this->request->get('github_repository_id');
      $this->active_repository = GithubRepositories::findById($repository_id);
      if (!instance_of($this->active_repository, 'GithubRepository')) {
        $this->active_repository = new GithubRepository();
      }
      
      $this->wireframe->print_button = false;
      $this->smarty->assign(array(
        'active_repository' => $this->active_repository,
        'project_tab' => GITHUB_MODULE,
        'page_tab' => 'github',
        'add_repository_url' => github_module_add_repository_url($this->active_project)
      ));
    }
    
    /**
     * Index - main page for github
     *
     * @param void
     * @return null
     */
    function index() {      
      if(Repository::canAdd($this->logged_user, $this->active_project)) {
        $this->wireframe->addPageAction(lang('Add Github Repository'), github_module_add_repository_url($this->active_project));
      } // if
      
      $repositories = GithubRepositories::findByProjectId($this->active_project->getId());
      
      $this->smarty->assign(array(
        'repositories' => $repositories,
      ));
      
    } // index
    
    /**
     * Add a Github repository
     *
     * @return void
     * @return null
     **/
    function add()
    {
      if(!Repository::canAdd($this->logged_user, $this->active_project)) {
        $this->httpError(HTTP_ERR_FORBIDDEN);
      } // if
      
      $repository_data = $this->request->post('repository');
      if(!is_array($repository_data)) {
        $repository_data = array(
        'visibility'       => $this->active_project->getDefaultVisibility(),
        );
      } // if
      
      if ($this->request->isSubmitted()) {
        $repository_data['name'] = trim($repository_data['name']) == '' ? $repository_data['url'] : $repository_data['name'];
        $this->active_repository->setAttributes($repository_data);
        $this->active_repository->setBody(clean(array_var($repository_data, 'url', null)));
        $this->active_repository->setProjectId($this->active_project->getId());
        $this->active_repository->setCreatedBy($this->logged_user);
        $this->active_repository->setState(STATE_VISIBLE);
        
        $result = $this->active_repository->testRepositoryConnection();
        if ($result === true) {
          $save = $this->active_repository->save();
          if ($save && !is_error($save)) {
            flash_success(lang('Project repository &quot;:name&quot; has been added successfully'), array('name'=>$this->active_repository->getName()));
            $this->redirectToUrl(github_module_url($this->active_project));
          } else {
            $save->errors['-- any --'] = $save->errors['body'];
            $this->smarty->assign('errors', $save);
          } //if
        }
        else {
          $errors = new ValidationErrors();
          $errors->addError(lang('Failed to connect to repository: :message', array('message'=>$result)));
          $this->smarty->assign('errors', $errors);
        } // if
      } // if
      
      $this->smarty->assign(Array(
        'repository_data' => $repository_data
      ));
    } // add
    
    /**
     * Repository history
     *
     * @return void
     * @author Chris Conover
     **/
    function history() 
    {
      $page       = intval(array_var($_GET, 'page')) > 0 ? array_var($_GET, 'page') : 1;
      $branch_tag = strval(array_var($_GET, 'branch_tag')) != '' ? array_var($_GET, 'branch_tag'): 'master';
      
      js_assign(  'commit_filepath_url', 
                  assemble_url('github_commit_filepaths', 
                    array('project_id' => $this->active_project->getId(),
                          'github_repository_id' => $this->active_repository->getID()
                    )
                  )
                );
      
      $commits = $this->active_repository->getBranchTagCommits($branch_tag, $page);
      
      // Group commits by days
      $grouped_commits = Array();
      $date_format = 'F j. Y';
      foreach($commits as $commit) {
        $commit->short_id = substr($commit->id, 0, 9).'...'.substr($commit->id, -9);
        $commit->message = $this->analyzeCommitMessage($commit->message);
                
        $commit_formatted_date = date($date_format, strtotime($commit->committed_date));
        if(count($grouped_commits) == 0 || !isset($grouped_commits[$commit_formatted_date])) {
          $grouped_commits[$commit_formatted_date] = Array($commit);
        } else {
          array_push($grouped_commits[$commit_formatted_date], $commit);
        }
      }
      
      $this->smarty->assign(Array(
        'path_info'  => strval(array_var($_GET, 'path_info')),
        'page'       => $page,
        'next_page'  => ($page + 1),
        'prev_page'  => (($page > 1) ? ($page - 1) : null),
        'branches'   => $this->active_repository->getBranches(),
        'tags'       => $this->active_repository->getTags(),
        'commits'    => $grouped_commits,
        'user'       => $this->active_repository->getUserName(),
        'repo'       => $this->active_repository->getRepoName(),
        'branch_tag' => $branch_tag
      ));
    }
    
    /**
     * Add AC object links to commit messages
     *
     * @param string
     * @return string
     **/
    private function analyzeCommitMessage($commit_message)
    {
      $pattern = '/(ticket|milestone|discussion|task)[s]*[\s]+[#]*(\d+)/i';
      
      if (preg_match_all($pattern, $commit_message, $matches)) {
        $i = 0;
        $search = array();
        $replace = array();
        
        $matches_unique = array_unique($matches['0']);
        
        foreach ($matches_unique as $key => $match) {
          $match_data = preg_split('/[\s,]+/', $match, null, PREG_SPLIT_NO_EMPTY);
                    
          $object_class_name = $match_data['0'];
        	$module_name = Inflector::pluralize($object_class_name);
        	$object_id = trim($match_data['1'], '#');
        	$search[$i] = $match;
        	
        	if (class_exists($module_name) && class_exists($object_class_name)) {
        	  $object = null;
        	  
        	  switch (strtolower($module_name)) {
        	  	case 'tickets':
        	  	  $object = Tickets::findByTicketId($this->active_project, $object_id);
        	  		break;
        	  	case 'discussions':
        	  	  $object = Discussions::findById($object_id);
        	  	  break;
        	  	case 'milestones':
        	  	  $object = Milestones::findById($object_id);
        	  	  break;
        	  	case 'tasks' :
        	  	  $object = Tasks::findById($object_id);
        	  	  break;
        	  } // switch
        	  
        	  if (instance_of($object, $object_class_name)) {
        	    $replace[$i] = '<a href="'.$object->getViewUrl().'">'.$match_data['0'].' '.$match_data['1'].'</a>';
        	  }
        	  else {
        	    $replace[$i] = '<a href="#" class="project_object_missing" title="'.lang('Project object does not exist in this project').'">'.$match_data['0'].' '.$match_data['1'].'</a>';
        	  } // if instance_of
        	  
        	  $i++;
        	} // if module loaded
        } // foreach
        
        return str_ireplace($search, $replace, htmlspecialchars($commit_message)); // linkify
      } // if preg_match
      return $commit_message;
    }
    
    /**
     * Fetch git commit file paths
     *
     * 
     * @return JSON
     **/
    function commit_filepaths()
    {
      $errors = Array();
      $commit_id = strval(array_var($_GET, 'commit_id')) != '' ? array_var($_GET, 'commit_id'): '';
      
      if($commit_id == '') {
        array_push($errors, 'Invalid commit id');
      } else {
        if( is_array($response = $this->active_repository->getCommitFilePaths($commit_id)) ) {
          $this->serveData($response, 'file_paths', null, FORMAT_JSON);
        } else {
          array_push($errors, $response);
        }
      }
      $this->serveData(Array('errors' => $errors), null, FORMAT_JSON);
    }
  }
?>