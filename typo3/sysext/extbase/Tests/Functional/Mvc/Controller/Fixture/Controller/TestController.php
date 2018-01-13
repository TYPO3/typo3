<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Extbase\Tests\Functional\Mvc\Controller\Fixture\Controller;

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
        /** @var MvcPropertyMappingConfiguration $propertMappingConfiguration */
        $propertMappingConfiguration = $this->arguments['fooParam']->getPropertyMappingConfiguration();
        $propertMappingConfiguration->allowAllProperties();
        $propertMappingConfiguration->setTypeConverterOption(
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
     * @validate $barParam TYPO3.CMS.Extbase.Tests.Functional.Mvc.Controller.Fixture:CustomValidator
     * @return string
     */
    public function barAction(string $barParam)
    {
        // return string so we don't need to mock a view
        return '';
    }

    /**
     * @param array $bazParam
     * @validate $bazParam NotEmpty
     * @return string
     */
    public function bazAction(array $bazParam)
    {
        // return string so we don't need to mock a view
        return '';
    }
}
