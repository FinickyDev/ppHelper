<?php
/**
 * PayPal Helper | Billing Plans
 *
 * @package FinickyDev\ppHelper\Library\RecurringPayments
 * @author FinickyDev <finickydev@gmail.com>
 */
 
namespace FinickyDev\ppHelper\Library\RecurringPayments;

use FinickyDev\ppHelper\Library\Core\ConnectionModel;

use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;


class BillingPlan extends ConnectionModel
{
	/**
	 * Default data for create billing plan
	 *
	 * @return array
	 */
	private function getDefaultData()
	{
		return [
			'name'										=> 'Example Billing Plan',
			'description'								=> 'Example Brand - Monthly Subscriptions',
			'currency'									=> 'USD',
			'plan_type'								=> 'INFINITE',
			'pd_name'									=> 'Regular Payment Definition',
			'pd_type'									=> 'REGULAR',
			'pd_frequency_interval'				=> '1',
			'pd_frequency'							=> 'MONTH',
			'pd_cycles'								=> '0',
			'mp_auto_bill_amount'				=> 'YES',
			'mp_initial_fail_amount_action'	=> 'CANCEL',
			'mp_max_fail_attempts'				=> '1'
		];
	}
	
	
	/**
	 * Get billing plan.
	 *
	 * @param string $id Unique identifier for billing plan.
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return PayPal\Api\Plan|array
	 */
	public function get($id)
	{
		return Plan::get($id, self::getApiContext());
	}
	
	
	/**
	 * Create billing plan.
	 *
	 *
	 * The `Billing Plan` or `$data parameter` takes an associative array with the following
	 * properties:
	 *
	 * - `name`: Billing plan name
	 *    (string, default: Example Billing Plan)
	 * - `description`: Billing plan description
	 *    (string, default: Example Brand - Monthly Subscriptions)
	 * - `currency`: Billing plan currency
	 *    (string, default: USD)
	 * - `amount`: Billing plan amount
	 *    (string)
	 * - `cancel_url`: If agreement execute is failure that return to this handler URL.
	 *    (string)
	 * - `return_url`: If agreement execute is success that return to this handler URL.
	 *    (string)
	 *
	 * @param array $data Billing plan properties. [See above]
	 * @throws Exception When required property is not defined for `Billing Plan` or `$data parameter` 
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return string|false Return `created billing plan id` OR `(false)`
	 */
	public function create(array $data)
	{
		$defaultData = $this->getDefaultData();
		
		$getData = function($prop) use($data, $defaultData) {
			// -*-
				if(array_key_exists($prop, $data))
				{
					return $data[$prop];
				}
				else if(array_key_exists($prop, $defaultData))
				{
					return $defaultData[$prop];
				}
				
				throw new \Exception('Required property for `Billing Plan` or `$data parameter`, could not found ! [propertyName = '. $prop .']', 404);
			// -*-
		};
		
		$price = new Currency();
		$price->setCurrency($getData('currency'));
		$price->setValue($getData('amount'));
		
		// PAYMENT DEFINITIONS		
		$paymentDefData = [
			"name"						=> $getData('pd_name'), // Regular Payment Definition
			"type"						=> $getData('pd_type'), // REGULAR
			"frequency_interval"	=> $getData('pd_frequency_interval'), // 1
			"frequency"				=> $getData('pd_frequency'), // MONTH
			"cycles"						=> $getData('pd_cycles'), // 0
			"amount"					=> $price,
		];
		$paymentDefinitions = new PaymentDefinition($paymentDefData);
		
		
		// MERCHANT PREFERENCES
		$merchantPrefData = [
			"cancel_url"							=> $getData('cancel_url'), // http://..../agreement-handler/cancel
			"return_url"							=> $getData('return_url'), // http://..../agreement-handler/success
			"auto_bill_amount"					=> $getData('auto_bill_amount'), // YES
			"initial_fail_amount_action"		=> $getData('initial_fail_amount_action'), // CANCEL
			"max_fail_attempts"				=> $getData('max_fail_attempts') // 1
		];
		$merchantPreferences = new MerchantPreferences($merchantPrefData);
		
		
		// BILLING PLAN
		$billingPlanData = [
			"name"							=> $getData('name'), // Example Billing Plan
			"description"					=> $getData('description'), // Example Brand - Monthly Subscriptions
			"type"							=> $getData('plan_type'), // INFINITE
			"payment_definitions"		=> array($paymentDefinitions),
			"merchant_preferences"	=> $merchantPreferences
		];
		
		// CREATE AND ACTIVATE IT !
		$billingPlan = new Plan($billingPlanData)->create(self::getApiContext());
		if(! $this->update($billingPlan->getId(), ["state" => "ACTIVE"]))
		{
			throw new \Exception('Created billing plan could not be activated ! Problematic Billing Plan ID = ['. $billingPlan->getId() .']');
		}

		return $billingPlan->getId();
	}
	
	/**
	 * Update billing plan.
	 *
	 * @param string $id Unique identifier for billing plan
	 * @param array $data Data to be updated
	 * @param string $path A JSON pointer that references a location in the target document where the operation is performed.
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return boolean
	 */
	public function update($id, array $data, $path = "/")
	{
		if($data)
		{
			$patchData = [
				'path'	=> $path,
				'op'		=> 'replace',
				'value'	=> (object) $data
			];
			
			$patch = new Patch($patchData);
			$patchRequest = new PatchRequest(['patches'  =>  array($patch)]);
			
			$billingPlan = new Plan();
			$billingPlan->setId($id);
			
			return $billingPlan->update($patchRequest, self::getApiContext());
		}
		
		return false;
	}
	
	
	/**
	 * Remove billing plan.
	 *
	 * @param string $id Unique identifier for billing plan
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return boolean
	 */
	public function remove($id)
	{
		return (new Plan())->setId($id)->delete(self::getApiContext());
	}
}