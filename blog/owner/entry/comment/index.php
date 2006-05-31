<?
define('ROOT', '../../../..');
require ROOT . '/lib/includeForOwner.php';
requireComponent('Tattertools.Data.Filter');
$categoryId = empty($_POST['category']) ? 0 : $_POST['category'];
$name = empty($_POST['name']) ? '' : $_POST['name'];
$ip = empty($_POST['ip']) ? '' : $_POST['ip'];
$search = empty($_POST['withSearch']) || empty($_POST['search']) ? '' : trim($_POST['search']);
$page = getPersonalization($owner, 'rowsPerPage');
if (empty($_POST['perPage'])) {
	$perPage = $page;
} else if ($page != $_POST['perPage']) {
	setPersonalization($owner, 'rowsPerPage', $_POST['perPage']);
	$perPage = $_POST['perPage'];
} else {
	$perPage = $_POST['perPage'];
}
list($comments, $paging) = getCommentsWithPagingForOwner($owner, $categoryId, $name, $ip, $search, $suri['page'], $perPage);
require ROOT . '/lib/piece/owner/header0.php';
require ROOT . '/lib/piece/owner/contentMenu01.php';
?>
									<input type="hidden" name="withSearch" value="" />
									<input type="hidden" name="name" value="" />
									<input type="hidden" name="ip" value="" />
									
									<script type="text/javascript">
										//<![CDATA[
											function deleteComment(id) {
												if (!confirm("<?=_t('선택된 댓글을 삭제합니다. 계속하시겠습니까?')?>"))
													return;
												var request = new HTTPRequest("GET", "<?=$blogURL?>/owner/entry/comment/delete/" + id);
												request.onSuccess = function () {
													document.forms[0].submit();
												}
												request.send();
											}
											function deleteComments() {	
												if (!confirm("<?=_t('선택된 댓글을 삭제합니다. 계속하시겠습니까?')?>"))
													return false;
												
												var oElement;
												var targets = '';
												for (i = 0; document.forms[0].elements[i]; i ++) {
													
													oElement = document.forms[0].elements[i];
													if ((oElement.name == "entry") && oElement.checked) {
														targets += oElement.value +'~*_)';
													
													}
												}
												
												var request = new HTTPRequest("POST", "<?=$blogURL?>/owner/entry/comment/delete/");
												request.onSuccess = function() {
													document.forms[0].submit();
												}
												request.send("targets=" + targets);
											}
											
											function checkAll(checked) {
												for (i = 0; document.forms[0].elements[i]; i ++)
													if (document.forms[0].elements[i].name == "entry")
														document.forms[0].elements[i].checked = checked;
											}
											
											function changeState(caller, value, no, mode) {
												try {
													if (caller.className == 'block-icon bullet') {
														var command 	= 'unblock';
													} else {
														var command 	= 'block';
													}
													var name 		= caller.getAttribute('name');

													param  	=  '?value='	+ encodeURIComponent(value);
													param 	+= '&mode=' 	+ mode;
													param 	+= '&command=' 	+ command;
													param 	+= '&id=' 	+ no;

													var request = new HTTPRequest("GET", "<?=$blogURL?>/owner/setting/filter/change/" + param);
													var iconList = document.getElementsByTagName("a");	
													for (var i = 0; i < iconList.length; i++) {
														icon = iconList[i];
														if(icon.getAttribute('name') == null || icon.getAttribute('name').toLowerCase() != name.toLowerCase()) continue;
														
														if (command == 'block') {
															icon.className = 'block-icon bullet';
															icon.innerHTML = "<span><?=_t('[차단됨]')?></span>";
															if (mode == 'name') {
																icon.setAttribute('title', "<?=_t('이 이름은 차단되었습니다. 클릭하시면 차단을 해제합니다.')?>");
															} else {
																icon.setAttribute('title', "<?=_t('이 IP는 차단되었습니다. 클릭하시면 차단을 해제합니다.')?>");
															}
														} else {
															icon.className = 'unblock-icon bullet';
															icon.innerHTML = "<span><?=_t('[허용됨]')?></span>";
															if (mode == 'name') {
																icon.setAttribute('title', "<?=_t('이 이름은 차단되지 않았습니다. 클릭하시면 차단합니다.')?>");
															} else {
																icon.setAttribute('title', "<?=_t('이 IP는 차단되지 않았습니다. 클릭하시면 차단합니다.')?>");
															}
														}
														//if(icon.getAttribute('id').toLowerCase() != id.toLowerCase())
														//?? request.presetProperty(icon.style, "display", "block");
														//else
														//?? request.presetProperty(icon.style, "display", "none");
													}
													request.send();
												} catch(e) {
													alert(e.message);
												}
											}
										//]]>
									</script>
									
									<div id="part-post-comment" class="part">
										<h2 class="caption">
											<span class="category">
												<select id="category" name="category" onchange="document.forms[0].page.value=1; document.forms[0].submit()">
													<option value="0"><?php echo _t('전체')?></option>
