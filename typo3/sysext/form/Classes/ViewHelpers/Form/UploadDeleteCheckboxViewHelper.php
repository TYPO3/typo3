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

namespace TYPO3\CMS\Form\ViewHelpers\Form;

use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Form\Security\HashScope;

/**
 * ViewHelper which renders a checkbox for file deletion in EXT:form.
 *
 * This ViewHelper is similar to Extbase's UploadDeleteCheckboxViewHelper but adapted
 * for the EXT:form context. It renders a checkbox that, when checked, marks the
 * associated file for deletion on form submission.
 *
 * Example usage:
 * ```
 * <formvh:form.uploadDeleteCheckbox
 *     property="{element.identifier}"
 *     fileReference="{file}"
 *     fileIndex="{iterator.index}"
 * />
 * ```
 *
 * Scope: frontend
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-form-form-uploaddeletecheckbox
 */
final class UploadDeleteCheckboxViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function __construct(
        private readonly HashService $hashService,
    ) {
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('fileReference', FileReference::class, 'The file reference object', true);
        $this->registerArgument('fileIndex', 'int', 'Index of the file in multiple upload context', false, 0);
    }

    public function render(): string
    {
        /** @var FileReference|null $fileReference */
        $fileReference = $this->arguments['fileReference'];
        $fileIndex = (int)$this->arguments['fileIndex'];

        // Early return if no file reference given
        if (!$fileReference instanceof FileReference) {
            return '';
        }

        $this->tag->addAttribute('type', 'checkbox');

        // Build the deletion data that will be validated on submit
        $deleteData = [
            'property' => $this->arguments['property'],
            'fileIndex' => $fileIndex,
            'fileUid' => $fileReference->getUid() ?? $fileReference->getOriginalResource()->getOriginalFile()->getUid(),
        ];

        // Create HMAC-signed value
        $valueAttribute = $this->hashService->appendHmac(
            json_encode($deleteData, JSON_THROW_ON_ERROR),
            HashScope::DeleteFile->prefix()
        );

        // Build name attribute using the form field prefix
        $name = $this->getName();
        $nameAttribute = $name . '[__deleteFile][' . $fileIndex . ']';

        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);

        // Check if this checkbox was previously checked (in case of validation errors)
        if ($this->isChecked($fileIndex)) {
            $this->tag->addAttribute('checked', 'checked');
        }

        return $this->tag->render();
    }

    /**
     * Checks if the checkbox for the given file index was checked in the current request
     */
    private function isChecked(int $fileIndex): bool
    {
        $value = $this->getValueAttribute();
        if (is_array($value) && isset($value['__deleteFile'][$fileIndex])) {
            return !empty($value['__deleteFile'][$fileIndex]);
        }
        return false;
    }
}
