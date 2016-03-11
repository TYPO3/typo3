<?php
namespace TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGenerator;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;
use TYPO3\CMS\Styleguide\TcaDataGenerator\FieldGeneratorInterface;

/**
 * Generate data for type=input fields
 */
class TypeInput implements FieldGeneratorInterface {

    /**
     * @var KauderwelschService
     */
    protected $kauderwelschService;

    /**
     * TypeInput constructor.
     */
    public function __construct()
    {
        $this->kauderwelschService = GeneralUtility::makeInstance(KauderwelschService::class);
    }

    /**
     * Return true if this FieldGenerator matches
     *
     * @param array $criteria
     * @return bool
     */
    public function match(array $criteria): bool
    {
        if ($criteria['fieldConfig']['config']['type'] === 'input') {
            return true;
        }
        return false;
    }

    /**
     * Returns the generated value to be inserted into DB for this field
     *
     * @param array $criteria
     * @return string
     */
    public function generate(array $criteria): string
    {
        return $this->kauderwelschService->getWord();
    }
}
