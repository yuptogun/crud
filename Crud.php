<?php

/**
 * generic CodeIgniter model for little more structured query building
 * 
 * damn i had no idea how amateur i was had i?
 * 
 * @author yuptogun <eojin1211@hanmail.net>
 */
class Crud extends CI_Model
{
    /**
     * delimiter
     *
     * @var string
     */
    public $delimiter = ', ';

    /**
     * take a database connection name to initiate
     *
     * @param string $database
     * @param string $delimiter
     */
    public function __construct($database = null, $delimiter = null)
    {
        parent::__construct();

        $this->load->database($database);

        if ($delimiter) $this->delimiter = $delimiter;
    }

    /**
     * c reate
     * 
     * basically it does exactly what the basic $this->db->insert() do
     *
     * @param string $where what table to deal with
     * @param array $what all the VALUES in an array
     * @param string $how how you want to get the result. 'json' or null
     * @return string|bool
     * @todo support mass create
     */
    public function c($where, $what, $how = null)
    {
        if (empty($where) || empty($what)) return false;

        $this->db->insert($where, $what);
        $result = ($this->db->affected_rows() > 0);

        switch ($how) {
            case 'json':
                return json_encode(compact('result'));
                break;
            
            default:
                return $result;
                break;
        }
    }

    /**
     * alias of $this->c()
     *
     * @param string $where
     * @param array $what
     * @param string $how
     * @return string|bool
     */
    public function create($where, $what, $how = null)
    {
        return $this->c($where, $what, $how);
    }

    /**
     * r ead
     *
     * @param string $what what table to deal with
     * @param array $where all the WHEREs in an array of the queries, set null to get all rows whatsoever
     * @param string $how how you want to get the results. 'json', 'array', 'row' or null
     * @param mixed $who for whom the field names should be provided, 'human', 'robot' or 'world'. utilizing $this->h().
     * @return array|string
     */
    public function r($what, $where = null, $how = null, $who = false)
    {
        if (empty($what)) return false;

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

            // https://github.com/yuptogun/crud/issues/1
            case 'row':
                return $query->row();
                break;

            default:
                return $query->result();
                break;
        }
    }

    /**
     * alias of $this->r()
     * 
     * @param string $what
     * @param array $where
     * @param string $how
     * @param mixed $who
     * @return array|string
     */
    public function read($what, $where = null, $how = null, $who = false)
    {
        return $this->r($what, $where, $how, $who);
    }

    /**
     * u pdate
     *
     * @param string $where which table to update
     * @param array $when when some of the data should be updated. ['uid', 47] or ['uid' => 47]
     * @param array $what what you want that data to be
     * @param string $how how you want to get the result
     * @return string|bool
     */
    public function u($where, $when, $what, $how = null)
    {
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

    /**
     * alias of $this->u()
     *
     * @param string $where
     * @param array $when
     * @param array $what
     * @param string $how
     * @return string|bool
     */
    public function update($where, $when, $what, $how = null)
    {
        return $this->u($where, $when, $what, $how);
    }

    /**
     * d elete
     *
     * @param string $where which table to delete some of its data
     * @param array $what what data you want to delete. ['uid', 47] or ['uid' => 47]
     * @param string $how how you want to get your result
     * @return string|bool
     */
    public function d($where, $what, $how = null)
    {
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

    /**
     * alias of $this->d()
     *
     * @param string $where
     * @param array $what
     * @param string $how
     * @return string|bool
     */
    public function delete($where, $what, $how = null)
    {
        return $this->d($where, $what, $how);
    }

    /**
     * s et -- more like UPDATE OR CREATE
     *
     * @param string $where where the target data are -- thus the table name
     * @param array $when when the target data should be set
     * @param array $what what the target data should have
     * @param string $how how you want to get the result
     * @return string|bool
     */
    public function s($where, $when, $what, $how = null)
    {
        return count($this->r($where, $when)) == 1 ?
            $this->u($where, $when, $what, $how) :
            $this->c($where, $what, $how);
    }

    /**
     * alias of $this->s()
     *
     * @param string $where
     * @param array $when
     * @param array $what
     * @param string $how
     * @return string|bool
     */
    public function set($where, $when, $what, $how = null)
    {
        return $this->s($where, $when, $what, $how);
    }
    
    /**
     * h umanize column names -- utilizing the column comments
     *
     * @param string $where where the columns are -- thus the table name
     * @param boolean $how how you want to get the result. true if you want original name as key, false by default gives only the readable names
     * @return array
     */
    public function h($where, $how = false)
    {
        $return = [];
        $names_query = "SELECT `COLUMN_NAME`, `COLUMN_COMMENT` FROM `INFORMATION_SCHEMA`.COLUMNS WHERE TABLE_NAME = ?";
        $names = $this->db->query($names_query, [$where])->result_array();
        foreach ($names as $name) {
            if ($how) {
                $key = $name['COLUMN_NAME'];
                $value = $name['COLUMN_COMMENT'];
                $return[$key] = $value;
            } else {
                $column_name = ($name['COLUMN_COMMENT'] != '') ? $name['COLUMN_COMMENT'] : $name['COLUMN_NAME'] ;
                $return[] = $column_name;
            }
        }
        return $return;
    }

    /**
     * alias of $this->h()
     *
     * @param string $where
     * @param boolean $how
     * @return array
     */
    public function humanize_column_names($where, $how = false)
    {
        return $this->h($where, $how);
    }

    /**
     * alias of $this->h()
     *
     * @param string $where
     * @param boolean $how
     * @return array
     */
    public function humanize_columns($where, $how = false)
    {
        return $this->h($where, $how);
    }

    /**
     * alias of $this->h()
     *
     * @param string $where
     * @param boolean $how
     * @return array
     */
    public function humanize_column($where, $how = false)
    {
        return $this->h($where, $how);
    }

    /**
     * alias of $this->h()
     *
     * @param string $where
     * @param boolean $how
     * @return array
     */
    public function humanize($where, $how = false)
    {
        return $this->h($where, $how);
    }

    /**
     * m etadata to retrieve
     *
     * @param string $where where the fields metadata would be -- thus table name
     * @param string $what which field you need the metadata
     * @return array
     */
    public function m($where, $what = null)
    {
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

    /**
     * alias of $this->m()
     *
     * @param string $where
     * @param string $what
     * @return array
     */
    public function metadata($where, $what = null)
    {
        return $this->m($where, $what);
    }

    /**
     * o perator check -- to make simple math query like this work
     * 
     * $this->u('posts', ['uid' => 21], ['cnt' => 'cnt + 1']);
     *
     * @param string $where where the field is -- thus table name
     * @param string $what what field should accept operators
     * @param string $how how the field should be calculated
     * @return bool
     */
    public function o($where, $what, $how)
    {
        return ($this->m($where, $what)->type == 'int') && preg_match('/\w+\s?\W+\s?\d+/', $how);
    }

    /**
     * alias of $this->o()
     *
     * @param string $where
     * @param string $what
     * @param string $how
     * @return bool
     */
    public function operator($where, $what, $how)
    {
        return $this->o($where, $what, $how);
    }
}