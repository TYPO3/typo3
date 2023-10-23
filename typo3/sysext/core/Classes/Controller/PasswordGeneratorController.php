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

namespace TYPO3\CMS\Core\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * @internal Only to be used within TYPO3. Might change in the future.
 */
class PasswordGeneratorController
{
    public function __construct(
        private readonly Random $random,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {}

    public function generate(ServerRequestInterface $request): ResponseInterface
    {
        $passwordRules = $request->getParsedBody()['passwordRules'] ?? [];

        if (is_array($passwordRules)) {
            try {
                $password = $this->random->generateRandomPassword($passwordRules);
                return $this->createResponse([
                    'success' => true,
                    'password' => $password,
                ]);
            } catch (InvalidPasswordRulesException $e) {
            }
        }

        return $this->createResponse([
            'success' => false,
            'message' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.misconfiguredPasswordRules'),
        ]);
    }

    protected function createResponse(array $data): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream((string)json_encode($data)));
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
