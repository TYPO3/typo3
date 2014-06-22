<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
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
 * Defines constants used in the query object model.
 *
 * @deprecated since Extbase 1.1; use \TYPO3\CMS\Extbase\Persistence\QueryInterface::* instead
 */
interface QueryObjectModelConstantsInterface {

	/**
	 * An inner join.
	 */
	const JCR_JOIN_TYPE_INNER = '{http://www.jcp.org/jcr/1.0}joinTypeInner';

	/**
	 * A left-outer join.
	 */
	const JCR_JOIN_TYPE_LEFT_OUTER = '{http://www.jcp.org/jcr/1.0}joinTypeLeftOuter';

	/**
	 * A right-outer join.
	 */
	const JCR_JOIN_TYPE_RIGHT_OUTER = '{http://www.jcp.org/jcr/1.0}joinTypeRightOuter';

	/**
	 * The '=' comparison operator.
	 */
	const JCR_OPERATOR_EQUAL_TO = '{http://www.jcp.org/jcr/1.0}operatorEqualTo';

	/**
	 * The '!=' comparison operator.
	 */
	const JCR_OPERATOR_NOT_EQUAL_TO = '{http://www.jcp.org/jcr/1.0}operatorNotEqualTo';

	/**
	 * The '<' comparison operator.
	 */
	const JCR_OPERATOR_LESS_THAN = '{http://www.jcp.org/jcr/1.0}operatorLessThan';

	/**
	 * The '<=' comparison operator.
	 */
	const JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO = '{http://www.jcp.org/jcr/1.0}operatorLessThanOrEqualTo';

	/**
	 * The '>' comparison operator.
	 */
	const JCR_OPERATOR_GREATER_THAN = '{http://www.jcp.org/jcr/1.0}operatorGreaterThan';

	/**
	 * The '>=' comparison operator.
	 */
	const JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO = '{http://www.jcp.org/jcr/1.0}operatorGreaterThanOrEqualTo';

	/**
	 * The 'like' comparison operator.
	 */
	const JCR_OPERATOR_LIKE = '{http://www.jcp.org/jcr/1.0}operatorLike';

	/**
	 * Ascending order.
	 */
	const JCR_ORDER_ASCENDING = '{http://www.jcp.org/jcr/1.0}orderAscending';

	/**
	 * Descending order.
	 */
	const JCR_ORDER_DESCENDING = '{http://www.jcp.org/jcr/1.0}orderDescending';
}
