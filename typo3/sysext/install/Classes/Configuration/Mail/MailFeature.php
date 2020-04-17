<?php

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

namespace TYPO3\CMS\Install\Configuration\Mail;

use TYPO3\CMS\Install\Configuration\AbstractFeature;
use TYPO3\CMS\Install\Configuration\FeatureInterface;

/**
 * Mail feature detects sendmail settings
 * @internal only to be used within EXT:install
 */
class MailFeature extends AbstractFeature implements FeatureInterface
{
    /**
     * @var string Name of feature
     */
    protected $name = 'Mail';

    /**
     * @var array List of preset classes
     */
    protected $presetRegistry = [
        SendmailPreset::class,
        SmtpPreset::class,
        CustomPreset::class,
    ];
}
