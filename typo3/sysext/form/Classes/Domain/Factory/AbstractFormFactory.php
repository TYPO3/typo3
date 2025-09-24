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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Factory;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Event\AfterFormIsBuiltEvent;

/**
 * Base class for custom *Form Factories*. A Form Factory is responsible for building
 * a {@link TYPO3\CMS\Form\Domain\Model\FormDefinition}.
 *
 * Example
 * =======
 *
 * Generally, you should use this class as follows:
 *
 * <pre>
 * class MyFooBarFactory extends AbstractFormFactory {
 *   public function build(array $configuration, $prototypeName) {
 *     $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
 *     $prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);
 *     $formDefinition = GeneralUtility::makeInstance(FormDefinition::class, 'nameOfMyForm', $prototypeConfiguration);
 *
 *     // now, you should call methods on $formDefinition to add pages and form elements
 *
 *     return $formDefinition;
 *   }
 * }
 * </pre>
 *
 * Scope: frontend / backend
 * **This class is meant to be sub classed by developers.**
 */
abstract class AbstractFormFactory implements FormFactoryInterface
{
    protected ?EventDispatcherInterface $eventDispatcher = null;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function triggerFormBuildingFinished(FormDefinition $form): FormDefinition
    {
        return $this->eventDispatcher->dispatch(new AfterFormIsBuiltEvent($form))->form;
    }
}
