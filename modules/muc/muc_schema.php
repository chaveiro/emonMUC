<?php

$schema['muc'] = array(
	'id' => array('type' => 'int(11)', 'Null'=>'NO', 'Key'=>'PRI', 'Extra'=>'auto_increment'),
    'userid' => array('type' => 'int(11)'),
	'address' => array('type' => 'text'),
	'description' => array('type' => 'text','default'=>''),
	'password' => array('type' => 'varchar(64)')
);