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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;

class EventListenersProvider extends AbstractProvider
{
    public function __construct(protected readonly ListenerProvider $listenerProvider) {}

    public function getConfiguration(): array
    {
        $listenerDefinitions = $this->listenerProvider->getAllListenerDefinitions();
        array_walk($listenerDefinitions, static function (array &$listenerDefinition): void {
            array_walk($listenerDefinition, static function (array &$listener): void {
                $listener['method'] ??= '__invoke';
            });
        });
        return $listenerDefinitions;
    }
}
