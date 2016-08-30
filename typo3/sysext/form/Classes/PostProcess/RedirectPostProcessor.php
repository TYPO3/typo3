<?php
namespace TYPO3\CMS\Form\PostProcess;

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
 * The redirect post-processor
 */
class RedirectPostProcessor extends AbstractPostProcessor implements PostProcessorInterface
{
    /**
     * @var \TYPO3\CMS\Form\Domain\Model\Element
     */
    protected $form;

    /**
     * @var array
     */
    protected $typoScript;

    /**
     * @var string
     */
    protected $destination;

    /**
     * Constructor
     *
     * @param \TYPO3\CMS\Form\Domain\Model\Element $form Form domain model
     * @param array $typoScript Post processor TypoScript settings
     */
    public function __construct(\TYPO3\CMS\Form\Domain\Model\Element $form, array $typoScript)
    {
        $this->form = $form;
        $this->typoScript = $typoScript;
    }

    /**
     * The main method called by the post processor
     *
     * @return string HTML message from this processor
     */
    public function process()
    {
        $this->setDestination();
        $this->render();
    }

    /**
     * Sets the redirect destination
     *
     * @return void
     */
    protected function setDestination()
    {
        $this->destination = '';
        if ($this->typoScript['destination']) {
            $urlConf = ['parameter' => $this->typoScript['destination']];
            $this->destination = $GLOBALS['TSFE']->cObj->typoLink_URL($urlConf);
        }
    }

    /**
     * Redirect to a destination
     *
     * @return void
     */
    protected function render()
    {
        \TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->destination);
        return;
    }
}
