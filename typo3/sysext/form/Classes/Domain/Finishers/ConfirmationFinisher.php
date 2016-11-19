<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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

use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;

/**
 * A simple finisher that outputs a given text
 *
 * Options:
 *
 * - message: A hard-coded message to be rendered
 *
 * Usage:
 * //...
 * $confirmationFinisher = $this->objectManager->get(ConfirmationFinisher::class);
 * $confirmationFinisher->setOptions(
 *   [
 *     'message' => 'foo',
 *   ]
 * );
 * $formDefinition->addFinisher($confirmationFinisher);
 * // ...
 *
 * Scope: frontend
 */
class ConfirmationFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'message' => 'The form has been submitted.',
    ];

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @return void
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $message = $this->parseOption('message');
        $formRuntime->getResponse()->setContent($message);
    }
}
