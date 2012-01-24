#!/usr/bin/env php
<?php

// Start - Config

// Settings for the thesis-web interface
define('THESIS_WEB_POST_URL', 'http://www.jamierf.co.uk/wp-content/misc/thesis/record.php');

define('THESIS_WEB_USERID', '');
define('THESIS_WEB_SECRET', '');

// Settings for common word counting
define('MIN_WORD_LENGTH', 3);
define('MIN_WORD_FREQUENCY', 5);
define('MAX_WORD_COUNT', 150);

// Path to a couple required binaries
define('PDFTK_PATH', '/usr/bin/pdftk');
define('PERL_PATH', '/usr/bin/perl');

// Filename of the thesis, minus it's extension
define('THESIS_FILENAME', 'thesis');

// End - Config

if (!file_exists(THESIS_FILENAME.'.tex') || !file_exists(THESIS_FILENAME.'.pdf') || !file_exists(THESIS_FILENAME.'.bbl'))
	exit('No input file (.tex, .bbl, and .pdf required)'."\n");

function filter_empty_string($var) {
	return trim($var) !== '';
}

function translate_file_to_key($file) {
	list ($type, $file) = explode(': ', $file);

	if ($type == 'File(s) total')
		$file = 'total';

	return $file;
}

function process_wordcount($input) {
	$tokens = array_map('trim', explode(',', $input));
	$tokens = array_filter($tokens, 'filter_empty_string');

	array_shift($tokens); // Remove the date

	$sections = array();

	while (!empty($tokens)) {
		$key = translate_file_to_key(array_shift($tokens));

		$words = intval(array_shift($tokens));
		$headers = intval(array_shift($tokens));
		$floats = intval(array_shift($tokens));

		if ($key === null)
			continue;

		$sections[$key] = array(
			'words'		=> $words,
			'headers'	=> $headers,
			'floats'	=> $floats,
		);
	}

	return $sections;
}

function accept_word($word) {
	static $stopwords;

	if (!isset($stopwords)) {
		$stopwords = @file(dirname(__FILE__).'/stopwords.txt');
		if ($stopwords === false)
			$stopwords = array();
		else
			$stopwords = array_filter(array_map('trim', $stopwords));
	}

	if (strlen($word) < MIN_WORD_LENGTH)
		return false;

	if (in_array($word, $stopwords))
		return false;

	return true;
}

function process_frequencies($input) {
	if (!preg_match('%number of unique words:\s*(\d+)%i', $input, $matches))
		return null;

	$total = $matches[1];

	$lines = array_map('trim', explode("\n", $input));
	$lines = array_filter($lines, 'filter_empty_string');

	$words = array();

	foreach ($lines as $line) {
		if (!preg_match('%^(\w+):\s*(\d+)%', $line, $matches))
			continue;

		if (!accept_word($matches[1]))
			continue;

		$words[$matches[1]] = intval($matches[2]);
	}

	// Ensure the words are sorted with most common at the top
	arsort($words);

	// Trim the list of words to the top MAX_WORD_COUNT
	$words = array_slice($words, 0, MAX_WORD_COUNT, true);

	return array('total' => $total, 'words' => $words);
}

function process_status($input) {
	static $levels;

	if (!isset($levels)) {
		$levels = array('part', 'chapter', 'section', 'subsection', 'subsubsection', 'paragraph', 'subparagraph');
		$levels = array_combine(array_values($levels), array_keys($levels)); // swap around the key => value to value => key for fast reverse lookups
	}

	$lines = array_map('trim', explode("\n", $input));
	$lines = array_filter($lines, 'filter_empty_string');

	$sections = array();

	$last_level = -1;

	$current_idx = 0;
	$parent_idx = -1;

	foreach ($lines as $line) {
		@list ($type, $title, $state, $include) = explode("\t", $line);

		if (!array_key_exists($type, $levels))
			continue;

		$level = $levels[$type];

		if ($level > $last_level)
			$parent_idx = $current_idx - 1;

		while ($parent_idx > -1 && $level <= $sections[$parent_idx]['level'])
			$parent_idx = $sections[$parent_idx]['parent_idx'];

		if ($state == 0 && $parent_idx > -1)
			$state = $sections[$parent_idx]['state'];

		$sections[$current_idx++] = array(
			'level'			=> $level,
			'title'			=> $title,
			'state'			=> $state,
			'include'		=> ($include == null || $include == 'y'),
			'parent_idx'	=> $parent_idx,
		);

		$last_level = $level;
	}

	return $sections;
}

