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

namespace TYPO3\CMS\Frontend\ContentObject\Exception;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Error\AbstractExceptionHandler;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

/**
 * Exception handler class for content object rendering
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class ProductionExceptionHandler implements ExceptionHandlerInterface
{
    protected array $configuration = [];

    protected Context $context;
    protected Random $random;
    protected LoggerInterface $logger;

    public function __construct(Context $context, Random $random, LoggerInterface $logger)
    {
        $this->context = $context;
        $this->random = $random;
        $this->logger = $logger;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * Handles exceptions thrown during rendering of content objects
     * The handler can decide whether to re-throw the exception or
     * return a nice error message for production context.
     *
     * @param \Exception $exception
     * @param AbstractContentObject|null $contentObject
     * @param array $contentObjectConfiguration
     * @return string
     * @throws \Exception
     */
    public function handle(\Exception $exception, AbstractContentObject $contentObject = null, $contentObjectConfiguration = []): string
    {
        // ImmediateResponseException (and the derived PropagateResponseException) should work similar to
        // exit / die and must therefore not be handled by this ExceptionHandler.
        if ($exception instanceof ImmediateResponseException) {
            throw $exception;
        }

        if (!empty($this->configuration['ignoreCodes.'])
            && in_array($exception->getCode(), array_map('intval', $this->configuration['ignoreCodes.']), true)
        ) {
            throw $exception;
        }

        $errorMessage = $this->configuration['errorMessage'] ?? 'Oops, an error occurred! Code: {code}';
        $code = $this->context->getAspect('date')->getDateTime()->format('YmdHis') . $this->random->generateRandomHexString(8);

        // "%s" has to be replaced by {code} for b/w compatibility
        $errorMessage = str_replace('%s', '{code}', $errorMessage);

        // Log exception except HMAC validation exceptions caused by potentially forged requests
        if (!in_array($exception->getCode(), AbstractExceptionHandler::IGNORED_HMAC_EXCEPTION_CODES, true)) {
            $this->logger->alert($errorMessage, ['exception' => $exception, 'code' => $code]);
        }

        // Return error message by replacing {code} with the actual code, generated above
        return str_replace('{code}', $code, $errorMessage);
    }
}
