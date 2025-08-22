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

namespace TYPO3\CMS\RteCKEditor\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\AfterRichtextConfigurationPreparedEvent;
use TYPO3\CMS\RteCKEditor\Configuration\CKEditor5Migrator;

final readonly class AfterRichtextConfigurationPreparedEventListener
{
    #[AsEventListener('typo3/cms-rte-ckeditor/migrate-ckeditor4-configuration')]
    public function __invoke(AfterRichtextConfigurationPreparedEvent $event)
    {
        $event->setConfiguration((new CKEditor5Migrator($event->getConfiguration()))->get());
    }
}
