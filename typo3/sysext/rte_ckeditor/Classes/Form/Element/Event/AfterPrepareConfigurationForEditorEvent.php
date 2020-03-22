<?php
declare(strict_types=1);

namespace TYPO3\CMS\RteCKEditor\Form\Element\Event;

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
 * This event is fired after preparing the editor configuration.
 */
final class AfterPrepareConfigurationForEditorEvent
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var array
     */
    private $data;

    public function __construct(array $configuration, array $data)
    {
        $this->configuration = $configuration;
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
