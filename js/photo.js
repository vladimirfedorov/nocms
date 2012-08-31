// Photos functions
// http://github.com/vladimirfedorov/nocms

var photo = {
	// Initialization
	init: function() {
		photo.getPhotosArray();
	}, 

	getPhotosArray: function() {
		var language = site.getLanguage();
		$.ajax({
			url: "core/core.php?action=photos&lang=" + language,
			success: function(data) {
				eval("photo.photosArray=" + data + ";");
				photo.fillPhotoBlock();
				photo.onLoadingComplete();
			}
		});
	},

	fillPhotoBlock: function() {
		var result = "";
		for(var i=0, imax=photo.photosArray.length; i < imax; i++) {
			var e = photo.photosArray[i];

			// if text tile 
			if (e.length == 3) {
				result += photo.textTileTemplate
					.replace("%title%", (e[2]=="" ? e[1]: e[2]))
					.replace("%year%", e[0]);
			}
			else if (e.length == 4) {
				result += photo.photoTileTemplate
					.replace("%thmb%", "photos/" + e[0] + "/" + e[1] + "/thmb/" + e[2])
					.replace("%src%", "photos/" + e[0] + "/" + e[1] + "/" + e[2])
					.replace("%alt%", e[3]);
			}
		} // loop through images

		$("#photoBlock").html(result);
	},

	onLoadingComplete: function() {},

	photosArray: Array(),
	textTileTemplate: '<div class="photoTextTile"><table><tr><td><br>%title%<br><span class="year">%year%</span></td></tr></table></div>',
	photoTileTemplate: '<div class="photoImageTile"><a class="fbimg" rel="photos" href="%src%"><img src="%thmb%" alt="%alt%" /></a></div>'
}