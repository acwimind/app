<?php

class Rating extends AppModel {
	
	public $belongsTo = array(
		'Event',
		'Member',
	);
	
	public function hasRating($memberBig, $eventBig)
	{
		$db = $this->getDataSource();
		$sql = 'SELECT * FROM ratings WHERE member_big = ? AND event_big = ?';
		
		$rating = $db->fetchAll($sql, array($memberBig, $eventBig));
		return $rating;
	}
	
	public function insertRating($memBig, $eventBig, $rating)
	{
		$data = array(
			'Rating' => array(
				'event_big' => $eventBig,
				'member_big' => $memBig,
				'rating' => $rating,
				'created' => 'NOW()',
				'updated' => 'NOW()',
			)
		);
		
		//TODO: update rating_avg a rating_count in Place and Event
		
		return $this->save($data);
	}
	
	public function updateRating($memBig, $eventBig, $rating)
	{
		$db = $this->getDataSource();
		$sql = 'UPDATE ratings SET rating = ?, updated = NOW() WHERE member_big = ? AND event_big = ?';
		
		//TODO: update rating_avg a rating_count in Place and Event
		
		$rating = $db->fetchAll($sql, array($rating, $memBig, $eventBig));
		return $rating;
	}
	
	
}