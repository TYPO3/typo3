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

namespace TYPO3\CMS\Form\Mvc\Property\Exception;

use TYPO3\CMS\Extbase\Error\Error;

/**
 * A "Type Converter" Exception
 */
final class TypeConverterException extends \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException
{
    private ?Error $error = null;

    public static function fromError(Error $error): TypeConverterException
    {
        $exception = new self($error->render(), $error->getCode());
        $exception->error = $error;

        return $exception;
    }

    public function getError(): Error
    {
        if ($this->error === null) {
            return new Error($this->getMessage(), $this->getCode(), [$this->getPrevious()]);
        }

        return $this->error;
    }
}
