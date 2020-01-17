<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Crud extends CI_Model {

    /*-----------

    generic CRUD model by Eojin K.

    Purpose: prevent the coder's shitting around with the most expected/frequent queries.
    
    -----------*/

    public function __construct () {
        parent::__construct();
        $this->load->database();

        $this->delimiter = ', ';
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
    // $who => 'human' or true for humanized names only, 'robot' or false or default for DB field keys only or 'world' for both. utilizing $this->h() function.
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
                                $limit = intval($value[0]);
                                $offset = isset($value[1]) ? intval($value[1]) : 0;
                                $this->db->limit($limit, $offset);
                                break;

                            case 'order' :
                                foreach ($value as $field => $order) {
                                    $this->db->order_by($field, $order);
                                }
                                break;
                            
                            default:
                                if (isset($value)) : $this->db->where($key, $value); endif;
                                break;
                        }
                    }
                }
                if (is_array($what)) {
                    $this->db->select($what['fields']);
                    $this->db->from($what['table']);
                    $query = $this->db->get();
                } else {
                    $query = $this->db->get($what);
                }

            } else {
                $the_query = "SELECT ";

                $what_table = is_array($what) ? $what['table'] : $what;				

                $comments = $this->h($what_table);
                $fields_query = $this->db->list_fields($what_table);
                $what_fields = is_array($what) ? explode($this->delimiter, $what['columns']) : $fields_query;

                for ($i = 0; $i < count($what_fields); $i++) { 
                    $comment = $comments[$i];
                    if (in_array($fields_query[$i], $what_fields)) {
                        $the_query .= "`$fields_query[$i]` AS '$comment'";
                        if ($i < count($what_fields) - 1) : $the_query .= $this->delimiter; endif;
                    }
                }
                if ($who === 'world') {
                    $the_query .= $this->delimiter;
                    for ($i = 0; $i < count($what_fields); $i++) {
                        if (in_array($fields_query[$i], $what_fields)) {
                            $the_query .= "`$fields_query[$i]`";
                            if ($i < count($what_fields) - 1) : $the_query .= $this->delimiter; endif;
                        }
                    }
                }
                $the_query .= " FROM `$what_table`";
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
                                foreach ($value as $field => $order) {
                                    $the_query .= " ORDER BY `$field` $order";
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

                case 'row':
                    return $query->row();
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

            foreach ($what as $key_to_update => $value_to_update) {
                if ($this->o($where, $key_to_update, $value_to_update)) {
                    $this->db->set($key_to_update, $value_to_update, false);
                } else {
                    $this->db->set($key_to_update, $value_to_update);
                }
            }

            // array('uid', 47)
            if (count($when) == 2) {
                $this->db->where($when[0], $when[1]);
            // array('uid' => 47)
            } else {
                $this->db->where(array_keys($when)[0], array_values($when)[0]);
            }

            $this->db->update($where);

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

    // m etadata please?
    // $where => table name
    // $what => certain field name or null for all the fields
    public function metadata ($where, $what = null) { return $this->m($where, $what); }
    public function m ($where, $what = null) {

        $fields = $this->db->field_data($where);
        if ($what) {
            foreach ($fields as $field) {
                if ($field->name == $what) {
                    return $field;
                }
            }
        } else {
            return $fields;
        }
    }

    // o perator is there?
    // return true if the field type is int and the input value contains operator
    // $where => table name
    // $what => field to check
    // $how => value to check
    public function operator ($where, $what, $how) { return $this->o($where, $what, $how); }
    public function o ($where, $what, $how) {

        return ($this->m($where, $what)->type == 'int') && preg_match('/\w+\s?\W+\s?\d+/', $how);
    }
}