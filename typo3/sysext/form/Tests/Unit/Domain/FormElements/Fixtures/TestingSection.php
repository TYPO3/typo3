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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\FormElements\Fixtures;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\AbstractSection;

/**
 * Testing subclass of the abstract class.
 */
class TestingSection extends AbstractSection
{
    public function __construct(private readonly FormDefinition $rootForm)
    {
        parent::__construct('testing_section', '');
    }

    public function getRootForm(): FormDefinition
    {
        return $this->rootForm;
    }
}
