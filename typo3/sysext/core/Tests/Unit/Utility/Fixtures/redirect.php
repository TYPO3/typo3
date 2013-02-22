<?php

$redirectLevel = isset($_GET['redirectLevel']) ? intval($_GET['redirectLevel']) : 0;
$redirectNoLocation = isset($_GET['redirectNoLocation']) ? intval($_GET['redirectNoLocation']) : 0;

if ($redirectLevel < 1) {
	$url = $redirectNoLocation ? '' : '/typo3/sysext/core/Tests/Unit/Utility/Fixtures/redirect.php?redirectLevel=' . ($redirectLevel + 1);
	header('Location: ' . $url, FALSE, 302);
} else {
	echo 'Success.';
}

?>
