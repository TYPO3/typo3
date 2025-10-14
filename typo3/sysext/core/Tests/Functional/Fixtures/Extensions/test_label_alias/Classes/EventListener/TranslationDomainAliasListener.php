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

namespace TYPO3Tests\TestLabelAlias\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Localization\Event\BeforeLabelResourceResolvedEvent;

/**
 * Event listener for providing backward compatibility aliases for renamed label files.
 *
 * Example: wizard.xlf was renamed to wizards.xlf
 */
final class TranslationDomainAliasListener
{
    #[AsEventListener]
    public function __invoke(BeforeLabelResourceResolvedEvent $event): void
    {
        // Only handle our extension
        if ($event->packageKey !== 'test_label_alias') {
            return;
        }

        $event->domains['test_label_alias.wizard'] = $event->domains['test_label_alias.wizards'];
        $event->domains[$event->packageKey . '.wizard'] = $event->domains['test_label_alias.wizards'];
    }
}
