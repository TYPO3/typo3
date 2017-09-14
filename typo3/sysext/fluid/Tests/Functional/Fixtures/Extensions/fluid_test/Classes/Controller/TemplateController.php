<?php
namespace TYPO3Fluid\FluidTest\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class TemplateController
 */
class TemplateController extends ActionController
{
    public function baseTemplateAction()
    {
        $this->view->assign('objects', ['foo']);
    }
}
