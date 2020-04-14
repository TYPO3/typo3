<?php

defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
    ->registerImplementation(
        \ExtbaseTeam\A\Domain\Model\A::class,
        \ExtbaseTeam\B\Domain\Model\B::class
    );
