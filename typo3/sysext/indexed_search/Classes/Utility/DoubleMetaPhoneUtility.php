<?php
namespace TYPO3\CMS\IndexedSearch\Utility;

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
 * TYPO3: Had to change name to "\TYPO3\CMS\IndexedSearch\Utility\DoubleMetaPhoneUtility" from just "DoubleMetaPhone" because TYPO3 requires a user class to be prefixed so:
 * TYPO3: If you want to use this metaphone method instead of the default in the indexer you can enable it in the extension configuration
 * TYPO3: Of course you can write your own metaphone hook methods by taking this class and configuration as example (also see ext_localconf.php)
 */
class DoubleMetaPhoneUtility
{
    /**
     * @var string
     */
    public $original = '';

    /**
     * @var string
     */
    public $primary = '';

    /**
     * @var string
     */
    public $secondary = '';

    /**
     * @var int
     */
    public $length = 0;

    /**
     * @var int
     */
    public $last = 0;

    /**
     * @var int
     */
    public $current = 0;

    //  methods
    // TYPO3 specific API to this class. BEGIN
    /**
     * Metaphone
     *
     * @param string $string
     * @param int $sys_language_uid
     * @return string
     */
    public function metaphone($string, $sys_language_uid = 0)
    {
        $res = $this->DoubleMetaPhone($string);
        return $res['primary'];
    }

