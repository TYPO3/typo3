<?php
namespace TYPO3\CMS\Install\Configuration\Mail;

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

use TYPO3\CMS\Install\Configuration;

/**
 * Sendmail path handling preset
 */
class SendmailPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Sendmail';

    /**
     * @var int Priority of preset
     */
    protected $priority = 50;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'MAIL/transport_sendmail_command' => '',
    ];

    /**
     * Get configuration values to activate prefix
     *
     * @return array Configuration values needed to activate prefix
     */
    public function getConfigurationValues()
    {
        $configurationValues = $this->configurationValues;
        $configurationValues['MAIL/transport_sendmail_command'] = $this->getSendmailPath();
        return $configurationValues;
    }

    /**
     * Check if sendmail path if set
     *
     * @return bool TRUE if sendmail path if set
     */
    public function isAvailable()
    {
        return !empty($this->getSendmailPath());
    }

    /**
     * Path where executable was found
     *
     * @return string|bool Sendmail path or FALSE if not set
     */
    public function getSendmailPath()
    {
        return ini_get('sendmail_path');
    }

    /**
     * Check is preset is currently active on the system
     *
     * @return bool TRUE if preset is active
     */
    public function isActive()
    {
        $this->configurationValues['MAIL/transport_sendmail_command'] = $this->getSendmailPath();
        return parent::isActive();
    }
}
