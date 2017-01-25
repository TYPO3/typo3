<?php
namespace TYPO3\CMS\Form\Domain\Model;

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
 * The Configuration model is a high-level API
 * for the underlying TypoScript configuration.
 */
class Configuration
{
    /**
     * @var string
     */
    const DISABLE_CONTENT_ELEMENT_RENDERING = 'disableContentElement';

    /**
     * @var string
     */
    const DEFAULT_THEME_NAME = 'Default';

    /**
     * @return Configuration
     */
    public static function create()
    {
        return \TYPO3\CMS\Form\Utility\FormUtility::getObjectManager()->get(self::class);
    }

    /**
     * @var array
     */
    protected $typoScript = [];

    /**
     * @var bool
     */
    protected $contentElementRendering = false;

    /**
     * @var string
     */
    protected $prefix = 'form';

    /**
     * @var bool
     */
    protected $compatibility = false;

    /**
     * @var string
     */
    protected $themeName = '';

    /**
     * @var \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository
     */
    protected $typoScriptRepository;

    /**
     * @param \TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository
     * @return void
     */
    public function injectTypoScriptRepository(\TYPO3\CMS\Form\Domain\Repository\TypoScriptRepository $typoScriptRepository)
    {
        $this->typoScriptRepository = $typoScriptRepository;
    }

    /**
     * @return array
     */
    public function getTypoScript()
    {
        return $this->typoScript;
    }

    /**
     * @param array $typoScript
     * @return Configuration
     */
    public function setTypoScript(array $typoScript)
    {
        $this->typoScript = $typoScript;
        $this->update();
        return $this;
    }

    public function getContentElementRendering()
    {
        return $this->contentElementRendering;
    }

    /**
     * @param $contentElementRendering
     * @return Configuration
     */
    public function setContentElementRendering($contentElementRendering)
    {
        $this->contentElementRendering = (bool)$contentElementRendering;
        return $this;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     * @return Configuration
     */
    public function setPrefix($prefix)
    {
        $this->prefix = (string)$prefix;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCompatibility()
    {
        return $this->compatibility;
    }

    /**
     * @param bool $compatibility
     * @return Configuration
     */
    public function setCompatibility($compatibility)
    {
        $this->compatibility = (bool)$compatibility;
        return $this;
    }

    /**
     * @return string
     */
    public function getThemeName()
    {
        return $this->themeName;
    }

    /**
     * @param string $themeName
     * @return Configuration
     */
    public function setThemeName($themeName = '')
    {
        if ($themeName === '') {
            $themeName = static::DEFAULT_THEME_NAME;
        }
        $this->themeName = $themeName;
        return $this;
    }

    /**
     * Updates the local properties - called after
     * new TypoScript has been assigned in this object.
     */
    protected function update()
    {
        // Determine content rendering mode. If activated, cObject and stdWrap can be
        // used to execute various processes that must not be allowed on TypoScript
        // that has been created by non-privileged backend users (= insecure TypoScript)
        $this->setContentElementRendering(
            empty($this->typoScript[static::DISABLE_CONTENT_ELEMENT_RENDERING])
        );
        // Determine the HTML form element prefix to distinguish
        // different form components on the same page in the frontend
        if (!empty($this->typoScript['prefix'])) {
            $this->setPrefix($this->typoScript['prefix']);
        }
        // Determine compatibility behavior
        $this->setCompatibility((bool)$this->typoScriptRepository->getModelConfigurationByScope('FORM', 'compatibilityMode'));
        if (isset($this->typoScript['compatibilityMode'])) {
            if ((int)($this->typoScript['compatibilityMode']) === 0) {
                $this->setCompatibility(false);
            } else {
                $this->setCompatibility(true);
            }
        }
        // Set the theme name
        if (!empty($this->typoScript['themeName'])) {
            $this->setThemeName($this->typoScript['themeName']);
        } elseif (!empty($this->typoScriptRepository->getModelConfigurationByScope('FORM', 'themeName'))) {
            $this->setThemeName($this->typoScriptRepository->getModelConfigurationByScope('FORM', 'themeName'));
        } else {
            $this->setThemeName(static::DEFAULT_THEME_NAME);
        }
    }
}
