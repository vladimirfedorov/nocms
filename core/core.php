<?php

require_once "core.cfg";

// include Markdown library
require_once SERVERPATH.BASEPATH.'core/markdown/markdown.php'; 

// read input parmeters: "action", "page" and "lang"
$action = '';
if (isset($_GET['action'])) $action = $_GET['action'];

$requestedPage = '';
if (isset($_GET['page'])) $requestedPage = $_GET['page'];

$language = 'en';
if (isset($_GET['lang'])) $language = $_GET['lang'];
if (strlen($language) > 2) $language = substr($language, 0, 2);


if ($requestedPage != '') {
	if (substr($requestedPage,-5)=='.text') {
		$requestedPage = substr($requestedPage,0, -5);
		$action = 'text';
	}
}


// action
switch ($action) {
	case 'page':
		// uri example: http://sitename/#about
		// request example: ?action=page&page=about&lang=en
		echo getProcessedPageContents($requestedPage, $language);
		break;
	
	case 'text':
		// uri example: http://sitename/#about.text
		// request example: ?action=text&page=about&lang=en
		echo '<pre>'.getPageContents($requestedPage, $language).'</pre>';
		break;

	case 'latest':
		// get latest post
		// request example: ?action=latest&lang=en
		echo getLatestPost($language);
		break;

	case 'post':
		// get post by Id (yyyymmdd-hhmm)
		// request example: ?action=post&page=20120101-0000&lang=en
		echo getPost($requestedPage, $language);
		break;

	case 'blogroll':
		// get JS array of posts, RECSPERPAGE, starting from (page × RECSPERPAGE)
		// request example: ?action=blogroll&page=0&lang=en
		echo getBlogRoll($requestedPage, $language);
		break;

	case 'pageforpost':
		// get JS array of posts for a requested post
		// request example: ?action=pageforpost&page=20120101-0000&lang=en
		echo getPageForPost($requestedPage, $language);
		break;

	case 'photos':
		// get array of photos with description
		// request example: ?action=photos&lang=en
		echo getPhotos($language);
		break;

	case 'l10n':
		// get localized elements
		// request example: ?action:l10n&lang=ru
		echo getLocalization($language);
		break;

	case 'test':
		// test
		echo getPageForPost($requestedPage, $language);
		break;
}

// return full blog path
function getBlogPath($lang) {
	return SERVERPATH . BASEPATH . "blog/$lang";
}

// return full blog.lst file name
function getBlogList($lang) {
	return SERVERPATH . BASEPATH . "blog/$lang/blog.lst";
}

// return JS array elementId => localizedValue 
function getLocalization($lang) {
	$langFile = SERVERPATH . BASEPATH . "pages/$lang/lang.$lang.txt";
	if (file_exists($langFile))
		return file_get_contents($langFile);
	else
		return "[]";
}

// return page file name without inner anchors (e.g. "#osdev-boot" -> "/.../osdev.html")
//   if file doesn't exist, function returns "/.../404.html"
function getPageFileName($page, $lang) {
	// if page name contains hyphen
	// page name is part of the string before the hyphen
	$hyphenPos = strpos($page, '-');
	if ($hyphenPos !== false) $page = substr($page, 0, $hyphenPos);
	
	// full path
	$fileName = SERVERPATH . BASEPATH . "pages/$lang/$page";
	$fileCommonName = SERVERPATH . BASEPATH . "pages/common/$page";
	if (file_exists($fileName . '.txt')) $fileName .= '.txt';			// markdown page
	else if (file_exists($fileName . '.html')) $fileName .= '.html';	// html page
	else if (file_exists($fileCommonName . '.txt')) $fileName = $fileCommonName . '.txt'; // no localized version; markdown 
	else if (file_exists($fileCommonName . '.html')) $fileName = $fileCommonName . '.html'; // no localized version; html 
 	else if (file_exists(SERVERPATH . BASEPATH . "pages/$lang/404.html")) $fileName = SERVERPATH . BASEPATH . "pages/$lang/404.html"; 	// if there's no page with such a name, return 404.html
	else $fileName = SERVERPATH . BASEPATH . "pages/common/404.html";
	return $fileName;
}

// return page contents processed with the Markdown processor
function getProcessedPageContents($page, $lang) {
	$fileName = getPageFileName($page, $lang);
	$result = file_get_contents($fileName);
	return (substr($fileName, -5) == '.html' ? $result : str_replace('%MD%', Markdown($result), MARKDOWNWRAPPER));
}

// return original page contents
function getPageContents($page, $lang) {
	$fileName = getPageFileName($page, $lang);
	$result = file_get_contents($fileName);
	return $result;
}

// return latest post
function getLatestPost($lang) {
	// update list of blog posts
	updateBlogCache($lang);

	// full cache file name
	$blogFolder = getBlogPath($lang);
	$blogCacheFileName = getBlogList($lang);
	$latestPostId = '';
	if ($fh = fopen($blogCacheFileName, 'r')) {
		fgets($fh); // number of files
		$latestPostId = fgets($fh);
		$latestPostId = explode("\t", $latestPostId);
		fclose($fh);
	}

	return getPost($latestPostId[0], $lang);
}

