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

namespace TYPO3\CMS\Workspaces\EventListener;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use TYPO3\CMS\Core\Schema\Field\FileFieldType;
use TYPO3\CMS\Core\Schema\Field\FlexFormFieldType;
use TYPO3\CMS\Core\Schema\Field\InlineFieldType;
use TYPO3\CMS\Core\Schema\RelationshipType;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent;

/**
 * Core listener that marks inline/file/flex references as structural
 * workspace dependencies.
 *
 * @internal
 */
#[AutoconfigureTag('event.listener')]
final readonly class WorkspaceDependencyReferenceListener
{
    public function __construct(
        private TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    public function __invoke(IsReferenceConsideredForDependencyEvent $event): void
    {
        $schema = $this->tcaSchemaFactory->get($event->getTableName());
        if (!$schema->hasField($event->getFieldName())) {
            return;
        }
        $field = $schema->getField($event->getFieldName());
        if ($field instanceof FlexFormFieldType) {
            $event->setDependency(true);
            return;
        }
        if (
            ($field instanceof InlineFieldType || $field instanceof FileFieldType)
            && in_array($field->getRelationshipType(), [RelationshipType::OneToMany, RelationshipType::List], true)
        ) {
            $event->setDependency(true);
        }
    }
}
