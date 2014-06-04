<?php

App::uses('Model', 'Model');


class AppModel extends Model {
	
	public $inserted_ids = array();
	
	public function beforeFind($queryData) {
		
		foreach($this->schema() as $field=>$schema) {
			if ($field == 'status') {
				$queryData['conditions'][ $this->alias . '.status !=     ' ] = DELETED;
				break;
			}
		}
		
		return $queryData;
	
	}
	
	/*
	 * after saving a model that is "gem", duplicate it in gems table
	 */
	public function afterSave($created) {

		if($created) {
			$this->inserted_ids[] = $this->getLastInsertID(); //$this->getInsertID();
		}
		
		if ($this->primaryKey == 'big' && in_array($this->name, Defines::$gems)) {	//if this model has BIG as primary key and is a Gem, save it to Gem table
			
			App::import('Model', 'Gem');
			
			$gem_data = array(
				'big' => isset($this->data[ $this->name ]['big']) ? $this->data[ $this->name ]['big'] : null,
				'type' => array_search($this->name, Defines::$gems),
				'member_big' => isset($this->data[ $this->name ]['member_big']) ? $this->data[ $this->name ]['member_big'] : null,
				'product_big' => isset($this->data[ $this->name ]['product_big']) ? $this->data[ $this->name ]['product_big'] : null,
			);
			
			if (isset($this->data[ $this->name ]['created'])) {
				$gem_data['created'] = $this->data[ $this->name ]['created'];
			}
			
			if ( in_array($this->name, array('Comment', 'Photo')) ) {	//related Gem (BIG and type)
				$gem_data['related_big'] = isset($this->data[ $this->name ]['related_big']) ? $this->data[ $this->name ]['related_big'] : null;
				$gem_data['related_type'] = isset($this->data[ $this->name ]['related_type']) ? $this->data[ $this->name ]['related_type'] : null;
			}
			
			//GEM data
			{
				//TODO: get data other way than via $_POST / $_GET (but we do not have access to $this->api in this class)
				$coords_lat = null;
				if (isset($_POST['coords_lat'])) {
					$coords_lat = $_POST['coords_lat'];
				} elseif(isset($_GET['coords_lat'])) {
					$coords_lat = $_GET['coords_lat'];
				}
				if (!preg_match('/^[\-+]{0,1}[0-9]+(\.[0-9]+)?$/', $coords_lat)) {
					$coords_lat = null;
				}
				
				$coords_lon = null;
				if (isset($_POST['coords_lon'])) {
					$coords_lon = $_POST['coords_lon'];
				} elseif(isset($_GET['coords_lon'])) {
					$coords_lon = $_GET['coords_lon'];
				}
				if (!preg_match('/^[\-+]{0,1}[0-9]+(\.[0-9]+)?$/', $coords_lon)) {
					$coords_lon = null;
				}
				
				$privacy = 0;
				if (isset($_POST['privacy'])) {
					$privacy = trim($_POST['privacy']);
				} elseif(isset($_GET['privacy'])) {
					$privacy = trim($_GET['privacy']);
				}
				
				$privacy = intval($privacy);
				if ($privacy != 0 && $privacy != 1) // !in_array($privacy, array(0,1))
					$privacy = 0;
				
				if (isset($coords_lon) && isset($coords_lat)) {
					$gem_data['coords'] = '('.floatval($coords_lon).','.floatval($coords_lat).')';
				} else {
					$gem_data['coords'] = null;
				}
				$gem_data['privacy'] = $privacy;
			}
			
			$gem = new Gem();
			$gem->set(array(	//save data as Gem
				'Gem' => $gem_data,
			));
			$gem->save();
			$gem_data['big'] = $gem->id;
			
			$this->gem = $gem_data;
			
		}
		
		if (isset(Defines::$stats) && array_key_exists($this->name, Defines::$stats)) {	//should the data for this model be cached in any *Stats model?
			
			foreach(Defines::$stats[$this->name] as $stat) {
				
				$stat_model = $stat[0];
				$stat_field = $stat[1];
				$stat_related = isset($stat[2]) ? $stat[2] : false;
				
				if (!class_exists($stat_model)) {
					App::import('Model', $stat_model);
				}
				
				if (!isset($this->data[ $this->name ]['state']) || $this->data[ $this->name ]['state'] == ACTIVE) {
					$modification = '+1';
				} else {
					$modification = '-1';
				}
				
				$stats = new $stat_model();
					
				$stat_data = array($stat_model => array(
					$stat_field => DboSource::expression('"'.$stat_field.'"' . $modification)
				));
				
				if (!is_array($stats->mappedKeys)) {
					$stats->mappedKeys = array($stats->mappedKeys);
				}
				
				foreach($stats->mappedKeys as $from => $to) {
					if (is_numeric($from)) {
						$from = $to;
					}
					$stat_data[ $stat_model ][ $from ] = $this->data[ $this->name ][ $to ];
				}
				
				try {
					$stats->save($stat_data);
				} catch (Exception $e) {
					if ($e->errorInfo[0] == 42703) {	//42703 : "IS NOT VALID IN THE CONTEXT WHERE IT IS USED" ->cannot increase stat (statfield = stat_field + 1)
						$stat_data[$stat_model][$stat_field] = $modification>0 ? 1 : 0;
						$stats->save($stat_data);
					}
				}
				unset($stats);
				
			}
			
		}
		
	}
	
