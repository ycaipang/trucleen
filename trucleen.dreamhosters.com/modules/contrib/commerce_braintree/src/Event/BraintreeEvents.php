<?php

namespace Drupal\commerce_braintree\Event;

/**
 * Defines events for the Commerce Braintree module.
 */
class BraintreeEvents {

  /**
   * Name of the event fired to add additional transaction data.
   *
   * This event is triggered when a Charge transaction is going
   * to be created. It allows subscribers to add additional
   * transaction data and metadata about the transaction.
   *
   * @Event
   *
   * @see https://developers.braintreepayments.com/reference/request/transaction/sale/php
   * @see \Drupal\commerce_braintree\Event\TransactionDataEvent
   */
  const TRANSACTION_DATA = 'commerce_braintree.transaction_data';

}
