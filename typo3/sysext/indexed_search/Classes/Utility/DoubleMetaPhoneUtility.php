<?php
namespace TYPO3\CMS\IndexedSearch\Utility;

/***************************************************************
 *	VERSION DoubleMetaphone Class 1.01
 *
 *	DESCRIPTION
 *
 *	  This class implements a "sounds like" algorithm developed
 *	  by Lawrence Philips which he published in the June, 2000 issue
 *	  of C/C++ Users Journal.  Double Metaphone is an improved
 *	  version of Philips' original Metaphone algorithm.
 *
 *	COPYRIGHT
 *
 *	  Copyright 2001, Stephen Woodbridge <woodbri@swoodbridge.com>
 *	  All rights reserved.
 *
 *	  http://swoodbridge.com/DoubleMetaPhone/
 *
 *	  This PHP translation is based heavily on the C implementation
 *	  by Maurice Aubrey <maurice@hevanet.com>, which in turn
 *	  is based heavily on the C++ implementation by
 *	  Lawrence Philips and incorporates several bug fixes courtesy
 *	  of Kevin Atkinson <kevina@users.sourceforge.net>.
 *
 *	  This module is free software; you may redistribute it and/or
 *	  modify it under the same terms as Perl itself.
 *
 *	CONTRIBUTIONS
 *
 *	  17-May-2002 Geoff Caplan	http://www.advantae.com
 *		Bug fix: added code to return class object which I forgot to do
 *		Created a functional callable version instead of the class version
 *		which is faster if you are calling this a lot.
 *
 ***************************************************************/

/**
 * TYPO3: Had to change name to "user_DoubleMetaPhone" from just "DoubleMetaPhone" because TYPO3 requires a user class to be prefixed so:
 * TYPO3: If you want to use this metaphone method instead of the default in the class.indexer.php you can enable it in the extension configuration
 * TYPO3: Of course you can write your own metaphone hook methods by taking this class and configuration as example (also see ext_localconf.php)
 */
class DoubleMetaPhoneUtility {

	//  properties
	/**
	 * @todo Define visibility
	 */
	public $original = '';

	/**
	 * @todo Define visibility
	 */
	public $primary = '';

	/**
	 * @todo Define visibility
	 */
	public $secondary = '';

	/**
	 * @todo Define visibility
	 */
	public $length = 0;

	/**
	 * @todo Define visibility
	 */
	public $last = 0;

	/**
	 * @todo Define visibility
	 */
	public $current = 0;

	//  methods
	// TYPO3 specific API to this class. BEGIN
	/**
	 * @todo Define visibility
	 */
	public function metaphone($string, $sys_language_uid = 0) {
		$res = $this->DoubleMetaPhone($string);
		// debug(array($string,$res['primary']));
		return $res['primary'];
	}

