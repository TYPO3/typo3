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

namespace TYPO3\CMS\Form\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Event\BeforeRenderableIsValidatedEvent;
use TYPO3\CMS\Form\Service\TranslationService;

readonly class ValidateAdvancedPasswordRenderable
{
    public function __construct(
        private TranslationService $translationService,
    ) {}

    #[AsEventListener('form-framework/validate-advanced-password')]
    public function __invoke(BeforeRenderableIsValidatedEvent $event): void
    {
        $renderable = $event->renderable;
        if ($renderable->getType() !== 'AdvancedPassword') {
            return;
        }
        if (!$renderable instanceof AbstractRenderable) {
            return;
        }

        $elementValue = $event->value;
        if ($elementValue['password'] !== $elementValue['confirmation']) {
            $processingRule = $renderable->getRootForm()->getProcessingRule($renderable->getIdentifier());
            $processingRule->getProcessingMessages()->addError(
                new Error(
                    $this->translationService->translate('validation.error.1556283177', null, 'EXT:form/Resources/Private/Language/locallang.xlf'),
                    1556283177
                )
            );
        }
        $event->value = $elementValue['password'];
    }

}
