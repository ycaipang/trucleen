<?php

namespace Drupal\custom_checkout_services\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a service selection pane.
 *
 * @CommerceCheckoutPane(
 *   id = "service_selection",
 *   label = @Translation("Service Selection"),
 *   default_step = "order_information",
 * )
 */
class ServiceSelectionPane extends CheckoutPaneBase {

    /**
 * {@inheritdoc}
 */

  // Builds the form for the checkout pane.
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['service_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Service'),
      '#options' => [
        'pickup' => $this->t('Pickup (Free)'),
        'delivery' => $this->t('Delivery (+$10)'),
      ],
      '#default_value' => 'pickup',
      '#required' => TRUE,
    ];
    return $pane_form;
  }

  // Handles form submission.
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $service_type = $values['service_type'];
    $order = $this->order;

    // Remove existing adjustments (to avoid duplicate fees).
    foreach ($order->getAdjustments() as $adjustment) {
      if ($adjustment->getType() === 'custom_fee') {
        $order->removeAdjustment($adjustment);
      }
    }

    // Add delivery fee adjustment.
    if ($service_type === 'delivery') {
      $price = new \Drupal\commerce_price\Price('10', 'USD');
      $adjustment = \Drupal::service('commerce_order.adjustment_factory')->create([
        'type' => 'custom_fee',
        'label' => $this->t('Delivery Service'),
        'amount' => $price,
        'included' => FALSE,
      ]);
      $order->addAdjustment($adjustment);
    }

    $order->save();
  }

}