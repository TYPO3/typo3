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

namespace TYPO3\CMS\Core\Log;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\Exception\InvalidLogProcessorConfigurationException;
use TYPO3\CMS\Core\Log\Exception\InvalidLogWriterConfigurationException;
use TYPO3\CMS\Core\Log\Processor\ProcessorInterface;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Global LogManager that keeps track of global logging information.
 *
 * Inspired by java.util.logging
 */
class LogManager implements SingletonInterface, LogManagerInterface
{
    /**
     * @var string
     */
    const CONFIGURATION_TYPE_WRITER = 'writer';

    /**
     * @var string
     */
    const CONFIGURATION_TYPE_PROCESSOR = 'processor';

    /**
     * Loggers to retrieve them for repeated use.
     */
    protected array $loggers = [];

    /**
     * Default / global / root logger.
     */
    protected Logger $rootLogger;

    /**
     * Unique ID of the request
     */
    protected string $requestId = '';

    /**
     * Constructor
     *
     * @param string $requestId Unique ID of the request
     */
    public function __construct(string $requestId = '')
    {
        $this->requestId = $requestId;
        $this->rootLogger = GeneralUtility::makeInstance(Logger::class, '', $requestId);
        $this->loggers[''] = $this->rootLogger;
    }

    /**
     * For use in unit test context only. Resets the internal logger registry.
     */
    public function reset()
    {
        $this->loggers = [];
    }

    /**
     * Gets a logger instance for the given name.
     *
     * \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger('main.sub.subsub');
     *
     * $name can also be submitted as a underscore-separated string, which will
     * be converted to dots. This is useful to call this method with __CLASS__
     * as parameter.
     *
     * @param string $name Logger name, empty to get the global "root" logger.
     * @return Logger Logger with name $name
     */
    public function getLogger(string $name = ''): LoggerInterface
    {
        // Transform namespaces and underscore class names to the dot-name style
        $separators = ['_', '\\'];
        $name = str_replace($separators, '.', $name);

        return $this->loggers[$name] ??= $this->makeLogger($name, $this->requestId);
    }

    /**
     * Instantiates a new logger object, with the appropriate attached writers and processors.
     */
    protected function makeLogger(string $name, string $requestId): Logger
    {
        $logger = GeneralUtility::makeInstance(Logger::class, $name, $requestId);
        $this->setWritersForLogger($logger);
        $this->setProcessorsForLogger($logger);
        return $logger;
    }

    /**
     * For use in unit test context only.
     *
     * @param string $name
     */
    public function registerLogger($name)
    {
        $this->loggers[$name] = null;
    }

    /**
     * For use in unit test context only.
     *
     * @return array
     */
    public function getLoggerNames()
    {
        return array_keys($this->loggers);
    }

    /**
     * Appends the writers to the given logger as configured.
     *
     * @param \TYPO3\CMS\Core\Log\Logger $logger Logger to configure
     */
    protected function setWritersForLogger(Logger $logger)
    {
        $configuration = $this->getConfigurationForLogger(self::CONFIGURATION_TYPE_WRITER, $logger->getName());
        foreach ($configuration as $severityLevel => $writer) {
            $writer = array_filter($writer, static fn (array $options) => !($options['disabled'] ?? false));
            foreach ($writer as $logWriterClassName => $logWriterOptions) {
                try {
                    unset($logWriterOptions['disabled']);
                    /** @var WriterInterface $logWriter */
                    $logWriter = GeneralUtility::makeInstance($logWriterClassName, $logWriterOptions);
                    $logger->addWriter($severityLevel, $logWriter);
                } catch (InvalidArgumentException|InvalidLogWriterConfigurationException $e) {
                    $logger->warning('Instantiation of LogWriter "{class_name}" failed for logger {name}', [
                        'class_name' => $logWriterClassName,
                        'name' => $logger->getName(),
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    /**
     * Appends the processors to the given logger as configured.
     *
     * @param \TYPO3\CMS\Core\Log\Logger $logger Logger to configure
     */
    protected function setProcessorsForLogger(Logger $logger)
    {
        $configuration = $this->getConfigurationForLogger(self::CONFIGURATION_TYPE_PROCESSOR, $logger->getName());
        foreach ($configuration as $severityLevel => $processor) {
            foreach ($processor as $logProcessorClassName => $logProcessorOptions) {
                try {
                    /** @var ProcessorInterface $logProcessor */
                    $logProcessor = GeneralUtility::makeInstance($logProcessorClassName, $logProcessorOptions);
                    $logger->addProcessor($severityLevel, $logProcessor);
                } catch (InvalidArgumentException|InvalidLogProcessorConfigurationException $e) {
                    $logger->warning('Instantiation of LogProcessor "{class_name}" failed for logger {name}', [
                        'class_name' => $logProcessorClassName,
                        'name' => $logger->getName(),
                        'exception' => $e,
                    ]);
                }
            }
        }
    }

    /**
     * Returns the configuration from $TYPO3_CONF_VARS['LOG'] as
     * hierarchical array for different components of the class hierarchy.
     *
     * @param string $configurationType Type of config to return (writer, processor)
     * @param string $loggerName Logger name
     * @throws \Psr\Log\InvalidArgumentException
     * @return array
     */
    protected function getConfigurationForLogger($configurationType, $loggerName)
    {
        // Split up the logger name (dot-separated) into its parts
        $explodedName = explode('.', $loggerName);
        // Search in the $TYPO3_CONF_VARS['LOG'] array
        // for these keys, for example "writerConfiguration"
        $configurationKey = $configurationType . 'Configuration';
        $configuration = $GLOBALS['TYPO3_CONF_VARS']['LOG'] ?? [];
        $result = $configuration[$configurationKey] ?? [];
        // Walk from general to special (t3lib, t3lib.db, t3lib.db.foo)
        // and search for the most specific configuration
        foreach ($explodedName as $partOfClassName) {
            if (!isset($configuration[$partOfClassName])) {
                break;
            }
            if (!empty($configuration[$partOfClassName][$configurationKey])) {
                $result = $configuration[$partOfClassName][$configurationKey];
            }
            $configuration = $configuration[$partOfClassName];
        }
        // Validate the config
        foreach ($result as $level => $unused) {
            try {
                LogLevel::validateLevel(LogLevel::normalizeLevel($level));
            } catch (InvalidArgumentException $e) {
                throw new InvalidArgumentException('The given severity level "' . htmlspecialchars($level) . '" for ' . $configurationKey . ' of logger "' . $loggerName . '" is not valid.', 1326406447);
            }
        }
        return $result;
    }
}
