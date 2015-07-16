<?php
require '{originalRoot}typo3/sysext/core/Tests/Functional/Framework/Frontend/RequestBootstrap.php';
\TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\RequestBootstrap::setGlobalVariables({arguments});
\TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\RequestBootstrap::executeAndOutput();
?>
