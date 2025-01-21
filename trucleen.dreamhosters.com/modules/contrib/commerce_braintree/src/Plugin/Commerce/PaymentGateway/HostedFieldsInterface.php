<?php

namespace Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the HostedFields payment gateway.
 */
interface HostedFieldsInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Creates a payment method nonce.
   *
   * Used by the review checkout pane.
   *
   * @return \Braintree\PaymentMethodNonce
   *   The PaymentMethodNonce response object.
   */
  public function createPaymentMethodNonce($payment_method_token);

  /**
   * Finds a payment method nonce.
   *
   * Used by the review checkout pane.
   *
   * @return \Braintree\PaymentMethodNonce
   *   The PaymentMethodNonce response object.
   */
  public function findPaymentMethodNonce($payment_method_nonce);

  /**
   * Generates the client token.
   *
   * Used by the add-payment-method plugin form and review checkout pane.
   *
   * @param string|null $currency_code
   *   The currency code.
   *
   * @return string
   *   The client token.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the merchant account is not configured for currency.
   */
  public function generateClientToken($currency_code = NULL);

}
