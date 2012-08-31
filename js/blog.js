// Blog functions
// http://github.com/vladimirfedorov/nocms

var blog = {
	
	// current page:
	currentPage: 0,

	// number of pages:
	numberOfPages: 1,

	// Initialize
	init: function() {
		// .hash can be either #blog or #!blog-yyyymmdd-hhmm
		var blogrec = location.hash;
		if (blogrec.length !=  20)
			blog.loadBlogRoll(0);
		else
			blog.loadBlogRoll(blogrec.substr(7));

	},

	// Load page content
	// page must contain #content block and #loadingMessage block
	loadPost: function(action, postId) {
		var language = site.getLanguage();
		// update content block
		$('#postContent')
			.html($('#loadingMessage').html())
			.load("core/core.php?action=" + action + "&page=" + postId + "&lang=" + language,
				function() {
					// Clean up if disqus is loading at the moment
					$("#disqus_thread").children().each(function() {
						if (this.id == "") $(this).remove();
					});
					// Reload disquss
					DISQUS.reset({
					  reload: true,
					  config: function () {  
					    this.page.identifier = "#!blog-"+postId;  
					    this.page.url = "http://vladimirfedorov.net/#!blog-"+postId;
					  }
					});		
				});
		
		// save new site address
		$.cookie('page', postId, {expires: 1461, path: '/'});
	},

	// Load list of recent posts starting from <pageNum> * posts per page, in <language> language
	loadBlogRoll: function(pageNum) {
		var language = site.getLanguage();
		var ajax_url = "core/core.php";
		if (pageNum.length ==  13)
			ajax_url += "?action=pageforpost&page=" + pageNum + "&lang=" + language;
		else
			ajax_url += "?action=blogroll&page=" + parseInt(pageNum) + "&lang=" + language;

		$.ajax({
			url: ajax_url,
			success: function(data) {
				eval("var dataArray = " + data);
				var language = site.getLanguage();
				$("#panelCaption").html(blog.getPanelCaption(pageNum, language));

				// output
				var result = "";
				
				// yyyymm
				var recDateDivder = "";

				// change number of pages and current page number
				blog.numberOfPages = dataArray[0][0];
				blog.currentPage = dataArray[0][1];

				for(var i=1, imax=dataArray.length; i<imax; i++)
				{
					var recDate = dataArray[i][0].substr(0,6);
					if (recDate != recDateDivder) {
						result += blog.templateMonthDivider.replace("%date%", blog.getDateDividerName(recDate, language));
						recDateDivder = recDate;
					}
					
					result += blog.templateRecTitle
						.replace(/%id%/g, dataArray[i][0])
						.replace("%title%", dataArray[i][1]);
				}

				// add buttons up and down if needed
				if (blog.currentPage > 0)
					result = blog.templatePageUp + result;

				if (blog.currentPage < (blog.numberOfPages-1))
					result += blog.templatePageDown;

				$("#blogRoll").html(result);

				// select the first post in the list
				if (pageNum.length != 13)
					$("#"+dataArray[1][0]).click();
				else {
					if ($("#"+pageNum).length == 1) $("#"+pageNum).click();
					else $("#"+dataArray[1][0]).click();
				}

			}
		});
	},

	// Returns date divider title, like "MAY 2010"
	getDateDividerName: function(date) {
		var language = site.getLanguage();
		var monthNames = Array("en", "ru");
		monthNames["en"] = Array("","JANUARY","FEBRUARY","MARCH","APRIL","MAY","JUNE","JULY","AUGUST","SEPTEMBER","OCTOBER","NOVEMBER","DECEMBER");
		monthNames["de"] = Array("","JANUAR","FEBRUAR","MÄRZ","APRIL","MAI","JUNI","JULI","AUGUST","SEPTEMBER","OKTOBER","NOVEMBER","DEZEMBER");
		monthNames["fr"] = Array("","JANVIER","FÉVRIER","MARS","AVRIL","MAI","JUIN","JUILLET","AOÛT","SEPTEMBRE","OCTOBRE","NOVEMBRE","DÉCEMBRE");		
		return monthNames[language][parseInt(date.substr(4,2))] + " " + date.substr(0,4);
	},

	// Returns right panel title, "recent posts" if it's the 0th page, "posts" if not
	getPanelCaption: function(pageNum) {
		var language = site.getLanguage();
		var result = "Recent posts";
		switch(language) {
			case "en": 
				result = (pageNum == 0 ? "Recent posts" : "Posts");
				break;
			case "ru":
				result = (pageNum == 0 ? "Последние записи" : "Записи");
				break;
		}
		return result;
	},

	// Title clicked, change post
	onTitleClick: function(sender) {
		location.hash = "#!blog-"+sender.id;

		$("title").html($(sender).html());
		$("#postId").html(sender.id);
		$("#postTitle").html($(sender).html());
		blog.loadPost("post", sender.id);
		$(sender).siblings().removeClass("active");
		$(sender).addClass("active");
		$.cookie('post', sender.id, {expires: 1461, path: '/'})
	},

	pageUp: function() {
		blog.loadBlogRoll(blog.currentPage - 1);
		return false;
	},

	pageDown: function() {
		blog.loadBlogRoll(blog.currentPage + 1);
		return false;
	},

////// Templates ////////
	templateCaption: '%caption%',
	templateRecTitle: '<a class="" id="%id%" href="#!blog-%id%" onclick="blog.onTitleClick(this);">%title%</a>',
	templatePageUp: '<a class="nav pageup" href="up" onclick="return blog.pageUp();">&uarr; future</a>',
	templatePageDown: '<a class="nav pagedown" href="down" onclick="return blog.pageDown();">&darr; past</a>',
	templateMonthDivider: '<div class="postsListDate">%date%</div>'

};