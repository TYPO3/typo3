<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\BackendUser;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Views sprite icon for a record (object)
 * @internal
 */
class SpriteIconForRecordViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('table', 'string', '', true);
        $this->registerArgument('object', 'object', '', true);
    }

    /**
     * Displays spriteIcon for database table and object
     *
     * @return string
     * @see IconFactory::getIconForRecord()
     */
    public function render()
    {
        return static::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     * @throws \Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $object = $arguments['object'];
        $table = $arguments['table'];

        if (!is_object($object) || !method_exists($object, 'getUid')) {
            return '';
        }
        $row = [
            'uid' => $object->getUid(),
            'startTime' => false,
            'endTime' => false
        ];
        if (method_exists($object, 'getIsDisabled')) {
            $row['disable'] = $object->getIsDisabled();
        }
        if (method_exists($object, 'getHidden')) {
            $row['hidden'] = $object->getHidden();
        }
        if ($table === 'be_users' && $object instanceof BackendUser) {
            $row['admin'] = $object->getIsAdministrator();
        }
        if (method_exists($object, 'getStartDateAndTime')) {
            $row['startTime'] = $object->getStartDateAndTime();
        }
        if (method_exists($object, 'getEndDateAndTime')) {
            $row['endTime'] = $object->getEndDateAndTime();
        }
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render();
    }
}
