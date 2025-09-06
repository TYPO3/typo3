<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Event\BeforeFormIsCreatedEvent;

#[AsEventListener(
    identifier: 'my-extension/before-form-is-created',
)]
final readonly class MyEventListener
{
    public function __invoke(BeforeFormIsCreatedEvent $event): void
    {
        $event->form['label'] = 'foo';
    }
}
