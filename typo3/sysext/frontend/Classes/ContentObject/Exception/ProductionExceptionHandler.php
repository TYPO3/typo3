<?php
namespace TYPO3\CMS\Frontend\ContentObject\Exception;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;

/**
 * Exception handler class for content object rendering
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class ProductionExceptionHandler implements ExceptionHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $configuration = [];

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
        if (!empty($this->configuration['ignoreCodes.'])) {
            if (in_array($exception->getCode(), array_map('intval', $this->configuration['ignoreCodes.']), true)) {
                throw $exception;
            }
        }
        $errorMessage = $this->configuration['errorMessage'] ?? 'Oops, an error occurred! Code: %s';
        $code = date('YmdHis', $_SERVER['REQUEST_TIME']) . GeneralUtility::makeInstance(Random::class)->generateRandomHexString(8);

        $this->logException($exception, $errorMessage, $code);

        return sprintf($errorMessage, $code);
    }

    /**
     * @param \Exception $exception
     * @param string $errorMessage
     * @param string $code
     */
    protected function logException(\Exception $exception, $errorMessage, $code)
    {
        $this->logger->alert(sprintf($errorMessage, $code), ['exception' => $exception]);
    }
}
