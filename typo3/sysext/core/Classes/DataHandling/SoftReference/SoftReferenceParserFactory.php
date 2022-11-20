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

namespace TYPO3\CMS\Core\DataHandling\SoftReference;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Factory class for soft reference parsers
 */
class SoftReferenceParserFactory
{
    protected array $softReferenceParsers = [];
    protected FrontendInterface $runtimeCache;
    protected LoggerInterface $logger;

    public function __construct(FrontendInterface $runtimeCache, LoggerInterface $logger)
    {
        $this->runtimeCache = $runtimeCache;
        $this->logger = $logger;
    }

    /**
     * Adds a parser via DI.
     *
     * @internal
     */
    public function addParser(SoftReferenceParserInterface $softReferenceParser, string $parserKey): void
    {
        if (!isset($this->softReferenceParsers[$parserKey])) {
            $this->softReferenceParsers[$parserKey] = $softReferenceParser;
        }
    }

    /**
     * Returns array of soft parser references
     *
     * @param string $parserList softRef parser list
     * @return array|null Array where the parser key is the key and the value is the parameter string, FALSE if no parsers were found
     */
    protected function explodeSoftRefParserList(string $parserList): ?array
    {
        // Return immediately if list is blank:
        if ($parserList === '') {
            return null;
        }
        $cacheId = 'backend-softRefList-' . md5($parserList);
        $parserListCache = $this->runtimeCache->get($cacheId);
        if ($parserListCache !== false) {
            return $parserListCache;
        }
        // Otherwise parse the list:
        $keyList = GeneralUtility::trimExplode(',', $parserList, true);
        $output = [];
        foreach ($keyList as $val) {
            $reg = [];
            if (preg_match('/^([[:alnum:]_-]+)\\[(.*)\\]$/', $val, $reg)) {
                $output[$reg[1]] = GeneralUtility::trimExplode(';', $reg[2], true);
            } else {
                $output[$val] = '';
            }
        }
        $this->runtimeCache->set($cacheId, $output);
        return $output;
    }

    /**
     * @param array|null $forcedParameters
     * @return iterable<SoftReferenceParserInterface>
     */
    public function getParsersBySoftRefParserList(string $softRefParserList, array $forcedParameters = null): iterable
    {
        foreach ($this->explodeSoftRefParserList($softRefParserList) ?? [] as $parserKey => $parameters) {
            if (!is_array($parameters)) {
                $parameters = $forcedParameters ?? [];
            }

            if (!$this->hasSoftReferenceParser($parserKey)) {
                $this->logger->warning('No soft reference parser exists for the key "{parserKey}".', ['parserKey' => $parserKey]);
                continue;
            }

            $parser = $this->getSoftReferenceParser($parserKey);
            $parser->setParserKey($parserKey, $parameters);

            yield $parser;
        }
    }

    public function hasSoftReferenceParser(string $softReferenceParserKey): bool
    {
        return isset($this->softReferenceParsers[$softReferenceParserKey]);
    }

    /**
     * Get a Soft Reference Parser by the given soft reference key.
     * Implementation must be registered in Configuration/Services.yaml
     *
     *   VENDOR\YourExtension\SoftReference\UserDefinedSoftReferenceParser:
     *     tags:
     *       - name: softreference.parser
     *         parserKey: userdefined
     *
     *
     * @param string $softReferenceParserKey
     */
    public function getSoftReferenceParser(string $softReferenceParserKey): SoftReferenceParserInterface
    {
        if ($softReferenceParserKey === '') {
            throw new \InvalidArgumentException(
                'The soft reference parser key cannot be empty.',
                1627899274
            );
        }

        if (!$this->hasSoftReferenceParser($softReferenceParserKey)) {
            throw new \OutOfRangeException(
                sprintf('No soft reference parser found for "%s".', $softReferenceParserKey),
                1627899342
            );
        }

        return $this->softReferenceParsers[$softReferenceParserKey];
    }

    /**
     * Get all registered soft reference parsers
     */
    public function getSoftReferenceParsers(): array
    {
        return $this->softReferenceParsers;
    }
}
