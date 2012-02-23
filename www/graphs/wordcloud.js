document.write('<div id="wordcloud" style="width:100%;height:100%;overflow:hidden;position:relative;"></div>');

$(document).ready(function () {
	$.getJSON(cache_dir + 'common_words.json', function(data) {
		var series = [];

		$.each(data.total, function(key, value) {
			series.push({text: key, weight: value, title: value + " occurances"});
		});

		$("#wordcloud").jQCloud(series, { randomClasses: 4 });
	});
});

