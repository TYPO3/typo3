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
 * SMTP settings handling preset
 * @internal only to be used within EXT:install
 */
class SmtpPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Smtp';

    /**
     * @var int Priority of preset
     */
    protected $priority = 40;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'MAIL/transport' => 'smtp',
        'MAIL/transport_sendmail_command' => '',
        'MAIL/transport_smtp_server' => 'localhost:25',
        'MAIL/transport_smtp_encrypt' => '',
        'MAIL/transport_smtp_username' => '',
        'MAIL/transport_smtp_password' => '',
    ];

    /**
     * Get configuration values to activate prefix
     *
     * @return array Configuration values needed to activate prefix
     */
    public function getConfigurationValues()
    {
        $configurationValues = $this->configurationValues;
        $keys = array_keys($configurationValues);
        foreach ($keys as $key) {
            if (!empty($this->postValues['Smtp'][$key])) {
                $configurationValues[$key] = $this->postValues['Smtp'][$key];
            }
        }
        if ($this->postValues['Mail']['enable'] === 'Smtp') {
            $configurationValues['MAIL/transport'] = 'smtp';
        }
        return $configurationValues;
    }

    /**
     * Check if sendmail path if set
     *
     * @return bool TRUE if sendmail path if set
     */
    public function isAvailable()
    {
        return true;
    }
}
