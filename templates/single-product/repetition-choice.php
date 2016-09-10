<?php
/**
 * Single Product Price Input
 *
 * @author 		Kathy Darling
 * @package 	WC_Name_Your_Price/Templates
 * @version     2.1
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>

<p class="systempay-subscription">

  Je veux donner<br>
  <label><input type="radio" name="systempay-subscription-repetition" value="cheque"> une seule fois par chèque<br></label>
  <label><input type="radio" name="systempay-subscription-repetition" value="1" checked> une seule fois par carte bancaire<br></label>
  <label><input type="radio" name="systempay-subscription-repetition" value="3"> tous les mois durant trois mois (carte bancaire)<br></label>
  <label><input type="radio" name="systempay-subscription-repetition" value="end"> tous les mois jusqu'à la fin de la campagne (carte bancaire)</label>

</p>
