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

/**
 * Finisher that can be attached to a form in order to be invoked
 * as soon as the complete form is submitted
 *
 * Scope: frontend
 */
interface FinisherInterface
{
    /**
     * Executes the finisher
     *
     * @param FinisherContext $finisherContext The Finisher context that contains the current Form Runtime and Response
     * @return string|null
     */
    public function execute(FinisherContext $finisherContext);

    /**
     * @param string $finisherIdentifier
     * @todo enable this method in TYPO3 v12 with a Breaking.rst as the interface changes and drop the if condition (not body) in AbstractFinisher.
     */
    //public function setFinisherIdentifier(string $finisherIdentifier): void;

    /**
     * @param array $options configuration options in the format ['option1' => 'value1', 'option2' => 'value2', ...]
     */
    public function setOptions(array $options);

    /**
     * Sets a single finisher option (@see setOptions())
     *
     * @param string $optionName name of the option to be set
     * @param mixed $optionValue value of the option
     */
    public function setOption(string $optionName, $optionValue);

    /**
     * Returns whether this finisher is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool;
}