// retun post with the specified id
function getPost($postId, $lang) {
	if ($postId == '')
		return '';

	// update list of blog posts
	updateBlogCache($lang);
	
	// trim post contents, remove attributes
	$postFileName = getBlogPath($lang) .'/' . $postId . '.txt';
	$contents = '';
	if ($fh = fopen($postFileName, 'r')) {
		while(false !== ($recLine = fgets($fh))) {
			if (substr($recLine, 0, 1) != '@')
				$contents .= $recLine . "\n";
		}
	}
	
	// markdown
	$contents = Markdown($contents);

	return $contents;
}

// return post attribute (@title:, @tags:, etc.)
function getPostAttribute($postId, $lang, $attribute) {
	// full cache file name
	$blogFolder = getBlogPath($lang);
	$postFileName = $blogFolder . '/' . $postId . '.txt';

	if ($fh = fopen($postFileName, 'r')) {
		while(false !== ($recLine = fgets($fh, 1024))) {
			$recLine = trim($recLine);
			if (substr($recLine, 0, strlen('@'.$attribute)) == '@'.$attribute)
				return trim(substr($recLine, strlen('@'.$attribute)+1));
			if ($recLine == '')
				break;
		}
		fclose($fh);
	}
	return '';
}

// return list of posts. $pageN=0 for the first page ("Recent posts").
function getBlogRoll($pageN, $lang){
	// update list of blog posts
	updateBlogCache($lang);

	// generate js array (to avoid json libraries)
	// full cache file name
	$blogFolder = getBlogPath($lang);
	$blogCacheFileName = getBlogList($lang);
	if (!file_exists($blogCacheFileName))
		return "[]";

	$numOfRecs = 0;
	$jsArray = "";
	if ($fh = fopen($blogCacheFileName, 'r')) {
		$numOfRecs = trim(fgets($fh)); // number of files
		// filter files we want to show
		$fileCounter = 0;
		$skipFiles = $pageN * RECSPERPAGE;

		// read line by line
		while (false !== ($recLine = fgets($fh, 4096))) {
			// skip posts
			if (--$skipFiles >= 0) continue;
			// posts per page
			if (++$fileCounter > RECSPERPAGE) break;

			$p = explode("\t", $recLine);
			if (count($p) != 3) continue;
			$jsArray .= '["' . 
				addslashes($p[0]) . '","' . 
				addslashes(trim($p[1])) . '","' . 
				addslashes(trim($p[2])) . '"],'  ;
		}
		fclose($fh);
	}

	// return JS array
	$jsArray = '[[' . (ceil($numOfRecs/RECSPERPAGE)) . ',' . $pageN . '],' . trim($jsArray, ',') . ']';
	return $jsArray;
}

// return list of posts that contains postId
function getPageForPost($postId, $lang){
	// update list of blog posts
	updateBlogCache($lang);

	// generate js array (to avoid jason libraries)
	// full cache file name
	$blogFolder = getBlogPath($lang);
	$blogCacheFileName = getBlogList($lang);
	if ($fh = fopen($blogCacheFileName, 'r')) {
		fgets($fh); 		// number of files in the file
		$fileCounter = 0; 	// counter of files

		// read line by line
		while (false !== ($recLine = fgets($fh, 4096))) {
			$p = explode("\t", $recLine);
			if (count($p) != 3) continue;
			if ($p[0] == $postId) 
				break;				
			++$fileCounter ;
		}
		fclose($fh);
		return getBlogRoll(floor($fileCounter/RECSPERPAGE), $lang);
	}
}


// update file blog/$lang/blog.lst
// File format:
//   num_of_posts
//   recent_file_name
//   ...
//   the_oldest_file_name
function updateBlogCache($lang) {
	// full cache file name
	$blogFolder = getBlogPath($lang);
	$blogCacheFileName = getBlogList($lang);
	$cachedNum = 0;

	// get files in the folder
	$files = array();
	$exceptFiles = array('.', '..', 'blog.lst');
	if ($handle = opendir($blogFolder)) {
		while (false !== ($entry = readdir($handle))) {
			// skip directories and non-blog entries 
			if (is_dir($entry) || in_array($entry, $exceptFiles))
				continue;
			
			if (substr($entry, -4) == '.txt') $files[] = $entry;
		}
	}
	closedir($handle);
	rsort($files);

	if (!file_exists($blogCacheFileName))
		file_put_contents($blogCacheFileName, "");

	// read number of files in the cache file
	if ($fh = fopen($blogCacheFileName, 'r')) {
		$cacheHeader = fread($fh, 16);
		fclose($fh);
	}

	// we need the first word (number) only
	$keywords = preg_split("/[\s\r\n]+/", $cacheHeader);
	if (is_numeric($keywords[0])) {
		$cachedNum = (int)$keywords[0];
	}

	// compare and update if numbers are not equal
	if (count($files) != $cachedNum) {
		if ($fh = fopen($blogCacheFileName, 'w')){
			// write number of files
			fwrite($fh, count($files) . "\n");
			// write files, titles and tags
			foreach ($files as $file) {
				if (substr($file,-4) != '.txt') continue;
				$postId = substr($file, 0, -4);
				fwrite($fh, $postId . "\t" . 
					getPostAttribute($postId, $lang, 'title') . "\t" . 
					getPostAttribute($postId, $lang, 'tags') . 
					"\n");
			}
			fclose($fh);
		}
	}
}

