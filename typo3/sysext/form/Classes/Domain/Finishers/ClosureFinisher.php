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

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;

/**
 * A simple finisher that invokes a closure when executed
 *
 * Usage:
 * //...
 * $closureFinisher = GeneralUtility::makeInstance(ClosureFinisher::class);
 * $closureFinisher->setOption('closure', function($finisherContext) {
 *   $formRuntime = $finisherContext->getFormRuntime();
 *   // ...
 * });
 * $formDefinition->addFinisher($closureFinisher);
 * // ...
 *
 * Scope: frontend
 */
class ClosureFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'closure' => null,
    ];

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @throws FinisherException
     */
    protected function executeInternal()
    {
        /** @var \Closure|null $closure */
        $closure = $this->parseOption('closure');
        if ($closure === null) {
            return;
        }
        if (!$closure instanceof \Closure) {
            throw new FinisherException(sprintf('The option "closure" must be of type Closure, "%s" given.', gettype($closure)), 1332155239);
        }
        $closure($this->finisherContext);
    }
}
