<?php
/// Copyright (c) 2004-2011, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)

function printMobileEntryContentView($blogid, $entry, $keywords = array()) {
	global $blogURL;
	if (doesHaveOwnership() || ($entry['visibility'] >= 2) || (isset($_COOKIE['GUEST_PASSWORD']) && (trim($_COOKIE['GUEST_PASSWORD']) == trim($entry['password'])))) {
		$content = getEntryContentView($blogid, $entry['id'], $entry['content'], $entry['contentformatter'], $keywords, 'Post', false);
		print '<div class="entry_body" data-role="content" data-theme="d">' . printMobileFreeImageResizer($content) . '</div>';
	} else {
	?>
	<p><b><?php echo _text('Protected post!');?></b></p>
	<form id="passwordForm" class="dialog" method="post" action="<?php echo $blogURL;?>/protected/<?php echo $entry['id'];?>">
		<fieldset>
			<label for="password"><?php echo _text('Password:');?></label>
			<input type="password" id="password" name="password" />
			<a href="#" class="whiteButton margin-top10" type="submit"><?php echo _text('View Post');?></a>
        </fieldset>
	</form>
	<?php
	}
}

function printMobileEntryContent($blogid, $userid, $id) {
	global $database;
	$result = POD::queryCell("SELECT content 
		FROM {$database['prefix']}Entries
		WHERE 
			blogid = $blogid AND userid = $userid AND id = $id");
	return $result;
}

function printMobileCategoriesView($totalPosts, $categories) {
	global $blogURL, $service, $blog;
	requireModel('blog.category');
	requireLibrary('blog.skin');
	$blogid = getBlogId();
	$categoryCount = 0;
	$categoryCountAll = 0;
	$parentCategoryCount = 0;
	$tree = array('id' => 0, 'label' => 'All Category', 'value' => $totalPosts, 'link' => "$blogURL/category/0", 'children' => array());
	foreach ($categories as $category1) {
		$children = array();
		if(doesHaveOwnership() || getCategoryVisibility($blogid, $category1['id']) > 1) {
			foreach ($category1['children'] as $category2) {
				if( doesHaveOwnership() || getCategoryVisibility($blogid, $category2['id']) > 1) {
					array_push($children, 
						array('id' => $category2['id'], 
							'label' => $category2['name'], 
							'value' => (doesHaveOwnership() ? $category2['entriesinlogin'] : $category2['entries']), 
							'link' => "$blogURL/category/" . $category2['id'], 
							'children' => array()
						)
					);
					$categoryCount = $categoryCount + (doesHaveOwnership() ? $category2['entriesinlogin'] : $category2['entries']);
				}
				$categoryCountAll = $categoryCountAll + (doesHaveOwnership() ? $category2['entriesinlogin'] : $category2['entries']);
			}
			$parentCategoryCount = (doesHaveOwnership() ? $category1['entriesinlogin'] - $categoryCountAll : $category1['entries'] - $categoryCountAll);
			if($category1['id'] != 0) {
				array_push($tree['children'], 
					array('id' => $category1['id'], 
						'label' => $category1['name'], 
						'value' => $categoryCount + $parentCategoryCount, 
						'link' => "$blogURL/category/" . $category1['id'], 
						'children' => $children)
				);
			}
			$categoryCount = 0;
			$categoryCountAll = 0;
			$parentCategoryCount = 0;
		}
	}
	return printMobilePrintTreeView($tree, true);
}

function printMobilePrintTreeView($tree, $xhtml=true) {
	if ($xhtml) {
		$printCategory  = '<li class="category"><a href="' . htmlspecialchars($tree['link']) . '" class="link">' . htmlspecialchars($tree['label']);
		$printCategory .= ' <span class="c_cnt">' . $tree['value'] . '</span>';
		$printCategory .= '</a></li>';
		for ($i=0; $i<count($tree['children']); $i++) {
			$child = $tree['children'][$i];
			$printCategory .= '<li class="category" data-theme="b"><a href="' . htmlspecialchars($child['link']) . '" class="link">' . htmlspecialchars($child['label']);
			$printCategory .= ' <span class="c_cnt">' . $child['value'] . '</span>';
			$printCategory .= '</a></li>';
			if (sizeof($child['children']) > 0) {
				for ($j=0; $j<count($child['children']); $j++) {
					$leaf = $child['children'][$j];
					$printCategory .= '<li class="category_sub"><a href="' . htmlspecialchars($leaf['link']) . '" class="link">&bull;&nbsp; ' . htmlspecialchars($leaf['label']);
					$printCategory .= ' <span class="c_cnt">' . $leaf['value'] . '</span>';
					$printCategory .= '</a></li>';
				}
			}
		}
		return $printCategory;
	}
}

function printMobileArchives($blogid) {
	global $database;
	$archives = array();
	$visibility = doesHaveOwnership() ? '' : 'AND e.visibility > 0'.getPrivateCategoryExclusionQuery($blogid);
	$skinSetting = Setting::getSkinSettings($blogid);
	$result = POD::queryAllWithDBCache("SELECT EXTRACT(year_month FROM FROM_UNIXTIME(e.published)) period, COUNT(*) count 
		FROM {$database['prefix']}Entries e
		WHERE e.blogid = $blogid AND e.draft = 0 $visibility AND e.category >= 0 
		GROUP BY period 
		ORDER BY period 
		DESC ");
	if ($result) {
		foreach($result as $archive)
			array_push($archives, $archive);
	}
	return $archives;
}

function printMobileArchivesView($archives) {
	global $blogURL;
	$oldPeriod = '';
	$newPeriod = '';
	$printArchive = '';
	foreach ($archives as $archive) {
		$newPeriod = substr($archive['period'],0,4);
		if($newPeriod != $oldPeriod){
			$printArchive .= '<li class="group"><span class="left">' . $newPeriod . '</span><span class="right">&nbsp;</span></li>';
		}
		$dateName = date("F Y",(mktime(0,0,0,substr($archive['period'],4),1,substr($archive['period'],0,4))));
		$printArchive .= '<li class="archive"><a href="' . $blogURL . '/archive/' . $archive['period'] . '" class="link">' . $dateName;
		$printArchive .= ' <span class="c_cnt">' . $archive['count'] . '</span>';
		$printArchive .= '</a></li>';
		$oldPeriod = substr($archive['period'],0,4);
	}
	return $printArchive;
}

function printMobileTags($blogid, $flag = 'random', $max = 10) {
	global $database, $skinSetting;
	$tags = array();
	$aux = "limit $max";
	if ($flag == 'count') { // order by count
			$tags = POD::queryAll("SELECT name, count(*) AS cnt, t.id FROM {$database['prefix']}Tags t,
				{$database['prefix']}TagRelations r, 
				{$database['prefix']}Entries e 
				WHERE r.entry = e.id AND e.visibility > 0 AND t.id = r.tag AND r.blogid = $blogid 
				GROUP BY r.tag, name, cnt, t.id
				ORDER BY cnt DESC $aux");
	} else if ($flag == 'name') {  // order by name
			$tags = POD::queryAll("SELECT DISTINCT name, count(*) AS cnt, t.id FROM {$database['prefix']}Tags t, 
				{$database['prefix']}TagRelations r,
				{$database['prefix']}Entries e 
				WHERE r.entry = e.id AND e.visibility > 0 AND t.id = r.tag AND r.blogid = $blogid 
				GROUP BY r.tag, name, cnt, t.id 
				ORDER BY t.name $aux");
	} else { // random
			$tags = POD::queryAll("SELECT name, count(*) AS cnt, t.id FROM {$database['prefix']}Tags t,
				{$database['prefix']}TagRelations r,
				{$database['prefix']}Entries e
				WHERE r.entry = e.id AND e.visibility > 0 AND t.id = r.tag AND r.blogid = $blogid 
				GROUP BY r.tag 
				ORDER BY RAND() $aux");
	}
	return $tags;
}

function printMobileTagsView($tags) {
	global $blogURL, $service;
	ob_start();
	list($maxTagFreq, $minTagFreq) = getTagFrequencyRange();
	foreach ($tags as $tag) {
		$printTag .= '<li class="tag"> <a href="' . $blogURL . '/tag/' . $tag['id'] . '" class="cloud' . getTagFrequency($tag, $maxTagFreq, $minTagFreq).'" >' . htmlspecialchars($tag['name']);
		$printTag .= '</a> </li>';
	}
	$view = ob_get_contents();
	ob_end_clean();
	return $printTag;
}

function printMobileLinksView($links) {
	global $blogURL, $skinSetting, $suri, $pathURL;
	if( rtrim( $suri['url'], '/' ) == $pathURL ) {
		$home = true;
	} else {
		$home = false;
	}
	$categoryName = '';
	foreach ($links as $link) {
		if((!doesHaveOwnership() && $link['visibility'] == 0) ||
			(!doesHaveMembership() && $link['visibility'] < 2)) {
			continue;
		}
		if (!empty($link['categoryName']) && $link['categoryName'] != $categoryName) {
			$linkView .= '<li data-theme="b">'. htmlspecialchars(UTF8::lessenAsEm($link['categoryName'], $skinSetting['linkLength'])) . '</li>'.CRLF;
			$categoryName = $link['categoryName'];			
		}  
		$linkView .= '<li><a href="' . htmlspecialchars($link['url']) . '" class="link" target="_blank">' . htmlspecialchars(UTF8::lessenAsEm($link['name'], $skinSetting['linkLength'])) . '</a></li>'.CRLF;
	}
	return $linkView;
}

function printMobileHTMLHeader($title = '') {
	$context = Model_Context::getInstance();
	$title = htmlspecialchars($context->getProperty('blog.title') . ' :: ' . $title);
?><!DOCTYPE html> 
<html> 
	
<head>
	<title><?php echo $title;?></title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1"> 
	<link rel="stylesheet" type="text/css" href="<?php echo $context->getProperty('service.path');?>/resources/style/iphone/iphone.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo $context->getProperty('service.path');?>/resources/style/iphone/jquery.mobile-<?php echo JQUERYMOBILE_VERSION;?>.css" />
<?php
	if(Setting::getBlogSettingGlobal('useBlogIconAsIphoneShortcut',true) && file_exists(ROOT."/attach/".$context->getProperty('blog.id')."/index.gif")) {
?>
	<link rel="apple-touch-icon" href="<?php echo $context->getProperty('uri.default')."/index.gif";?>" />
<?php
	}
?>
	<script type="application/x-javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/jquery/jquery-<?php echo JQUERY_VERSION;?>.js"></script>
	<script type="application/x-javascript" src="<?php echo $context->getProperty('service.path');?>/resources/script/jquery.mobile/jquery.mobile-<?php echo JQUERYMOBILE_VERSION;?>.js"></script>
</head>
<body>
<?php
}

function printMobileHTMLFooter() {
?>
	</body>
</html>
<?php
}

function printMobileHTMLMenu($title = '',$menu='') {
	$context = Model_Context::getInstance();
	$title = htmlspecialchars($context->getProperty('blog.title') . ' :: ' . $title);
?>
	<div data-role="header" class="toolbar">
		<h1 id="pageTitle"><?php echo htmlspecialchars($context->getProperty('blog.title'));?></h1>
		<a data-role="button" data-rel="back" data-icon="back" data-iconpos="notext" id="backButton" class="button" href="#"><?php echo _text('뒤로');?></a>
		<a data-role="button" data-icon="search" class="button" href="#searchForm" id="searchButton" onclick="searchAction(true);"><?php echo _text('검색');?></a>
	</div>
	<div data-role="navbar" data-position="fixed" class="toolbar shortcut">
		<ul>
			<li><a href="<?php echo $context->getProperty('uri.blog');?>" rel="external" <?php echo $menu=="list" ? 'class="ui-btn-active"' : '';?>><?php echo _text('글목록');?></a></li>
			<li><a href="<?php echo $context->getProperty('uri.blog');?>/comment" rel="external" <?php echo $menu=="comment" ? 'class="ui-btn-active"' : '';?>><?php echo _text('댓글');?></a></li>
			<li><a href="<?php echo $context->getProperty('uri.blog');?>/trackback" rel="external" <?php echo $menu=="trackback" ? 'class="ui-btn-active"' : '';?>><?php echo _text('트랙백');?></a></li>
			<li><a href="<?php echo $context->getProperty('uri.blog');?>/guestbook" rel="external" <?php echo $menu=="guestbook" ? 'class="ui-btn-active"' : '';?>><?php echo _text('방명록');?></a></li>
		</ul>
	</div>
<?php
}

function printMobileAttachmentExtract($content){
	global $service;
	$blogid = getBlogId();
	$result = null;

	if(preg_match_all('/\[##_(1R|1L|1C|2C|3C|iMazing|Gallery)\|[^|]*\.(gif|jpg|jpeg|png|bmp|GIF|JPG|JPEG|PNG|BMP)\|.*_##\]/si', $content, $matches)) {
		$split = explode("|", $matches[0][0]);
		$result = $split[1];
	} else if(preg_match_all('/<img[^>]+?src=("|\')?([^\'">]*?)("|\')/si', $content, $matches)) {
		$pattern1 = $service['path'] . "/attach/{$blogid}/";
		$pattern2 = "[##_ATTACH_PATH_##]";

		if ((strpos($matches[2][0], $pattern1) === 0) || (strpos($matches[2][0], $pattern2) === 0)) {
			$result = basename($matches[2][0]);
		} else {
			$result = $matches[2][0];
		}
	}
	return $result;
}

function printMobileFreeImageResizer($content) {
	global $service, $blogURL;
	$blogid = getBlogId();
	$pattern1 = "@<img.+src=['\"](.+)['\"].*>@Usi";
	$pattern2 = $service['path'] . "/attach/{$blogid}/";

	if (preg_match_all($pattern1, $content, $matches)) {
		foreach($matches[0] as $imageTag) {
			preg_match($pattern1, $imageTag, $matche);
			if (strpos($matche[1], $pattern2) === 0) {
				$filename = basename($matche[1]);
				$replaceTag = preg_replace($pattern1 , "<img src=\"{$blogURL}/imageResizer/?f={$filename}\" alt=\"\" />", $matche[0]);
				$content = str_replace($matche[0], $replaceTag, $content);
			}
		}
	}
	return $content;
}

function printMobileImageResizer($blogid, $filename, $cropSize){
	global $serviceURL;
	requireComponent('Textcube.Function.misc');

	if (!is_dir(ROOT."/cache/thumbnail")) {
		@mkdir(ROOT."/cache/thumbnail");
		@chmod(ROOT."/cache/thumbnail", 0777);
	}
	if (!is_dir(ROOT."/cache/thumbnail/" . $blogid)) {
		@mkdir(ROOT."/cache/thumbnail/" . $blogid);
		@chmod(ROOT."/cache/thumbnail/" . $blogid, 0777);
	}
	if (!is_dir(ROOT."/cache/thumbnail/" . $blogid . "/iphoneThumbnail/")) {
		@mkdir(ROOT."/cache/thumbnail/" . $blogid . "/iphoneThumbnail/");
		@chmod(ROOT."/cache/thumbnail/" . $blogid . "/iphoneThumbnail/", 0777);
	}
	
	$thumbFilename = $filename;
	$imageURL = "{$serviceURL}/attach/{$blogid}/{$filename}";
	if (extension_loaded('gd')) {	
		if (stristr($filename, 'http://')) {
			$thumbFilename = printMobileRemoteImageFilename($filename);
		}

		$thumbnailSrc = ROOT . "/cache/thumbnail/{$blogid}/iphoneThumbnail/th_{$thumbFilename}";
		if (!file_exists($thumbnailSrc)) {
			$imageURL = printMobileCropProcess($blogid, $filename, $cropSize);
		} else {
			$imageURL = "{$serviceURL}/thumbnail/{$blogid}/iphoneThumbnail/th_{$thumbFilename}";
		}
	} else {
		if (stristr($filename, 'http://')) {
			$imageURL = $filename;
		}
	}
	return $imageURL;
}

function printMobileCropProcess($blogid, $filename, $cropSize) {
	global $serviceURL;
	$tempFile = null;
	$imageURL = null;
	if(stristr($filename, 'http://') ){
		list($originSrc, $filename, $tempFile) = printMobileCreateRemoteImage($blogid, $filename);
	} else {
		$originSrc = ROOT . "/attach/{$blogid}/{$filename}";
	}

	$thumbnailSrc = ROOT . "/cache/thumbnail/{$blogid}/iphoneThumbnail/th_{$filename}";
	if (file_exists($originSrc)) {
		$imageInfo = getimagesize($originSrc);

		$objThumbnail = new Utils_Image();
		if ($imageInfo[0] > $imageInfo[1])
			list($tempWidth, $tempHeight) = $objThumbnail->calcOptimizedImageSize($imageInfo[0], $imageInfo[1], NULL, $cropSize);
		else
			list($tempWidth, $tempHeight) = $objThumbnail->calcOptimizedImageSize($imageInfo[0], $imageInfo[1], $cropSize, null);

		$objThumbnail->imageFile = $originSrc;
		if ($objThumbnail->resample($tempWidth, $tempHeight) && $objThumbnail->cropRectBySize($cropSize, $cropSize)) {
			$imageURL = "{$serviceURL}/thumbnail/{$blogid}/iphoneThumbnail/th_{$filename}";
			$objThumbnail->saveAsFile($thumbnailSrc);
		}

		unset($objThumbnail);
		if($tempFile) unlink($originSrc);
	} else {
		$imageURL = null;
	}

	return $imageURL;
}

function printMobileCreateRemoteImage($blogid, $filename) {
	$fileObject = false;
	$tmpDirectory = ROOT . "/cache/thumbnail/{$blogid}/iphoneThumbnail/";
	$tempFilename = tempnam($tmpDirectory, "remote_");
	$fileObject = @fopen($tempFilename, "w");

	if ($fileObject) {
		$originSrc = $tempFilename;
		$remoteImage = printMobileHTTPRemoteImage($filename);
		$filename = printMobileRemoteImageFilename($filename);
		fwrite($fileObject, $remoteImage);
		fclose($fileObject);
		return array($originSrc, $filename, true);
	} else {
		return array(null, null, null);
	}
}

function printMobileHTTPRemoteImage($remoteImage) {
    $response = '';
	$remoteStuff = parse_url($remoteImage);
	$port = isset($remoteStuff['port']) ? $remoteStuff['port'] : 80;

	$socket = @fsockopen($remoteStuff['host'], $port);
    fputs($socket, "GET " . $remoteStuff['path'] . " HTTP/1.1\r\n");
    fputs($socket, "Host: " . $remoteStuff['host'] . "\r\n");
    fputs($socket, "User-Agent: Mozilla/4.0 (compatible; Textcube)\r\n");
    fputs($socket, "Accept-Encoding: identity\r\n");
    fputs($socket, "Connection: close\r\n");
    fputs($socket, "\r\n");

	while ($buffer = fread($socket, 1024)) {
		$response .= $buffer;
	}

	preg_match('/Content-Length: ([0-9]+)/', $response, $matches);
	return substr($response, - $matches[1]);
}

function printMobileRemoteImageFilename($filename) {
	$filename = md5($filename) . "." . Misc::getFileExtension($filename);
	return $filename;
}

function printMobileNavigation($entry, $jumpToComment = true, $jumpToTrackback = true, $paging = null, $mode = 'entry') {
	$context = Model_Context::getInstance();
	global $suri, $blogURL;
?>
<!--	<div data-role="footer" class="ui-bar" data-theme="c">-->
		<div data-role="navbar" data-theme="c">
			<ul>
		<?php
	if (isset($paging['prev'])) {
?>
				<li><a data-role="button" data-theme="d" data-icon="arrow-l" href="<?php echo $blogURL.'/'.$mode;?>/<?php echo $paging['prefix'].$paging['prev'];?>" accesskey="1"><?php echo _text('이전 페이지');?></a></li>
		<?php
	}
	if (!isset($paging)) {
?>	
				<li><a data-role="button" data-transition="flip" href="<?php echo $blogURL.'/'.$mode;?>/<?php echo $entry['id'];?>" accesskey="3"><?php echo _text('원 글 보기');?></a></li>
		<?php
	}
	if ($jumpToComment) {
?>
				<li><a data-role="button" data-icon="info" data-transition="flip" href="<?php echo $blogURL;?>/comment/<?php echo $entry['id'];?>" accesskey="4"><?php echo _text('댓글 보기');?> (<?php echo $entry['comments'];?>)</a></li>
		<?php
	}
	if ($jumpToTrackback) {
?>
				<li><a data-role="button" data-icon="info" data-transition="flip" href="<?php echo $blogURL;?>/trackback/<?php echo $entry['id'];?>" accesskey="5"><?php echo _text('트랙백 보기');?> (<?php echo $entry['trackbacks'];?>)</a></li>
		<?php
	}
	if ($suri['directive'] != '/i') {
	/*
?>
				<li><a href="<?php echo $blogURL;?>" onclick="window.location.href='<?php echo $blogURL;?>';" accesskey="6"><?php echo _text('첫화면으로 돌아가기');?></a></li>
		<?php
	*/
	}
	if (isset($paging['next'])) {
?>
				<li><a data-role="button" data-theme="d" data-icon="arrow-r" href="<?php echo $blogURL.'/'.$mode;?>/<?php echo $paging['prefix'].$paging['next'];?>" accesskey="2"><?php echo _text('다음 페이지');?></a></li>
		<?php
	}
?>
			</ul>
		</div>
<?php
}

function printMobileTrackbackView($entryId, $page, $mode = null) {
	global $paging, $blogid;
	if($mode == 'recent') {
		list($trackbacks,$paging) = getRemoteResponsesWithPaging($blogid, -1, $page, 10, null, '?page=');
	} else {
		$trackbacks = getTrackbacks($entryId);
	}
	if (count($trackbacks) == 0) {
?>
		<p>&nbsp;<?php echo _text('트랙백이 없습니다');?></p>
		<?php
	} else {
		foreach ($trackbacks as $trackback) {
?>
		<ul data-role="listview" data-inset="true" id="trackback_<?php echo $commentItem['id'];?>" class="trackback">
			<li class="group">
				<span class="left">
					<?php echo htmlspecialchars($trackback['subject']);?>
				</span>
				<span class="right">
					<a href="<?php echo $blogURL;?>/i/entry/<?php echo $trackback['entry'];?>"><?php echo  _text('글보기');?></a>
				</span>
			</li>
			<li class="body">
				<p class="ui-li-aside"><?php echo Timestamp::format5($trackback['written']);?></p>
				<?php echo htmlspecialchars($trackback['excerpt']);?>
			</li>
		</ul>
		<?php
		}
	}
}

function printMobileCommentView($entryId, $page = null, $mode = null) {
	global $blogURL, $blogid, $skinSetting, $paging;
	if ($mode == 'recent') {	// Recent comments
		list($comments, $paging) = getCommentsWithPaging($blogid, $page, 10, null, '?page=');
	} else if(!is_null($page)) {	// Guestbook
		list($comments, $paging) = getCommentsWithPagingForGuestbook($blogid, $page, $skinSetting['commentsOnGuestbook']);
	} else {	// Comments related to specific article
		$comments = getComments($entryId);
	}
	if (count($comments) == 0) {
?>
		<p>&nbsp;<?php echo ($entryId == 0 ? _text('방명록이 없습니다') : _text('댓글이 없습니다'));?></p>
		<?php
	} else {
		foreach ($comments as $commentItem) {
?>
		<ul data-role="listview" id="comment_<?php echo $commentItem['id'];?>" class="comment">
			<li class="group">
				<p class="left">
					<?php if(!empty($commentItem['name'])) { ?><strong><?php echo htmlspecialchars($commentItem['name']);?></strong><?php } ?>
				</p>
				<p class="ui-li-aside">
					<?php echo Timestamp::format5($commentItem['written']);?>
				</p>
				<p class="right">
					<div class="comment_button" data-role="controlgroup" data-type="horizontal">
						<a href="<?php echo $blogURL;?>/comment/comment/<?php echo $commentItem['id'];?>" data-role="button" data-icon="plus" data-iconpos="notext"><?php echo ($entryId == 0 ? _text('방명록에 댓글 달기') : _text('댓글에 댓글 달기'));?></a> 
						<a href="<?php echo $blogURL;?>/comment/delete/<?php echo $commentItem['id'];?>" data-role="button" data-icon="delete" data-iconpos="notext"><?php echo _text('지우기');?></a>
					</div>
				</p>
				<p class="body">
				<?php echo ($commentItem['secret'] && doesHaveOwnership() ? '<div class="hiddenComment" style="font-weight: bold; color: #e11">'.($entryId == 0 ? _text('비밀 방명록') : _text('비밀 댓글')).' &gt;&gt;</div>' : '').nl2br(addLinkSense(htmlspecialchars($commentItem['comment'])));?>
				</p>
			</li>
			<?php
			foreach (getCommentComments($commentItem['id']) as $commentSubItem) {
?>
			<li class="groupSub">
				<p class="left">&nbsp;Re :
					<?php if(!empty($commentSubItem['name'])) { ?><strong><?php echo htmlspecialchars($commentSubItem['name']);?></strong><?php } ?>
				</p>
				<p class="ui-li-aside">
					<?php echo Timestamp::format5($commentSubItem['written']);?>
				</p>
				<p class="right">
					<div class="comment_button" data-role="controlgroup" data-type="horizontal">
						<a href="<?php echo $blogURL;?>/comment/delete/<?php echo $commentSubItem['id'];?>" data-role="button" data-icon="delete" data-inline="true" data-iconpos="notext"><?php echo _text('지우기');?></a>
					</div>
				</p>
				<p class="body">
				<?php echo ($commentSubItem['secret'] && doesHaveOwnership() ? '<div class="hiddenComment" style="font-weight: bold; color: #e11">'._t('Secret Comment').' &gt;&gt;</div>' : '').nl2br(addLinkSense(htmlspecialchars($commentSubItem['comment'])));?>
				</p>
			</li>
			<?php
			}
?>
		</ul>
		<?php
		}
	}
	if($mode != 'recent') {	
		printMobileCommentFormView($entryId, ($entryId == 0 ? _text('방명록 쓰기') : _text('댓글 쓰기')), 'comment');
	}
}

function printMobileGuestbookView($page) {
	return printMobileCommentView(0, $page);
}

function printMobileRecentCommentView($page) {
	return printMobileCommentView(1, $page, 'recent');
}

function printMobileRecentTrackbackView($page) {
	return printMobileTrackbackView(1, $page, 'recent');
}

function printMobileCommentFormView($entryId, $title, $actionURL) {
	global $blogURL;
?>
	
	<form method="GET" action="<?php echo $blogURL;?>/<?php echo $actionURL;?>/add/<?php echo $entryId;?>" class="commentForm">
	<h2><?php echo $title;?></h2>
	<fieldset>
		<?php
	if (!doesHaveOwnership()) {
?>
		<input type="hidden" name="id" value="<?php echo $entryId;?>" />
		<input type="hidden" id="secret_<?php echo $entryId;?>" name="secret_<?php echo $entryId;?>" value="0" />
	</fieldset>
	<fieldset class="ui-grid-a">
		<div class="ui-block-a"><label for="secret_<?php echo $entryId;?>" ><?php echo _text('비밀 댓글');?></label></div>
		<div class="ui-block-b"><select name="secretButton" id="secret_<?php echo $entryId;?>" data-role="slider">
			<option value="0">|</option>
			<option value="1">O</option>
			</select>
		</div>
	</fieldset>
	<fieldset>
		<div data-role="fieldcontain">
			<label for="name_<?php echo $entryId;?>"><?php echo _text('이름');?></label>
			<input type="text" id="name_<?php echo $entryId;?>" name="name_<?php echo $entryId;?>" value="<?php echo isset($_COOKIE['guestName']) ? htmlspecialchars($_COOKIE['guestName']) : '';?>" />
			<label for="password_<?php echo $entryId;?>"><?php echo _text('비밀번호');?></label>
			<input type="password" id="password_<?php echo $entryId;?>" name="password_<?php echo $entryId;?>" />
			<label for="homepage_<?php echo $entryId;?>"><?php echo _text('홈페이지');?></label>
			<input type="text" id="homepage_<?php echo $entryId;?>" name="homepage_<?php echo $entryId;?>"  value="<?php echo (isset($_COOKIE['guestHomepage']) && $_COOKIE['guestHomepage'] != 'http://') ? htmlspecialchars($_COOKIE['guestHomepage']) : 'http://';?>" />
		</div>
		<?php
	}
?>
		<div class="row">
			<textarea cols="40" rows="6" id="comment_<?php echo $entryId;?>" name="comment_<?php echo $entryId;?>"></textarea>
		</div>
	</fieldset>
	<fieldset class="ui-grid-a">
		<div class="ui-block-a"><button type="reset" data-theme="d"><?php echo _text('취소');?></button></div>
		<div class="ui-block-b"><button type="submit" data-theme="a"><?php echo _text('작성');?></button></div>
	</fieldset>
	</form>
	
	<?php
}

function printMobileErrorPage($messageTitle, $messageBody, $redirectURL) {
?>
	<div id="postError" title="Error" class="panel">
		<h2 class="title"><?php echo htmlspecialchars($messageTitle);?></h2>
		<div class="content">
			<?php echo htmlspecialchars($messageBody);?>
		</div>
		<a href="<?php echo $redirectURL;?>" class="whiteButton margin-top10"><?php echo _text('이전 페이지로 돌아가기');?></a>
	</div>
<?php
}

function printMobileSimpleMessage($message, $redirectMessage, $redirectURL, $title = '') {
?>
	<div id="postSuccess" title="Successfully" class="panel">
		<div class="content">
			<?php echo htmlspecialchars($message);?>
		</div>
		<a href="<?php echo $redirectURL;?>" class="whiteButton margin-top10"><?php echo htmlspecialchars($redirectMessage);?></a>
	</div>
<?php
}
?>