// get array of photos
function getPhotos($language) {
	$photosArray = "";
	$photosBasePath = SERVERPATH . BASEPATH . 'photos';
	$cacheLst = $photosBasePath . "/$language/cache.lst";
	$description = $photosBasePath . "/$language/description.txt";

	// modification date of description.txt
	$changeDate = date('YmdHis', filemtime($description));

	if ($fh = fopen($cacheLst, 'r')) {
		$cacheHeader = fgets($fh, 16);
		fclose($fh);

		$cacheHeader = trim($cacheHeader);

		if ($cacheHeader !== $changeDate) 
			updatePhotoCache($language);
	}

	if ($fh = fopen($cacheLst, 'r')) {
		fgets($fh, 16); // date of modification
		// read list of files
		while (false !== ($recLine = fgets($fh, 4096))) {
			$p = explode("\t", $recLine);
			if (count($p) == 3)
				$photosArray .= '[' . $p[0] . ', "' . stripcslashes($p[1]) . '","' . trim(stripcslashes($p[2])) . '"],';
			else if (count($p) == 4)
				$photosArray .= '[' . $p[0] . ', "' . stripcslashes($p[1]) . '","' . stripcslashes($p[2]) . '","' . trim(stripcslashes($p[3])) . '"],';
		}
	}
	
	return '[' . trim($photosArray, ',') . ']';
}

// update /photos/lang/cache.lst
// updates when datetime in the first line of cache.lst != datetime of description.txt
function updatePhotoCache($language) {
	$photosBasePath = SERVERPATH . BASEPATH . 'photos/';
	$cacheLst = $photosBasePath . "/$language/cache.lst";
	$description = $photosBasePath . "/$language/description.txt";

	$changeDate = date('YmdHis', filemtime($description));
	// array of descriptions
	$descArray = array();

	// read description.txt into array
	if (file_exists($description)) {
		if ($fh = fopen($description, 'r')) {
			while (false !== ($descLine = fgets($fh, 4096))) {
				$descLine = trim($descLine);
				if (!is_numeric(substr($descLine, 0, 4))) 
					continue;

				$p = explode(' ', $descLine, 2);
				$descArray[strtolower($p[0])] = trim($p[1]);
			}
			fclose($fh);
		}
	}

	// for each image or path find description in the array of description
	$cacheLines = array();
	
	// .../year/place/##.jpg
	
	// iterate through years
	$yearEntries = scandir($photosBasePath, 1);

	for ($i = 0, $imax = count($yearEntries); $i < $imax; $i++) {
		$yearEntry = $yearEntries[$i];
		if ($yearEntry == '.' || $yearEntry == '..' || !is_dir("$photosBasePath/$yearEntry"))
			continue;

		// iterate through places
		if ($places = opendir("$photosBasePath/$yearEntry")) {
			while (false !== ($placeEntry = readdir($places))) {
				if ($placeEntry == '.' || $placeEntry == '..' || !is_dir("$photosBasePath/$yearEntry/$placeEntry"))
					continue;

				$pathHash = strtolower("$yearEntry/$placeEntry");

				$cacheLines[] = "$yearEntry\t$placeEntry\t" . 
					(isset($descArray[$pathHash]) ? trim($descArray[$pathHash]) : '');

				// iterate through files
				if ($files = opendir("$photosBasePath/$yearEntry/$placeEntry")) {
					while (false !== ($fileEntry = readdir($files))) {
						if (is_dir("$photosBasePath/$yearEntry/$placeEntry/$fileEntry") ||
							(strtolower(substr($fileEntry,-4)) !== '.jpg')
							)
							continue;

						$fileHash = strtolower("$pathHash/$fileEntry");

						$cacheLines[] = "$yearEntry\t$placeEntry\t$fileEntry\t" . 
							(isset($descArray[$fileHash]) ? trim($descArray[$fileHash]) : ''); 
					}
				} // images 
				closedir($files);
			} 
		} // places
		closedir($places);
	} // years



	if ($fh = fopen($cacheLst, 'w')) {
		// write modify date of description.txt
		fwrite($fh, $changeDate . "\n");
		
		// write files and descriptions
		foreach ($cacheLines as $value) {
			fwrite($fh, $value . "\n");
		}
		
		fclose($fh);
	}

}

