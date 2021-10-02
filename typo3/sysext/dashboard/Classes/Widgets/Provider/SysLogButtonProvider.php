<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Dashboard\Widgets\Provider;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Dashboard\Widgets\ButtonProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\ElementAttributesInterface;

/**
 * Provide link for sys log button.
 * Check whether belog is enabled and add link to module.
 * No link is returned if not enabled.
 */
class SysLogButtonProvider implements ButtonProviderInterface, ElementAttributesInterface
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $target;

    public function __construct(string $title, string $target = '')
    {
        $this->title = $title;
        $this->target = $target;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        return '';
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getElementAttributes(): array
    {
        if (!ExtensionManagementUtility::isLoaded('belog')) {
            return [];
        }
        return [
            'data-dispatch-action' => 'TYPO3.ModuleMenu.showModule',
            'data-dispatch-args-list' => 'system_BelogLog,&'
                . http_build_query(['tx_belog_system_beloglog' => ['constraint' => ['level' => 'notice']]]),
        ];
    }
}
