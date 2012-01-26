<?php

require_once 'inc/common.php';

$query = $db->prepare('SELECT `from`, `to` FROM translations WHERE userid = :userid');
$query->execute(array(':userid' => $userid));

$result = $query->fetchAll(PDO::FETCH_ASSOC);

$translations = array();
foreach ($result as $row)
	$translations[$row['from']] = $row['to'];

require 'inc/header.php';

?>
<script type="text/javascript">

var translations = <?php echo json_encode($translations); ?>;
var cache_dir = '<?php echo 'cache/'.sha1($userid).'/'; ?>';

</script>
<h1>Thesis and Ridiculous Data</h1>
<ul>
	<li><a href="status.php?userid=<?php echo $userid; ?>">Status so Far...</a></li>
</ul>

<div id="wordcloud"></div>
<script type="text/javascript">

$(document).ready(function () {
	$.getJSON(cache_dir + 'common_words.json', function(data) {
		var series = [];

		$.each(data.total, function(key, value) {
			series.push({text: key, weight: value, title: value + " occurances"});
		});

		$("#wordcloud").jQCloud(series, { randomClasses:4 });
	});
});

</script>
<!--<p style="text-align:center;"><img src="cloudall.png" alt="Word Cloud" /></p>-->

<h2 id="cloudstitle" style="float:right;width:50%;">Word Cloud</h2>
<h2 style="float:left;">Total Word Count for each Chapter</h2>
<div style="clear:both;"><!-- clearer --></div>
<div id="clouds" style="width:45%;height:520px;margin-right:2.5%;float:right;text-align:center;background-color:black;"><a href="cloudall.png"><img src="cloudall.png" alt="Word cloud" style="max-width:100%;max-height:100%;" /></a></div>
<div id="totals" style="border: 10px solid #FFFBD0;background-color:#FFFBD0;width:45%;height:500px;margin-left:2.5%;"></div>
<div style="clear:both;"><!-- clearer --></div>
<script type="text/javascript">

$(document).ready(function () {
	$.getJSON(cache_dir + 'chapters.json', function(data) {
		var merged = {};

		$.each(data.total, function(key, value) {
			if (key in translations)
				key = translations[key];

			if (key == null)
				return;

			value = value[value.length - 1][1];

			if (merged[key] == undefined)
				merged[key] = value;
			else if (value > merged[key])
				merged[key] = value;
		});

		var labels = [];
		var values = [];

		var count = 0;
		$.each(merged, function(key, value) {
			labels.push([count, key]);
			values.push([count, value]);

			count++;
		});

		var plot = $.plot($("#totals"), [ { data: values } ], { grid: { hoverable: true }, series: { stack: false, bars: { show: true, barWidth: 0.5, align: "center" } }, xaxis: { ticks: labels, tickLength: 0} });

		$("#totals").bind("plothover", function(event, position, item) {
			if (item == null)
				return;

			var url = "cloud" + (item.dataIndex + 1) + ".png";

			var a = $("#clouds > a");
			var img = a.find("img");
			if (img.attr("src") != url) {
				img.attr("src", url);
				a.attr("href", url);
				$("#cloudstitle").text("Word Cloud for Chapter: " + labels[item.dataIndex][1]);
			}
		});
	});
});

</script>
<div style="margin-top:3em;margin-bottom:1em;">
	<h2 style="display:inline;">Total Word Count over Time for each Chapter</h2>
	<span style="float:right;margin-right:2.5%;">
		<label for="stacktoggle">Stacked</label>
		<input id="stacktoggle" type="checkbox"/>
	</span>
</div>
<div id="wordcountlegend" style="position:absolute;margin-left:70px;margin-top:40px;z-index:2;"></div>
<div id="wordcount" style="border: 10px solid #FFFBD0;background-color:#FFFBD0;width:95%;height:500px;margin:auto;"></div> 
<div style="clear:both;"><!-- clearer --></div>
<script type="text/javascript">

var series = [];
var plot;
var latestPosition = null;

$(document).ready(function () {
	$.getJSON(cache_dir + 'chapters.json', function(data) {
		var merged = {};

		$.each(data.total, function(key, value) {
			if (key in translations)
				key = translations[key];

			if (key == null)
				return;

			if (merged[key] == undefined)
				merged[key] = [];

			merged[key] = merged[key].concat(value);
		});

		$.each(merged, function(key, value) {
			value.sort(function(a, b) {
				return a[0] - b[0];
			});

			series.push({data: value, label: key});
		});

		drawGraph();

		function updateLegend() {
			var legends = $("#wordcountlegend .legendLabel");

			var pos = latestPosition;
			latestPosition = null;

			var axes = plot.getAxes();
			if (pos.x < axes.xaxis.min || pos.x > axes.xaxis.max ||
				pos.y < axes.yaxis.min || pos.y > axes.yaxis.max)
				return;

			var i, j, dataset = plot.getData();
			for (i = 0; i < dataset.length; ++i) {
				var series = dataset[i];

				// find the nearest points, x-wise
				for (j = 0; j < series.data.length; ++j)
					if (series.data[j][0] > pos.x)
						break;
				
				// now interpolate
				var y, p1 = series.data[j - 1], p2 = series.data[j];
				if (p1 == null)
					y = p2[1];
				else if (p2 == null)
					y = p1[1];
				else
					y = p1[1] + (p2[1] - p1[1]) * (pos.x - p1[0]) / (p2[0] - p1[0]);

				legends.eq(i).text(legends.eq(i).text().replace(/:.*/, ": " + y.toFixed(0)));
			}
		}

		function drawGraph() {
			var checked = $("#stacktoggle").attr('checked');
			plot = $.plot($("#wordcount"), series, { series: { stack: checked ? true : null, lines: { fill: checked } }, xaxis: { mode: "time", tickSize: [2, "day"] }, grid: { hoverable: true, autoHighlight: false }, crosshair: { mode: "x" }, legend: { container: $("#wordcountlegend") } });
			
			var counter = 0;
			$("#wordcountlegend .legendLabel").each(function() {
				// fix the widths so they don't jump around
				var data = series[counter++].data;
				$(this).text($(this).text() + ": " + data[data.length-1][1]);
				$(this).css("width", $(this).width() + 50);
			});
		}
		
		$("#wordcount").bind("plothover", function(event, position, item) {
			if (latestPosition != null)
				return;

			latestPosition = position;
			setTimeout(updateLegend, 50);
		});
		
		$("#stacktoggle").change(function (e) {
			e.preventDefault();
			drawGraph();
		});
	});
});

</script>

<h2>Total Page and Citation Count over Time</h2>
<div id="pageslegend" style="position:absolute;margin-left:70px;margin-top:40px;z-index:2;"></div>
<div id="pages" style="border: 10px solid #FFFBD0;background-color:#FFFBD0;width:95%;height:400px;margin:auto;"></div>
<div style="clear:both;"><!-- clearer --></div>
<script type="text/javascript">

$(document).ready(function () {
	$.getJSON(cache_dir + 'summary.json', function(data) {
		$.plot($("#pages"), [ { data: data.pages, label: "Pages"}, { data: data.citations, label: "Citations" } ], { series: { stack: null, lines: { fill: false } }, xaxis: { mode: "time", tickSize: [2, "day"] }, grid: { hoverable: true, autoHighlight: false }, crosshair: { mode: "x" }, legend: { container: $("#pageslegend") } });
	});
});

</script>
<?php require 'inc/footer.php'; ?>
