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

namespace TYPO3\CMS\FrontendLogin\Service;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class ValidatorResolverService implements SingletonInterface
{
    /**
     * Resolves Validator classes based on the validator config. This array can either
     * contain a FQCN or an array with keys "className"(string) and "options"(array).
     *
     * @template T of ValidatorInterface
     * @param array<class-string<T>|array{className: class-string<T>, options: array}> $validatorConfig
     *
     * @return \Generator<T>|null
     */
    public function resolve(array $validatorConfig): ?\Generator
    {
        foreach ($validatorConfig as $validator) {
            if (is_string($validator)) {
                yield GeneralUtility::makeInstance($validator);
            } elseif (is_array($validator)) {
                $validatorInstance = GeneralUtility::makeInstance($validator['className']);
                $validatorInstance->setOptions($validator['options']);
                yield $validatorInstance;
            }
        }
    }
}
