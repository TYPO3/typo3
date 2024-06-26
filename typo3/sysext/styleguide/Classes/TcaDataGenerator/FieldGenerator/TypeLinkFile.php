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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

use TYPO3\CMS\Core\LinkHandling\FileLinkHandler;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;
use TYPO3\CMS\Styleguide\TcaDataGenerator\RecordFinder;

/**
 * Generate data for type=link fields, where allowed type is file only
 *
 * @internal
 */
final class TypeLinkFile extends AbstractFieldGenerator implements FieldGeneratorInterface
{
    protected array $matchArray = [
        'fieldConfig' => [
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['file'],
            ],
        ],
    ];

    public function __construct(
        private readonly RecordFinder $recordFinder,
        private readonly FileLinkHandler $fileLinkHandler
    ) {}

    public function generate(array $data): string
    {
        $demoImages = $this->recordFinder->findDemoFileObjects();
        $image = next($demoImages);
        return $this->fileLinkHandler->asString(['file' => $image]);
    }
}
