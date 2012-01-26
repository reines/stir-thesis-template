<?php

require_once 'inc/config.php';

function data_decode($data) {
	return unserialize(gzuncompress(urldecode($data)));
}

function words_encode($words) {
	if (empty($words))
		return '{}';

	return json_encode($words);
}

if (!is_dir(JSON_DIR) || !is_writable(JSON_DIR))
	exit('Cache dir not writable'."\n");

$fields = array(
	'userid'		=> null,
	'secret'		=> null,
	'date'			=> null,
	'pages'			=> null,
	'references'	=> null,
	'chapters'		=> null,
	'status'		=> null,
);

foreach ($fields as $key => $value) {
	if (empty($_POST[$key])) {
		header('HTTP/1.0 400 Bad Request');
		exit('Missing field: '.$key."\n");
	}

	$fields[$key] = data_decode($_POST[$key]);
}

try {

	$db = new PDO(DB_DSN, DB_USER, DB_PASS);

	$db->beginTransaction();

	$query = $db->prepare('SELECT userid FROM users WHERE userid = :userid AND secret = :secret');
	$query->execute(array(
		':userid'				=> (string) $fields['userid'],
		':secret'				=> (string) $fields['secret'],
	));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	if (empty($result))
		exit('Invalid userid'."\n");

	$fields['userid'] = $result[0]['userid'];

	// Insert the thesis summary statistics
	$query = $db->prepare('INSERT INTO summary VALUES(:userid, :date, :pages, :citations)');
	$query->execute(array(
		':userid'				=> (string) $fields['userid'],
		':date'					=> (int) $fields['date'],
		':pages'				=> (int) $fields['pages'],
		':citations'			=> (int) $fields['references'],
	));

	// Insert chapter (and total) summary statistics
	$query = $db->prepare('INSERT INTO chapters VALUES(:userid, :date, :chapter, :unique_words, :total_words, :total_headers, :total_floats, :common_words)');

	foreach ($fields['chapters'] as $chapter => $data) {
		$query->execute(array(
			':userid'			=> (string) $fields['userid'],
			':date'				=> (int) $fields['date'],
			':chapter'			=> (string) $chapter,

			':unique_words'		=> (int) $data['unique_words'],
			':total_words'		=> (int) $data['total_words'],
			':total_headers'	=> (int) $data['total_headers'],
			':total_floats'		=> (int) $data['total_floats'],

			':common_words'		=> (string) words_encode($data['common_words']),
		));
	}

	// Insert overall thesis status
	$query = $db->prepare('INSERT INTO status VALUES(:userid, :date, :position, :level, :title, :state, :weight, :parent_position)');

	foreach ($fields['status'] as $position => $data) {
		// Temp, for old scripts which don't have weighting
 		if (!isset($data['weight']))
 			$data['weight'] = ($data['include'] ? 1 : 0);

		$query->execute(array(
			':userid'			=> (string) $fields['userid'],
			':date'				=> (int) $fields['date'],
			':position'			=> (int) $position,

			':level'			=> (int) $data['level'],
			':title'			=> (string) $data['title'],
			':state'			=> (string) $data['state'],
			':weight'			=> (int) $data['weight'],
			':parent_position'	=> (int) $data['parent_idx'] === -1 ? null : $data['parent_idx'],
		));
	}

	$db->commit();

	require 'inc/cache.php';
	
	// Generate json cache
	pre_cache_setup($fields['userid']);

	generate_all($fields['userid']);

}
catch (PDOException $e) {
	exit('Database error: '.$e->getMessage()."\n");
}

