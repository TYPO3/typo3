<?php

declare(strict_types=1);

use ExtbaseTeam\A\Domain\Model\A;
use ExtbaseTeam\B\Domain\Model\B;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Container\Container;

defined('TYPO3') or die();

GeneralUtility::makeInstance(Container::class)
    ->registerImplementation(
        A::class,
        B::class
    );