/** Note the current time **/

$now = time();

/** Count the number of pages in the entire thesis **/

$numpages = trim(shell_exec(PDFTK_PATH.' "'.THESIS_FILENAME.'.pdf" dump_data'));
if (!preg_match('%NumberOfPages:\s*(\d+)%i', $numpages, $matches))
	exit('Unable to count number of pages in PDF'."\n");

$numpages = $matches[1];

/** Fetch and process the word/header/float count for the thesis and each included file **/

$wordcount = trim(shell_exec(PERL_PATH.' bin/texcount.pl -relaxed -q -inc -incbib -template="{T},{1},{4},{5}," "'.THESIS_FILENAME.'.tex"'));
$wordcount = process_wordcount($wordcount);

/** Fetch and process the word frequency for the entire thesis, and each chapter individually **/

$interested_files = array();

foreach (glob('content/*/*.tex') as $file)
	$interested_files[$file] = $file;

$interested_files['total'] = THESIS_FILENAME.'.tex';

$frequencies = array();

foreach ($interested_files as $title => $path) {
	$frequencies[$title] = trim(shell_exec(PERL_PATH.' bin/texcount.pl -restricted -freq='.MIN_WORD_FREQUENCY.' -nosub -nosum -merge -q -template="{T}" "'.$path.'"'));
	$frequencies[$title] = process_frequencies($frequencies[$title]);
}

unset ($interested_files);

/** Fetch and process the section progress for the entire thesis **/

$status = trim(shell_exec(PERL_PATH.' bin/texcount.pl -relaxed -inc -nosub -nosum -printThesisState -total -brief -q "'.THESIS_FILENAME.'.tex"'));
$status = process_status($status);

/** Count how many items are in the bibliography **/

$references = @file_get_contents(THESIS_FILENAME.'.bbl');
if ($references === false)
	exit('Unable to count used references'."\n");

$references = substr_count($references, '\\bibitem');

/** Do something cool with the data... **/

// Unset any wordcount data which doesn't have matching frequency data
foreach ($wordcount as $chapter => $data) {
	if (!isset($frequencies[$chapter]))
		unset($wordcount[$chapter]);
}

// Unset any frequency data which doesn't have matching wordcount data
foreach ($frequencies as $chapter => $data) {
	if (!isset($wordcount[$chapter]))
		unset($frequencies[$chapter]);
}

$chapters = array();

// Merge the frequency and wordcount data
foreach ($wordcount as $chapter => $data) {
	$chapters[$chapter] = array(
		'unique_words'		=> $frequencies[$chapter]['total'],
		'total_words'		=> $wordcount[$chapter]['words'],
		'total_headers'		=> $wordcount[$chapter]['headers'],
		'total_floats'		=> $wordcount[$chapter]['floats'],

		'common_words'		=> $frequencies[$chapter]['words'],
	);
}

function data_encode($data) {
	return urlencode(gzcompress(serialize($data)));
}

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, THESIS_WEB_POST_URL);		// URL to submit data to
curl_setopt($curl, CURLOPT_POST, true);						// POST the data
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);			// Return the result rather than spitting it out
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));	// Remove the expect header since Lighttpd goes ape shit
curl_setopt($curl, CURLOPT_POSTFIELDS, array(				// The data to submit...
	'userid'		=> data_encode(THESIS_WEB_USERID),
	'secret'		=> data_encode(THESIS_WEB_SECRET),
	'date'			=> data_encode($now),
	'pages'			=> data_encode($numpages),
	'references'	=> data_encode($references),
	'chapters'		=> data_encode($chapters),
	'status'		=> data_encode($status),
));

$response = curl_exec($curl);
exit(curl_errno($curl));

