<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Defines constants used in the query object model.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: QueryObjectModelConstantsInterface.php 2148 2010-03-30 09:28:45Z jocrau $
 * @deprecated since Extbase 1.1; use Tx_Extbase_Persistence_QueryInterface::* instead
 */
interface Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface {

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

?>