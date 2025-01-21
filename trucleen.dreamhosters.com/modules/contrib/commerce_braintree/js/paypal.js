/**
 * @file
 * Defines behaviors for the Braintree paypal checkout payment method form.
 */

(function ($, Drupal, drupalSettings, braintree) {

  'use strict';

  Drupal.commerceBraintreePaypal = function ($form, settings) {
    var $submit = $form.find(':input.button--primary');
    var that = this;
    braintree.client.create({
      authorization: settings.clientToken
    }, function (clientError, clientInstance) {
      if (clientError) {
        return;
      }
      // Disable the Continue button until we get a nonce.
      $submit.attr("disabled", "disabled");
      braintree.paypalCheckout.create({
        client: clientInstance
      }, function (paypalCheckoutError, paypalCheckoutInstance) {
        that.integration = paypalCheckoutInstance;
        paypalCheckoutInstance.loadPayPalSDK({
          vault: true
        }, function (loadPayPalSDKErr) {
          // Stop if there was a problem creating a PayPal Checkout.
          if (loadPayPalSDKErr) {
            return;
          }

          var renderOptions = {
            fundingSource: paypal.FUNDING.PAYPAL,

            createBillingAgreement: function () {
              var options = {
                flow: 'vault'
              };
              if (drupalSettings['commerceBraintree']['paymentMethodType'] === "paypal_credit") {
                options.offerCredit = true;
              }
              return paypalCheckoutInstance.createPayment(options);
            },

            onApprove: function (data, actions) {
              return paypalCheckoutInstance.tokenizePayment(data)
                .then(function (payload) {
                  // May be there is a better way to display email. In the old
                  // system Paypal was doing it automatically.
                  $('#paypal-button', $form).append('<div class="paypal-account">' + Drupal.t('PayPal account (') + payload.details.email + ')</div>');

                  // Hiding the button now that we have the nonce.
                  $('#paypal-button .paypal-buttons').hide();

                  // Submit 'payload.nonce' to the server.
                  $('.braintree-nonce', $form).val(payload.nonce);
                  // We have a nonce, let's enable the Continue button.
                  $submit.prop('disabled', false);
                });
            },

            onError: function (error) {
              // Show the message above the form.
              $form.prepend(Drupal.theme('commerceBraintreeError', that.errorMsg(error)));
              return;
            }
          };

          if (drupalSettings['commerceBraintree']['paymentMethodType'] === 'paypal_credit') {
            renderOptions.fundingSource = paypal.FUNDING.CREDIT;
          }

          paypal.Buttons(renderOptions).render('#paypal-button');
        });
      });
    });

    return this;
  };

})(jQuery, Drupal, drupalSettings, window.braintree);
