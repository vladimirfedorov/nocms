NOCMS is an example of an ajax-based site without any content management system. NOCMS is released under MIT license.

The actual system consists of two files only: 
	**core/core.cfg** contains configuration data, **core/core.php** contains all server-side functions of the system.
	**pages** folder contains pages like this one, **blog** folder contains blog records. 


CMS file structure:
-------------------

	blog/
	   <language>/ 					two-letter language definition or 'common'
	      <yyyyMMdd-HHmm>.txt 		blog record
	      blog.lst 					blog cache
	core/
	   core.cfg 					system configuration
	   core.php 					system functions
	css/ 							
	   styles.css 					styles for the site
	images/							images for the site
	js/
	   blog.js 						'blog' class
	   menu.js 						'menu' class
	   photo.js						'photo' class
	   script.js 					site scripts — insert your code into this file
	   site.js 						'site' class
	page/
	   <language>/					two-letter language definition or 'common'
	      about.html 				example of a simple page
	      blog.html 				blog
	      photo.html 				photos
	      lang.<language>.txt 		file contains menu translation for the <i>language</i> language
	photo/
	   yyyy/
	      album/
	         <image>.jpg 			full size image
	         thmb/
	            <image>.jpg 		thumbnail
	   <language>/					two-letter language definition or 'common'
	      cache.lst 				photo cache
	      description.lst 			description or translation for each folder in photo
	plugins/						3rd party plugins
	

Blog record file format
-----------------------

Each blog record file has a name in the form of a date in '*yyyyMMdd-HHmm*.txt' format and some text inside the file with special fields for title and tags. It is possible add more special fields and use them. 

**@title:** describes the title of a blog record
**@tags:** lists tags for this record

For example:

	@title: Morning coffee
	@tags: coffee, morning

	Strong aroma of a cup of coffee wakes me up every morning.



Localized menu file format
--------------------------

Files contain javascript array of pairs ["<i>elementId</i>", "<i>name</i>"]. For example, **lang.de.txt** contains:

	[["#photo", "Foto"]
	,["#about","Über nocms"]
	,["#blog","Blog"]
	,["#contacts","Kontakte"]
	]


Photo description file format
-----------------------------

Files contain a localized name for each folder. If no localized name is found, the original name is used.

	Year/FolderName LocalizedName
