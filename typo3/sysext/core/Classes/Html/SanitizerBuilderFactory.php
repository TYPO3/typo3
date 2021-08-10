<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 project.
 *
 * It is free software; you can redistribute it and/or modify it under the terms
 * of the MIT License (MIT). For the full copyright and license information,
 * please read the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Html;

use LogicException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;

/**
 * Factory for creating a (sanitizer) builder instance. Corresponding presets can
 * be declared in `$GLOBALS['TYPO3_CONF_VARS']['SYS']['htmlSanitizer']` like e.g.
 *
 * ```
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['htmlSanitizer'] = [
 *   'default' => \TYPO3\CMS\Core\Html\DefaultSanitizerBuilder::class,
 *   'custom' => \Vendor\Package\CustomBuilder::class,
 * ];
 * ```
 *
 * @internal
 */
class SanitizerBuilderFactory
{
    /**
     * @var array
     */
    protected $configuration;

    public function __construct(array $configuration = null)
    {
        $this->configuration = $configuration ?? $GLOBALS['TYPO3_CONF_VARS']['SYS']['htmlSanitizer'] ?? [];
    }

    public function build(string $identifier): BuilderInterface
    {
        if (empty($this->configuration[$identifier])) {
            throw new LogicException(
                sprintf('Undefined `htmlSanitizer` identifier `%s`', $identifier),
                1624876139
            );
        }
        $builder = GeneralUtility::makeInstance($this->configuration[$identifier]);
        if (!$builder instanceof BuilderInterface) {
            throw new LogicException(
                sprintf(
                    'Builder `%s` must implement interface `%s`',
                    get_class($builder),
                    BuilderInterface::class
                ),
                1624876266
            );
        }
        return $builder;
    }
}
