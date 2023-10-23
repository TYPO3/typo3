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

namespace TYPO3\CMS\Core\PasswordPolicy\Validator;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\PasswordPolicy\Validator\Dto\ContextData;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Abstract password validator class, which all TYPO3 password validators must extend.
 */
abstract class AbstractPasswordValidator
{
    private array $requirements = [];
    private array $errorMessages = [];

    public function __construct(protected array $options = [])
    {
        $this->initializeRequirements();
    }

    /**
     * Function must be overwritten by extending classes in order to add requirements.
     * Use `$this->addRequirement(string $identifier, string $message);` to add a requirement.
     */
    public function initializeRequirements(): void {}

    /**
     * Validates the given password. Function must be overwritten by extending classes.
     * If validation is considered as failed, use `addErrorMessage(string $identifier, string $errorMessage)`
     * to add an error message and return `false`.
     *
     * @param string $password The password to validate
     * @param ContextData|null $contextData ContextData for usage in additional checks (e.g. password must not contain users firstname).
     */
    public function validate(string $password, ?ContextData $contextData = null): bool
    {
        return false;
    }

    /**
     * Returns all requirements
     */
    final public function getRequirements(): array
    {
        return array_map('htmlspecialchars', $this->requirements);
    }

    /**
     * Adds a requirement with the given identifier and message.
     *
     * @param string $identifier Unique identifier for requirement
     * @param string $message Message describing the requirement (e.g. "At least one digit")
     */
    final protected function addRequirement(string $identifier, string $message): void
    {
        $classIdentifier = $this->getClassId();
        $this->requirements[$classIdentifier . $identifier] = $message;
    }

    /**
     * Returns all error messages
     */
    final public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Adds an validation error message with the given identifier and message.
     *
     * @param string $identifier Unique identifier for error message
     * @param string $errorMessage Message describing the error (e.g. "The password must at least contain one digit")
     */
    final protected function addErrorMessage(string $identifier, string $errorMessage): void
    {
        $classIdentifier = $this->getClassId();
        $this->errorMessages[$classIdentifier . $identifier] = $errorMessage;
    }

    private function getClassId(): string
    {
        $classParts = explode('\\', static::class);
        return lcfirst(end($classParts)) . '.';
    }

    protected function getLanguageService(): LanguageService
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isFrontend()) {
            $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
            return $languageServiceFactory->createFromSiteLanguage($request->getAttribute('language')
                ?? $request->getAttribute('site')->getDefaultLanguage());
        }

        if (($GLOBALS['LANG'] ?? null) instanceof LanguageService) {
            return $GLOBALS['LANG'];
        }

        $languageServiceFactory = GeneralUtility::makeInstance(LanguageServiceFactory::class);
        return $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER'] ?? null);
    }
}
