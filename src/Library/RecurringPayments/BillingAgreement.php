<?php
/**
 * PayPal Helper | Billing Agreements
 *
 * @package FinickyDev\ppHelper\Library\RecurringPayments
 * @author FinickyDev <finickydev@gmail.com>
 */
 
namespace FinickyDev\ppHelper\Library\RecurringPayments;

use FinickyDev\ppHelper\Library\Core\ConnectionModel;
use Carbon\Carbon;

use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\Agreement;
use PayPal\Api\AgreementStateDescriptor;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
 
 
class BillingAgreement extends ConnectionModel
{
	/**
	 * Reason for changing the state of the billing agreement.
	 *
	 * @var string 
	 */
	const STATUS_REACTIVATE = 'Your subscription has been reactivated.';
	
	/**
	 * Reason for changing the state of the billing agreement.
	 *
	 * @var string
	 */
	const STATUS_SUSPEND = 'Your subscription has been suspended. Please contact us.';
	
	/**
	 * Reason for changing the state of the billing agreement.
	 *
	 * @var string
	 */
	const STATUS_CANCEL = 'Your subscription could not be activated due to a system error or was cancelled. Please contact us.';
	
	
	/**
	 * Default data for create billing agreement
	 *
	 * @return array
	 */
	private static function getDefaultData()
	{
		return [
			'name'						=> 'Example Billing Agreement',
			'description'				=> 'Example Subscription - per month $50',
			'start_date'				=> Carbon::now(self::getTimezone())->addMinutes(10)->toIso8601String(),
			'setupFee_currency'	=> 'USD',
			'setupFee_value'		=> '0'
		];
	}
	
	
	/**
	 * Get billing agreement.
	 *
	 * @param string $id Unique identifier for billing agreement
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return PayPal\Api\Agreement|array
	 */
	public function get($id)
	{
		return Agreement::get($id, self::getApiContext());
	}
	
	
	/**
	 * Create billing agreement.
	 *
	 * The `Billing Agreement` or `$data parameter` takes an associative array with the following
	 * properties:
	 *
	 * - `id`: Billing agreement id
	 *    (string)
	 * - `name`: Billing agreement name
	 *    (string, default: Example Billing Agreement)
	 * - `description`: Billing agreement description
	 *    (string, default: Example Subscription - per month $50)
	 * - `start_date`: Start date of the billing agreement. When in this date the first payment will taken from the customer.
	 *                        Date format yyyy-MM-dd z, as defined in ISO8601 | Example: 2005-08-15T15:52:01+0000
	 *    (string, default: [now + 10 minutes])
	 * - `setupFee_currency`: Billing agreement setup fee currency
	 *    (string, default: USD)
	 * - `setupFee_value`: Billing agreement setup fee value
	 *    (string, default: 0)
	 *
	 * @param array $data Billing agreement properties [See above]
	 * @throws Exception When required property is not defined for `Billing Agreement` or `$data parameter`.
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return string|false Returns the approval link for the buyer. | (false)
	 */
	public static function create(array $data)
	{
		$defaultData = self::getDefaultData();
		
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
				
				throw new \Exception('Required property for `Billing Agreement` or `$data parameter`, could not found ! [propertyName = ' . $prop . ']', 404);
				
			// -*-
		};
		
		$billingAgreementData = [
			'name'			=> $getData('name'),
			'description'	=> $getData('description'),
			'start_date'	=> $getData('start_date'),
			'plan'			=> ['id' => $getData('id')],
			'payer'			=> ['payment_method' => 'paypal']
		];
		
		// If setup fee is defined
		if(array_key_exists('setupFee_currency', $data) && array_key_exists('setupFee_value', $data))
		{
			$setupFee = new Currency();
			$setupFee->setCurrency($getData('setupFee_currency'));
			$setupFee->setValue($getData('setupFee_value'));
			
			$overrideMerchantPreferences = [
				"setup_fee" => $setupFee
			];
			
			$billingAgreementData["override_merchant_preferences"] = new MerchantPreferences($overrideMerchantPreferences);
		}
		
		$billingAgreement = new Agreement($billingAgreementData)->create(self::getApiContext());
		$approvalLink = $billingAgreement->getApprovalLink();
		
		return isset($approvalLink) ? $approvalLink : false;
	}
	
	
	/**
	 * Update billing agreement
	 *
	 * @param string $id Unique identifier for billing agreement
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
			
			$billingAgreement = new Agreement();
			$billingAgreement->setId($id);
			
			return $billingAgreement->update($patchRequest, self::getApiContext());
		}
		
		return false;
	}
	
	
	/**
	 * Change status of billing agreement accepted by the buyer
	 *
	 * @param string $id Unique identifier for billing agreement
	 * @param string $action This parameter can take 'suspend', 'reActivate' and 'cancel' values.
	 * @param array $reasons Reasons for changing the state of the billing agreement. Example Value: array('suspend' => 'This is a new reason', 'reActivate' => '...', 'cancel' => '...')
	 * @throws PayPal\Exception\PayPalConnectionException
	 * @return boolean
	 */
	public function changeStatus($id, $action, array $reasons = [])
	{
		// Reason for changing the state of the billing agreement.
		switch($action)
		{
			case "suspend";
			// *-*
				if(array_key_exists($action, $reasons))
				{
					$note = $reasons[$action];
				}
				else
				{
					$note = self::STATUS_SUSPEND;
				}
			// *-*
			break;
			
			case "reActivate";
			// *-*
				if(array_key_exists($action, $reasons))
				{
					$note = $reasons[$action];
				}
				else
				{
					$note = self::STATUS_REACTIVATE;
				}
			// *-*	
			break;

			case "cancel";
			// *-*
				if(array_key_exists($action, $reasons))
				{
					$note = $reasons[$action];
				}
				else
				{
					$note = self::STATUS_CANCEL;
				}
			// *-*
			break;

			default;
				return false;
			break;
		}
		
		$agreementStateDescriptor = new AgreementStateDescriptor(['note'  =>  $note]);
		$billingAgreement = new Agreement(['id'  =>  $id]);
		
		try
		{
			return $billingAgreement->{$action}($agreementStateDescriptor, self::getApiContext());
		}
		catch(PayPalConnectionException $e)
		{
			if($action == "cancel")
			{
				// If the billing agreement cancelled in the recent past, we can check this status.
				$error = json_decode($e->getData(), true);
				if($error["name"] === "STATUS_INVALID") 
				{
					return true;
				}
			}
			
			throw $e;
		}
		
		return false;
	}
	
	/**
	 * Execute a billing agreement after buyer approval by passing the payment token to the request URI.
	 *
	 * @param $paymentToken
	 * @return string The billing agreement id associated with $paymentToken
	 */
	public static function execute($paymentToken)
	{
		return (new Agreement())->execute($paymentToken, self::getApiContext())->getId();
	}
}