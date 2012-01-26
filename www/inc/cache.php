<?php

function pre_cache_setup($userid) {
	$cache_dir = userid_to_cachedir($userid);
	if (!is_dir($cache_dir))
		mkdir($cache_dir);
}

function userid_has_cachedir($userid) {
	$cache_dir = userid_to_cachedir($userid);
	return is_dir($cache_dir);
}

function userid_to_cachedir($userid) {
	return JSON_DIR.sha1($userid).'/';
}

function generate_all($userid) {
	global $db;

	$query = $db->prepare('SELECT MAX(date) AS latest FROM summary WHERE userid = :userid');
	$query->execute(array(':userid' => $userid));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);

	$latest = $result[0]['latest'];

	unset ($query, $result);

	generate_status($userid, $latest);
	generate_summary($userid);
	generate_common_words($userid, $latest);
	generate_chapters($userid);
}

function generate_status($userid, $date) {
	global $db;

	$query = $db->prepare('SELECT level, title, state, weight FROM status WHERE userid = :userid AND date = :date ORDER BY position ASC');
	$query->execute(array(':userid' => $userid, ':date' => $date));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);

	$sections = array();
	foreach ($result as $row) {
		$sections[] = array(
			'level'		=> (int) $row['level'],
			'title'		=> $row['title'],
			'state'		=> $row['state'],
			'weight'	=> (int) $row['weight'],
		);
	}

	unset ($query, $result);

	// Shift around the sections to make sure everything only moves up or
	// down 1 level at a time.
	$real_last_level = -1;
	$fake_last_level = 0;

	for ($i = 0;$i < count($sections);) {
		$level = $sections[$i]['level'];

		if ($level > $real_last_level) {
			while ($i < count($sections) && $sections[$i]['level'] == $level)
				$sections[$i++]['level'] = $fake_last_level + 1;

			$fake_last_level++;
		}
		else if ($level < $real_last_level) {
			$i++;

			$fake_last_level = $level;
		}
		else
			$i++;

		$real_last_level = $level;
	}

	file_put_contents(userid_to_cachedir($userid).'status.json', json_encode($sections));
}

function generate_summary($userid) {
	global $db;

	$query = $db->prepare('SELECT date, pages, citations FROM summary WHERE userid = :userid ORDER BY date ASC');
	$query->execute(array(':userid' => $userid));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);
	
	$pages = array();
	$citations = array();
	foreach ($result as $row) {
		$date = $row['date'] * 1000; // javascript has ms precision

		$pages[] = array(
			$date,
			(int) $row['pages'],
		);
		
		$citations[] = array(
			$date,
			(int) $row['citations'],
		);
	}

	unset ($query, $result);

	file_put_contents(userid_to_cachedir($userid).'summary.json', json_encode(array('pages' => $pages, 'citations' => $citations)));
}

function generate_common_words($userid, $date) {
	global $db;

	$query = $db->prepare('SELECT chapter, common_words FROM chapters WHERE userid = :userid AND date = :date ORDER BY chapter ASC');
	$query->execute(array(':userid' => $userid, ':date' => $date));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);

	$chapters = array();
	foreach ($result as $row)
		$chapters[$row['chapter']] = json_decode($row['common_words']);

	unset ($query, $result);

	file_put_contents(userid_to_cachedir($userid).'common_words.json', json_encode($chapters));
}

function generate_chapters($userid) {
	global $db;

	$query = $db->prepare('SELECT date, chapter, unique_words, total_words FROM chapters WHERE userid = :userid ORDER BY chapter ASC, date ASC');
	$query->execute(array(':userid' => $userid));

	$result = $query->fetchAll(PDO::FETCH_ASSOC);

	$unique = array();
	$total = array();
	foreach ($result as $row) {
		if ($row['chapter'] === 'total')
			continue;

		$date = $row['date'] * 1000; // javascript has ms precision

		$unique[$row['chapter']][] = array(
			$date,
			(int) $row['unique_words'],
		);

		$total[$row['chapter']][] = array(
			$date,
			(int) $row['total_words'],
		);
	}

	unset ($query, $result);

	file_put_contents(userid_to_cachedir($userid).'chapters.json', json_encode(array('unique' => $unique, 'total' => $total)));
}

