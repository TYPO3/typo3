<?php
require dirname(dirname(dirname(dirname(__DIR__)))) . '/Classes/Core/CliBootstrap.php';
\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

require dirname(__DIR__) . '/Frontend/RequestBootstrap.php';
\TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\RequestBootstrap::setGlobalVariables();
\TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\RequestBootstrap::executeAndOutput();
?>