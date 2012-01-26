<?php

require_once 'inc/config.php';
require_once 'inc/cache.php';

$userid = empty($_GET['userid']) ? null : trim($_GET['userid']);

try {
	$db = new PDO(DB_DSN, DB_USER, DB_PASS);

	$query = $db->prepare('SELECT userid FROM users WHERE userid = :userid');
	$query->execute(array(':userid' => $userid));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	if (empty($result))
		exit('Invalid userid'."\n");

	$userid = $result[0]['userid'];
}
catch (PDOException $e) {
	exit('Database error: '.$e->getMessage()."\n");
}

if (!userid_has_cachedir($userid)) {
	pre_cache_setup($userid);

	generate_all($userid);
}

