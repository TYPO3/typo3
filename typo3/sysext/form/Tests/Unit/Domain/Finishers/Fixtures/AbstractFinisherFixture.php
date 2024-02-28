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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\Finishers\Fixtures;

use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

final class AbstractFinisherFixture extends AbstractFinisher
{
    public $options = [];
    public $defaultOptions = [];
    public $finisherContext;

    protected function executeInternal()
    {
        return null;
    }

    public function parseOption(string $optionName)
    {
        return parent::parseOption($optionName);
    }

    protected function translateFinisherOption(
        $subject,
        FormRuntime $formRuntime,
        string $optionName,
        $optionValue,
        array $translationOptions
    ) {
        return $subject;
    }

    public function substituteRuntimeReferences($needle, FormRuntime $formRuntime)
    {
        return parent::substituteRuntimeReferences($needle, $formRuntime);
    }
}
