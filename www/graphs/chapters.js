document.write('<div style="width:100%;height:100%;padding:0;"><div id="totals" style="width:50%;height:100%;padding:0;margin:0;float:left;"></div><div id="clouds" style="width:50%;height:100%;padding:0;margin:0;float:right;"><p style="color:red;text-align:center;">TODO: chapter word clouds</p></div></div>');

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

		$.plot($("#totals"), [ { data: values } ], { grid: { hoverable: true }, series: { stack: false, bars: { show: true, barWidth: 0.5, align: "center" } }, xaxis: { ticks: labels, tickLength: 0} });
	});
});

