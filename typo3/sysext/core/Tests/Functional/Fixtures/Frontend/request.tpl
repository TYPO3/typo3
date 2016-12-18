<?php
require '{originalRoot}components/testing_framework/core/Functional/Framework/Frontend/RequestBootstrap.php';
\TYPO3\CMS\Components\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::setGlobalVariables({arguments});
\TYPO3\CMS\Components\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::executeAndOutput();
?>
