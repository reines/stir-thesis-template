document.write('<div style="width:100%;height:100%;padding:0;"><div style="width:20%;height:100%;padding:0;margin:0;float:left;"><div style="width:100%;height:10%;padding:0;margin:0;"><label for="stacktoggle">Stacked</label><input id="stacktoggle" type="checkbox"/></div><div id="wordcountlegend" style="width:100%;height:90%;padding:0;margin:0;"></div></div><div id="wordcount" style="width:80%;height:100%;padding:0;margin:0;float:right;"></div></div>');

$(document).ready(function () {
	var series = [];
	var plot;
	var latestPosition = null;

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

