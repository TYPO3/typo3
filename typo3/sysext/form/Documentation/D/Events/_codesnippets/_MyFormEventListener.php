<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Event\BeforeFormIsSavedEvent;

#[AsEventListener(
    identifier: 'my-extension/before-form-is-saved',
)]
final readonly class MyFormEventListener
{
    public function __invoke(BeforeFormIsSavedEvent $event): void
    {
        // Enrich the form definition before it is persisted
        $event->form['renderingOptions']['myCustomOption'] = 'value';
    }
}