	// TYPO3 specific API to this class. END
	// Public method
	/**
	 * @todo Define visibility
	 */
	public function DoubleMetaPhone($string) {
		$this->primary = '';
		$this->secondary = '';
		$this->current = 0;
		$this->current = 0;
		$this->length = strlen($string);
		$this->last = $this->length - 1;
		$this->original = $string . '     ';
		$this->original = strtoupper($this->original);
		// skip this at beginning of word
		if ($this->StringAt($this->original, 0, 2, array('GN', 'KN', 'PN', 'WR', 'PS'))) {
			$this->current++;
		}
		// Initial 'X' is pronounced 'Z' e.g. 'Xavier'
		if (substr($this->original, 0, 1) == 'X') {
			$this->primary .= 'S';
			// 'Z' maps to 'S'
			$this->secondary .= 'S';
			$this->current++;
		}
		// main loop
		while (strlen($this->primary) < 4 || strlen($this->secondary < 4)) {
			if ($this->current >= $this->length) {
				break;
			}
			switch (substr($this->original, $this->current, 1)) {
			case 'A':

			case 'E':

			case 'I':

			case 'O':

			case 'U':

			case 'Y':
				if ($this->current == 0) {
					// all init vowels now map to 'A'
					$this->primary .= 'A';
					$this->secondary .= 'A';
				}
				$this->current += 1;
				break;
			case 'B':
				// '-mb', e.g. "dumb", already skipped over ...
				$this->primary .= 'P';
				$this->secondary .= 'P';
				if (substr($this->original, $this->current + 1, 1) == 'B') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			case 'Ç':
				$this->primary .= 'S';
				$this->secondary .= 'S';
				$this->current += 1;
				break;
			case 'C':
				// various gremanic
				if ($this->current > 1 && !$this->IsVowel($this->original, ($this->current - 2)) && $this->StringAt($this->original, $this->current - 1, 3, array('ACH')) && (substr($this->original, $this->current + 2, 1) != 'I' && (substr($this->original, $this->current + 2, 1) != 'E' || $this->StringAt($this->original, $this->current - 2, 6, array('BACHER', 'MACHER'))))) {
					$this->primary .= 'K';
					$this->secondary .= 'K';
					$this->current += 2;
					break;
				}
				// special case 'caesar'
				if ($this->current == 0 && $this->StringAt($this->original, $this->current, 6, array('CAESAR'))) {
					$this->primary .= 'S';
					$this->secondary .= 'S';
					$this->current += 2;
					break;
				}
				// italian 'chianti'
				if ($this->StringAt($this->original, $this->current, 4, array('CHIA'))) {
					$this->primary .= 'K';
					$this->secondary .= 'K';
					$this->current += 2;
					break;
				}
				if ($this->StringAt($this->original, $this->current, 2, array('CH'))) {
					// find 'michael'
					if ($this->current > 0 && $this->StringAt($this->original, $this->current, 4, array('CHAE'))) {
						$this->primary .= 'K';
						$this->secondary .= 'X';
						$this->current += 2;
						break;
					}
					// greek roots e.g. 'chemistry', 'chorus'
					if ($this->current == 0 && ($this->StringAt($this->original, $this->current + 1, 5, array('HARAC', 'HARIS')) || $this->StringAt($this->original, $this->current + 1, 3, array('HOR', 'HYM', 'HIA', 'HEM'))) && !$this->StringAt($this->original, 0, 5, array('CHORE'))) {
						$this->primary .= 'K';
						$this->secondary .= 'K';
						$this->current += 2;
						break;
					}
					// germanic, greek, or otherwise 'ch' for 'kh' sound
					if ($this->StringAt($this->original, 0, 4, array('VAN ', 'VON ')) || $this->StringAt($this->original, 0, 3, array('SCH')) || $this->StringAt($this->original, $this->current - 2, 6, array('ORCHES', 'ARCHIT', 'ORCHID')) || $this->StringAt($this->original, $this->current + 2, 1, array('T', 'S')) || ($this->StringAt($this->original, $this->current - 1, 1, array('A', 'O', 'U', 'E')) || $this->current == 0) && $this->StringAt($this->original, $this->current + 2, 1, array('L', 'R', 'N', 'M', 'B', 'H', 'F', 'V', 'W', ' '))) {
						$this->primary .= 'K';
						$this->secondary .= 'K';
					} else {
						if ($this->current > 0) {
							if ($this->StringAt($this->original, 0, 2, array('MC'))) {
								// e.g. 'McHugh'
								$this->primary .= 'K';
								$this->secondary .= 'K';
							} else {
								$this->primary .= 'X';
								$this->secondary .= 'K';
							}
						} else {
							$this->primary .= 'X';
							$this->secondary .= 'X';
						}
					}
					$this->current += 2;
					break;
				}
				// e.g. 'czerny'
				if ($this->StringAt($this->original, $this->current, 2, array('CZ')) && !$this->StringAt($this->original, ($this->current - 2), 4, array('WICZ'))) {
					$this->primary .= 'S';
					$this->secondary .= 'X';
					$this->current += 2;
					break;
				}
				// e.g. 'focaccia'
				if ($this->StringAt($this->original, $this->current + 1, 3, array('CIA'))) {
					$this->primary .= 'X';
					$this->secondary .= 'X';
					$this->current += 3;
					break;
				}
				// double 'C', but not McClellan'
				if ($this->StringAt($this->original, $this->current, 2, array('CC')) && !($this->current == 1 && substr($this->original, 0, 1) == 'M')) {
					// 'bellocchio' but not 'bacchus'
					if ($this->StringAt($this->original, $this->current + 2, 1, array('I', 'E', 'H')) && !$this->StringAt($this->original, ($this->current + 2), 2, array('HU'))) {
						// 'accident', 'accede', 'succeed'
						if ($this->current == 1 && substr($this->original, $this->current - 1, 1) == 'A' || $this->StringAt($this->original, $this->current - 1, 5, array('UCCEE', 'UCCES'))) {
							$this->primary .= 'KS';
							$this->secondary .= 'KS';
						} else {
							$this->primary .= 'X';
							$this->secondary .= 'X';
						}
						$this->current += 3;
						break;
					} else {
						// Pierce's rule
						$this->primary .= 'K';
						$this->secondary .= 'K';
						$this->current += 2;
						break;
					}
				}
				if ($this->StringAt($this->original, $this->current, 2, array('CK', 'CG', 'CQ'))) {
					$this->primary .= 'K';
					$this->secondary .= 'K';
					$this->current += 2;
					break;
				}
				if ($this->StringAt($this->original, $this->current, 2, array('CI', 'CE', 'CY'))) {
					// italian vs. english
					if ($this->StringAt($this->original, $this->current, 3, array('CIO', 'CIE', 'CIA'))) {
						$this->primary .= 'S';
						$this->secondary .= 'X';
					} else {
						$this->primary .= 'S';
						$this->secondary .= 'S';
					}
					$this->current += 2;
					break;
				}
				// else
				$this->primary .= 'K';
				$this->secondary .= 'K';
				// name sent in 'mac caffrey', 'mac gregor'
				if ($this->StringAt($this->original, $this->current + 1, 2, array(' C', ' Q', ' G'))) {
					$this->current += 3;
				} else {
					if ($this->StringAt($this->original, $this->current + 1, 1, array('C', 'K', 'Q')) && !$this->StringAt($this->original, ($this->current + 1), 2, array('CE', 'CI'))) {
						$this->current += 2;
					} else {
						$this->current += 1;
					}
				}
				break;
			case 'D':
				if ($this->StringAt($this->original, $this->current, 2, array('DG'))) {
					if ($this->StringAt($this->original, $this->current + 2, 1, array('I', 'E', 'Y'))) {
						// e.g. 'edge'
						$this->primary .= 'J';
						$this->secondary .= 'J';
						$this->current += 3;
						break;
					} else {
						// e.g. 'edgar'
						$this->primary .= 'TK';
						$this->secondary .= 'TK';
						$this->current += 2;
						break;
					}
				}
				if ($this->StringAt($this->original, $this->current, 2, array('DT', 'DD'))) {
					$this->primary .= 'T';
					$this->secondary .= 'T';
					$this->current += 2;
					break;
				}
				// else
				$this->primary .= 'T';
				$this->secondary .= 'T';
				$this->current += 1;
				break;
			case 'F':
				if (substr($this->original, $this->current + 1, 1) == 'F') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'F';
				$this->secondary .= 'F';
				break;
			case 'G':
				if (substr($this->original, $this->current + 1, 1) == 'H') {
					if ($this->current > 0 && !$this->IsVowel($this->original, ($this->current - 1))) {
						$this->primary .= 'K';
						$this->secondary .= 'K';
						$this->current += 2;
						break;
					}
					if ($this->current < 3) {
						// 'ghislane', 'ghiradelli'
						if ($this->current == 0) {
							if (substr($this->original, $this->current + 2, 1) == 'I') {
								$this->primary .= 'J';
								$this->secondary .= 'J';
							} else {
								$this->primary .= 'K';
								$this->secondary .= 'K';
							}
							$this->current += 2;
							break;
						}
					}
					// Parker's rule (with some further refinements) - e.g. 'hugh'
					if ($this->current > 1 && $this->StringAt($this->original, $this->current - 2, 1, array('B', 'H', 'D')) || $this->current > 2 && $this->StringAt($this->original, $this->current - 3, 1, array('B', 'H', 'D')) || $this->current > 3 && $this->StringAt($this->original, $this->current - 4, 1, array('B', 'H'))) {
						$this->current += 2;
						break;
					} else {
						// e.g. 'laugh', 'McLaughlin', 'cough', 'gough', 'rough', 'tough'
						if ($this->current > 2 && substr($this->original, $this->current - 1, 1) == 'U' && $this->StringAt($this->original, $this->current - 3, 1, array('C', 'G', 'L', 'R', 'T'))) {
							$this->primary .= 'F';
							$this->secondary .= 'F';
						} elseif ($this->current > 0 && substr($this->original, $this->current - 1, 1) != 'I') {
							$this->primary .= 'K';
							$this->secondary .= 'K';
						}
						$this->current += 2;
						break;
					}
				}
				if (substr($this->original, $this->current + 1, 1) == 'N') {
					if ($this->current == 1 && $this->IsVowel($this->original, 0) && !$this->SlavoGermanic($this->original)) {
						$this->primary .= 'KN';
						$this->secondary .= 'N';
					} else {
						// not e.g. 'cagney'
						if (!$this->StringAt($this->original, ($this->current + 2), 2, array('EY')) && substr($this->original, $this->current + 1) != 'Y' && !$this->SlavoGermanic($this->original)) {
							$this->primary .= 'N';
							$this->secondary .= 'KN';
						} else {
							$this->primary .= 'KN';
							$this->secondary .= 'KN';
						}
					}
					$this->current += 2;
					break;
				}
				// 'tagliaro'
				if ($this->StringAt($this->original, $this->current + 1, 2, array('LI')) && !$this->SlavoGermanic($this->original)) {
					$this->primary .= 'KL';
					$this->secondary .= 'L';
					$this->current += 2;
					break;
				}
				// -ges-, -gep-, -gel- at beginning
				if ($this->current == 0 && (substr($this->original, $this->current + 1, 1) == 'Y' || $this->StringAt($this->original, $this->current + 1, 2, array(
					'ES',
					'EP',
					'EB',
					'EL',
					'EY',
					'IB',
					'IL',
					'IN',
					'IE',
					'EI',
					'ER'
				)))) {
					$this->primary .= 'K';
					$this->secondary .= 'J';
					$this->current += 2;
					break;
				}
				// -ger-, -gy-
				if (($this->StringAt($this->original, $this->current + 1, 2, array('ER')) || substr($this->original, $this->current + 1, 1) == 'Y') && !$this->StringAt($this->original, 0, 6, array('DANGER', 'RANGER', 'MANGER')) && !$this->StringAt($this->original, ($this->current - 1), 1, array('E', 'I')) && !$this->StringAt($this->original, ($this->current - 1), 3, array('RGY', 'OGY'))) {
					$this->primary .= 'K';
					$this->secondary .= 'J';
					$this->current += 2;
					break;
				}
				// italian e.g. 'biaggi'
				if ($this->StringAt($this->original, $this->current + 1, 1, array('E', 'I', 'Y')) || $this->StringAt($this->original, $this->current - 1, 4, array('AGGI', 'OGGI'))) {
					// obvious germanic
					if ($this->StringAt($this->original, 0, 4, array('VAN ', 'VON ')) || $this->StringAt($this->original, 0, 3, array('SCH')) || $this->StringAt($this->original, $this->current + 1, 2, array('ET'))) {
						$this->primary .= 'K';
						$this->secondary .= 'K';
					} else {
						// always soft if french ending
						if ($this->StringAt($this->original, $this->current + 1, 4, array('IER '))) {
							$this->primary .= 'J';
							$this->secondary .= 'J';
						} else {
							$this->primary .= 'J';
							$this->secondary .= 'K';
						}
					}
					$this->current += 2;
					break;
				}
				if (substr($this->original, $this->current + 1, 1) == 'G') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'K';
				$this->secondary .= 'K';
				break;
			case 'H':
				// only keep if first & before vowel or btw. 2 vowels
				if (($this->current == 0 || $this->IsVowel($this->original, $this->current - 1)) && $this->IsVowel($this->original, $this->current + 1)) {
					$this->primary .= 'H';
					$this->secondary .= 'H';
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			case 'J':
				// obvious spanish, 'jose', 'san jacinto'
				if ($this->StringAt($this->original, $this->current, 4, array('JOSE')) || $this->StringAt($this->original, 0, 4, array('SAN '))) {
					if ($this->current == 0 && substr($this->original, $this->current + 4, 1) == ' ' || $this->StringAt($this->original, 0, 4, array('SAN '))) {
						$this->primary .= 'H';
						$this->secondary .= 'H';
					} else {
						$this->primary .= 'J';
						$this->secondary .= 'H';
					}
					$this->current += 1;
					break;
				}
				if ($this->current == 0 && !$this->StringAt($this->original, $this->current, 4, array('JOSE'))) {
					$this->primary .= 'J';
					// Yankelovich/Jankelowicz
					$this->secondary .= 'A';
				} else {
					// spanish pron. of .e.g. 'bajador'
					if ($this->IsVowel($this->original, $this->current - 1) && !$this->SlavoGermanic($this->original) && (substr($this->original, $this->current + 1, 1) == 'A' || substr($this->original, $this->current + 1, 1) == 'O')) {
						$this->primary .= 'J';
						$this->secondary .= 'H';
					} else {
						if ($this->current == $this->last) {
							$this->primary .= 'J';
							$this->secondary .= '';
						} else {
							if (!$this->StringAt($this->original, ($this->current + 1), 1, array('L', 'T', 'K', 'S', 'N', 'M', 'B', 'Z')) && !$this->StringAt($this->original, ($this->current - 1), 1, array('S', 'K', 'L'))) {
								$this->primary .= 'J';
								$this->secondary .= 'J';
							}
						}
					}
				}
				if (substr($this->original, $this->current + 1, 1) == 'J') {
					// it could happen
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			case 'K':
				if (substr($this->original, $this->current + 1, 1) == 'K') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'K';
				$this->secondary .= 'K';
				break;
			case 'L':
				if (substr($this->original, $this->current + 1, 1) == 'L') {
					// spanish e.g. 'cabrillo', 'gallegos'
					if ($this->current == $this->length - 3 && $this->StringAt($this->original, $this->current - 1, 4, array('ILLO', 'ILLA', 'ALLE')) || ($this->StringAt($this->original, $this->last - 1, 2, array('AS', 'OS')) || $this->StringAt($this->original, $this->last, 1, array('A', 'O'))) && $this->StringAt($this->original, $this->current - 1, 4, array('ALLE'))) {
						$this->primary .= 'L';
						$this->secondary .= '';
						$this->current += 2;
						break;
					}
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'L';
				$this->secondary .= 'L';
				break;
			case 'M':
				if ($this->StringAt($this->original, $this->current - 1, 3, array('UMB')) && ($this->current + 1 == $this->last || $this->StringAt($this->original, $this->current + 2, 2, array('ER'))) || substr($this->original, $this->current + 1, 1) == 'M') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'M';
				$this->secondary .= 'M';
				break;
			case 'N':
				if (substr($this->original, $this->current + 1, 1) == 'N') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'N';
				$this->secondary .= 'N';
				break;
			case 'Ñ':
				$this->current += 1;
				$this->primary .= 'N';
				$this->secondary .= 'N';
				break;
			case 'P':
				if (substr($this->original, $this->current + 1, 1) == 'H') {
					$this->current += 2;
					$this->primary .= 'F';
					$this->secondary .= 'F';
					break;
				}
				// also account for "campbell" and "raspberry"
				if ($this->StringAt($this->original, $this->current + 1, 1, array('P', 'B'))) {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'P';
				$this->secondary .= 'P';
				break;
			case 'Q':
				if (substr($this->original, $this->current + 1, 1) == 'Q') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'K';
				$this->secondary .= 'K';
				break;
			case 'R':
				// french e.g. 'rogier', but exclude 'hochmeier'
				if ($this->current == $this->last && !$this->SlavoGermanic($this->original) && $this->StringAt($this->original, $this->current - 2, 2, array('IE')) && !$this->StringAt($this->original, ($this->current - 4), 2, array('ME', 'MA'))) {
					$this->primary .= '';
					$this->secondary .= 'R';
				} else {
					$this->primary .= 'R';
					$this->secondary .= 'R';
				}
				if (substr($this->original, $this->current + 1, 1) == 'R') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			case 'S':
				// special cases 'island', 'isle', 'carlisle', 'carlysle'
				if ($this->StringAt($this->original, $this->current - 1, 3, array('ISL', 'YSL'))) {
					$this->current += 1;
					break;
				}
				// special case 'sugar-'
				if ($this->current == 0 && $this->StringAt($this->original, $this->current, 5, array('SUGAR'))) {
					$this->primary .= 'X';
					$this->secondary .= 'S';
					$this->current += 1;
					break;
				}
				if ($this->StringAt($this->original, $this->current, 2, array('SH'))) {
					// germanic
					if ($this->StringAt($this->original, $this->current + 1, 4, array('HEIM', 'HOEK', 'HOLM', 'HOLZ'))) {
						$this->primary .= 'S';
						$this->secondary .= 'S';
					} else {
						$this->primary .= 'X';
						$this->secondary .= 'X';
					}
					$this->current += 2;
					break;
				}
				// italian & armenian
				if ($this->StringAt($this->original, $this->current, 3, array('SIO', 'SIA')) || $this->StringAt($this->original, $this->current, 4, array('SIAN'))) {
					if (!$this->SlavoGermanic($this->original)) {
						$this->primary .= 'S';
						$this->secondary .= 'X';
					} else {
						$this->primary .= 'S';
						$this->secondary .= 'S';
					}
					$this->current += 3;
					break;
				}
				// german & anglicisations, e.g. 'smith' match 'schmidt', 'snider' match 'schneider'
				// also, -sz- in slavic language altho in hungarian it is pronounced 's'
				if ($this->current == 0 && $this->StringAt($this->original, $this->current + 1, 1, array('M', 'N', 'L', 'W')) || $this->StringAt($this->original, $this->current + 1, 1, array('Z'))) {
					$this->primary .= 'S';
					$this->secondary .= 'X';
					if ($this->StringAt($this->original, $this->current + 1, 1, array('Z'))) {
						$this->current += 2;
					} else {
						$this->current += 1;
					}
					break;
				}
				if ($this->StringAt($this->original, $this->current, 2, array('SC'))) {
					// Schlesinger's rule
					if (substr($this->original, $this->current + 2, 1) == 'H') {
						// dutch origin, e.g. 'school', 'schooner'
						if ($this->StringAt($this->original, $this->current + 3, 2, array('OO', 'ER', 'EN', 'UY', 'ED', 'EM'))) {
							// 'schermerhorn', 'schenker'
							if ($this->StringAt($this->original, $this->current + 3, 2, array('ER', 'EN'))) {
								$this->primary .= 'X';
								$this->secondary .= 'SK';
							} else {
								$this->primary .= 'SK';
								$this->secondary .= 'SK';
							}
							$this->current += 3;
							break;
						} else {
							if ($this->current == 0 && !$this->IsVowel($this->original, 3) && substr($this->original, $this->current + 3, 1) != 'W') {
								$this->primary .= 'X';
								$this->secondary .= 'S';
							} else {
								$this->primary .= 'X';
								$this->secondary .= 'X';
							}
							$this->current += 3;
							break;
						}
					}
					if ($this->StringAt($this->original, $this->current + 2, 1, array('I', 'E', 'Y'))) {
						$this->primary .= 'S';
						$this->secondary .= 'S';
						$this->current += 3;
						break;
					}
					// else
					$this->primary .= 'SK';
					$this->secondary .= 'SK';
					$this->current += 3;
					break;
				}
				// french e.g. 'resnais', 'artois'
				if ($this->current == $this->last && $this->StringAt($this->original, $this->current - 2, 2, array('AI', 'OI'))) {
					$this->primary .= '';
					$this->secondary .= 'S';
				} else {
					$this->primary .= 'S';
					$this->secondary .= 'S';
				}
				if ($this->StringAt($this->original, $this->current + 1, 1, array('S', 'Z'))) {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			case 'T':
				if ($this->StringAt($this->original, $this->current, 4, array('TION'))) {
					$this->primary .= 'X';
					$this->secondary .= 'X';
					$this->current += 3;
					break;
				}
				if ($this->StringAt($this->original, $this->current, 3, array('TIA', 'TCH'))) {
					$this->primary .= 'X';
					$this->secondary .= 'X';
					$this->current += 3;
					break;
				}
				if ($this->StringAt($this->original, $this->current, 2, array('TH')) || $this->StringAt($this->original, $this->current, 3, array('TTH'))) {
					// special case 'thomas', 'thames' or germanic
					if ($this->StringAt($this->original, $this->current + 2, 2, array('OM', 'AM')) || $this->StringAt($this->original, 0, 4, array('VAN ', 'VON ')) || $this->StringAt($this->original, 0, 3, array('SCH'))) {
						$this->primary .= 'T';
						$this->secondary .= 'T';
					} else {
						$this->primary .= '0';
						$this->secondary .= 'T';
					}
					$this->current += 2;
					break;
				}
				if ($this->StringAt($this->original, $this->current + 1, 1, array('T', 'D'))) {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'T';
				$this->secondary .= 'T';
				break;
			case 'V':
				if (substr($this->original, $this->current + 1, 1) == 'V') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				$this->primary .= 'F';
				$this->secondary .= 'F';
				break;
			case 'W':
				// can also be in middle of word
				if ($this->StringAt($this->original, $this->current, 2, array('WR'))) {
					$this->primary .= 'R';
					$this->secondary .= 'R';
					$this->current += 2;
					break;
				}
				if ($this->current == 0 && ($this->IsVowel($this->original, $this->current + 1) || $this->StringAt($this->original, $this->current, 2, array('WH')))) {
					// Wasserman should match Vasserman
					if ($this->IsVowel($this->original, $this->current + 1)) {
						$this->primary .= 'A';
						$this->secondary .= 'F';
					} else {
						// need Uomo to match Womo
						$this->primary .= 'A';
						$this->secondary .= 'A';
					}
				}
				// Arnow should match Arnoff
				if ($this->current == $this->last && $this->IsVowel($this->original, $this->current - 1) || $this->StringAt($this->original, $this->current - 1, 5, array('EWSKI', 'EWSKY', 'OWSKI', 'OWSKY')) || $this->StringAt($this->original, 0, 3, array('SCH'))) {
					$this->primary .= '';
					$this->secondary .= 'F';
					$this->current += 1;
					break;
				}
				// polish e.g. 'filipowicz'
				if ($this->StringAt($this->original, $this->current, 4, array('WICZ', 'WITZ'))) {
					$this->primary .= 'TS';
					$this->secondary .= 'FX';
					$this->current += 4;
					break;
				}
				// else skip it
				$this->current += 1;
				break;
			case 'X':
				// french e.g. breaux
				if (!($this->current == $this->last && ($this->StringAt($this->original, $this->current - 3, 3, array('IAU', 'EAU')) || $this->StringAt($this->original, $this->current - 2, 2, array('AU', 'OU'))))) {
					$this->primary .= 'KS';
					$this->secondary .= 'KS';
				}
				if ($this->StringAt($this->original, $this->current + 1, 1, array('C', 'X'))) {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			case 'Z':
				// chinese pinyin e.g. 'zhao'
				if (substr($this->original, $this->current + 1, 1) == 'H') {
					$this->primary .= 'J';
					$this->secondary .= 'J';
					$this->current += 2;
					break;
				} elseif ($this->StringAt($this->original, $this->current + 1, 2, array('ZO', 'ZI', 'ZA')) || $this->SlavoGermanic($this->original) && ($this->current > 0 && substr($this->original, $this->current - 1, 1) != 'T')) {
					$this->primary .= 'S';
					$this->secondary .= 'TS';
				} else {
					$this->primary .= 'S';
					$this->secondary .= 'S';
				}
				if (substr($this->original, $this->current + 1, 1) == 'Z') {
					$this->current += 2;
				} else {
					$this->current += 1;
				}
				break;
			default:
				$this->current += 1;
			}
		}
		// end while
		$this->primary = substr($this->primary, 0, 4);
		$this->secondary = substr($this->secondary, 0, 4);
		$result['primary'] = $this->primary;
		$result['secondary'] = $this->secondary;
		return $result;
	}

	// end of function MetaPhone
	// Private methods
	/**
	 * @todo Define visibility
	 */
	public function StringAt($string, $start, $length, $list) {
		if ($start < 0 || $start >= strlen($string)) {
			return 0;
		}
		$listCount = count($list);
		for ($i = 0; $i < $listCount; $i++) {
			if ($list[$i] == substr($string, $start, $length)) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$string: ...
	 * @param 	[type]		$pos: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function IsVowel($string, $pos) {
		return preg_match('/[AEIOUY]/', substr($string, $pos, 1));
	}

	/**
	 * [Describe function...]
	 *
	 * @param 	[type]		$string: ...
	 * @return 	[type]		...
	 * @todo Define visibility
	 */
	public function SlavoGermanic($string) {
		return preg_match('/W|K|CZ|WITZ/', $string);
	}

}


?>