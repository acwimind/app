<?php

class Category extends AppModel {
	
	public $hasMany = array(
		'CatLang',
	);
	
	public $validate = array(
		'name' => array(
			'rule' => array('minLength', 2),
			'message' => 'Please fill in category name',
		),
	);
	
	public function getCatLangs($id)
	{
		$data = $this->findById($id);
		
		$entries = $data['CatLang'];
		unset($data['CatLang']);
		
		foreach ($entries as $entry)
		{
			$data['CatLang'][$entry['language_id']] = $entry;
		}
		
		return $data;
	}
	
	public function getOne($id) {
		
		$data = $this->findById($id);
		$data['CatLang'] = $data['CatLang'][ CURRENT_LANG ];
		return $data;
		
	}
	public function getCategoriesWithPlaceCounts($where, $search)
	{
		$db = $this->getDataSource();
		$query = 'SELECT categories.id AS "Category__id", categories.name AS "Category__name", pcount.count AS "Category__count" FROM categories 
			LEFT JOIN (
				SELECT category_id, COUNT(*) FROM places 
					LEFT JOIN (
					 SELECT events.place_big, COUNT(*) FROM events 
					 WHERE  (events.start_date IS NULL or events.start_date < now()) AND (events.end_date IS NULL or events.end_date > now()) 
					 AND (events.status = 1)
					' . (!empty($search) ? 'AND to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ events.tsv ' : '' ) .
			 		' GROUP BY events.place_big
					) as evts ON evts.place_big = places.big
					WHERE status < 255 
					' . (!empty($search) ? 'AND to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ places.tsv ' : '' ) .
					(!empty($where) ? '  AND ' . implode(' AND ', $where) . ' ' : '' ) .
			 	' GROUP BY category_id
				) AS pcount ON (categories.id = pcount.category_id) 
				ORDER BY categories.name ASC ';
		
		$categories = $db->fetchAll($query);
		
		// Add All venues - no category
		$sum = 0;
		foreach ($categories as $cat)
		{
			$sum += $cat['Category']['count'];
		}
		$allCat = array(
			0 => array(
				'Category' => array(
					'id' => 0,
					'name' => 'All venues',
					'count' => $sum
				)
			)
		);
		$categories = array_merge($allCat, $categories);
		
		return $categories;
	}
	
	public function getCategoriesWithEventCounts($where)
	{
		$db = $this->getDataSource();
		$query = 'SELECT 
				categories.id AS "Category__id", 
				categories.name AS "Category__name", 
				pcount.count AS "Category__count"
			FROM categories
			LEFT JOIN (SELECT places.category_id, COUNT(events.big) 
				FROM events
				INNER JOIN places ON (events.place_big = places.big) AND places.status != 255
				WHERE events.status != 255 AND 
				events.type != 2 
				AND events.start_date < \'now\' AND events.end_date > \'now\'
				' . (!empty($where) ? '  AND ' . implode(' AND ', $where) . ' ' : '' ) .
				' GROUP BY places.category_id) AS pcount ON (categories.id = pcount.category_id) 
				ORDER BY categories.name ASC ';
		
		$categories = $db->fetchAll($query);
		
		// Add All events - no category
		$sum = 0;
		foreach ($categories as $cat)
		{
			$sum += $cat['Category']['count'];
		}
		$allCat = array(
			0 => array(
				'Category' => array(
					'id' => 0,
					'name' => 'All events',
					'count' => $sum
				)
			)
		);
		$categories = array_merge($allCat, $categories);
		
		return $categories;
	}
	
	public function getAll() {
	
		$data = array();
		$raw_data = $this->find('all');
		
		foreach($raw_data as $key=>$val) {
			$current_lang = array();
			foreach($val['CatLang'] as $v) {
				if ($v['language_id'] == CURRENT_LANG) {
					$current_lang = $v;
				}
			}
			$data[ $val['Category']['id'] ] = array(
				'Category' => $val['Category'],
				'CatLang' => $current_lang,
			);
		}
		
		return $data;
	
	}
	
	
}