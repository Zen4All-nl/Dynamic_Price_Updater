<?php

/**
 * Dynamic Price Updater V4.0
 * @copyright Dan Parry (Chrome) / Erik Kerkhoven (Design75)
 * @original author Dan Parry (Chrome)
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */
if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}

class DPU extends base {
/**
 * 
 * @global object $db
 * @param int $products_id
 * @return type
 */
  public function getOptionPricedIds($products_id)
  {
    global $db;
    // Identify the attribute information associated with the provided $products_id.
    $attribute_price_query = "SELECT *
                              FROM " . TABLE_PRODUCTS_ATTRIBUTES . "
                              WHERE products_id = " . (int)$products_id . "
                              ORDER BY options_id, options_values_price";

    $attribute_price = $db->Execute($attribute_price_query);

    $last_id = 'X';
    $options_id = [];

    // Populate $options_id to contain the options_ids that potentially affect price.
    while (!$attribute_price->EOF) {
      // Basically if the options_id has already been captured, then don't try to process again.
      if ($last_id == $attribute_price->fields['options_id']) {
        $attribute_price->MoveNext();
        continue;
      }

      /* Capture the options_id of option names that could affect price

        Identify an option name that could affect price by:
        having a price that is not zero,
        having quantity prices (though this is not (yet) deconstruct the prices and existing quantity),
        having a price factor that could affect the price,
        is a text field that has a word or letter price.
       */
      if (!(
              $attribute_price->fields['options_values_price'] == 0 &&
              !zen_not_null($attribute_price->fields['attributes_qty_prices']) &&
              !zen_not_null($attribute_price->fields['attributes_qty_prices_onetime']) &&
              $attribute_price->fields['attributes_price_onetime'] == 0 &&
              (
              $attribute_price->fields['attributes_price_factor'] ==
              $attribute_price->fields['attributes_price_factor_offset']
              ) &&
              (
              $attribute_price->fields['attributes_price_factor_onetime'] ==
              $attribute_price->fields['attributes_price_factor_onetime_offset']
              )
              ) ||
              (
              zen_get_attributes_type($attribute_price->fields['products_attributes_id']) == PRODUCTS_OPTIONS_TYPE_TEXT &&
              !($attribute_price->fields['attributes_price_words'] == 0 &&
              $attribute_price->fields['attributes_price_letters'] == 0)
              )
      ) {

        $prefix_format = 'id[:option_id:]';

        $attribute_type = zen_get_attributes_type($attribute_price->fields['products_attributes_id']);

        switch ($attribute_type) {
          case (PRODUCTS_OPTIONS_TYPE_TEXT):
            $prefix_format = $db->bindVars($prefix_format, ':option_id:', TEXT_PREFIX . ':option_id:', 'noquotestring');
            break;
          case (PRODUCTS_OPTIONS_TYPE_FILE):
            $prefix_format = $db->bindVars($prefix_format, ':option_id:', TEXT_PREFIX . ':option_id:', 'noquotestring');
            break;
          default:
            $GLOBALS['zco_notifier']->notify('NOTIFY_DYNAMIC_PRICE_UPDATER_ATTRIBUTE_ID_TEXT', $attribute_price->fields, $prefix_format, $options_id, $last_id);
        }

        $result = $db->bindVars($prefix_format, ':option_id:', $attribute_price->fields['options_id'], 'integer');
        $options_id[$attribute_price->fields['options_id']] = $result;
        $last_id = $attribute_price->fields['options_id'];

        $attribute_price->MoveNext();
        continue;
      }

      $attribute_price->MoveNext();
    }

    return $options_id;
  }

}
