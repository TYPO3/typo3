<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Configuration\FrameworkConfiguration\Extractors;

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
 * @internal
 */
class ExtractorDto
{

    /**
     * @var array
     */
    protected $prototypeConfiguration;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @param array $prototypeConfiguration
     */
    public function __construct(array $prototypeConfiguration)
    {
        $this->prototypeConfiguration = $prototypeConfiguration;
    }

    /**
     * @return array
     */
    public function getPrototypeConfiguration(): array
    {
        return $this->prototypeConfiguration;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     * @return ExtractorDto
     */
    public function setResult(array $result): ExtractorDto
    {
        $this->result = $result;
        return $this;
    }
}
