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

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;

/**
 * This finisher remove the submitted files.
 * Use this e.g after the email finisher if you don't want
 * to keep the files online.
 *
 * Scope: frontend
 */
class DeleteUploadsFinisher extends AbstractFinisher
{

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();

        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();
        foreach ($elements as $element) {
            if (!$element instanceof FileUpload) {
                continue;
            }
            $file = $formRuntime[$element->getIdentifier()];
            if (!$file) {
                continue;
            }

            if ($file instanceof FileReference) {
                $file = $file->getOriginalResource();
            }
            $file->getStorage()->deleteFile($file->getOriginalFile());
        }
    }
}
