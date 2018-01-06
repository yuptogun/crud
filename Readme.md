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

The other purpose is, to give extra functionality to the developers who works under PHP-MySQL environment and the expected needs they sometimes need to meet. Like, storing and retrieving the human readable names of the table columns.

> Please note that this model assumes the DB is MySQL.

## Installation ##

- Simply put `Crud.php` into your Codeigniter application's models directory.
- If you're familiar with Git, clone and make use of this entire repo as you please.

## Available Methods ##

1.  `c($where, $what, $how = null)`
    Create.
    -  `$where` *(str)* Where to insert. A table name.
    - `$what` *(arr)* What to insert into the table. An associative array of the columns and the content.
    - `$how` *(str)* How to get the result of the query. Either `'json'` or `null`.


2.  `r($what, $where = null, $how = null, $who = false)`
    Read.
    - `$what`   *(str)* Where to start reading. A table name.
    - `$where` *(arr)* The "where" information to read the table. An associative array of either `order`, `limit` or the existing columnn name.
      - `order` *(arr)* An associative array with one key specifying the target column and its value valid to SQL ORDER syntax, such as `random`, `asc`, `desc`.
      - `limit` *(arr)* A simple array of 2 numbers. The first element is `LIMIT` and the last element is `OFFSET`.
      - if the key is neither `order` nor `limit`, **CRUD** regards it as the column key and its value as the rest part of SQL WHERE syntax.
      For example: `array('column_user_age' => '> 19')`
    - `$how` *(str)* How to get the result of the query. Either `'json'`, `'array'`(a perfect array) or `null`(array of row objects).
    - `$who` *(mixed)* Who is going to read it. Utilizing `h()` method. Either `'human'`(or `true`) for human readable names only, `'robot'`(or `false`) for the code-friendly column keys only or `'world'` for both.


3.  `u($where, $when, $what, $how = null)`
    Update.
    - `$where` *(str)* Where to update the row. A table name.
    - `$when` *(arr)* When to insert. A simple array of 2 values about the target row. The first element is column key and the last element is the **unique** value.
        - For example: `array('uid', 49)`
    - `$what` *(arr)* What to update into the table. An associative array of the target columns and the new contents.
    - `$how` *(str)* Exact same of `c($how)`.


4.  `d($where, $what, $how = null)`
    Delete.
    - `$where` *(str)* Where to delete a row. A table name.
    - `$what` *(arr)* What row to delete. A simple array of 2 values about the target row. The first element is column key and the last element is the **unique** value.
        For example: `array('uid', 2)`
    - `$how` *(str)* Exact same of `c($how)`.


5.  `s($where, $when, $what, $how = null)`
    Set, that is equivalent to:
    - `u($where, $when, $what, $how)`, if `r($where, $when)` gives result of one row.
    - `c($where, $what, $how)`, otherwise.


6.  `h($where, $how = false)`
    Humanize (or, retrieve the column comments to give the name of) each column of the table.
    - `$where` *(str)* A table name to humanize.
    - `$how` *(bool)* Whether you need to have the column key with its human readable name. `true` gives an associative array with the column keys as the key and the column comments as the value. `false` gives a simple array of the column comments only.
    - When the column comment is not set, it uses the column key as the readable name.


## Alias of the methods ##

A cheatsheet just in case you don't recall! :-)

1.  `create() === c()`
2.  `read() === r()`
3.  `update() === u()`
4.  `delete() === d()`
5.  `set() === s()`
6.  `humanize_column_names() === humanize_columns() === humanize_column() === humanize() === h()`

## Lisence ##

MIT Lisence.

## To do ##

*   Update `$when` logic of `u()` and `s()`, so that it could run multiple column match test (Currently it fails when more than 2 rows match with `$when`)
*   Update `$where` logic of `r()` and `h()` so that it could select certain columns of the table (Currently it's only `SELECT * FROM`)
*   Add more `$where` options than `'offset'` & `'limit'`
*   Pass the unit test of the code
*   Support more database than MySQL