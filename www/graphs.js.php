<?php

require_once 'inc/common.php';

$query = $db->prepare('SELECT `from`, `to` FROM translations WHERE userid = :userid');
$query->execute(array(':userid' => $userid));

$result = $query->fetchAll(PDO::FETCH_ASSOC);

$translations = array();
foreach ($result as $row)
	$translations[$row['from']] = $row['to'];

header('Content-type: text/javascript');

?>

var translations = <?php echo json_encode($translations); ?>;
var cache_dir = '<?php echo 'http://www.jamierf.co.uk/wp-content/misc/thesis/cache/'.sha1($userid).'/'; ?>';

