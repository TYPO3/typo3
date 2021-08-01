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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Core\DataHandling\SoftReference\SoftReferenceParserFactory;

class SoftReferenceParsersProvider extends AbstractProvider
{
    protected SoftReferenceParserFactory $softReferenceParserFactory;

    public function __construct(SoftReferenceParserFactory $softReferenceParserFactory)
    {
        $this->softReferenceParserFactory = $softReferenceParserFactory;
    }

    public function getConfiguration(): array
    {
        $configuration = [];
        foreach ($this->softReferenceParserFactory->getSoftReferenceParsers() as $parserKey => $parser) {
            $configuration[$parserKey] = [
                'parserKey' => $parserKey,
                'className' => get_class($parser),
            ];
        }
        return $configuration;
    }
}