<?php
foreach (getCategories($owner) as $category) {
?>
													<option value="<?php echo $category['id']?>"<?php echo ($category['id'] == $categoryId ? ' selected="selected"' : '')?>><?php echo htmlspecialchars($category['name'])?></option>
<?php
	foreach ($category['children'] as $child) {
?>
													<option value="<?php echo $child['id']?>"<?php echo ($child['id'] == $categoryId ? ' selected="selected"' : '')?>>&nbsp;― <?php echo htmlspecialchars($child['name'])?></option>
<?php
	}
}
?>
												</select>
											</span>
											<span class="interword"><?php echo _t('분류에')?></span>
											<span class="main-text"><?php echo _t('등록된 댓글 목록입니다')?></span>
<?
if (strlen($name) > 0 || strlen($ip) > 0) {
	if (strlen($name) > 0) {
?>
											<span class="divider"> : </span><span class="name"><?=htmlspecialchars($name)?></span>
<?
	}
	
	if (strlen($ip) > 0) {
?>
											<span class="divider"> : </span><span class="site"><?=htmlspecialchars($ip)?></span>
<?
	}
}
?>

											<span class="clear"></span>
										</h2>
										
										<table class="data-inbox" cellspacing="0" cellpadding="0">
											<thead>
												<tr>
													<td class="selection"><input type="checkbox" class="checkbox" onclick="checkAll(this.checked);" /></td>
													<td class="date"><span class="text"><?=_t('등록일자')?></span></td>
													<td class="name"><span class="text"><?=_t('이름')?></span></td>
													<td class="content"><span class="text"><?=_t('내용')?></span></td>
													<td class="ip"><acronym title="Internet Protocol">ip</acronym></td>
													<td class="delete"><span class="text"><?=_t('삭제')?></span></td>
												</tr>
											</thead>
											<tbody>
