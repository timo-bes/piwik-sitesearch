#!/usr/bin/php
<?php

$config = parse_ini_file('../../../config/config.ini.php');

$db = mysql_connect($config['host'], $config['username'], $config['password'])
        or die("Could not connect to database");

mysql_select_db($config['dbname'], $db);

$result = mysql_query('SHOW TABLES LIKE "'.$config['tables_prefix'].'archive_%"', $db);
if (!$result) {
    echo mysql_error($db);
    echo "\n";
}

$tables = array();
while ($row = mysql_fetch_row($result)) {
    $tables[] = $row[0];
}

foreach ($tables as $table) {
    echo 'Dropping '.$table;
    echo "\n";
    $result = mysql_query('DROP TABLE '.$table, $db);
    if (!$result) {
        echo mysql_error($db);
        echo "\n";
    }
}

?>