<?php
namespace TYPO3\CMS\Form\Domain\Model\Json;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Broens (patrick@patrickbroens.nl)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * JSON form
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FormJsonElement extends \TYPO3\CMS\Form\Domain\Model\Json\ContainerJsonElement {

	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-basic-form';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(),
		'prefix' => 'tx_form',
		'confirmation' => TRUE,
		'postProcessor' => array()
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'accept',
		'accept-charset',
		'action',
		'class',
		'dir',
		'enctype',
		'id',
		'lang',
		'method',
		'style',
		'title'
	);

	/**
	 * Set all the parameters for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @see \TYPO3\CMS\Form\Domain\Model\Json\ContainerJsonElement::setParameters()
	 */
	public function setParameters(array $parameters) {
		parent::setParameters($parameters);
		$this->setPrefix($parameters);
		$this->setConfirmation($parameters);
		$this->setPostProcessors($parameters);
	}

	/**
	 * Set the confirmation message boolean
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setConfirmation(array $parameters) {
		if (isset($parameters['confirmation'])) {
			$this->configuration['confirmation'] = $parameters['confirmation'];
		}
	}

	/**
	 * Set the post processors and their configuration
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setPostProcessors(array $parameters) {
		if (isset($parameters['postProcessor.']) && is_array($parameters['postProcessor.'])) {
			$postProcessors = $parameters['postProcessor.'];
			foreach ($postProcessors as $key => $postProcessorName) {
				if (intval($key) && !strstr($key, '.')) {
					$postProcessorConfiguration = array();
					if (isset($postProcessors[$key . '.'])) {
						$postProcessorConfiguration = $postProcessors[$key . '.'];
					}
					$this->configuration['postProcessor'][$postProcessorName] = $postProcessorConfiguration;
				}
			}
		} else {
			$this->configuration['postProcessor'] = array(
				'mail' => array(
					'recipientEmail' => '',
					'senderEmail' => ''
				)
			);
		}
	}

	/**
	 * Set the prefix
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setPrefix(array $parameters) {
		if (isset($parameters['prefix'])) {
			$this->configuration['prefix'] = $parameters['prefix'];
		}
	}

}

?>