<?
$nameNumber = array();
$ipNumber = array();
for ($i=0; $i<sizeof($comments); $i++) {
	$comment = $comments[$i];
	
	($i % 2) == 1 ? $className = 'tr-odd-body' : $className = 'tr-even-body';
	$comment['parent'] ? $className .= ' tr-reply-body' : null;
	$filter = new Filter();
	if (Filter::isFiltered('name', $comment['name']))
		$isNameFiltered = true;
	else
		$isNameFiltered = false;
	
	if (Filter::isFiltered('ip', $comment['ip']))
		$isIpFiltered = true;
	else
		$isIpFiltered = false;
	
	if (!isset($nameNumber[$comment['name']])) {
		$nameNumber[$comment['name']] = $i;
		$currentNumber = $i;
	} else {
		$currentNumber = $nameNumber[$comment['name']];
	}
	
	if (!isset($ipNumber[$comment['ip']])) {
		$ipNumber[$comment['ip']] = $i;
		$currentIP = $i;
	} else {
		$currentIP = $ipNumber[$comment['ip']];
	}
	
	if ($i == sizeof($comments) - 1) {
?>
												<tr class="<?php echo $className?> tr-last-body inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
													<td class="selection"><input type="checkbox" class="checkbox" name="entry" value="<?=$comment['id']?>" /></td>
													<td class="date"><?=Timestamp::formatDate($comment['written'])?></td>
													<td class="name">
<?
		if ($isNameFiltered) {
?>
														<a class="block-icon bullet" name="name<?=$currentNumber?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['name'])?>', '<?=$filter->id?>', 'name')" title="<?=_t('이 이름은 차단되었습니다. 클릭하시면 차단을 해제합니다.')?>"><span class="text"><?=_t('[차단됨]')?></span></a>
<?
		} else {
?>
														<a class="unblock-icon bullet" name="name<?=$currentNumber?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['name'])?>', '<?=$filter->id?>', 'name')" title="<?=_t('이 이름은 차단되지 않았습니다. 클릭하시면 차단합니다.')?>"><span class="text"><?=_t('[허용됨]')?></span></a>
<?
		}
?>
														<a href="#void" onclick="document.forms[0].name.value='<?=escapeJSInAttribute($comment['name'])?>'; document.forms[0].submit();" title="<?=_t('이 이름으로 등록된 댓글 목록을 보여줍니다.')?>"><?=htmlspecialchars($comment['name'])?></a>
													</td>
													<td class="content">
<?
		echo '<a class="entryURL" href="'.$blogURL.'/'.$comment['entry'].'#comment'.$comment['id'].'" title="'._t('댓글이 작성된 포스트로 직접 이동합니다.').'">';
		//echo "<strong>";
		echo $comment['title'];
		
		if ($comment['title'] != '' && $comment['parent'] != '') {
			echo '<span class="divider"> | </span>';
		}
		
		echo empty($comment['parent']) ? '' : '<span class="explain">' . $comment['parentName'] . _t('님의 댓글에 대한 댓글') . '</span>';
		//echo "</strong>";
		echo "</a>";
?>
														<?=((!empty($comment['title']) || !empty($comment['parent'])) ? '<br />' : '')?>
														<?=htmlspecialchars($comment['comment'])?>
									 				</td>
													<td class="ip">
<?
		if ($isIpFiltered) {
?>
														<a class="block-icon bullet" name="ip<?=$currentIP?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['ip'])?>', '<?=$filter->id?>', 'ip')" title="<?=_t('이 IP는 차단되었습니다. 클릭하시면 차단을 해제합니다.')?>"><span class="text"><?=_t('[차단됨]')?></span></a>
<?
		} else {
?>
														<a class="unblock-icon bullet" name="ip<?=$currentIP?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['ip'])?>', '<?=$filter->id?>', 'ip')" title="<?=_t('이 IP는 차단되지 않았습니다. 클릭하시면 차단합니다.')?>"><span class="text"><?=_t('[허용됨]')?></span></a>
<?
		}
?>
														<a href="#void" onclick="document.forms[0].ip.value='<?=escapeJSInAttribute($comment['ip'])?>'; document.forms[0].submit();" title="<?=_t('이 IP로 등록된 댓글 목록을 보여줍니다.')?>"><?=$comment['ip']?></a>
													</td>
													<td class="delete">
														<a class="delete-button button" href="#void" onclick="deleteComment(<?=$comment['id']?>)" title="<?=_t('이 댓글을 삭제합니다.')?>"><span class="text"><?=_t('삭제')?></span></a>
													</td>
						  						</tr>
<?
	} else {
?>
												<tr class="<?php echo $className?> tr-body inactive-class" onmouseover="rolloverClass(this, 'over')" onmouseout="rolloverClass(this, 'out')">
													<td class="selection"><input type="checkbox" class="checkbox" name="entry" value="<?=$comment['id']?>" /></td>
													<td class="date"><?=Timestamp::formatDate($comment['written'])?></td>
													<td class="name">
<?
		if ($isNameFiltered) {
?>
														<a class="block-icon bullet" name="name<?=$currentNumber?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['name'])?>', '<?=$filter->id?>', 'name')" title="<?=_t('이 이름은 차단되었습니다. 클릭하시면 차단을 해제합니다.')?>"><span class="text"><?=_t('[차단됨]')?></span></a>
<?
		} else {
?>
														<a class="unblock-icon bullet" name="name<?=$currentNumber?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['name'])?>', '<?=$filter->id?>', 'name')" title="<?=_t('이 이름은 차단되지 않았습니다. 클릭하시면 차단합니다.')?>"><span class="text"><?=_t('[허용됨]')?></span></a>
<?
		}
?>
														<a href="#void" onclick="document.forms[0].name.value='<?=escapeJSInAttribute($comment['name'])?>'; document.forms[0].submit();" title="<?=_t('이 이름으로 등록된 댓글 목록을 보여줍니다.')?>"><?=htmlspecialchars($comment['name'])?></a>
													</td>
													<td class="content">
<?
		echo '<a class="entryURL" href="'.$blogURL.'/'.$comment['entry'].'#comment'.$comment['id'].'" title="'._t('댓글이 작성된 포스트로 직접 이동합니다.').'">';
		//echo "<strong>";
		echo $comment['title'];
		
		if ($comment['title'] != '' && $comment['parent'] != '') {
			echo '<span class="divider"> | </span>';
		}
		
		echo empty($comment['parent']) ? '' : '<span class="explain">' . $comment['parentName'] . _t('님의 댓글에 대한 댓글') . '</span>';
		//echo "</strong>";
		echo "</a>";
?>
														<?=((!empty($comment['title']) || !empty($comment['parent'])) ? '<br />' : '')?>
														<?=htmlspecialchars($comment['comment'])?>
									 				</td>
													<td class="ip">
<?
		if ($isIpFiltered) {
?>
														<a class="block-icon bullet" name="ip<?=$currentIP?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['ip'])?>', '<?=$filter->id?>', 'ip')" title="<?=_t('이 IP는 차단되었습니다. 클릭하시면 차단을 해제합니다.')?>"><span class="text"><?=_t('[차단됨]')?></span></a>
<?
		} else {
?>
														<a class="unblock-icon bullet" name="ip<?=$currentIP?>block" href="#void" onclick="changeState(this,'<?=escapeJSInAttribute($comment['ip'])?>', '<?=$filter->id?>', 'ip')" title="<?=_t('이 IP는 차단되지 않았습니다. 클릭하시면 차단합니다.')?>"><span class="text"><?=_t('[허용됨]')?></span></a>
<?
		}
?>
														<a href="#void" onclick="document.forms[0].ip.value='<?=escapeJSInAttribute($comment['ip'])?>'; document.forms[0].submit();" title="<?=_t('이 IP로 등록된 댓글 목록을 보여줍니다.')?>"><?=$comment['ip']?></a>
													</td>
													<td class="delete">
														<a class="delete-button button" href="#void" onclick="deleteComment(<?=$comment['id']?>)" title="<?=_t('이 댓글을 삭제합니다.')?>"><span class="text"><?=_t('삭제')?></span></a>
													</td>
						  						</tr>
<?
	}
}
?>
											</tbody>
										</table>
	    								
	    								<hr class="hidden" />
	    								
										<div class="data-subbox">
											<div id="delete-section" class="section">
												<span class="label"><span class="text"><?=_t('선택한 댓글을')?></span></span>
												<a class="delete-button button" href="#void" onclick="deleteComments();"><span class="text"><?=_t('삭제')?></span></a>
												
												<div class="clear"></div>
											</div>
											
											<div id="page-section" class="section">
												<div id="page-navigation">
													<span id="total-count"><?=_t('총')?> <?=$paging['total']?><?=_t('건')?><span class="hidden">, </span></span>
													<span id="page-list">
