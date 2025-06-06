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

namespace TYPO3\CMS\IndexedSearch\Hook;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
#[Autoconfigure(public: true)]
readonly class AvailableTcaTables
{
    public function __construct(
        private IconFactory $iconFactory,
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * itemsProcFunc for adding all available TCA tables
     */
    public function populateTables(array &$fieldDefinition): void
    {
        foreach ($this->tcaSchemaFactory->all() as $name => $schema) {
            // Hide "admin only" tables
            if ($schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
                continue;
            }
            $label = $schema->getRawConfiguration()['title'] ?? '';
            $icon = $this->iconFactory->mapRecordTypeToIconIdentifier($name, []);
            $fieldDefinition['items'][] = ['label' => $label, 'value' => $name, 'icon' => $icon];
        }
    }
}
