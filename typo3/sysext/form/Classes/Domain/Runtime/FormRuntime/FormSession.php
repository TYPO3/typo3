<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Error\Http\BadRequestException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Security\Cryptography\HashService;
use TYPO3\CMS\Extbase\Security\Exception\InvalidArgumentForHashGenerationException;
use TYPO3\CMS\Extbase\Security\Exception\InvalidHashException;

/**
 * @internal
 */
class FormSession
{
    protected $identifier;

    /**
     * Factory to create the form session from the current state
     *
     * @param string|null $authenticatedIdentifier
     * @throws BadRequestException
     */
    public function __construct(string $authenticatedIdentifier = null)
    {
        if ($authenticatedIdentifier === null) {
            $this->identifier = $this->generateIdentifier();
        } else {
            $this->identifier = $this->validateIdentifier($authenticatedIdentifier);
        }
    }

    /**
     * @return string
     * @internal
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Consumed by TYPO3\CMS\Form\ViewHelpers\FormViewHelper
     *
     * @return string
     * @internal
     */
    public function getAuthenticatedIdentifier(): string
    {
        return GeneralUtility::makeInstance(HashService::class)
            // restrict string expansion by adding some char ('|')
            ->appendHmac($this->identifier . '|');
    }

    /**
     * @return string
     */
    protected function generateIdentifier(): string
    {
        return GeneralUtility::makeInstance(Random::class)->generateRandomHexString(40);
    }

    /**
     * @param string $authenticatedIdentifier
     * @return string
     * @throws BadRequestException
     */
    protected function validateIdentifier(string $authenticatedIdentifier): string
    {
        try {
            $identifier = GeneralUtility::makeInstance(HashService::class)
                ->validateAndStripHmac($authenticatedIdentifier);
            return rtrim($identifier, '|');
        } catch (InvalidHashException | InvalidArgumentForHashGenerationException $e) {
            throw new BadRequestException('The HMAC of the form session could not be validated.', 1613300274);
        }
    }
}
