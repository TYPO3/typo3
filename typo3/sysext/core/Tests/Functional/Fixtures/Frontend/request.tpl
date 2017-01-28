<?php
require '{originalRoot}components/testing_framework/Classes/Core/Functional/Framework/Frontend/RequestBootstrap.php';
\TYPO3\Components\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::setGlobalVariables({arguments});
\TYPO3\Components\TestingFramework\Core\Functional\Framework\Frontend\RequestBootstrap::executeAndOutput();
?>
