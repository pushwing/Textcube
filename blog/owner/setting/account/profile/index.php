<?php
/// Copyright (c) 2004-2007, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
define('ROOT', '../../../../..');
$IV = array(
	'POST' => array(
		'userid'=>array('id')
	)
);
require ROOT . '/lib/includeForBlogOwner.php';
requireStrictRoute();
if (changeSetting($_SESSION['admin'], $_POST['email'], $_POST['nickname'])) {
	respondResultPage(0);
}
respondResultPage( - 1);
?>