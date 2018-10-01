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

/**
 */
class ValidationDto
{

    /**
     * @var string
     */
    protected $prototypeName;

    /**
     * @var string
     */
    protected $formElementType;

    /**
     * @var string
     */
    protected $formElementIdentifier;

    /**
     * @var string
     */
    protected $propertyPath;

    /**
     * @var string
     */
    protected $propertyCollectionName;

    /**
     * @var string
     */
    protected $propertyCollectionElementIdentifier;

    /**
     * @param string $prototypeName
     * @param string $formElementType
     * @param string $formElementIdentifier
     * @param string $propertyPath
     * @param string $propertyCollectionName
     * @param string $propertyCollectionElementIdentifier
     */
    public function __construct(
        string $prototypeName = null,
        string $formElementType = null,
        string $formElementIdentifier = null,
        string $propertyPath = null,
        string $propertyCollectionName = null,
        string $propertyCollectionElementIdentifier = null
    ) {
        $this->prototypeName = $prototypeName;
        $this->formElementType = $formElementType;
        $this->formElementIdentifier = $formElementIdentifier;
        $this->propertyPath = $propertyPath;
        $this->propertyCollectionName = $propertyCollectionName;
        $this->propertyCollectionElementIdentifier = $propertyCollectionElementIdentifier;
    }

    /**
     * @return string
     */
    public function getPrototypeName(): string
    {
        return $this->prototypeName;
    }

    /**
     * @return string
     */
    public function getFormElementType(): string
    {
        return $this->formElementType;
    }

    /**
     * @return string
     */
    public function getFormElementIdentifier(): string
    {
        return $this->formElementIdentifier;
    }

    /**
     * @return string
     */
    public function getPropertyPath(): string
    {
        return $this->propertyPath;
    }

    /**
     * @return string
     */
    public function getPropertyCollectionName(): string
    {
        return $this->propertyCollectionName;
    }

    /**
     * @return string
     */
    public function getPropertyCollectionElementIdentifier(): string
    {
        return $this->propertyCollectionElementIdentifier;
    }

    /**
     * @return bool
     */
    public function hasPrototypeName(): bool
    {
        return !empty($this->prototypeName);
    }

    /**
     * @return bool
     */
    public function hasFormElementType(): bool
    {
        return !empty($this->formElementType);
    }

    /**
     * @return bool
     */
    public function hasFormElementIdentifier(): bool
    {
        return !empty($this->formElementIdentifier);
    }

    /**
     * @return bool
     */
    public function hasPropertyPath(): bool
    {
        return !empty($this->propertyPath);
    }

    /**
     * @return bool
     */
    public function hasPropertyCollectionName(): bool
    {
        return !empty($this->propertyCollectionName);
    }

    /**
     * @return bool
     */
    public function hasPropertyCollectionElementIdentifier(): bool
    {
        return !empty($this->propertyCollectionElementIdentifier);
    }

    /**
     * @param string $prototypeName
     * @return ValidationDto
     */
    public function withPrototypeName(string $prototypeName): ValidationDto
    {
        return GeneralUtility::makeInstance(self::class, $prototypeName, $this->formElementType, $this->formElementIdentifier, $this->propertyPath, $this->propertyCollectionName, $this->propertyCollectionElementIdentifier);
    }

    /**
     * @param string $formElementType
     * @return ValidationDto
     */
    public function withFormElementType(string $formElementType): ValidationDto
    {
        return GeneralUtility::makeInstance(self::class, $this->prototypeName, $formElementType, $this->formElementIdentifier, $this->propertyPath, $this->propertyCollectionName, $this->propertyCollectionElementIdentifier);
    }

    /**
     * @param string $formElementIdentifier
     * @return ValidationDto
     */
    public function withFormElementIdentifier(string $formElementIdentifier): ValidationDto
    {
        return GeneralUtility::makeInstance(self::class, $this->prototypeName, $this->formElementType, $formElementIdentifier, $this->propertyPath, $this->propertyCollectionName, $this->propertyCollectionElementIdentifier);
    }

    /**
     * @param string $propertyPath
     * @return ValidationDto
     */
    public function withPropertyPath(string $propertyPath): ValidationDto
    {
        return GeneralUtility::makeInstance(self::class, $this->prototypeName, $this->formElementType, $this->formElementIdentifier, $propertyPath, $this->propertyCollectionName, $this->propertyCollectionElementIdentifier);
    }

    /**
     * @param string $propertyCollectionName
     * @return ValidationDto
     */
    public function withPropertyCollectionName(string $propertyCollectionName): ValidationDto
    {
        return GeneralUtility::makeInstance(self::class, $this->prototypeName, $this->formElementType, $this->formElementIdentifier, $this->propertyPath, $propertyCollectionName, $this->propertyCollectionElementIdentifier);
    }

    /**
     * @param string $propertyCollectionElementIdentifier
     * @return ValidationDto
     */
    public function withPropertyCollectionElementIdentifier(string $propertyCollectionElementIdentifier): ValidationDto
    {
        return GeneralUtility::makeInstance(self::class, $this->prototypeName, $this->formElementType, $this->formElementIdentifier, $this->propertyPath, $this->propertyCollectionName, $propertyCollectionElementIdentifier);
    }
}
