document.write('<div style="width:100%;height:100%;padding:0;"><div id="pageslegend" style="width:20%;height:100%;padding:0;margin:0;float:left"></div><div id="pages" style="width:80%;height:100%;padding:0;margin:0;float:right;"></div></div>');

$(document).ready(function () {
	$.getJSON(cache_dir + 'summary.json', function(data) {
		$.plot($("#pages"), [ { data: data.pages, label: "Pages"}, { data: data.citations, label: "Citations" } ], { series: { stack: null, lines: { fill: false } }, xaxis: { mode: "time", tickSize: [2, "day"] }, grid: { hoverable: true, autoHighlight: false }, crosshair: { mode: "x" }, legend: { container: $("#pageslegend") } });
	});
});

