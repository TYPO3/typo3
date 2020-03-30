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

namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Controller;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Controller\MvcPropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\Model;

/**
 * Fixture controller
 */
class TestController extends ActionController
{
    public function initializeFooAction()
    {
        /** @var MvcPropertyMappingConfiguration $propertyMappingConfiguration */
        $propertyMappingConfiguration = $this->arguments['fooParam']->getPropertyMappingConfiguration();
        $propertyMappingConfiguration->allowAllProperties();
        $propertyMappingConfiguration->setTypeConverterOption(
            PersistentObjectConverter::class,
            PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED,
            true
        );
    }

    /**
     * @param \TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Domain\Model\Model $fooParam
     * @return string
     */
    public function fooAction(Model $fooParam)
    {
        // return string so we don't need to mock a view
        return '';
    }

    /**
     * @param string $barParam
     * @Extbase\Validate("TYPO3.CMS.Extbase.Tests.Functional.Mvc.Controller.Fixture:CustomValidator", param="barParam")
     * @return string
     */
    public function barAction(string $barParam)
    {
        // return string so we don't need to mock a view
        return '';
    }

    /**
     * @param array $bazParam
     * @Extbase\Validate("NotEmpty", param="bazParam")
     * @return string
     */
    public function bazAction(array $bazParam)
    {
        // return string so we don't need to mock a view
        return '';
    }

    public function quxAction()
    {
        return '';
    }
}