<?
$paging['url'] = 'javascript: document.forms[0].page.value=';
$paging['prefix'] = '';
$paging['postfix'] = '; document.forms[0].submit()';
$pagingTemplate = '[##_paging_rep_##]';
$pagingItemTemplate = '<a [##_paging_rep_link_##]>[[##_paging_rep_link_num_##]]</a>';
print getPagingView($paging, $pagingTemplate, $pagingItemTemplate);
?>
													</span>
												</div>
												<div class="page-count">
													<?php echo getArrayValue(explode('%1', _t('한 페이지에 글 %1건 표시')), 0)?>
													<select name="perPage" onchange="document.forms[0].page.value=1; document.forms[0].submit()">					
<?php
for ($i = 10; $i <= 30; $i += 5) {
	if ($i == $perPage) {
?>
														<option value="<?php echo $i?>" selected="selected"><?php echo $i?></option>
<?php
	} else {
?>
														<option value="<?php echo $i?>"><?php echo $i?></option>
<?php
	}
}
?>
													</select>
													<?php echo getArrayValue(explode('%1', _t('한 페이지에 글 %1건 표시')), 1)?>
												</div>
												
												<div class="clear"></div>
											</div>
											
											<hr class="hidden" />
											
											<div id="search-section" class="section">
												<!--label for="search"><span class="text"><?=_t('이름')?>, <?=_t('홈페이지 이름')?>, <?=_t('내용')?></span></label><span class="divider"> | </span-->
												<input type="text" id="search" class="text-input" name="search" value="<?=htmlspecialchars($search)?>" onkeydown="if (event.keyCode == '13') { document.forms[0].withSearch.value = 'on'; document.forms[0].submit(); }" />
												<a class="search-button button" href="#void" onclick="document.forms[0].withSearch.value = 'on'; document.forms[0].submit();"><span class="text"><?=_t('검색')?></span></a>
												
												<div class="clear"></div>
											</div>
											
											<div class="clear"></div>
										</div>
									</div>
<?
require ROOT . '/lib/piece/owner/footer0.php';
?>