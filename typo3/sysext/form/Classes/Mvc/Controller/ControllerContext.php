<?php
namespace TYPO3\CMS\Form\Mvc\Controller;

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

use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Form\Domain\Model\Configuration;
use TYPO3\CMS\Form\Domain\Model\ValidationElement;

/**
 * Extension to the default Extbase Controller Context.
 */
class ControllerContext extends \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
{
    /**
     * Extends a given default ControllerContext.
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     * @return ControllerContext
     */
    public static function extend(\TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $source)
    {
        $controllerContext = \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(self::class);
        $propertyNames = ObjectAccess::getGettableProperties($source);
        foreach ($propertyNames as $propertyName => $propertyValue) {
            ObjectAccess::setProperty($controllerContext, $propertyName, $propertyValue);
        }
        return $controllerContext;
    }

    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var ValidationElement
     */
    protected $validationElement;

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param Configuration $configuration
     * @return ControllerContext
     */
    public function setConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * @return ValidationElement
     */
    public function getValidationElement()
    {
        return $this->validationElement;
    }

    /**
     * @param ValidationElement $validationElement
     */
    public function setValidationElement(ValidationElement $validationElement)
    {
        $this->validationElement = $validationElement;
    }
}
