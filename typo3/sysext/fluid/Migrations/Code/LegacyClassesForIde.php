<?php
namespace {
    die('Access denied');
}

namespace TYPO3\CMS\Fluid\Core\Parser {
    interface InterceptorInterface extends \TYPO3Fluid\Fluid\Core\Parser\InterceptorInterface
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\Parser\SyntaxTree {
    interface NodeInterface extends \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\Rendering {
    interface RenderingContextInterface extends \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
    {
    }

}

namespace TYPO3\CMS\Fluid\Core\ViewHelper {
    interface ViewHelperInterface extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\ViewHelper\Facets {
    interface ChildNodeAccessInterface extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
    {
    }
    interface CompilableInterface extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
    {
    }
    interface PostParseInterface extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperInterface
    {
    }
}

namespace TYPO3\CMS\Fluid\Core {
    class Exception extends \TYPO3Fluid\Fluid\Core\Exception
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\ViewHelper {
    class Exception extends \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\ViewHelper\Exception {
    class InvalidVariableException extends \TYPO3Fluid\Fluid\Core\Exception
    {
    }
}

namespace TYPO3\CMS\Fluid\View {
    class Exception extends \TYPO3Fluid\Fluid\View\Exception
    {
    }
}

namespace TYPO3\CMS\Fluid\View\Exception {
    class InvalidSectionException extends \TYPO3Fluid\Fluid\View\Exception\InvalidSectionException
    {
    }
    class InvalidTemplateResourceException extends \TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
    {
    }

}

namespace TYPO3\CMS\Fluid\Core\Compiler {
    class TemplateCompiler extends \TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\Variables {
    class CmsVariableProvider extends \TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\Parser\SyntaxTree {
    class RootNode extends \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode
    {
    }
    class ViewHelperNode extends \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode
    {
    }
}

namespace TYPO3\CMS\Fluid\Core\ViewHelper {
    abstract class AbstractViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
    {
    }
    abstract class AbstractConditionViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper
    {
    }
    abstract class AbstractTagBasedViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
    {
    }
    class TemplateVariableContainer extends \TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider
    {
    }
    class ViewHelperVariableContainer extends \TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperVariableContainer
    {
    }
    class TagBuilder extends \TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder
    {
    }
}
