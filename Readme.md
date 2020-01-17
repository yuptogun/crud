# CRUD: The Generic Codeigniter Model #

> Disclaimer: THIS REPO IS OFFICIALLY OUT OF MAINTANENCE. Please do not expect any updates, although it's only MySQL-capable, it has no idea what "grouping" is and it may have possible bugs or points of improvements. (See "To Do" for more)

## Overview ##

**CRUD** has 2 key purposes.

1. To let developers build queries with structured idea.
2. To give extra functionalities -- converting it into JSON, utilizing comments in tables, etc.

Suppose you need a JSON of 30 `foo` records ordered by `bar` and `dee`, which is fairly the most common daily use case of CodeIgniter models.

```
$this->db->where('foo');
$this->db->order_by('bar', 'asc');
$this->db->order_by('dee', 'desc');
$this->db->limit(30, 0);
$foos = $this->db->get();
$json = json_encode($foos->result());
```

With **CRUD** loaded, you do that like this.

```
$json = $this->crud->r('foo', [
    'order' => ['bar' => 'asc', 'dee' => 'desc'],
    'limit' => [30, 0]
], 'json');
```

It would help you out when you:
- have 100+ tables that have no complicated relationships, or
- have to define 100+ model methods to get these fields or that fields, and
- have 0 of "heavy queries"

So choose at your own risk and enjoy.

## Installation ##

- Load `Crud.php` as you want it -- third party package, CI library or one of the models
- Clone or fork this repo and everybody gangsta

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
    - `$how` *(str)* How to get the result of the query. Either `'json'`, `'array'`(a perfect array), `'row'`(when you ) or `null`(array of row objects).
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

A cheatsheet just in case you don't recall.

1.  `create() === c()`
2.  `read() === r()`
3.  `update() === u()`
4.  `delete() === d()`
5.  `set() === s()`
6.  `humanize_column_names() === humanize_columns() === humanize_column() === humanize() === h()`
7.  `metadata() === m()`
8.  `operator() === o()`

## So you don't accept any PR ? ##

I probably do. Now the repo is applied of Git Flow, FYI.

## To do ##

Since CRUD has no support for mid-level SQL needs like JOIN, GROUP, COUNT or else, this approach should be replaced by something enhanced. To be announced.

## License

Public Domain lol