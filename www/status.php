<?php

require_once 'inc/common.php';

$query = $db->prepare('SELECT state, description, weight FROM states WHERE userid = :userid ORDER BY position ASC');
$query->execute(array(':userid' => $userid));

$result = $query->fetchAll(PDO::FETCH_ASSOC);

$total_weight = 0;
foreach ($result as $row)
	$total_weight += $row['weight'];

$weight_factor = 100 / $total_weight;

$states = array();
foreach ($result as $row) {
	if ($row['weight'] === '0')
		continue;

	$states[(string) $row['state']] = array(
		'description'	=> $row['description'],
		'percent'		=> round($row['weight'] * $weight_factor, 2),
	);
}

define('BODY_CLASS', 'statuspage');
require 'inc/header.php';

?>
<div id="statusheader">
	<div id="statusheader-inner">
<?php ob_start(); ?>
		<div id="slider"></div>
		<ol id="stateheader">
<?php

foreach($states as $state)
	echo "\t\t\t".'<li style="width:'.$state['percent'].'%">'.htmlspecialchars($state['description']).'</li>'."\n";

?>
		</ol>
		<div style="clear:both"></div>
	</div>
</div>

<div id="statustable">
	<div id="statustable-inner">
<?php

$json = @file_get_contents('cache/'.sha1($userid).'/status.json');
if ($json === false)
	exit('urg, no status data');

$sections = json_decode($json);

$top_level = PHP_INT_MAX;
foreach ($sections as $section) {
	if ($section->level < $top_level)
		$top_level = $section->level;
}

$last_level = -1;

// To know the current section index for passing to the percent calculator
$counter = 0;

// To calculate the average thesis status overall
$numPercentTopLevelItems = 0;
$sumPercentTopLevelItems = 0;

foreach ($sections as $section) {
	$tabs = $section->level > 0 ? str_repeat("\t", $section->level * 2) : '';
	$last_tabs = $last_level > 0 ? str_repeat("\t", $last_level * 2) : '';

	if ($section->level < $last_level) {
		for ($i = $last_level - $section->level;$i > 0;$i--) {
			$temp_tabs = str_repeat("\t", ($section->level + $i) * 2);

			echo $temp_tabs."\t".'</li>'."\n";
			echo $temp_tabs.'</ul>'."\n";
		}
	}

	if ($section->level > $last_level)
		echo $tabs.'<ul class="statuslist level-'.$section->level.'">'."\n";
	else
		echo $tabs."\t".'</li>'."\n";

	echo $tabs."\t".'<li class="state-'.htmlspecialchars($section->state).'"><!-- level '.$section->level.' -->'."\n";

	$statePercent = calculateStatePercent($section->state, $counter);
	$statePercent = round($statePercent, 0);

	echo $tabs."\t\t".'<div class="progressbar ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="80"><div class="ui-progressbar-value ui-widget-header ui-corner-left" style="width:'.$statePercent.'%;">'.($statePercent > 0 ? $statePercent.'%' : '').'</div></div>'."\n";
	echo $tabs."\t\t".'<span title="Weight: '.$section->weight.'">'.format_title($section->title).'</span>'."\n";
	echo $tabs."\t\t".'<div style="clear:both"></div>'."\n";

	if ($section->level == $top_level && $section->weight > 0) {
		$numPercentTopLevelItems += $section->weight;
		$sumPercentTopLevelItems += ($statePercent * $section->weight);
	}

	$last_level = $section->level;
	$counter++;
}

$average = round($sumPercentTopLevelItems / $numPercentTopLevelItems, 1);

$buffer = ob_get_clean();

echo '<div id="mainprogressbar" class="progressbar ui-progressbar ui-widget ui-widget-content ui-corner-all" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="'.$average.'"><div class="ui-progressbar-value ui-widget-header ui-corner-left" style="width:'.$average.'%;">'.($average > 0 ? $average.'%' : '').'</div></div>'."\n";

echo $buffer;

function format_title($title) {
	static $replace;

	if (!isset($replace)) {
		$replace = array(
			'``'		=> '"',
			'\'\''		=> '"',
		);
	}

	return htmlspecialchars(str_replace(array_keys($replace), array_values($replace), $title));
}

function calculateStatePercent($state, $indexOfSection) {
	global $states, $sections;

	if ($state == 'p') {
		$topLevel = $sections[$indexOfSection]->level;
		$sum = 0;
		$count = 0;

		while ($sections[++$indexOfSection]->level > $topLevel) {
			if ($sections[$indexOfSection]->level-1 == $topLevel) {
				$sum += calculateStatePercent($sections[$indexOfSection]->state, $indexOfSection);
				$count++;
			}
		}

		return ($count == 0) ? 0 : $sum / $count;
	}

	$cumulative_percent = 0;
	foreach($states as $key => $data) {
		$cumulative_percent += $data['percent'];
		if ($key == $state)
			return $cumulative_percent;
	}

	return 0;
}

echo $tabs."\t".'</li>'."\n";
echo $tabs.'</ul>'."\n";

?>
	</div>
</div>
<script src="status.js" type="text/javascript"></script>
<?php require 'inc/footer.php'; ?>
