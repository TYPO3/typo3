<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class)
    ->registerImplementation(
        \ExtbaseTeam\A\Domain\Model\A::class,
        \ExtbaseTeam\B\Domain\Model\B::class
    );
