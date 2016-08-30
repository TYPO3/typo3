<?php
namespace TYPO3\TestDatahandler\Classes\Tca;

/**
 * Items processor for radio buttons for the functional tests of DataHandler::checkValue()
 */
class CheckboxElementItems
{
    /**
     * @return array
     */
    public function getItems($params)
    {
        $params['items'][] = ['processed label', ''];
    }
}
