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

use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * A Form Factory is responsible for building a {@link \TYPO3\CMS\Form\Domain\Model\FormDefinition}.
 * **Instead of implementing this interface, subclassing {@link AbstractFormFactory} is more appropriate
 * in most cases**.
 *
 * A Form Factory can be called anytime a FormDefinition should be built; in most cases
 * it is done through an invocation of a Form Rendering ViewHelper.
 *
 * Scope: frontend / backend
 */
interface FormFactoryInterface
{

    /**
     * Build a form definition, depending on some configuration.
     *
     * The configuration array is factory-specific; for example a YAML or JSON factory
     * could retrieve the path to the YAML / JSON file via the configuration array.
     *
     * @param array $configuration factory-specific configuration array
     * @param string $prototypeName The name of the "PrototypeName" to use; it is factory-specific to implement this.
     * @return FormDefinition a newly built form definition
     */
    public function build(array $configuration, string $prototypeName = null): FormDefinition;
}
