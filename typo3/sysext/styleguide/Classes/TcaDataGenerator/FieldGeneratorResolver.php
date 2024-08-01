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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

/**
 * Find matching field generator class instance
 *
 * @internal
 */
final readonly class FieldGeneratorResolver
{
    public function __construct(
        private array $fieldValueGenerators,
    ) {}

    /**
     * Resolve a generator class and return its instance.
     * Either returns an instance of FieldGeneratorInterface or throws exception
     *
     * @param array $data Criteria data
     * @throws GeneratorNotFoundException|Exception
     */
    public function resolve(array $data): FieldGeneratorInterface
    {
        foreach ($this->fieldValueGenerators as $fieldValueGenerator) {
            if (!$fieldValueGenerator instanceof FieldGeneratorInterface) {
                throw new Exception(
                    'Field value generator ' . $fieldValueGenerator::class . ' must implement FieldGeneratorInterface',
                    1457693564
                );
            }
            if ($fieldValueGenerator->match($data)) {
                if ($fieldValueGenerator instanceof FieldGeneratorResolverAwareInterface) {
                    // This interface prevents circular DI with
                    // generators that need FieldGeneratorResolver again.
                    $fieldValueGenerator->setFieldGeneratorResolver($this);
                }
                return $fieldValueGenerator;
            }
        }
        throw new GeneratorNotFoundException(
            'No generator found',
            1457873493
        );
    }
}
