# CRUD: The Generic Codeigniter Model #

## Overview ##

**CRUD** has 2 purposes. The one is, to let the developers focus on the DB query variables and targeted values, rather than the repetitive "Query Builder Class" syntax.

    $this->db->where('foo');
    $this->db->order_by('bar', 'asc');
    $this->db->order_by('dee', 'desc');
    $this->db->limit(30,0);
    $query = $this->db->get();
    if ($query) : $result = json_encode($query->result()); endif;

With **CRUD** loaded, the above is equivalent to:

    $result = $this->crud->r('foo',
        array(
            'order' => array('bar' => 'asc', 'dee' => 'desc'),
            'limit' => array(30,0),
        ),
        'json');

The other purpose is, to give extra functionality to the developers who works under the LAMP environment and the expected needs they sometimes need to meet. Like, storing and retrieving the human readable names of the table fields.

> Please note that this model assumes the DB is MySQL.

## Installation ##

- Simply put `Crud.php` into your Codeigniter application's models directory and load it to use.
- If you're familiar with Git, clone and make use of this entire repo as you please.

## Available Methods ##

1. `c($where, $what, $how = null)`
    Create.
    - `$where` *(str)* Where to insert. A table name.
    - `$what` *(arr)* What to insert into the table. An associative array of the fields and the content.
    - `$how` *(str)* How to get the result of the query. Either `'json'` or `null`.


2.  `r($what, $where = null, $how = null, $who = false)`
    Read.
    - `$what`   *(str|arr)* Where to start reading. Either:
      - a string of table name, to do all-fields query, or
      - an associative array, to do field-specific query
        ```
        // Crud.php
        $this->delimiter = ', '; // this is the default, set in the construct function
        // Your_controller.php
        array(
          'table' => 'users',
          'fields' => 'username, nickname, gender')
        ```
    - `$where` *(arr)* The `WHERE` information to read the table. An associative array of either `order`, `limit` or the existing field name.
      - `order` *(arr)* An associative array with one key specifying the target field and its value valid to SQL ORDER syntax, such as `random`, `asc`, `desc`.
      - `limit` *(arr)* A simple array of 2 numbers (`LIMIT` and `OFFSET`)
      - if the key is neither `order` nor `limit`, **CRUD** regards it as the field name and its value as the rest part of SQL WHERE syntax.
      For example: `array('column_user_age' => '> 19')`
    - `$how` *(str)* How to get the result of the query. Either `'json'`, `'array'`(a perfect array) or `null`(array of row objects).
    - `$who` *(mixed)* Who is going to read it. Utilizing `h()` method. Either `'human'`(or `true`) for human readable names only, `'robot'`(or `false`) for the code-friendly field names only or `'world'` for both.


3.  `u($where, $when, $what, $how = null)`
    Update.
    - `$where` *(str)* Where to update the row. A table name.
    - `$when` *(arr)* When to insert. A simple array of 2 values about the target row. The first element is field name and the last element is the **unique** value.
        - For example: `array('uid', 49)`
    - `$what` *(arr)* What to update into the table. An associative array of the target fields and the new contents.
    - `$how` *(str)* Exact same of `c($how)`.


4.  `d($where, $what, $how = null)`
    Delete.
    - `$where` *(str)* Where to delete a row. A table name.
    - `$what` *(arr)* What row to delete. A simple array of 2 values about the target row. The first element is field name and the last element is the **unique** value.
        For example: `array('uid', 2)`
    - `$how` *(str)* Exact same of `c($how)`.


5. `s($where, $when, $what, $how = null)`
    Set, that is equivalent to:
    - `u($where, $when, $what, $how)`, if `r($where, $when)` gives result of one row.
    - `c($where, $what, $how)`, otherwise.


6.  `h($where, $how = false)`
    Humanize (or, by retrieving the field comments) the field of the table.
    - `$where` *(str)* A table name to humanize.
    - `$how` *(mixed)* Exact same with `c($how)`.


7.  `m($where, $what = null)`
    Return metadata of the table fields. An utility function specifically for `$o()`.
    - `$where` *(str)* A table name to get the fields.
    - `$what` *(str)* A specific target field name.


8.  `o($where, $what, $how)`
    Check if the field type is `int` and `$how` contains the operator symbol, so that you can do something like this:
    ```
    $counted = $this->crud->u('posts', array('uid', 21), array('counts' => 'counts + 1'));
    ```

    - `$where` *(str)* A table name that contains the regarding field.
    - `$what` *(str)* The field name in the table.
    - `$how` *(str)* The query string to test over the field.


## Alias of the methods ##

A cheatsheet just in case you don't recall! :-)

1.  `create() === c()`
2.  `read() === r()`
3.  `update() === u()`
4.  `delete() === d()`
5.  `set() === s()`
6.  `humanize_column_names() === humanize_columns() === humanize_column() === humanize() === h()`
7.  `metadata() === m()`
8.  `operator() === o()`

## To do ##

*   Update `$when` logic of both `u()` and `s()`, so that it could run multiple column match test
*   Add more `WHERE` options like `LIKE %string%`
*   Pass the unit test of the code
*   Supporting more database than MySQL
