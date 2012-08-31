// JS file for text menu
// http://github.com/vladimirfedorov/nocms

// menu functions
var menu = {

	// Initialize menu
	init: function() {
		$(".menu a").click(function(){
			var pageName = $(this).attr("href");
			if (pageName.length > 1) pageName = pageName.substr(1);
			site.loadPage(pageName);
		});
	},

	// open a page using menu element
	open: function(idName) {
		$("#"+idName).click(); // select menu element				
	},

	setActive: function(elem) {
		var element = (typeof(elem) == "string" ? $("#"+elem) : $(elem));
		if (element) {
			$(element).siblings().removeClass("active").css({borderBottomWidth:"5px"}); // restore border width
			$(element).addClass("active");
			if ($(window).width() >= 767) $(element).animate({borderBottomWidth: "10px"}, 100);
		}
	},

	// Update menu when page load comleted
	onPageChangeComplete: function() {}
}

