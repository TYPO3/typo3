<?php

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

/**
 * Exception handler class for content object rendering
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class ProductionExceptionHandler implements ExceptionHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $configuration = [];

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration = [])
    {
        $this->configuration = $configuration;
    }

    /**
     * Handles exceptions thrown during rendering of content objects
     * The handler can decide whether to re-throw the exception or
     * return a nice error message for production context.
     *
     * @param \Exception $exception
     * @param AbstractContentObject $contentObject
     * @param array $contentObjectConfiguration
     * @return string
     * @throws \Exception
     */
    public function handle(\Exception $exception, AbstractContentObject $contentObject = null, $contentObjectConfiguration = [])
    {
        // ImmediateResponseException (and the derived PropagateResponseException) should work similar to
        // exit / die and must therefore not be handled by this ExceptionHandler.
        if ($exception instanceof ImmediateResponseException) {
            throw $exception;
        }

        if (!empty($this->configuration['ignoreCodes.'])) {
            if (in_array($exception->getCode(), array_map('intval', $this->configuration['ignoreCodes.']), true)) {
                throw $exception;
            }
        }
        $errorMessage = $this->configuration['errorMessage'] ?? 'Oops, an error occurred! Code: {code}';
        $code = date('YmdHis', $_SERVER['REQUEST_TIME']) . GeneralUtility::makeInstance(Random::class)->generateRandomHexString(8);

        $this->logException($exception, $errorMessage, $code);

        // "%s" is for b/w compatibility
        return str_replace(['%s', '{code}'], $code, $errorMessage);
    }

    /**
     * @param \Exception $exception
     * @param string $errorMessage
     * @param string $code
     */
    protected function logException(\Exception $exception, $errorMessage, $code)
    {
        $this->logger->alert($errorMessage, ['exception' => $exception, 'code' => $code]);
    }
}
