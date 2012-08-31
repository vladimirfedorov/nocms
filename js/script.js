// Global site scripts
// http://github.com/vladimirfedorov/nocms

$(document).ready(function() {	
	if (menu) menu.init();
	if (site) site.init();
	
	// Set content height
	var viewportHeight = $(window).height();
	if ($("#content").height() < viewportHeight)
	$("#content").animate({
		minHeight: (viewportHeight-220)
	},'fast');
});