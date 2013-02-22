<?php

$redirectLevel = intval($_GET['redirectLevel']);
$redirectNoLocation = intval($_GET['redirectNoLocation']);

if ($redirectLevel < 1) {
	$url = $redirectNoLocation ? '' : '/typo3/sysext/core/Tests/Unit/Utility/Fixtures/redirect.php?redirectLevel=' . ($redirectLevel + 1);
	header('Location: ' . $url, FALSE, 302);
} else {
	echo 'Success.';
}

?>