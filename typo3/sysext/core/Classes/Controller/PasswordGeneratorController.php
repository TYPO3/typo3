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
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Exception\InvalidPasswordRulesException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\PasswordPolicy\Generator\PasswordGeneratorInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal Only to be used within TYPO3. Might change in the future.
 */
#[Autoconfigure(public: true)]
readonly class PasswordGeneratorController
{
    public function __construct(
        private Random $random,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private LoggerInterface $logger,
    ) {}

    public function generate(ServerRequestInterface $request): ResponseInterface
    {
        $passwordRules = $request->getParsedBody()['passwordRules'] ?? [];
        $passwordPolicy = $request->getParsedBody()['passwordPolicy'] ?? null;

        try {
            if (is_string($passwordPolicy) && $passwordPolicy !== 'null') {
                $generator = $GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies'][$passwordPolicy]['generator'] ?? null;
                if (empty($generator['className'])
                    || !is_string($generator['className'])
                    || !class_exists($generator['className'])
                    || !isset($generator['options'])
                    || !is_array($generator['options'])
                ) {
                    throw new \LogicException(
                        'The TYPO3_CONF_VARS.SYS.passwordPolicies.' . $passwordPolicy . '.generator configuration is misconfigured.'
                        . ' Please ensure that the sub key \'className\' is set, and the sub key \'options\' is an array of required option values.',
                        1770142937
                    );
                }

                $passwordGeneratorClassName = $generator['className'];
                $passwordGeneratorOptions = $generator['options'];

                $passwordGenerator = GeneralUtility::makeInstance($passwordGeneratorClassName);
                if (!$passwordGenerator instanceof PasswordGeneratorInterface) {
                    throw new \LogicException('Class ' . $passwordGeneratorClassName . ' does not implement PasswordGeneratorInterface', 1770142966);
                }

                $password = $passwordGenerator->generate($passwordGeneratorOptions);
                return $this->createResponse([
                    'success' => true,
                    'password' => $password,
                ]);
            }

            if (is_array($passwordRules)) {
                trigger_error(
                    'Using the Random password generator directly has been deprecated in TYPO3 v14.2 and will be removed in v15.0.'
                    . 'Use a passwort generator that implements the PasswordGeneratorInterface instead and adjust your TCA configuration.',
                    E_USER_DEPRECATED
                );
                $password = $this->random->generateRandomPassword($passwordRules);
                return $this->createResponse([
                    'success' => true,
                    'password' => $password,
                ]);
            }
        } catch (\LogicException|InvalidPasswordRulesException $exception) {
            $this->logger->error('Password generation failed', ['exception' => $exception]);
        }

        return $this->createResponse([
            'success' => false,
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
