<?php
class CommentsController extends AppController {
	public $uses = array('Place', 'Comment','Member','Friend','Advert','Photo');//load these models
	
	
	
	/**
	 * get board content for logged user 
	 */
	public function api_getComments() {
		$this->_checkVars ( array (
				'checkin_big'
		) );
		
		$MyComments= $this->Comment->getComments($this->api ['checkin_big']);
	
		$this->_apiOk ( $MyComments );
	}
	
	/**
	 * save a acooment
	 */
	public function api_saveComment() {
		
		// update existing member
	
	if ($this->Comment->saveComment($this->api ))	
		$this->_apiOk ( "Comment/Like Saved" );
		
		$this->_apiEr( "Comment/Like not saved" );
	}
	

}