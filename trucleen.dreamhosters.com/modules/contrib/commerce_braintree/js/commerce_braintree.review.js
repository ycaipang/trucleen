/**
 * @file
 * Defines behaviors for Braintree 3DS2 review checkout pane.
 */

(function ($, Drupal, drupalSettings, braintree, once) {

  'use strict';

  Drupal.behaviors.commerceBraintreeReview = {
    attach: function attach(context, settings) {
      const $form = $(once('braintree-attach', 'form#' + settings.commerceBraintree.formId, context));
      if ($form.length === 0) {
        return;
      }

      const waitForSdk = setInterval(function () {
        if (typeof braintree !== 'undefined') {
          let commerceBraintree = {};
          commerceBraintree = new Drupal.commerceBraintreeAuthorization($form, settings.commerceBraintree);
          $form.data('braintree', commerceBraintree);
          clearInterval(waitForSdk);
        }
      }, 100);

    },
    detach: function detach(context, settings, trigger) {
      if (trigger !== "unload") {
        return;
      }

      const $form = $(once('braintree-attach', 'form#' + settings.commerceBraintree.formId, context));
      if ($form.length === 0) {
        return;
      }
      const $submit = $form.find(':input[data-drupal-selector="edit-actions-next"]');
      const commerceBraintree = $form.data('braintree');
      // paypalCheckout doesn't have teardown() method.
      // See https://braintree.github.io/braintree-web/3.19.1/HostedFields.html
      // and https://braintree.github.io/braintree-web/3.19.1/PayPalCheckout.html
      if (commerceBraintree.integration.hasOwnProperty('teardown')) {
        commerceBraintree.integration.teardown();
      }
      $form.removeData('braintree');
      once.remove('braintree-attach', 'form#' + settings.commerceBraintree.formId, context);
      $form.off("submit.braintreeSubmit");
      $submit.prop('disabled', false);
    }
  };

  Drupal.commerceBraintreeAuthorization = function ($form, settings) {
    const $submit = $form.find(':input[data-drupal-selector="edit-actions-next"]');
    $form.append('<input type="hidden" name="_triggering_element_name" value="' + $submit.attr('name') + '" />');
    $form.append('<input type="hidden" name="_triggering_element_value" value="' + $submit.val() + '" />');
    const that = this;

    let clientData = {
      authorization: settings.clientToken
    };

    braintree.client.create(clientData, function (clientError, clientInstance) {
      if (clientError) {
        console.error(clientError);
        return;
      }
      let threeDSecureData = {
        // Use 3DS2 when possible.
        version: 2,
        client: clientInstance,
      };
      braintree.threeDSecure.create(threeDSecureData, function (threeDSecureErr, threeDSecureInstance) {
        if (threeDSecureErr) {
          console.error(threeDSecureErr);
          const message = that.errorMsg(threeDSecureErr);
          // Show the message above the form.
          $form.prepend(Drupal.theme('commerceBraintreeReviewError', message));
          $('html, body').animate({
            scrollTop: $('[role="alert"]').offset().top - 200
          }, 1000);
          return;
        }

        $submit.prop('disabled', false);

        $form.on('submit.braintreeSubmit', function (event, options) {
          options = options || {};
          if (options.tokenized) {
            // Tokenization complete, allow the form to submit.
            return;
          }

          event.preventDefault();
          $('.messages--error', $form).remove();

          threeDSecureInstance.on('lookup-complete', function (data, next) {
            console.log(data);
            next();
          });

          let verifyCardData = {
            nonce: settings.nonce,
            bin: settings.bin,
            amount: settings.amount,
            email: settings.email,
            challengeRequested: true,
            collectDeviceData: true,
          };

          threeDSecureInstance.verifyCard(verifyCardData, function (verifyCardError, payload) {
            if (verifyCardError) {
              console.error(verifyCardError);
              const message = that.errorMsg(verifyCardError);
              // Show the message above the form.
              $form.prepend(Drupal.theme('commerceBraintreeReviewError', message));
              $('html, body').animate({
                scrollTop: $('[role="alert"]').offset().top - 200
              }, 1000);
              return;
            }
            console.log(payload);
            $('.braintree-nonce', $form).val(payload.nonce);
            $form.trigger('submit', { 'tokenized' : true });
          });
        });
      });
    });
    return this;
  };

  Drupal.commerceBraintreeAuthorization.prototype.errorMsg = function (threeDSecureErr) {
    let message;

    switch (threeDSecureErr.code) {
      case 'THREEDS_NOT_ENABLED':
      case 'THREEDS_HTTPS_REQUIRED':
        message = Drupal.t('An error occurred while contacting the payment gateway.');
        break;
      case 'HOSTED_FIELDS_FIELDS_EMPTY':
        message = Drupal.t('Please enter your credit card details.');
        break;

      case 'HOSTED_FIELDS_FIELDS_INVALID':
        let fieldName = '';
        const fields = threeDSecureErr.details.invalidFieldKeys;
        if (fields.length > 0) {
          if (fields.length > 1) {
            const last = fields.pop();
            fieldName = fields.join(', ');
            fieldName += ' and ' + Drupal.t(last);
            message = Drupal.t('The @fields you entered are invalid.', {'@fields': fieldName});
          }
          else {
            fieldName = fields.pop();
            message = Drupal.t('The @field you entered is invalid.', {'@field': fieldName});
          }
        }
        else {
          message = Drupal.t('The payment details you entered are invalid.');
        }

        message += ' ' + Drupal.t('Please check your details and try again.');
        break;

      case 'HOSTED_FIELDS_TOKENIZATION_CVV_VERIFICATION_FAILED':
        message = Drupal.t('The CVV you entered is invalid.');
        message += ' ' + Drupal.t('Please check your details and try again.');
        break;

      case 'HOSTED_FIELDS_FAILED_TOKENIZATION':
        message = Drupal.t('An error occurred while contacting the payment gateway.');
        message += ' ' + Drupal.t('Please check your details and try again.');
        break;

      case 'HOSTED_FIELDS_TOKENIZATION_NETWORK_ERROR':
        message = Drupal.t('Could not connect to the payment gateway.');
        break;

      default:
        message = threeDSecureErr.message;
    }

    return message;
  };

  $.extend(Drupal.theme, /** @lends Drupal.theme */{
    commerceBraintreeReviewError: function (message) {
      return $('<div class="braintree-review-error" role="alert">' +
        '<div class="payment-messages payment-messages--error messages messages--error">' + message + '</div>' +
        '</div>'
      );
    }
  });

})(jQuery, Drupal, drupalSettings, window.braintree, once);
