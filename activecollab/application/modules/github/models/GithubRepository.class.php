<?php

  /**
   * Repository class
   * 
   * @package activeCollab.modules.source
   */
  class GithubRepository extends ProjectObject {
    
    private static $API_URLS = Array( 
      'REPO_INFO' => 'https://github.com/api/v2/json/repos/show/%s/%s',
      'BRANCHES'  => 'https://github.com/api/v2/json/repos/show/%s/%s/branches',
      'TAGS'      => 'https://github.com/api/v2/json/repos/show/%s/%s/tags',
      'COMMITS'   => 'http://github.com/api/v2/json/commits/list/%s/%s/%s',
      'COMMIT'    => 'http://github.com/api/v2/json/commits/show/%s/%s/%s');
    
    /**
     * Permission name
     * 
     * @var string
     */
    var $permission_name = 'github_repository';
    
    /**
     * Project tab (compatibility with rel 1.1)
     * 
     * @var string
     */
    var $project_tab = 'github';
  
    /**
     * Log object activities
     *
     * @var booelan
     */
    var $log_activities = false;
    
    /**
     * Repositories can have subscribers
     *
     * @var boolean
     */
    var $can_have_subscribers = true;
    
    /**
     * Name of the route used for portal view URL
     *
     * @var string
     */
    var $portal_view_route_name = 'portal_github_repository';
    
    /**
     * Name of the object ID variable used alongside project_id when URL-s are
     * generated (eg. comment_id, task_id, message_category_id etc)
     *
     * @var string
     */
    var $object_id_param_name = 'github_repository_id';
  
    /**
     * Fields used by this module
     *
     * @var array
     */
    var $fields = array(
    'id',
    'type', 'module',
    'project_id',
    'name',
    'body',
    'created_on', 'created_by_id', 'created_by_name', 'created_by_email',
    'updated_on', 'updated_by_id', 'updated_by_name', 'updated_by_email',
    'varchar_field_1', // username
    'varchar_field_2', // password
    'text_field_1', // repository url
    'state', 'visibility',
    );
  
    /**
     * Field map
     *
     * @var array
     */
    var $field_map = array(
    'root_path' => 'body',
    'username' => 'varchar_field_1',
    'password' => 'varchar_field_2',
    'url' => 'text_field_1'
    );
  
  
    /**
     * Construct a new repository
     *
     * @param int $id
     */
    function __construct() {
      parent::__construct();
  
      $this->setModule(GITHUB_MODULE);
  
    } // __construct
    
    /**
     * Returns true if $user can create a new repository in $project
     *
     * @param User $user
     * @param Project $project
     * @return boolean
     */
    function canAdd($user, $project) {
      return ProjectObject::canAdd($user, $project, 'github_repository');
    } // canAdd
    
    
    /**
     * undocumented function
     *
     * @param void
     * @return True or String
     **/
    function testRepositoryConnection()
    {
      if( ($url_info = $this->userRepoFromUrl()) !== False) {
        $request_url = sprintf(GithubRepository::$API_URLS['REPO_INFO'], $url_info['user'], $url_info['repo']);
        if(is_object($response = GithubRepository::githubAPIRequest($request_url))) {
          return True;
        } else {
          return $response;
        }
      } else {
        return 'Unable to extract username and repository name from URL string.';
      }
    }
    
    /**
     * Fetch branch list
     *
     * @param void
     * @return array or null
     **/
    function getBranches()
    {
      $cache_key = 'github_branches-'.$this->getRepoName();
      $cache_duration = 60 * 10; // 10 minutes
      
      if( !is_null($cache_val = cache_get($cache_key)) && $cache_val[1] > (time() - $cache_duration) ) {
        return $cache_val[0];
      } else {
        if( ($url_info = $this->userRepoFromUrl()) !== False) {
          $request_url = sprintf(GithubRepository::$API_URLS['BRANCHES'], $url_info['user'], $url_info['repo']);
          if( is_object($response = GithubRepository::githubAPIRequest($request_url)) ) {
            cache_set($cache_key, Array($response->branches, time()));
            return $response->branches;
          }
        }
        return null;
      }
    }
    
    /**
     * Fetch tag list
     *
     * @param void
     * @return array
     **/
    function getTags()
    {
      $cache_key = 'github_tags-'.$this->getRepoName();
      $cache_duration = 60 * 10; // 10 minutes
      
      if( !is_null($cache_val = cache_get($cache_key)) && $cache_val[1] > (time() - $cache_duration) ) {
        return $cache_val[0];
      } else {
        if( ($url_info = $this->userRepoFromUrl()) !== False) {
          $request_url = sprintf(GithubRepository::$API_URLS['TAGS'], $url_info['user'], $url_info['repo']);
          if( is_object($response = GithubRepository::githubAPIRequest($request_url)) ) {
            cache_set($cache_key, Array($response->tags, time()));
            return $response->tags;
          }
        }
        return null;
      }
    }
    
    /**
     * Get last tag 
     *
     * @return void
     * @author Chris Conover
     **/
    function getLastTag()
    {
      if($this->testRepositoryConnection() !== False) {
        if( !is_null($tags = $this->getTags()) ) {
          /*
          // Checks the most recent commit of each tag and select the one
          // with the most recent commit
          $last_tag = null;
          $last_commit = null;
          foreach($response->tags as $name => $hash) {
            if( is_array($_response = $this->getBranchTagCommits($name))) {
              if( is_null($last_commit) || strtotime($_response[0]->committed_date) > strtotime($last_commit->committed_date)) {
                $last_commit = $_response[0];
                $last_tag = Array('name' => $name, 'hash' => $hash, 'commit' => $last_commit);
              }
            }
          }
          return $last_tag;
          */
          
          $tag_names = Array(); // Some kind of iterable object is returned, array_keys doesn't work
          foreach($tags as $name => $hash) array_push($tag_names, $name);
          usort($tag_names, create_function('$a,$b', 'return version_compare($a, $b);'));
          return $tag_names[count($tag_names) - 1];
        }
      }
      return null;
    }
    
    /**
     * Got through each branch and find the last commit
     *
     * @param void
     * @return string
     **/
    function getLastCommit()
    {
      $cache_key = 'github_last_commit-'.$this->getRepoName();
      $cache_duration = 60 * 10; // 10 minutes
      
      if( !is_null($cache_val = cache_get($cache_key)) && $cache_val[1] > (time() - $cache_duration) ) {
        return $cache_val[0];
      } else {
        $last_commit = null;
        if(($url_info = $this->userRepoFromUrl()) !== False) {
          if( !is_null($response = $this->getBranches())) {
            foreach($response as $name=>$hash) {
              if( is_array($_response = $this->getBranchTagCommits($name)) ) {
                if(is_null($last_commit) || strtotime($_response[0]->committed_date) > strtotime($last_commit->committed_date)) {
                  $last_commit = $_response[0];
                }
              }
            }
          }
        }
        if(!is_null($last_commit)) cache_set($cache_key, Array($last_commit, time()));
        return $last_commit;
      }
    }
    
    /**
     * Fetch commits associated with a branch or a tag
     *
     * @param string
     * @return array
     **/
    function getBranchTagCommits($name, $page = 1)
    {
      $cache_key = 'github_branchtag_commits-'.implode('-', Array($this->getRepoName(), $name, $page));
      $cache_duration = 60 * 10; // 10 minutes
      
      if( !is_null($cache_val = cache_get($cache_key)) && $cache_val[1] > (time() - $cache_duration) ) {
        return $cache_val[0];
      } else {
      
        if(($url_info = $this->userRepoFromUrl()) !== False) {
          $request_url = sprintf(GithubRepository::$API_URLS['COMMITS'], $url_info['user'], $url_info['repo'], $name);
        
          if($page > 0)  $request_url .= '?page='.$page;
        
          if( is_object($response = GithubRepository::githubAPIRequest($request_url)) ) {
            cache_set($cache_key, Array($response->commits, time()));
            return $response->commits;
          } else {
            $error = $response;
          }
        }
        return $error;
      }
    }
    
    /**
     * Fetch details of a specific commit
     *
     * @return void
     * @author Chris Conover
     **/
    function getCommitFilePaths($hash)
    {
      $cache_key = 'github_commit_filepaths-'.$hash;
      $cache_duration = 60 * 10; // 10 minutes
      
      if( !is_null($cache_val = cache_get($cache_key)) && $cache_val[1] > (time() - $cache_duration) ) {
        return $cache_val[0];
      } else {
        
        if( ($url_info = $this->userRepoFromUrl()) !== False) {
          $request_url = sprintf(GithubRepository::$API_URLS['COMMIT'], $url_info['user'], $url_info['repo'], $hash);
        
          if( is_object($response = GithubRepository::githubAPIRequest($request_url)) ) {
            $file_paths = Array('added' => Array(), 'modified' => Array(), 'removed' => Array());
            foreach($response->commit->modified as $mod) {
              array_push($file_paths['modified'], $mod->filename);
            }
            foreach($response->commit->added as $mod) {
              array_push($file_paths['added'], $mod);
            }
            foreach($response->commit->removed as $mod) {
              array_push($file_paths['removed'], $mod);
            }
            cache_set($cache_key, Array($file_paths, time()));
            return $file_paths;
          } else {
            $error = $response;
          }
        }
        return $error;
      }
    }
    
    /**
     * Get view URL
     *
     * @return string
     */
    function getViewUrl() {
      return $this->getHistoryUrl();
    } // getViewUrl
  
    /**
     * Get repository history URL
     *
     * @param null
     * @return string
     */
    function getHistoryUrl($commit_author = null) {
      $params = array('github_repository_id'=>$this->getId(),'project_id'=>$this->getProjectId());
            
      return assemble_url('github_repository_history', $params);
    } // get history URL
    
    /**
     * Parse user and repo name
     *
     * @param void
     * @return Array
     **/
    function userRepoFromUrl()
    {
      if(preg_match('/^https:\/\/github.com\/(?<user>[^\/]+)\/(?<repo>[^\/]+)$/', $this->getUrl(), $matches) == 1) {
        return $matches;
      } else {
        return False;
      }
    }
    
    /**
     * User or org of Github repo
     *
     * @param void
     * @return string or null
     **/
    function getUserName()
    {
      $url_info = $this->userRepoFromUrl();
      if($url_info !== False) {
        return $url_info['user'];
      }
      return null;
    }
    
    /**
     * Name of Github Repo
     *
     * @param void
     * @return string or null
     **/
    function getRepoName()
    {
      $url_info = $this->userRepoFromUrl();
      if($url_info !== False) {
        return $url_info['repo'];
      }
      return null;
    }
    
    /**
     * Get repository URL
     *
     * @return string
     */
    function getUrl() {
      return str_replace(' ', '%20', $this->getFieldValue('text_field_1'));
    } // getUrl
    
    /**
     * Github API Request
     *
     * @return void
     * @author Chris Conover
     **/
    private static function githubAPIRequest($url, $data = NULL)
    {
      if( ($json = file_get_contents($url)) !== False) {
        if( !is_null($obj = json_decode($json)) ) {
          return (isset($obj->errors)) ? $obj->errors : $obj;
        } else {
          return 'JSON cannot be decoded or recursion too deep.';
        }
      } else {
        return 'Unable to complete API request.';
      }
    }
  }

?>