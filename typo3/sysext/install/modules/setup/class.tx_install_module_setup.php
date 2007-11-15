<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Thomas Hempel (thomas@work.de)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 *
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@work.de>
 * @author	Ingo Renner	<ingo@typo3.org>
 */
class tx_install_module_setup extends tx_install_module_base	{
	
	/**
	 * Main method of this module. This method is called by default at various places.
	 *
	 * @return plain HTML if AJAX request, otherwise array(content)
	 */
	public function main()	{
		$returnValue = '';
		
			// check if we should return AJAX content
		if ($this->env['ajax'] == 1)	{
			
			$ajaxContent = '';
			$titleSection = $this->get_LL('label_category_'.$this->env['categoryMain']);
			
				// create a header box for main category if sub catagory is empty
			if (empty($this->env['categorySub']))	{
				$aboutBoxCode = array (
					'type' => 'box',
					'value' => array (
						'class' => 'category_about',
						'elements' => array (
							array (
								'type' => 'image',
								'value' => array (
									'path' => 'imgs/icons/'.$this->env['categoryMain'].'.png'
								)
							),
							array (
								'type' => 'plain',
								'value' => $this->get_LL('descr_'.$this->env['categoryMain'])
							)
						)
					)
				);
				
					// add the about box
				$this->pObj->getViewObject()->addContent('', $this->pObj->getViewObject()->render($aboutBoxCode));
				
					// get all deliverables for all modules in main category
				$categoryDeliverables = $this->basicsObject->getCategoryModuleDeliverables($this->env['categoryMain']);
				$subCategoryListElements = array();
				
					// create a link to each subcategory
				foreach ($categoryDeliverables as $subCategory => $deliverables)	{
					$label = $this->get_LL('label_subcategory_'.$subCategory);
					$subCategoryListElements[] = array (
						'type' => 'plain',
						'value' => '<a href="#" onclick="loadModuleContent(\''.$this->env['categoryMain'].'\', \''.$subCategory.'\'); return false;">'.$label.'</a>'
					);
				}
				
					// render a list with the links
				$deliverableCode = $this->pObj->getViewObject()->render(array(
					'type' => 'list',
					'value' => $subCategoryListElements
				));
				
					// add a new section to the output
				$this->pObj->getViewObject()->addContent($this->get_LL('label_subsectionsfound'), $deliverableCode);
				
				$ajaxContent = $this->pObj->getViewObject()->getContent();
			} else {
					// sub category was selected...
				$titleSection .= ' / '.$this->get_LL('label_subcategory_'.$this->env['categorySub']);
				$moduleDeliverables = $this->pObj->getBasicsObject()->getCategoryModuleDeliverables($this->env['categoryMain'], $this->env['categorySub']);
				
					// create a about box
				$iconPath = 'imgs/icons/'.$this->env['categorySub'].'.png';
				if (!file_exists($iconPath))	{
					$iconPath = 'imgs/icons/'.$this->env['categoryMain'].'.png';
				}
				
				$aboutBoxCode = array (
					'type' => 'box',
					'value' => array (
						'class' => 'category_about',
						'elements' => array (
							array (
								'type' => 'image',
								'value' => array (
									'path' => $iconPath
								)
							),
							array (
								'type' => 'plain',
								'value' => $this->get_LL('descr_'.$this->env['categorySub'])
							)
						)
					)
				);
				
				$aboutBox = $this->pObj->getViewObject()->render($aboutBoxCode);
				
				if ($moduleDeliverables === false)	{
						// render errors if something went wrong before
					$ajaxContent = $this->pObj->getViewObj()->renderErrors(true);
				} else {
					$filterResults = $this->pObj->getFilterResults();
					
						// cycle through deliverables and execute or render them
					foreach ($moduleDeliverables as $deliverable => $names)	{
						$ajaxContent .= $this->pObj->getViewObject()->renderTag('h2', $this->get_LL('label_deliverable_'.$deliverable));
						
							// add form for saving options
						if ($deliverable == 'options')	{
							$formWrap = array (
								'<form action="index.php" method="post" id="optionsForm">'.
									'<input type="hidden" name="categoryMain" value="'.$this->env['categoryMain'].'" />'.
									'<input type="hidden" name="categorySub" value="'.$this->env['categorySub'].'" />',
								'<input type="button" onclick="sendForm(\'optionsForm\');" value="'.$this->get_LL('label_save').'" /></form>'
							);
						} else {
							$formWrap = array('', '');
						}
						
						$modifiedFields = array();
						
						foreach ($names as $name => $mod)	{
							$modConfig = $GLOBALS['MCA'][$mod][$deliverable][$name];
							
							if (!isset($modConfig['title']))	{
								$modConfig['title'] = 'title_'.$name;
							}
							if (!isset($modConfig['description']))	{
								$modConfig['description'] = 'description_'.$name;
							}
							if (!isset($modConfig['help']))	{
								$modConfig['help'] = 'help_'.$name;
							}
				
							$this->pObj->getBasicsObject()->loadModule($mod);
							
							$helpData = $this->pObj->getViewObject()->renderHelp($this->get_LL($modConfig['help']), $name);
							$descr = $this->pObj->getViewObject()->renderTag('div', $this->get_LL($modConfig['description']));
							$deliverableContent = '';
							
							switch ($deliverable)	{
								case 'checks':
										// execute check and print out result in plain
									// $this->pObj->getViewObject()->clearLastMessage();
									$checkResult = $this->basicsObject->executeMethod($modConfig['method']);
									$deliverableContent = $this->get_LL('label_'.(($checkResult) ? 'true' : 'false'));
									if (!$checkResult)	{
										$deliverableContent .= $this->pObj->getViewObject()->renderErrors();
									} else {
										$deliverableContent .= $this->pObj->getViewObject()->getLastMessage();
										$this->pObj->getViewObject()->clearLastMessage();
									}
									break; 
								case 'options':
										// options are rendered as input elements
									
										// save data
									if ($this->env['saveData'] && isset($this->env[$mod.':'.$name]))	{
											// get data from environment
										
										if ($modConfig['elementType'] == 'checkbox')	{
											$this->env[$mod.':'.$name] = intval($this->env[$mod.':'.$name]);
										}
										
										$saveData = $this->env[$mod.':'.$name];
										
											// add only if data has really changed
										$localConfValue = $this->basicsObject->getLocalconfValue($modConfig['value'], $modConfig['default']);
										if ($saveData != $localConfValue)	{
											$this->basicsObject->addToLocalconf($modConfig['value'], $saveData, $modConfig['valueType']);
											
											$modifiedFields[] = array (
												'type' => 'message',
												'value' => array (
													'label' => sprintf($this->get_LL('label_value_modified'), $this->get_LL($modConfig['title']), $saveData)
												)
											);
										}
									}
									
									$options = $modConfig;
									$inputConfig = array (
										'elementType' => $modConfig['elementType'],
										'options' => array_merge($modConfig, array (
											'name' => $mod.':'.$name,
											'value' => $this->basicsObject->getLocalconfValue($modConfig['value'], $modConfig['default']),
											'description' => $modConfig['description'],
											'help' => $modConfig['help']
										))
									);
									
									$descr .= $this->pObj->getViewObject()->renderTag('span', $this->get_LL('label_path').' '.$this->basicsObject->getLocalconfPath($modConfig['value']).' - '.$this->get_LL('label_default').' '.$modConfig['default'], array('class' => 'description italic'));
									$deliverableContent = $this->pObj->getViewObject()->renderFormelement($inputConfig);
									break;
								case 'methods':
										// the result of methods is simply printed out
									$deliverableContent = $this->basicsObject->executeMethod($modConfig['method']);
									break;
							}
							
							if ($modConfig['elementType'] == 'checkbox')	{
								$deliverableBox = $this->pObj->getViewObject()->renderTag('h3', $deliverableContent.$this->get_LL($modConfig['title']).' '.(($helpData) ? $helpData['button'] : '')).(($helpData) ? $helpData['container'] : '').$descr;
							} else {
								$deliverableBox = $this->pObj->getViewObject()->renderTag('h3', $this->get_LL($modConfig['title']).' '.(($helpData) ? $helpData['button'] : '')).(($helpData) ? $helpData['container'] : '').$descr.$deliverableContent;
							}
							
							$paramData = array (
								'id' => 'container_'.$name,
								'class' => 'deliverable-box'
							);
							if ($filterResults[$name])	{
								$paramData['style'] = 'background-color:#99ff99';
							}
							
							$ajaxContent .= $this->pObj->getViewObject()->renderTag('div', $deliverableBox, $paramData);
						}
					}
					
						// try to write the data to localconf
					if (!$this->basicsObject->saveLocalconf())	{
						$ajaxContent = $this->pObj->getViewObject()->renderErrors().$ajaxContent;
					} else {
						$modMessage = '';
						// var_dump($modifiedFields);
						if (count($modifiedFields) > 0)	{
							$modMessage = $this->pObj->getViewObject()->render(array('type' => 'list', 'value' => $modifiedFields));
						}
						$ajaxContent = $aboutBox.$this->pObj->getViewObject()->getLastMessage().$modMessage.$ajaxContent;
					}
					
				}
			}
				
			$returnValue = $this->pObj->getViewObject()->renderTag('h1', $titleSection).$formWrap[0].$ajaxContent.$formWrap[1];
			
		} else {
				// this is the default... In other words this is only executed on first call. All other requests are done via AJAX see above...
				
			$catData = $this->basicsObject->getModuleCategoryData();
			$tree    = $this->renderCategoryTree($catData, $tree);
	
			$installerWebPath = $this->basicsObject->getInstallerWebPath();
			
			$this->pObj->getViewObject()->addJS(
				'var minusSrc = "'.$installerWebPath.'imgs/icons/minus.gif";' .
				'var plusSrc = "'.$installerWebPath.'imgs/icons/plus.gif";' .
				'var expandSrc = "'.$installerWebPath.'imgs/icons/expandall.png";' .
				'var collapseSrc = "'.$installerWebPath.'imgs/icons/collapseall.png";' .
				'var labelExpand = "'.$this->get_LL('label_expandAll').'";' .
				'var labelCollapse = "'.$this->get_LL('label_collapseAll').'";'
			);
		
			$returnValue = array('content' => $tree.'<div id="moduleContent"></div>');
		}
		
		return $returnValue;
	}
	
	
	/**
	 * Renders a collapsable tree from the collected category data.
	 * 
	 * @param	array		$data: The collected category data (see: basicsObj->getModuleCategoryData())
	 * @return	HTML of the tree as unordered list.
	 */
	private function renderCategoryTree($data)	{
			// add the searchbox
		$content = '<div class="categoryTreeContainer">
			<input type="text" id="treeFilter" value="" /><br />
			<span id="filterStatus"></span>
			<a href="#" onclick="toggleAllLeafs()" id="collapseExpandToggle">
				'.$this->get_LL('label_expandAll').'
			</a>
		';
		
		$content .= '<ul class="categoryTree">';
		
			// first level
		foreach ($data as $level1Key => $level1Value)	{
			if (is_array($level1Value))	{
				$content .= '<li id="item_'.$level1Key.'" class="tree_item"><a href="#" onclick="toggleElement(\''.$level1Key.'\'); return false;"><img id="img_'.$level1Key.'" src="'.$this->pObj->getBasicsObject()->getInstallerWebPath().'imgs/icons/plus.gif" /></a>'.$this->renderTreeElement($level1Key);
				if (is_array($level1Value))	{
					$content .= '<ul id="'.$level1Key.'" class="subLeaf" style="display:none">';
					foreach ($level1Value as $level2Key => $level2Value)	{
						$content .= '<li id="item_'.$level2Key.'" class="tree_item">'.$this->renderTreeElement($level1Key, $level2Key).'</li>';
					}
					$content .= '</ul>';
				}
				$content .= '</li>';
			}
		}
		$content .= '</ul></div>';
	
		return $content;
	}
	
