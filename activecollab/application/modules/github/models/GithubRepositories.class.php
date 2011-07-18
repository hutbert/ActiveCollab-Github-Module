<?php

/**
 * Repositories
 *
 */
class GithubRepositories extends ProjectObjects {
  
  /**
   * Get repositories by project id and add last commit info
   *
   * @param int $project_id
   * @return array of objects
   */
  function findByProjectId($project_id) {
    $repositories = ProjectObjects::find(array(
      'conditions'  => "project_id = $project_id AND `type` = 'GithubRepository' AND state >='".STATE_VISIBLE."'",
      'order' => 'id asc'
    ));
    
    if (is_foreachable($repositories)) {
      foreach ($repositories as $repository) {
      	$repository->last_commit = $repository->getLastCommit();
      	if(!is_null($repository->last_commit) && !is_int($repository->last_commit->committed_date)) {
      	  $repository->last_commit->committed_date = strtotime($repository->last_commit->committed_date);
    	  }
    	  
      	$repository->last_tag = $repository->getLastTag();
      }
    }

    return $repositories;
  } // find repositories by project id
  
  
  /**
   * Find all repositories that match specific update type
   *
   * @param int $update_type
   * @return array
   */
  function findByUpdateType($update_type) {
    return ProjectObjects::find(array(
      'conditions'  => "`type` = 'GithubRepository' AND integer_field_2 = '$update_type' AND state != '".STATE_DELETED."'"
    ));
  } // find repositories by update type
  
  // ---------------------------------------------------
  //  Portal methods
  // ---------------------------------------------------
  
  /**
   * Return repository which was first added and last commit info
   *
   * @param Project $project
   * @return array
   */
  function findByPortalProject($project) {
  	$repository = ProjectObjects::find(array(
  		'conditions' => array('project_id = ? AND type = ? AND state >= ?', $project->getId(), 'GithubRepository', STATE_VISIBLE),
  		'order'      => 'created_on ASC',
  		'one'        => true
  	));
  	
  	if(instance_of($repository, 'GithubRepository')) {
  		$repository->last_commit = $repository->getLastCommit();
  	} // if
  	
  	return $repository;
  } // findByPortalProject
  
}

?>