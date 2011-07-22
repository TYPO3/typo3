<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
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
 * JSON fieldset
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_json_fieldset extends tx_form_domain_model_json_container {
	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-basic-fieldset';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(),
		'legend' => array(
			'value' => ''
		)
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class',
		'dir',
		'id',
		'lang',
		'style'
	);

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Set all the parameters for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 * @see tx_form_domain_model_json_element::setParameters()
	 */
	public function setParameters($parameters) {
		parent::setParameters($parameters);
		$this->setLegend($parameters);
	}

	/**
	 * Set the legend for the element
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function setLegend($parameters) {
		if (isset($parameters['legend']) && !isset($parameters['legend.'])) {
			$this->configuration['legend']['value'] = $parameters['legend'];
		} elseif (!isset($parameters['legend']) && isset($parameters['legend.'])) {
			$this->configuration['legend']['value'] = $parameters['legend.']['value'];
		}
	}
}
?>