<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * payment_DIBS.php
 *
 * This script handles payment via the danish payment gateway, DIBS.
 * Support: DIBS premium with Credit-cards  and Unibank Solo-E
 *
 * This script is used as a "handleScript" with the default productsLib.inc shopping system.
 *
 * DIBS:	http://www.dibs.dk
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */


if (!is_object($this) || !is_object($this->cObj))	die('$this and $this->cObj must be objects!');


// $lConf = $this->basketExtra["payment."]["handleScript."];		// Loads the handleScript TypoScript into $lConf.
$lConf = $conf;
$localTemplateCode = $this->cObj->fileResource($lConf[templateFile] ? $lConf[templateFile] : "media/scripts/payment_DIBS_template.tmpl");		// Fetches the DIBS template file
$localTemplateCode = $this->cObj->substituteMarkerArrayCached($localTemplateCode, $this->globalMarkerArray);

$orderUid = $this->getBlankOrderUid();		// Gets an order number, creates a new order if no order is associated with the current session

switch(t3lib_div::GPvar("products_cmd"))	{
	case "cardno":
		$tSubpart = $lConf["soloe"] ? "###DIBS_SOLOE_TEMPLATE###" : "###DIBS_CARDNO_TEMPLATE###";		// If solo-e is selected, use different subpart from template
		$content=$this->getBasket($tSubpart,$localTemplateCode);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!

		$markerArray=array();
		$markerArray["###HIDDEN_FIELDS###"] = '
<input type=hidden name=merchant value="'.$lConf["merchant"].'">
<input type=hidden name=amount value="'.round($this->calculatedSums_tax["total"]*100).'">
<input type=hidden name=currency value="'.$lConf["currency"].'">		<!--Valuta som angivet i ISO4217, danske kroner=208-->
<input type=hidden name=orderid value="'.$this->getOrderNumber($orderUid).'">		<!--Butikkens ordrenummer der skal knyttes til denne transaktion-->
<input type=hidden name=uniqueoid value="1">
<input type=hidden name="accepturl" value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf["relayURL"].'&products_cmd=accept&products_finalize=1&HTTP_COOKIE='.rawurlencode("fe_typo_user=".$GLOBALS["TSFE"]->fe_user->id).'">
<input type=hidden name="declineurl" value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf["relayURL"].'&products_cmd=decline&products_finalize=1&HTTP_COOKIE='.rawurlencode("fe_typo_user=".$GLOBALS["TSFE"]->fe_user->id).'">';	
		if ($lConf["soloe"])	{
		$markerArray["###HIDDEN_FIELDS###"].= '
<input type=hidden name="cancelurl" value="https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf["relayURL"].'&products_cmd=cancel&products_finalize=1&HTTP_COOKIE='.rawurlencode("fe_typo_user=".$GLOBALS["TSFE"]->fe_user->id).'">';
		}
		if ($lConf["test"])	{
			$markerArray["###HIDDEN_FIELDS###"].= '
				<input type=hidden name=test value="foo">
			';
		}
		if ($lConf["cardType"] && !$lConf["soloe"])	{
				/*
				Examples:
					DK 			Dankort
					V-DK 		Visa-Dankort
					MC(DK) 		Mastercard/Eurocard udstedt i Danmark
					VISA 		Visakort udstedt i udlandet
					MC 			Mastercard/Eurocard udstedt i udlandet
					DIN(DK) 	Diners Club, Danmark
					DIN 		Diners Club, international
				*/
		
			$markerArray["###HIDDEN_FIELDS###"].= '
				<input type=hidden name=cardtype value="'.$lConf["cardType"].'">
			';
		}
		if ($lConf["account"])	{		// DIBS account feature
			$markerArray["###HIDDEN_FIELDS###"].= '
				<input type=hidden name=account value="'.$lConf["account"].'">
			';
		}
		
		
				// Adds order info to hiddenfields.
		if ($lConf["addOrderInfo"])	{	
			$theFields="";
				// Delivery info
			reset($this->deliveryInfo);
			$cc=0;
			while(list($field,$value)=each($this->deliveryInfo))		{
				$value = trim($value);
				if ($value)	{
					$cc++;
					$theFields.=chr(10).'<input type=hidden name="delivery'.$cc.'.'.$field.'" value="'.htmlspecialchars($value).'">';
				}
			}
			
				// Order items
			reset($this->calculatedBasket);
			$theFields.='
<input type=hidden name="ordline1-1" value="Varenummer">
<input type=hidden name="ordline1-2" value="Beskrivelse">
<input type=hidden name="ordline1-3" value="Antal">
<input type=hidden name="ordline1-4" value="Pris">
';				
			$cc=1;
			while(list(,$rec)=each($this->calculatedBasket))		{
				$cc++;
				$theFields.='
<input type=hidden name="ordline'.$cc.'-1" value="'.htmlspecialchars($rec["rec"]["itemnumber"]).'">
<input type=hidden name="ordline'.$cc.'-2" value="'.htmlspecialchars($rec["rec"]["title"]).'">
<input type=hidden name="ordline'.$cc.'-3" value="'.$rec["count"].'">
<input type=hidden name="ordline'.$cc.'-4" value="'.$this->priceFormat($rec["totalTax"]).'">';
			}
		
			$theFields.='
<input type=hidden name="priceinfo1.Shipping" value="'.$this->priceFormat($this->calculatedSums_tax["shipping"]).'">';
			$theFields.='
<input type=hidden name="priceinfo2.Payment" value="'.$this->priceFormat($this->calculatedSums_tax["payment"]).'">';
			$theFields.='
<input type=hidden name="priceinfo3.Tax" value="'.$this->priceFormat($this->calculatedSums_tax["total"]-$this->calculatedSums_no_tax["total"]).'">';
			$markerArray["###HIDDEN_FIELDS###"].=$theFields;
		}
		$content= $this->cObj->substituteMarkerArrayCached($content, $markerArray);
	break;		
	case "decline":
		$markerArray=array();
		$markerArray["###REASON_CODE###"] = t3lib_div::GPvar("reason");
		$content=$this->getBasket("###DIBS_DECLINE_TEMPLATE###",$localTemplateCode, $markerArray);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
	break;
	case "cancel":
		$content=$this->getBasket("###DIBS_SOLOE_CANCEL_TEMPLATE###",$localTemplateCode, $markerArray);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
	break;
	case "accept":
		$content=$this->getBasket("###DIBS_ACCEPT_TEMPLATE###",$localTemplateCode);		// This is just done to calculate stuff

			// DIBS md5 keys
		$k1=$lConf["k1"];
		$k2=$lConf["k2"];
	
			// Checking transaction
		$amount=round($this->calculatedSums_tax["total"]*100);
		$currency="208";
		$transact=t3lib_div::GPvar("transact");
		$md5key= md5($k2.md5($k1."transact=".$transact."&amount=".$amount."&currency=".$currency));
		$authkey=t3lib_div::GPvar("authkey");
		if ($md5key != $authkey)	{
			$content=$this->getBasket("###DIBS_DECLINE_MD5_TEMPLATE###",$localTemplateCode);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		} elseif (t3lib_div::GPvar("orderid")!=$this->getOrderNumber($orderUid)) {
			$content=$this->getBasket("###DIBS_DECLINE_ORDERID_TEMPLATE###",$localTemplateCode);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		} else {
			$markerArray=array();
			$markerArray["###TRANSACT_CODE###"] = t3lib_div::GPvar("transact");

			$content=$this->getBasket("###BASKET_ORDERCONFIRMATION_TEMPLATE###","",$markerArray);
			$this->finalizeOrder($orderUid,$markerArray);	// Important: finalizeOrder MUST come after the call of prodObj->getBasket, because this function, getBasket, calculates the order! And that information is used in the finalize-function
		}
/*
		debug($md5key,1);
		debug($authkey,1);
		debug("transact");
		debug(t3lib_div::GPvar("transact"));
		debug($GLOBALS["TSFE"]->fe_user->id);
//				echo phpinfo();	
*/
	break;
	default:	
		if ($lConf["relayURL"])	{
			$markerArray=array();
			$markerArray["###REDIRECT_URL###"] = 'https://payment.architrade.com/cgi-ssl/relay.cgi/'.$lConf["relayURL"].'&products_cmd=cardno&products_finalize=1&HTTP_COOKIE='.rawurlencode("fe_typo_user=".$GLOBALS["TSFE"]->fe_user->id);
			$content=$this->getBasket("###DIBS_REDIRECT_TEMPLATE###",$localTemplateCode, $markerArray);		// This not only gets the output but also calculates the basket total, so it's NECESSARY!
		} else {
			$content = "NO .relayURL given!!";
		}
	break;
}
?>