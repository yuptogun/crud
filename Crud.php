<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Crud extends CI_Model {

	/*-----------

	generic CRUD model by Eojin K.

	Purpose: prevent the coder's shitting around with the most expected/frequent queries.
	
	-----------*/

	public function __construct () {
		parent::__construct();
		$this->load->database();
	}

	// c reate
	// $where => table name
	// $what => array of the queries
	// $how => 'json' for {result: true/false} json, default for simple PHP boolean
	public function create ($where, $what, $how = null) { return $this->c($where, $what, $how); }
	public function c ($where, $what, $how = null) {

		if (!isset($where) || !isset($what)) {
			return false;
		} else {

			$this->db->insert($where, $what);
			$query = ($this->db->affected_rows() > 0);

			switch ($how) {
				case 'json':
					$json = array('result' => $query);
					return json_encode($json);
					break;
				
				default:
					return $query;
					break;
			}
		}
	}

	// r ead
	// $what => table name
	// $where => array of the queries. set null to get all rows
	// $how => 'json' for json, 'array' for pure array or default for array of objects
	// $who => 'human' or true for humanized names only, 'robot' or false or default for DB column keys only or 'world' for both. utilizing $this->h() function.
	public function read ($what, $where = null, $how = null, $who = false) { return $this->r($what, $where, $how, $who); }
	public function r ($what, $where = null, $how = null, $who = false) {

		if (!isset($what)) {
			return false;
		} else {
			if ($who === false || $who === 'robot') {
				if (is_array($where)) {
					foreach ($where as $key => $value) {
						switch ($key) {
							case 'limit' :
								if (isset($value['limit'])) {
									$limit = intval($value[0]);
									$offset = isset($value[1]) ? intval($value[1]) : 0;
									$this->db->limit($limit, $offset);
								}
								break;

							case 'order' :
								foreach ($value as $column => $order) {
									$this->db->order_by($column, $order);
								}
								break;
							
							default:
								if (isset($value)) : $this->db->where($key, $value); endif;
								break;
						}
					}
				}
				$query = $this->db->get($what);

			} else {
				$the_query = "SELECT ";
				$comments = $this->h($what);
				$columns_query = $this->db->list_fields($what);
				for ($i=0; $i < count($columns_query); $i++) { 
					$comment = $comments[$i];
					$the_query .= "`$columns_query[$i]` AS '$comment'";
					if ($i < count($columns_query) - 1) : $the_query .= ', '; endif;
				}
				if ($who === 'world') {
					for ($i=0; $i < count($columns_query); $i++) { 
						$the_query .= ", `$columns_query[$i]`";
					}
				}
				$the_query .= " FROM `$what`";
				if (is_array($where)) {
					foreach ($where as $key => $value) {
						switch ($key) {
							case 'limit' :
								if (isset($value['limit'])) {
									$limit = intval($value[0]);
									$offset = isset($value[1]) ? intval($value[1]) : 0;
									$the_query .= " LIMIT $limit OFFSET $offset";
								}
								break;
	
							case 'order' :
								foreach ($value as $column => $order) {
									$the_query .= " ORDER BY `$column` $order";
								}
								break;
							
							default:
								if (isset($value)) {
									if (is_int($value)) {
										$the_query .= " WHERE $key = $value";
									} else {
										$the_query .= " WHERE $key = '$value'";
									}
								}
								break;
						}
					}
				}
				$query = $this->db->query($the_query);
			}

			switch ($how) {
				case 'json':
					return json_encode($query->result_array());
					break;

				case 'array':
					return $query->result_array();
					break;

				default:
					return $query->result();
					break;
			}
		}
	}

	// u pdate
	// $where => table name
	// $when => array of unique key match condition. array('uid', 47) || array('uid' => 47)
	// $what => array of the values under the existing columns
	// $how => 'json' for {result: true/false} json, default for simple PHP boolean
	public function update ($where, $when, $what, $how = null) { return $this->u($where, $when, $what, $how); }
	public function u ($where, $when, $what, $how = null) {

		if (!isset($where) || !isset($when) || !is_array($when)) {
			return false;
		} else {

			$this->db->trans_begin();

			// array('uid', 47)
			if (count($when) == 2) {
				$this->db->where($when[0], $when[1]);
				$this->db->update($where, $what);

			// array('uid' => 47)
			} else {
				$this->db->update($where, $what, $when);
			}

			$this->db->trans_complete();

			switch ($how) {
				case 'json':
					$json = array('result' => $this->db->trans_status());
					return json_encode($json);
					break;
				
				default:
					return $this->db->trans_status();
					break;
			}	
		}
	}

	// d elete
	// $where => table name
	// $what => array of unique key match condition. array('uid', 47) || array('uid' => 47)
	// $how => 'json' for {result: true/false} json, default for simple PHP boolean
	public function delete ($where, $what, $how = null) { return $this->d($where, $what, $how); }
	public function d ($where, $what, $how = null) {

		if (!isset($where) || !isset($what) || !is_array($what)) {
			return false;
		} else {

			if (count($what) == 2) {
				$this->db->where($what[0], $what[1]);
				$this->db->delete($where);
			} else {
				$this->db->delete($where, $what);
			}

			$query = ($this->db->affected_rows() > 0);

			switch ($how) {
				case 'json':
					$json = array('result' => $query);
					return json_encode($json);
					break;
				
				default:
					return $query;
					break;
			}
		}
	}

	// h umanize column names
	// $where => table name to returns array of the proper column names, utilizing column comment and column name itself
	// $how => set true to get associative array like ['uid' => 'unique ID', 'modified_at' => 'The Last Modification', ...]
	// $how => set false to get simple array like ['unique ID', 'The Last Modification', ...]
	public function humanize_column_names ($where, $how = false) { return $this->h($where, $how); }
	public function humanize_columns ($where, $how = false) { return $this->h($where, $how); }
	public function humanize_column ($where, $how = false) { return $this->h($where, $how); }
	public function humanize ($where, $how = false) { return $this->h($where, $how); }
	public function h ($where, $how = false) {

		$return = array();
		$names_query = "SELECT `COLUMN_NAME`, `COLUMN_COMMENT` FROM `INFORMATION_SCHEMA`.COLUMNS WHERE TABLE_NAME = '$where'";
		$names = $this->db->query($names_query)->result_array();
		foreach ($names as $name) {
			if ($how) {
				$key = $name['COLUMN_NAME'];
				$value = $name['COLUMN_COMMENT'];
				$return[$key] = $value;
			} else {
				$column_name = ($name['COLUMN_COMMENT'] != '') ? $name['COLUMN_COMMENT'] : $name['COLUMN_NAME'] ;
				array_push($return, $column_name);
			}
		}
		return $return;
	}

	// s et the row regardless of whether it is already inserted or not
	// $where => table name
	// $when => array of unique key match condition. array('uid', 47) || array('uid' => 47)
	// $what => array of the values under the existing columns
	// $how => 'json' for {result: true/false} json, default for simple PHP boolean
	public function set ($where, $when, $what, $how = null) { return $this->s($where, $when, $what, $how); }
	public function s ($where, $when, $what, $how = null) {

		if (count($this->r($where, $when)) == 1) :
			return $this->u($where, $when, $what, $how);
		else :
			return $this->c($where, $what, $how);
		endif;
	}
}