    // TYPO3 specific API to this class. END
    // Public method
    /**
     * Double metaphone
     *
     * @param string $string
     * @return array
     */
    public function DoubleMetaPhone($string)
    {
        $this->primary = '';
        $this->secondary = '';
        $this->current = 0;
        $this->current = 0;
        $this->length = strlen($string);
        $this->last = $this->length - 1;
        $this->original = $string . '     ';
        $this->original = strtoupper($this->original);
        // skip this at beginning of word
        if ($this->StringAt($this->original, 0, 2, ['GN', 'KN', 'PN', 'WR', 'PS'])) {
            $this->current++;
        }
        // Initial 'X' is pronounced 'Z' e.g. 'Xavier'
        if ($this->original[0] === 'X') {
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
                    if ($this->current > 1 && !$this->IsVowel($this->original, ($this->current - 2)) && $this->StringAt($this->original, $this->current - 1, 3, ['ACH']) && (substr($this->original, $this->current + 2, 1) != 'I' && (substr($this->original, $this->current + 2, 1) != 'E' || $this->StringAt($this->original, $this->current - 2, 6, ['BACHER', 'MACHER'])))) {
                        $this->primary .= 'K';
                        $this->secondary .= 'K';
                        $this->current += 2;
                        break;
                    }
                    // special case 'caesar'
                    if ($this->current == 0 && $this->StringAt($this->original, $this->current, 6, ['CAESAR'])) {
                        $this->primary .= 'S';
                        $this->secondary .= 'S';
                        $this->current += 2;
                        break;
                    }
                    // italian 'chianti'
                    if ($this->StringAt($this->original, $this->current, 4, ['CHIA'])) {
                        $this->primary .= 'K';
                        $this->secondary .= 'K';
                        $this->current += 2;
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current, 2, ['CH'])) {
                        // find 'michael'
                        if ($this->current > 0 && $this->StringAt($this->original, $this->current, 4, ['CHAE'])) {
                            $this->primary .= 'K';
                            $this->secondary .= 'X';
                            $this->current += 2;
                            break;
                        }
                        // greek roots e.g. 'chemistry', 'chorus'
                        if ($this->current == 0 && ($this->StringAt($this->original, $this->current + 1, 5, ['HARAC', 'HARIS']) || $this->StringAt($this->original, $this->current + 1, 3, ['HOR', 'HYM', 'HIA', 'HEM'])) && !$this->StringAt($this->original, 0, 5, ['CHORE'])) {
                            $this->primary .= 'K';
                            $this->secondary .= 'K';
                            $this->current += 2;
                            break;
                        }
                        // germanic, greek, or otherwise 'ch' for 'kh' sound
                        if ($this->StringAt($this->original, 0, 4, ['VAN ', 'VON ']) || $this->StringAt($this->original, 0, 3, ['SCH']) || $this->StringAt($this->original, $this->current - 2, 6, ['ORCHES', 'ARCHIT', 'ORCHID']) || $this->StringAt($this->original, $this->current + 2, 1, ['T', 'S']) || ($this->StringAt($this->original, $this->current - 1, 1, ['A', 'O', 'U', 'E']) || $this->current == 0) && $this->StringAt($this->original, $this->current + 2, 1, ['L', 'R', 'N', 'M', 'B', 'H', 'F', 'V', 'W', ' '])) {
                            $this->primary .= 'K';
                            $this->secondary .= 'K';
                        } else {
                            if ($this->current > 0) {
                                if ($this->StringAt($this->original, 0, 2, ['MC'])) {
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
                    if ($this->StringAt($this->original, $this->current, 2, ['CZ']) && !$this->StringAt($this->original, ($this->current - 2), 4, ['WICZ'])) {
                        $this->primary .= 'S';
                        $this->secondary .= 'X';
                        $this->current += 2;
                        break;
                    }
                    // e.g. 'focaccia'
                    if ($this->StringAt($this->original, $this->current + 1, 3, ['CIA'])) {
                        $this->primary .= 'X';
                        $this->secondary .= 'X';
                        $this->current += 3;
                        break;
                    }
                    // double 'C', but not McClellan'
                    if ($this->StringAt($this->original, $this->current, 2, ['CC']) && !($this->current == 1 && $this->original[0] === 'M')) {
                        // 'bellocchio' but not 'bacchus'
                        if ($this->StringAt($this->original, $this->current + 2, 1, ['I', 'E', 'H']) && !$this->StringAt($this->original, ($this->current + 2), 2, ['HU'])) {
                            // 'accident', 'accede', 'succeed'
                            if ($this->current == 1 && substr($this->original, $this->current - 1, 1) == 'A' || $this->StringAt($this->original, $this->current - 1, 5, ['UCCEE', 'UCCES'])) {
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
                    if ($this->StringAt($this->original, $this->current, 2, ['CK', 'CG', 'CQ'])) {
                        $this->primary .= 'K';
                        $this->secondary .= 'K';
                        $this->current += 2;
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current, 2, ['CI', 'CE', 'CY'])) {
                        // italian vs. english
                        if ($this->StringAt($this->original, $this->current, 3, ['CIO', 'CIE', 'CIA'])) {
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
                    if ($this->StringAt($this->original, $this->current + 1, 2, [' C', ' Q', ' G'])) {
                        $this->current += 3;
                    } else {
                        if ($this->StringAt($this->original, $this->current + 1, 1, ['C', 'K', 'Q']) && !$this->StringAt($this->original, ($this->current + 1), 2, ['CE', 'CI'])) {
                            $this->current += 2;
                        } else {
                            $this->current += 1;
                        }
                    }
                    break;
                case 'D':
                    if ($this->StringAt($this->original, $this->current, 2, ['DG'])) {
                        if ($this->StringAt($this->original, $this->current + 2, 1, ['I', 'E', 'Y'])) {
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
                    if ($this->StringAt($this->original, $this->current, 2, ['DT', 'DD'])) {
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
                        if ($this->current > 1 && $this->StringAt($this->original, $this->current - 2, 1, ['B', 'H', 'D']) || $this->current > 2 && $this->StringAt($this->original, $this->current - 3, 1, ['B', 'H', 'D']) || $this->current > 3 && $this->StringAt($this->original, $this->current - 4, 1, ['B', 'H'])) {
                            $this->current += 2;
                            break;
                        } else {
                            // e.g. 'laugh', 'McLaughlin', 'cough', 'gough', 'rough', 'tough'
                            if ($this->current > 2 && substr($this->original, $this->current - 1, 1) == 'U' && $this->StringAt($this->original, $this->current - 3, 1, ['C', 'G', 'L', 'R', 'T'])) {
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
                            if (!$this->StringAt($this->original, ($this->current + 2), 2, ['EY']) && substr($this->original, $this->current + 1) != 'Y' && !$this->SlavoGermanic($this->original)) {
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
                    if ($this->StringAt($this->original, $this->current + 1, 2, ['LI']) && !$this->SlavoGermanic($this->original)) {
                        $this->primary .= 'KL';
                        $this->secondary .= 'L';
                        $this->current += 2;
                        break;
                    }
                    // -ges-, -gep-, -gel- at beginning
                    if ($this->current == 0 && (substr($this->original, $this->current + 1, 1) == 'Y' || $this->StringAt($this->original, $this->current + 1, 2, [
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
                    ]))) {
                        $this->primary .= 'K';
                        $this->secondary .= 'J';
                        $this->current += 2;
                        break;
                    }
                    // -ger-, -gy-
                    if (($this->StringAt($this->original, $this->current + 1, 2, ['ER']) || substr($this->original, $this->current + 1, 1) == 'Y') && !$this->StringAt($this->original, 0, 6, ['DANGER', 'RANGER', 'MANGER']) && !$this->StringAt($this->original, ($this->current - 1), 1, ['E', 'I']) && !$this->StringAt($this->original, ($this->current - 1), 3, ['RGY', 'OGY'])) {
                        $this->primary .= 'K';
                        $this->secondary .= 'J';
                        $this->current += 2;
                        break;
                    }
                    // italian e.g. 'biaggi'
                    if ($this->StringAt($this->original, $this->current + 1, 1, ['E', 'I', 'Y']) || $this->StringAt($this->original, $this->current - 1, 4, ['AGGI', 'OGGI'])) {
                        // obvious germanic
                        if ($this->StringAt($this->original, 0, 4, ['VAN ', 'VON ']) || $this->StringAt($this->original, 0, 3, ['SCH']) || $this->StringAt($this->original, $this->current + 1, 2, ['ET'])) {
                            $this->primary .= 'K';
                            $this->secondary .= 'K';
                        } else {
                            // always soft if french ending
                            if ($this->StringAt($this->original, $this->current + 1, 4, ['IER '])) {
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
                    if ($this->StringAt($this->original, $this->current, 4, ['JOSE']) || $this->StringAt($this->original, 0, 4, ['SAN '])) {
                        if ($this->current == 0 && substr($this->original, $this->current + 4, 1) == ' ' || $this->StringAt($this->original, 0, 4, ['SAN '])) {
                            $this->primary .= 'H';
                            $this->secondary .= 'H';
                        } else {
                            $this->primary .= 'J';
                            $this->secondary .= 'H';
                        }
                        $this->current += 1;
                        break;
                    }
                    if ($this->current == 0 && !$this->StringAt($this->original, $this->current, 4, ['JOSE'])) {
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
                                if (!$this->StringAt($this->original, ($this->current + 1), 1, ['L', 'T', 'K', 'S', 'N', 'M', 'B', 'Z']) && !$this->StringAt($this->original, ($this->current - 1), 1, ['S', 'K', 'L'])) {
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
                        if ($this->current == $this->length - 3 && $this->StringAt($this->original, $this->current - 1, 4, ['ILLO', 'ILLA', 'ALLE']) || ($this->StringAt($this->original, $this->last - 1, 2, ['AS', 'OS']) || $this->StringAt($this->original, $this->last, 1, ['A', 'O'])) && $this->StringAt($this->original, $this->current - 1, 4, ['ALLE'])) {
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
                    if ($this->StringAt($this->original, $this->current - 1, 3, ['UMB']) && ($this->current + 1 == $this->last || $this->StringAt($this->original, $this->current + 2, 2, ['ER'])) || substr($this->original, $this->current + 1, 1) == 'M') {
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
                    if ($this->StringAt($this->original, $this->current + 1, 1, ['P', 'B'])) {
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
                    if ($this->current == $this->last && !$this->SlavoGermanic($this->original) && $this->StringAt($this->original, $this->current - 2, 2, ['IE']) && !$this->StringAt($this->original, ($this->current - 4), 2, ['ME', 'MA'])) {
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
                    if ($this->StringAt($this->original, $this->current - 1, 3, ['ISL', 'YSL'])) {
                        $this->current += 1;
                        break;
                    }
                    // special case 'sugar-'
                    if ($this->current == 0 && $this->StringAt($this->original, $this->current, 5, ['SUGAR'])) {
                        $this->primary .= 'X';
                        $this->secondary .= 'S';
                        $this->current += 1;
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current, 2, ['SH'])) {
                        // germanic
                        if ($this->StringAt($this->original, $this->current + 1, 4, ['HEIM', 'HOEK', 'HOLM', 'HOLZ'])) {
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
                    if ($this->StringAt($this->original, $this->current, 3, ['SIO', 'SIA']) || $this->StringAt($this->original, $this->current, 4, ['SIAN'])) {
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
                    if ($this->current == 0 && $this->StringAt($this->original, $this->current + 1, 1, ['M', 'N', 'L', 'W']) || $this->StringAt($this->original, $this->current + 1, 1, ['Z'])) {
                        $this->primary .= 'S';
                        $this->secondary .= 'X';
                        if ($this->StringAt($this->original, $this->current + 1, 1, ['Z'])) {
                            $this->current += 2;
                        } else {
                            $this->current += 1;
                        }
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current, 2, ['SC'])) {
                        // Schlesinger's rule
                        if (substr($this->original, $this->current + 2, 1) == 'H') {
                            // dutch origin, e.g. 'school', 'schooner'
                            if ($this->StringAt($this->original, $this->current + 3, 2, ['OO', 'ER', 'EN', 'UY', 'ED', 'EM'])) {
                                // 'schermerhorn', 'schenker'
                                if ($this->StringAt($this->original, $this->current + 3, 2, ['ER', 'EN'])) {
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
                        if ($this->StringAt($this->original, $this->current + 2, 1, ['I', 'E', 'Y'])) {
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
                    if ($this->current == $this->last && $this->StringAt($this->original, $this->current - 2, 2, ['AI', 'OI'])) {
                        $this->primary .= '';
                        $this->secondary .= 'S';
                    } else {
                        $this->primary .= 'S';
                        $this->secondary .= 'S';
                    }
                    if ($this->StringAt($this->original, $this->current + 1, 1, ['S', 'Z'])) {
                        $this->current += 2;
                    } else {
                        $this->current += 1;
                    }
                    break;
                case 'T':
                    if ($this->StringAt($this->original, $this->current, 4, ['TION'])) {
                        $this->primary .= 'X';
                        $this->secondary .= 'X';
                        $this->current += 3;
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current, 3, ['TIA', 'TCH'])) {
                        $this->primary .= 'X';
                        $this->secondary .= 'X';
                        $this->current += 3;
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current, 2, ['TH']) || $this->StringAt($this->original, $this->current, 3, ['TTH'])) {
                        // special case 'thomas', 'thames' or germanic
                        if ($this->StringAt($this->original, $this->current + 2, 2, ['OM', 'AM']) || $this->StringAt($this->original, 0, 4, ['VAN ', 'VON ']) || $this->StringAt($this->original, 0, 3, ['SCH'])) {
                            $this->primary .= 'T';
                            $this->secondary .= 'T';
                        } else {
                            $this->primary .= '0';
                            $this->secondary .= 'T';
                        }
                        $this->current += 2;
                        break;
                    }
                    if ($this->StringAt($this->original, $this->current + 1, 1, ['T', 'D'])) {
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
                    if ($this->StringAt($this->original, $this->current, 2, ['WR'])) {
                        $this->primary .= 'R';
                        $this->secondary .= 'R';
                        $this->current += 2;
                        break;
                    }
                    if ($this->current == 0 && ($this->IsVowel($this->original, $this->current + 1) || $this->StringAt($this->original, $this->current, 2, ['WH']))) {
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
                    if ($this->current == $this->last && $this->IsVowel($this->original, $this->current - 1) || $this->StringAt($this->original, $this->current - 1, 5, ['EWSKI', 'EWSKY', 'OWSKI', 'OWSKY']) || $this->StringAt($this->original, 0, 3, ['SCH'])) {
                        $this->primary .= '';
                        $this->secondary .= 'F';
                        $this->current += 1;
                        break;
                    }
                    // polish e.g. 'filipowicz'
                    if ($this->StringAt($this->original, $this->current, 4, ['WICZ', 'WITZ'])) {
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
                    if (!($this->current == $this->last && ($this->StringAt($this->original, $this->current - 3, 3, ['IAU', 'EAU']) || $this->StringAt($this->original, $this->current - 2, 2, ['AU', 'OU'])))) {
                        $this->primary .= 'KS';
                        $this->secondary .= 'KS';
                    }
                    if ($this->StringAt($this->original, $this->current + 1, 1, ['C', 'X'])) {
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
                    } elseif ($this->StringAt($this->original, $this->current + 1, 2, ['ZO', 'ZI', 'ZA']) || $this->SlavoGermanic($this->original) && ($this->current > 0 && substr($this->original, $this->current - 1, 1) != 'T')) {
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
     * String at
     *
     * @param string $string
     * @param int $start
     * @param int $length
     * @param array $list
     * @return bool
     */
    public function StringAt($string, $start, $length, $list)
    {
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
     * Is vowel?
     *
     * @param string $string
     * @param int $pos
     * @return bool|int
     */
    public function IsVowel($string, $pos)
    {
        return preg_match('/[AEIOUY]/', substr($string, $pos, 1));
    }

    /**
     * Is slavogermanic?
     *
     * @param string $string
     * @return bool|int
     */
    public function SlavoGermanic($string)
    {
        return preg_match('/W|K|CZ|WITZ/', $string);
    }
}
