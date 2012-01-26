$(document).ready(function() {

	var max_size = 0;

	$('ul.statuslist > li > span').each(function(i) {
		var size = $(this).position().left + $(this).outerWidth();
		if (size > max_size)
			max_size = size;
	});

	max_size += 24; // fucking padding not being counted!

	$('ul.statuslist > li > span').each(function(i) {
		$(this).css('display', 'block');
		$(this).css('width', max_size - $(this).position().left);
	});

	$('ul.statuslist > li > div.progressbar').each(function(i) {
		var parent = $(this).parent('li');

		$(this).css('float', 'right');
		$(this).css('width', parent.width() + parent.position().left - max_size);
	});

	$('#slider').each(function(i) {
		$(this).css('width', max_size - $(this).position().left - 40);
	});

	$('#stateheader').each(function(i) {
		var parent = $(this).parent();
		$(this).css('width', parent.width() + parent.position().left - max_size);
	});

	var sliderMin;
	for (sliderMin = 0; sliderMin<7; sliderMin++){	
		var found = false;
		$('ul.level-'+sliderMin).each(function(i) {
			found = true;
			return false;
			
		});
		if (found) break;
	}

	var sliderMax;
	for (sliderMax = 6; sliderMin>sliderMin; sliderMax--){	
		var found = false;
		$('ul.level-'+sliderMax).each(function(i) {
			found = true;
			return false;
			
		});
		if (found) break;
	}

	$('#slider').slider({
		value:sliderMin,
		min: sliderMin,
		max: sliderMax,
		range: 'min',
		step: 1,
		slide: function( event, ui ) {
			for (var index=sliderMin; index<=sliderMax; index++){	
				$('ul.level-'+index).each(function(i) {
					var expand = (ui.value>=index);
					var parent_li = $(this).parent('li');

					if (expand) {
						parent_li.addClass('expanded');
						parent_li.removeClass('collapsed');

						$(this).slideDown('medium');
					}
					else {
						parent_li.removeClass('expanded');
						parent_li.addClass('collapsed');

						$(this).slideUp('medium');
					}
				});
			}

			$('#slider').attr('title', getDescriptionForLevel(ui.value));
		}
	});

	$('#slider').attr('title', getDescriptionForLevel($('#slider').slider('value')));

	$("#slider[title]:gt(1)").tooltip({

		// use div.tooltip as our tooltip
		tip: '.tooltip',

		// use the fade effect instead of the default
		effect: 'fade',

		// make fadeOutSpeed similar to the browser's default
		fadeOutSpeed: 100,

		// the time before the tooltip is shown
		predelay: 0,

		// tweak the position
		position: "top center",
		offset: [-50, -80],
		
		events: {
		  def:     "mouseover,mousedown,mouseout,mousemove",
		  input:   "focus,blur",
		  widget:  "focus mouseover,blur mouseout",
		  tooltip: "mouseover,mouseout"
		}
	});
	
	//$('#slider').hover(function() {
	//	$('#slider .slider').attr('title', getDescriptionForLevel($('#slider').slider("value")));
	//	$('#slider .slider').tooltip();
	//});
	//$('#sliderText').text('Granularity: ' + getDescriptionForLevel($('#slider').slider("value"));

	$('ul.level-' + sliderMin).each(function(i) {
		$(this).addClass('toplevel');
	});

	function getDescriptionForLevel(level) {
		switch(level) {
			case 0: return 'Part';
			case 1: return 'Chapter';
			case 2: return 'Section';
			case 3: return 'Sub-Section';
			case 4: return 'Sub-Sub-Section';
			case 5: return 'Paragraph';
			case 6: return 'Sub-Paragraph';
			default: return 'Unknown';
		}
	}

	$('li > ul.statuslist').each(function(i) {
		var parent_li = $(this).parent('li');
		parent_li.addClass('collapsed');

		// Temporarily remove the list from the
		// parent list item, wrap the remaining
		// text in an anchor, then reattach it.
		var sub_ul = $(this).remove();
		parent_li.wrapInner('<a/>').find('a').click(function() {

			parent_li.toggleClass('expanded');
			parent_li.toggleClass('collapsed');

			if (parent_li.children().is(':hidden'))
				sub_ul.slideDown("medium");
			else
				sub_ul.slideUp("medium");

		});

		parent_li.append(sub_ul);
	});

	// Hide all lists except the outermost.
	$('ul.statuslist ul.statuslist').hide();

	$('#statustable').css('height', $(window).height() - $('#statusheader').outerHeight() - 10);

	$('.statuspage').css('visibility', 'visible');
});
