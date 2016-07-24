<?php
namespace TYPO3\CMS\Core\Tests\Unit\Mail\Fixtures;

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
 * Fixture fake valid spool
 */
class FakeValidSpoolFixture implements \Swift_Spool
{
    private $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function start()
    {
    }

    public function stop()
    {
    }

    public function isStarted()
    {
    }

    public function queueMessage(\Swift_Mime_Message $message)
    {
    }

    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
    }
}
