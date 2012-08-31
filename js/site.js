// Site functions
// http://github.com/vladimirfedorov/nocms

var site = {

	// Initialize site
	init: function(){
		// determine user language
		site.updateLanguageButtons();
		// update captions if needed
		site.translateElements();
		// open requested page
		var pageName = site.getCurrentPageName();
		if (pageName !== "") 
			site.loadPage(pageName);
		else
			$(".menu a:first").click();
	},

	// returns pageName without the octothrop sign
	getCurrentPageName: function() {
		var pageName = location.hash;
		if (pageName.length > 1) {
			if (pageName.substr(0,1) === '#') pageName = pageName.substr(1);
			if (pageName.substr(0,1) === '!') pageName = pageName.substr(1);
		}
		return pageName;
	},

	// returns short page name without anchor suffix
	getShortPageName: function(pageName) {
		var hyphenIdx = pageName.indexOf("-");
		if (hyphenIdx > -1)
			pageName = pageName.substr(0, hyphenIdx);
		return pageName;
	},

	// Load page content
	// page must contain #content block and #loadingMessage block
	loadPage: function(pageName) {
		var language = site.getLanguage();
		// update content block
		$('#content')
			.html($('#loadingMessage').html())
			.load("core/core.php?action=page&page=" + pageName + "&lang=" + language);
		
		// save new site address
		$.cookie('page', pageName, {expires: 1461, path: '/'});	
		$.cookie('lang', language, {expires: 1461, path: '/'});	

		// update page title
		$("title").html(pageName);
		
		// update menu status
		if (menu) {
			menu.setActive(site.getShortPageName(pageName));
			menu.onPageChangeComplete();
		}

	},


	// Change site language.
	// language is stored in a cookie
	// If cookie is undefined, default language is used
	changeLang: function(language) {
		$.cookie('lang', language, {expires: 1461, path: '/'});	
		location.reload();
		return false;
	},

	// Get current site language
	getLanguage: function() {
		var language = $.cookie("lang");
		if (!language || language == "") {
			var userLang = "";
			if (navigator.userLanguage)
				userLang = navigator.userLanguage;
			else if (window.navigator.language)
				userLang = window.navigator.language;
			else if (navigator.language)
				userLang = navigator.language;
			else if (navigator.browserLanguage)
				userLang = navigator.browserLanguage;

			if (userLang.length >= 2)
				language = userLang.substr(0, 2);
		}
		return language;
	},

	// Update language buttons
	updateLanguageButtons: function() {
		var language = site.getLanguage();
		$("#lang"+language).siblings().removeClass("active");
		$("#lang"+language).addClass("active");
	},

	// change elements names
	translateElements: function() {
		var language = site.getLanguage();
		if (language == "en") return;

		// request alternative names for elements
		$.ajax({
			url: "core/core.php?action=l10n&lang=" + language,
			success: function(data) {
				eval("var dataArray = " + data);
				if (dataArray.length == 0) return;

				// iterate through elements renaming each one
				for (var i=0, imax=dataArray.length; i<imax; i++) {
					if (dataArray[i].length == 2) {
						$(dataArray[i][0]).html(dataArray[i][1]);
					}
				}
			}
		});
	}

}