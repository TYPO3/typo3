<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FormDefinition\Validators;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionValidationService;

/**
 * @internal
 */
abstract class AbstractValidator implements ValidatorInterface
{

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    /**
     * @var array
     */
    protected $currentElement;

    /**
     * @var string
     */
    protected $sessionToken;

    /**
     * @var ValidationDto
     */
    protected $validationDto;

    /**
     * @param array $currentElement
     * @param string $sessionToken
     * @param ValidationDto $validationDto
     */
    public function __construct(array $currentElement, string $sessionToken, ValidationDto $validationDto)
    {
        $this->currentElement = $currentElement;
        $this->sessionToken = $sessionToken;
        $this->validationDto = $validationDto;
    }

    /**
     * Builds the path in which the hmac value is expected based on the property path.
     *
     * @param string $propertyPath
     * @return string
     */
    protected function buildHmacDataPath(string $propertyPath): string
    {
        $pathParts = explode('.', $propertyPath);
        $lastPathSegment = array_pop($pathParts);
        $pathParts[] = '_orig_' . $lastPathSegment;

        return implode('.', $pathParts);
    }

    /**
     * @return FormDefinitionValidationService
     */
    protected function getFormDefinitionValidationService(): FormDefinitionValidationService
    {
        return GeneralUtility::makeInstance(FormDefinitionValidationService::class);
    }

    /**
     * @return ConfigurationService
     */
    protected function getConfigurationService(): ConfigurationService
    {
        if (!($this->configurationService instanceof ConfigurationService)) {
            $this->configurationService = $this->getObjectManager()->get(ConfigurationService::class);
        }
        return $this->configurationService;
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager(): ObjectManager
    {
        return GeneralUtility::makeInstance(ObjectManager::class);
    }
}
