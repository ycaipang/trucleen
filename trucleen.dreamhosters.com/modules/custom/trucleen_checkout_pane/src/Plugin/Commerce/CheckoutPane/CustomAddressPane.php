<?php

namespace Drupal\trucleen_checkout_pane\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom adddress pane.
 *
 * @CommerceCheckoutPane(
 *   id = "trucleen_checkout_pane_custom_address",
 *   label = @Translation("Custom Address"),
 *  *   display_label = @Translation("Pick & Delivery"),
 *   default_step = "_sidebar",
 *   wrapper_element = "fieldset",
 * )
 */
class CustomAddressPane extends CheckoutPaneBase {

/**
 * {@inheritdoc}
 */

	public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form){
		$pane_form['name_group'] = [
			'#type' => 'container',
			'#attributes' => ['class' => ['name-group']],
		];

		$pane_form['name_group']['first_name'] = [
			'#type' => 'textfield',
			'#title' => $this->t('First Name'),
			'#size' => 60,
			'#maxlength' => 128,
		];

		$pane_form['name_group']['last_name'] = [
				'#type' => 'textfield',
				'#title' => $this->t('Last Name'),
				'#size' => 60,
				'#maxlength' => 128,
		];

		$pane_form['contact_group'] = [
			'#type' => 'container',
			'#attributes' => ['class' => ['contact-group']],
		];

		$pane_form['contact_group']['email'] = [
			'#type' => 'email',
			'#title' => $this->t('Email'),
			'#size' => 60,
			'#maxlength' => 128,
		];

		$pane_form['contact_group']['phone'] = [
				'#type' => 'tel',
				'#title' => $this->t('Phone'),
				'#size' => 20,
				'#maxlength' => 20,
		];

		$pane_form['street_address'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Street Address'),
			'#size' => 60,
			'#maxlength' => 128,
		];

		$pane_form['street_address_line_2'] = [
			'#type' => 'textfield',
			'#size' => 60,
			'#maxlength' => 128,
			'#placeholder' => $this->t('Apartment, suite, unit, etc. (optional)'),
		];

		$pane_form['town_city'] = [
			'#type' => 'select',
			'#title' => $this->t('Town/City'),
			'#options' => [
				'Sydney' => $this->t('Sydney'),
				'Melbourne' => $this->t('Melbourne'),
				'Brisbane' => $this->t('Brisbane'),
				'Perth' => $this->t('Perth'),
				'Adelaide' => $this->t('Adelaide'),
				'Hobart' => $this->t('Hobart'),
				'Darwin' => $this->t('Darwin'),
			],
		];

		$pane_form['state'] = [
			'#type' => 'select',
			'#title' => $this->t('State'),
			'#options' => [
					'Sydney' => $this->t('New South Wales'),
					'Melbourne' => $this->t('Victoria'),
					'Brisbane' => $this->t('Queensland'),
					'Perth' => $this->t('Western Australia'),
					'Adelaide' => $this->t('South Australia'),
					'TAS' => $this->t('Tasmania'),
					'Hobart' => $this->t('Northern Territory'),
					'Darwin' => $this->t('Australian Capital Territory'),
			],
		];

		$pane_form['country'] = [
			'#type' => 'textfield',
			'#title' => $this->t('Country'),
			'#size' => 30,
			'#maxlength' => 64,
			'#default_value' => 'Australia',
			'#attributes' => ['readonly' => 'readonly'],
		];

		return $pane_form;
	}
	
	public function isVisible() {
		// Check whether the order has an item Pickup & Delivery service.
		foreach ($this->order->getItems() as $order_item) {
			$purchased_entity = $order_item->getPurchasedEntity();
			if ($purchased_entity->get('field_pickup_delivery')->value == 1) {
			  return TRUE;
			}
		}
		
		return FALSE;
	}
}