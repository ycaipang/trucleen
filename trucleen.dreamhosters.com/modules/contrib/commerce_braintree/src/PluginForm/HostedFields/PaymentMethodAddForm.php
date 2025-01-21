<?php

namespace Drupal\commerce_braintree\PluginForm\HostedFields;

use Drupal\commerce\InlineFormManager;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the HostedFields payment method add form.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  use MessengerTrait;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new PaymentMethodForm.
   *
   * @param \Drupal\commerce_store\CurrentStoreInterface $current_store
   *   The current store.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(CurrentStoreInterface $current_store, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager, LoggerInterface $logger, RouteMatchInterface $route_match) {
    parent::__construct($current_store, $entity_type_manager, $inline_form_manager, $logger);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_store.current_store'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form'),
      $container->get('logger.channel.commerce_payment'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $payment_method = $this->entity;
    if ($payment_method->bundle() === 'paypal_credit') {
      $form['payment_details'] = $this->buildPayPalForm($form['payment_details'], $form_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPayPalForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $plugin */
    $plugin = $this->plugin;

    $currency_code = NULL;
    if ($order = $this->routeMatch->getParameter('commerce_order')) {
      $currency_code = $order->getTotalPrice()->getCurrencyCode();
    }

    try {
      $client_token = $plugin->generateClientToken($currency_code);
    }
    catch (\InvalidArgumentException $exception) {
      throw new PaymentGatewayException($exception->getMessage());
    }

    $element['#attached']['library'][] = 'commerce_braintree/paypal';
    $element['#attached']['drupalSettings']['commerceBraintree'] = [
      'clientToken' => $client_token,
      'integration' => 'paypal',
      'paypalButton' => 'paypal-button',
      'environment' => ($plugin->getMode() == 'test') ? 'sandbox' : 'production',
      'paymentMethodType' => $this->entity->bundle(),
    ];
    $element['#attributes']['class'][] = 'braintree-form';

    $element['paypal_button'] = [
      '#type' => 'container',
      '#id' => 'paypal-button',
    ];

    // Populated by the JS library.
    $element['payment_method_nonce'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['braintree-nonce'],
      ],
    ];
    // Put the PayPal button below the billing address.
    $element['#weight'] = 50;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $plugin */
    $plugin = $this->plugin;

    $currency_code = NULL;
    if ($order = $this->routeMatch->getParameter('commerce_order')) {
      $currency_code = $order->getTotalPrice()->getCurrencyCode();
    }

    try {
      $client_token = $plugin->generateClientToken($currency_code);
    }
    catch (\InvalidArgumentException $exception) {
      throw new PaymentGatewayException($exception->getMessage());
    }

    $element['#attached']['library'][] = 'commerce_braintree/hosted-fields';
    $element['#attached']['drupalSettings']['commerceBraintree'] = [
      'clientToken' => $client_token,
      'integration' => 'custom',
      'hostedFields' => [
        'number' => ['selector' => '#card-number'],
        'cvv' => ['selector' => '#cvv'],
        'expirationMonth' => ['selector' => '#expiration-month'],
        'expirationYear' => ['selector' => '#expiration-year'],
      ],
    ];
    $element['#attributes']['class'][] = 'braintree-form';
    // Populated by the JS library.
    $element['payment_method_nonce'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['braintree-nonce'],
      ],
    ];
    $element['card_type'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['braintree-card-type'],
      ],
    ];
    $element['last2'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['braintree-last2'],
      ],
    ];

    // Display credit card logos in checkout form.
    if ($plugin->getConfiguration()['enable_credit_card_icons']) {
      $element['#attached']['library'][] = 'commerce_braintree/credit_card_icons';
      $element['#attached']['library'][] = 'commerce_payment/payment_method_icons';

      $supported_credit_cards = [];
      foreach ($plugin->getCreditCardTypes() as $credit_card) {
        $supported_credit_cards[] = $credit_card->getId();
      }

      $element['credit_card_logos'] = [
        '#theme' => 'commerce_braintree_credit_card_logos',
        '#credit_cards' => $supported_credit_cards,
      ];
    }

    $element['number'] = [
      '#type' => 'item',
      '#title' => t('Card number'),
      '#markup' => '<div id="card-number" class="braintree-hosted-field"></div>',
    ];

    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['credit-card-form__expiration'],
      ],
    ];
    $element['expiration']['month'] = [
      '#type' => 'item',
      '#title' => t('Month'),
      '#markup' => '<div id="expiration-month" class="braintree-hosted-field"></div>',
    ];
    $element['expiration']['divider'] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<span class="credit-card-form__divider">/</span>',
    ];
    $element['expiration']['year'] = [
      '#type' => 'item',
      '#title' => t('Year'),
      '#markup' => '<div id="expiration-year" class="braintree-hosted-field"></div>',
    ];
    $element['cvv'] = [
      '#type' => 'item',
      '#title' => t('CVV'),
      '#markup' => '<div id="cvv" class="braintree-hosted-field"></div>',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validatePayPalForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitPayPalForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

}
