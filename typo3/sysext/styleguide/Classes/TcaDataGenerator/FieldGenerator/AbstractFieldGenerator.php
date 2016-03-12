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

    public function match(array $data): bool
    {
        return $this->checkMatchArray($data, $this->matchArray);
    }

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
                    $result = $result & true;
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
