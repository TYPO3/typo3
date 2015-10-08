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
class ResponseContent
{
    /**
     * @var array|ResponseSection[]
     */
    protected $sections;

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
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $content = json_decode($response->getContent(), true);

        if ($content !== null && is_array($content)) {
            foreach ($content as $sectionIdentifier => $sectionData) {
                $section = new ResponseSection($sectionIdentifier, $sectionData);
                $this->sections[$sectionIdentifier] = $section;
            }
        }
    }

    /**
     * @param string $sectionIdentifier
     * @return NULL|ResponseSection
     * @throws \RuntimeException
     */
    public function getSection($sectionIdentifier)
    {
        if (isset($this->sections[$sectionIdentifier])) {
            return $this->sections[$sectionIdentifier];
        }

        throw new \RuntimeException('ResponseSection "' . $sectionIdentifier . '" does not exist');
    }
}
