<?php

// Start - Config
define('THESIS_WEB_POST_URL', '');
define('THESIS_WEB_SECRET', '');
// End - Config

define('MIN_WORD_LENGTH', 3);
define('MIN_WORD_FREQUENCY', 5);

if (!file_exists('thesis.tex') || !file_exists('thesis.pdf'))
	exit('No input file');

$translations = array(
	'thesis.tex'									=>	null,

	'content/beginningContent/titlepage.tex'		=>	null,
	'content/beginningContent/declaration.tex'		=>	null,
	'content/beginningContent/abstract.tex'			=>	null,
	'content/beginningContent/acknowledgments.tex'	=>	null,
	'content/beginningContent/dedication.tex'		=>	null,
	'content/beginningContent/publications.tex'		=>	null,
	'content/beginningContent/contents.tex'			=>	null,
	'content/endContent/bibliography.tex'			=>	null,
);

$levels = array(
	'part'			=> 0,
	'chapter'		=> 1,
	'section'		=> 2,
	'subsection'	=> 3,
	'subsubsection'	=> 4,
	'paragraph'		=> 5,
	'subparagraph'	=> 6,
);

function filter_empty_string($var) {
	return trim($var) !== '';
}

function translate_file_to_key($file) {
	global $translations;

	list ($type, $file) = explode(': ', $file);

	if ($type == 'File(s) total')
		$file = 'total';

	while (array_key_exists($file, $translations))
		$file = $translations[$file];

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

	return $words;
}

function process_status($input) {
	global $levels;

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


/** Fetch and process the word/header/float count for the thesis and each included file **/

$wordcount = trim(shell_exec('/usr/bin/perl bin/texcount.pl -relaxed -q -inc -incbib -template="{T},{1},{4},{5}," "thesis.tex"'));
$wordcount = process_wordcount($wordcount);

/** Fetch and process the unique word count for the entire thesis **/

$uniquewords = trim(shell_exec('/usr/bin/perl bin/texcount.pl -restricted -freqSummary -nosub -nosum -merge -q -template="{T}" "thesis.tex"'));
list (, $uniquewords) = explode(',', $uniquewords);

/** Count the number of pages in the entire thesis **/

$numpages = trim(shell_exec('pdftk "thesis.pdf" dump_data | grep -i "NumberOfPages" | awk \'{print $2}\''));

/** Fetch and process the word frequency for the entire thesis, and each chapter individually **/

$interested_files = array();

foreach (glob('content/chapter*/*.tex') as $file)
	$interested_files[$file] = $file;

$interested_files['total'] = 'thesis.tex';

$frequencies = array();

foreach ($interested_files as $title => $path) {
	$frequencies[$title] = trim(shell_exec('/usr/bin/perl bin/texcount.pl -restricted -freq='.MIN_WORD_FREQUENCY.' -nosub -nosum -merge -q -template="{T}" "'.$path.'"'));
	$frequencies[$title] = process_frequencies($frequencies[$title]);
}

unset ($interested_files);

/** Fetch and process the section progress for the entire thesis **/

$status = trim(shell_exec('/usr/bin/perl bin/texcount.pl -relaxed -inc -nosub -nosum -printThesisState -total -brief -q "thesis.tex"'));
$status = process_status($status);

/** Do something cool with the data... **/

function data_encode($data) {
	return urlencode(gzcompress(serialize($data)));
}

$curl = curl_init();

curl_setopt($curl, CURLOPT_URL, THESIS_WEB_POST_URL);		// URL to submit data to
curl_setopt($curl, CURLOPT_POST, true);						// POST the data
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);			// Return the result rather than spitting it out
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));	// Remove the expect header since Lighttpd goes ape shit
curl_setopt($curl, CURLOPT_POSTFIELDS, array(				// The data to submit...
	'secret'		=> THESIS_WEB_SECRET,
	'wordcount'		=> data_encode($wordcount),
	'uniquewords'	=> data_encode($uniquewords),
	'numpages'		=> data_encode($numpages),
	'frequencies'	=> data_encode($frequencies),
	'status'		=> data_encode($status),
));

$response = curl_exec($curl);
exit(curl_errno($curl));
