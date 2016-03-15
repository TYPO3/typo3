<?php
declare(strict_types=1);
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

/**
 * Implement some standard stuff
 */
class AbstractFieldGenerator
{
    /**
     * @var KauderwelschService
     */
    protected $kauderwelschService;

    /**
     * @var array If all of these settings are identical to given values, match is true
     */
    protected $matchArray = [];

    /**
     * TypeInput constructor.
     */
    public function __construct()
    {
        $this->kauderwelschService = GeneralUtility::makeInstance(KauderwelschService::class);
    }

    /**
     * General match implementation checks input array against $this->matchArray.
     * If all keys and values of matchArray exist in $data and are identical, this generator matches.
     *
     * @param array $data Given data
     * @return bool
     */
    public function match(array $data): bool
    {
        return $this->checkMatchArray($data, $this->matchArray);
    }

    /**
     * Recursive compare of $data with $matchArray.
     *
     * @param array $data Given data
     * @param array $matchArray Part to mach against
     * @return bool
     */
    protected function checkMatchArray(array $data, array $matchArray): bool
    {
        $result = true;
        foreach ($matchArray as $name => $value) {
            if (isset($data[$name])) {
                if (is_array($value)) {
                    $result = $this->checkMatchArray($data[$name], $value);
                    if ($result === false) {
                        return false;
                    }
                } elseif ($data[$name] === $value) {
                    $result = (bool)($result & true);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
        return $result;
    }
}
