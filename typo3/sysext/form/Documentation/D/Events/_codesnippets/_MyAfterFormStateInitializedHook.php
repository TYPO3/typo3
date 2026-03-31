<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Hooks;

use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime\Lifecycle\AfterFormStateInitializedInterface;

final class MyAfterFormStateInitializedHook implements AfterFormStateInitializedInterface
{
    public function afterFormStateInitialized(FormRuntime $formRuntime): void
    {
        // Access $formRuntime->getFormState() here
    }
}
