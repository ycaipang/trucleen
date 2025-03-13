<?php

namespace Drupal\trucleen_cart;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\OrderProcessorBase;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Applies a service fee based on the selected service option.
 */
class ServiceFeeOrderProcessor extends OrderProcessorBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new ServiceFeeOrderProcessor object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function process(OrderInterface $order) {
    $service_option = $order->get('field_service_option')->value;
    if (empty($service_option)) {
      return;
    }

    // Define fees for each service option (in cents).
    $fees = [
      'pickup' => 500,        // $5.00
      'delivery' => 1000,     // $10.00
      'pickup_delivery' => 1500, // $15.00
    ];

    $fee_amount = $fees[$service_option] ?? 0;

    if ($fee_amount > 0) {
      $currency_code = $order->getStore()->getDefaultCurrencyCode();
      $adjustments = $order->getAdjustments();

      // Remove existing adjustments from this processor.
      foreach ($adjustments as $key => $adjustment) {
        if ($adjustment->getSourceId() === 'custom_service_fee') {
          $order->removeAdjustment($key);
        }
      }

      // Add the new adjustment.
      $order->addAdjustment(new Adjustment([
        'type' => 'fee',
        'label' => t('Service Fee'),
        'amount' => new Price($fee_amount / 100, $currency_code),
        'source_id' => 'custom_service_fee',
      ]));
    }
  }

}