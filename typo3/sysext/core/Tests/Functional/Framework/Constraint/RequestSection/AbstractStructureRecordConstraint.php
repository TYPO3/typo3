<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection;

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

/**
 * Model of frontend response
 */
abstract class AbstractStructureRecordConstraint extends AbstractRecordConstraint
{
    /**
     * @var string
     */
    protected $recordIdentifier;

    /**
     * @var string
     */
    protected $recordField;

    public function setRecordIdentifier($recordIdentifier)
    {
        $this->recordIdentifier = $recordIdentifier;
        return $this;
    }

    public function setRecordField($recordField)
    {
        $this->recordField = $recordField;
        return $this;
    }
}
