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

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;

/**
 * A generic file upload form element
 *
 * Scope: frontend
 */
class FileUpload extends AbstractFormElement
{

    /**
     * Initializes the Form Element by setting the data type to an Extbase File Reference
     * @internal
     */
    public function initializeFormElement()
    {
        $this->setDataType(FileReference::class);
        parent::initializeFormElement();
    }
}
