<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend;

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
 * Model of frontend response content
 */
class ResponseSection
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var array
     */
    protected $structure;

    /**
     * @var array
     */
    protected $structurePaths;

    /**
     * @var array
     */
    protected $records;

    /**
     * @var array
     */
    protected $queries;

    /**
     * @param string $identifier
     * @param array $data
     */
    public function __construct($identifier, array $data)
    {
        $this->identifier = (string)$identifier;
        $this->structure = $data['structure'];
        $this->structurePaths = $data['structurePaths'];
        $this->records = $data['records'];

        if (!empty($data['queries'])) {
            $this->queries = $data['queries'];
        }
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return array
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * @return array
     */
    public function getStructurePaths()
    {
        return $this->structurePaths;
    }

    /**
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * @param string $recordIdentifier
     * @param string $fieldName
     * @return array
     */
    public function findStructures($recordIdentifier, $fieldName = '')
    {
        $structures = [];

        if (empty($this->structurePaths[$recordIdentifier])) {
            return $structures;
        }

        foreach ($this->structurePaths[$recordIdentifier] as $steps) {
            $structure = $this->structure;
            $steps[] = $recordIdentifier;

            if (!empty($fieldName)) {
                $steps[] = $fieldName;
            }

            foreach ($steps as $step) {
                if (!isset($structure[$step])) {
                    $structure = null;
                    break;
                }
                $structure = $structure[$step];
            }

            if (!empty($structure)) {
                $structures[implode('/', $steps)] = $structure;
            }
        }

        return $structures;
    }
}