	/*
	 * Find primary_keys of model via HABTM relation
	 */
	public function findIds($query) {
		
		$primary_keys = array();
		
		foreach($query as $field => $condition) {
			
			if (empty($condition)) {
				continue;
			}
			
			list($model, $column) = explode('.', $field);
			if (empty($model) || empty($column)) {
				continue;
			}
			if (!isset($this->{$model})) {
				continue;
			}
			
			if ( $column == end(explode('_', $this->hasAndBelongsToMany[ $model ]['associationForeignKey'])) ) {	//searching via FK of related table - search in "joinTable"
				
				$where = $this->getDataSource()->conditions(array($this->hasAndBelongsToMany[ $model ]['associationForeignKey'] => $condition));
				
				$primary_keys_from_join_table  = $this->query(
					'select ' . $this->hasAndBelongsToMany[ $model ]['foreignKey'] . ' as "foreign_key"'
					.' from ' . $this->hasAndBelongsToMany[ $model ]['joinTable']
					.$where
				);
				foreach($primary_keys_from_join_table as $item) {
					$primary_keys[] = $item[0]['foreign_key'];
				}
				
			} else {	//searching via other field than FK - search in "models table" and join to "joinTable"
				
				$where = $this->getDataSource()->conditions(array($field => $condition));
				
				$m = $this->hasAndBelongsToMany[ $model ];
				
				$primary_keys_from_join_table  = $this->query(
					'select "' . $m['foreignKey'] . '" as "foreign_key"'
					.' from "' . $m['joinTable'] . '" as "join_table"'
					.' left join "' . $this->{$model}->table . '" as "' . $model .
						'" on "' . $model . '"."' . $this->{$model}->primaryKey . '" = "join_table"."' . $m['associationForeignKey'] . '"'
					.$where
				);
				foreach($primary_keys_from_join_table as $item) {
					$primary_keys[] = $item[0]['foreign_key'];
				}
				
			}
			
		}
		
		return $primary_keys;
		
	}
	
	/*
	 * override of exists for models with $primaryKeyArray (multi column PK)
	 */
	public function exists($reset = null) {
		
		if (!isset($this->primaryKeyArray)) {	//no multi column PK
			return parent::exists($reset);
		}
		
		if (!empty($this->__exists) && $reset !== true) {
			return $this->__exists;
		}
		$conditions = array();
		$fields = array();
		foreach ($this->primaryKeyArray as $pk) {
			if (isset($this->data[$this->alias][$pk]) && $this->data[$this->alias][$pk]) {
				$conditions[$this->alias.'.'.$pk] = $this->data[$this->alias][$pk];
			}
			else {
				$conditions[$this->alias.'.'.$pk] = 0;
			}
			$fields[] = $this->alias.'.'.$pk;
		}
		
		$query = array('conditions' => $conditions, 'fields' => $fields, 'recursive' => -1, 'callbacks' => false);
		if (is_array($reset)) {
			$query = array_merge($query, $reset);
		}
		if ($exists = $this->find('first', $query)) {
			$this->__exists = 1;
			$values = array();
			foreach($this->primaryKeyArray as $pk) {
				$values[$pk] = $exists[$this->alias][$pk];
			}
			$this->id = $values;
			return true;
		}
		else {
			return parent::exists($reset);
		}
	}
	
}