	private function renderTreeElement($level1Key, $level2Key = NULL)	{
		if(is_null($level2Key))	{
			$label = $this->get_LL('label_category_'.$level1Key);
		} else {
			$label = $this->get_LL('label_subcategory_'.$level2Key);
		}
		
		return '<a href="#" onclick="openLeaf(\''.$level1Key.'\'); loadModuleContent(\''.$level1Key.'\', \''.$level2Key.'\'); return false;">'.$label.'</a>';
	}
	
	
	/**
	 * Searches in all registered modules for a given string and returns a list of categories that contain
	 * the searchword.
	 *
	 * @param unknown_type $searchString
	 * @return unknown
	 */
	public function searchCategories()	{
		$searchString = $this->env['searchString'];
		
		$result = array(
			'searchString' => $searchString,
			'resultCount' => 0,
			'resultMessage' => '',
			'catMain' => array(),
			'catSub' => array()
		);
		$filterResults = array();
		
		if (strlen($searchString) >= 2)	{
		
			$this->basicsObject->getModuleCategoryData();
			$localLang = $this->pObj->getLocalLang();
			foreach ($localLang[$this->pObj->getLanguage()] as $index => $label)	{
				if (stripos($label, $searchString) !== false)	{
					$deliverableData = $this->pObj->getLabelIndexItem($index);
					if (!empty($deliverableData))	{
						$result['catMain'][$deliverableData['mainCat']] = true;
						$result['catSub'][$deliverableData['subCat']][] = $deliverableData;
						$result['resultCount']++;
						$filterResults[$deliverableData['deliverable']] = true;
					}
				}
			}
			
			$result['resultMessage'] = sprintf($this->get_LL('msg_searchResultMessage'), $result['resultCount'], $searchString);
		}
		
		$this->pObj->setFilterResults($filterResults);
		return json_encode($result);
	}

}

?>