<?php
/**
 * PayPal Helper | Connection Model
 *
 * @package FinickyDev\ppHelper\Library\Core
 * @author FinickyDev <finickydev@gmail.com>
 */

namespace FinickyDev\ppHelper\Library\Core;

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;


class ConnectionModel
{
	/**
	 * PayPal ApiContext Object
	 *
	 * @var PayPal\Rest\ApiContext
	 */
	private static $api_context;
	
	/**
	 * PayPal APP Client ID
	 *
	 * @var string
	 */
	private static $client_id;
	
	
	/**
	 * PayPal APP Client Secret
	 *
	 * @var string
	 */
	private static $client_secret;
	
	
	/**
	 * PayPal APP Client Settings
	 *
	 * @var array
	 */
	private static $client_settings = [];
	
	
	/**
	 * TIMEZONE
	 *
	 * @var string
	 */
	private static $timezone = 'Europe/Istanbul';
	
	
	/**
	 * withoutAcApiCallsProcess Status
	 *
	 * This process with named `withoutAcApiCallsProcess` has to be done only one time while this class constructing. 
	 * This let ours allow to make api calls without ApiContext object.
	 *
	 * true  > done
	 * false > not done
	 *
	 * @var boolean
	 */
	private static $withoutAcApiCallsProcess = false;	
	
	
	/**
	 * ppClientSettingsApplied
	 *
	 * @var boolean
	 */
	private static $ppClientSettingsApplied = false;
	
	
	/**
	 * Constructor
	 * 
	 * @see FinickyDev\ppHelper\Library\Core\ConnectionModel::$withoutAcApiCallsProcess
	 */
	public function __construct()
	{
		if(self::$withoutAcApiCallsProcess === false)
		{
			if(self::$ppClientSettingsApplied === false)
			{
				PayPalConfigManager::getInstance()->addConfigs(self::getClientSettings());
				self::$ppClientSettingsApplied = true;
			}
			
			PayPalModel::setCredential(new OAuthTokenCredential(self::$client_id, self::$client_secret));
			self::$withoutAcApiCallsProcess = true;
		}
	}
	
	
	/**
	 * Get default settings for PayPal client
	 *
	 * @return array
	 */
	private static function getDefaultClientSettings()
	{
		return [
			// Available option 'sandbox' or 'live'
			'mode' => 'live',

			// Specify the max request time in seconds
			'http.ConnectionTimeOut' => 30,

			// Whether want to log to a file
			'log.LogEnabled' => true,

			//Specify the file that want to write on
			'log.FileName' => realpath('../../../logs/paypal.log'),

			/*
			 * Available option 'FINE', 'INFO', 'WARN' or 'ERROR'
			 *
			 * Logging is most verbose in the 'FINE' level and decreases as you
			 * proceed towards ERROR
			 */
			'log.LogLevel' => 'ERROR'
		];
	}
	
	
	/**
	 * Get PayPal client settings
	 *
	 * @return array
	 */
	public static function getClientSettings()
	{
		if(! self::$client_settings)
		{
			self::$client_settings = self::getDefaultClientSettings();
		}
		
		return self::$client_settings;
	}


	/**
	 * If PayPal client settings has not been applied still, you can call this function statically for update PayPal client settings.
	 *
	 * @see FinickyDev\ppHelper\Library\Core\ConnectionModel::getDefaultClientSettings()
	 * @param array $settings New settings for PayPal Client.
	 * @return void
	 */
	public static function updateClientSettings(array $settings)
	{
		self::$client_settings = array_merge(self::$client_settings, $settings);
	}


	/**
	 * Get ApiContext object
	 *
	 * We are using this function returning ApiContext object for create PayPal API calls.
	 * We don't have to use this function but we should use this function for it to be more yielding while make more than one calls 
	 * at the same time.
	 *
	 * @return PayPal\Rest\ApiContext
	 */
	protected static function getApiContext()
	{
		if(! isset(self::$api_context))
		{
			self::$api_context = new ApiContext(new OAuthTokenCredential(self::$client_id, self::$client_secret));
			
			// If PayPal client settings has been applied one time, we dont need apply them again.
			if(self::$ppClientSettingsApplied === false)
			{
				self::$api_context->setConfig(self::getClientSettings());
				self::$ppClientSettingsApplied = true;
			}
		}
		else
		{
			// We need to reset request id while multiple create calls using the same ApiContext object.
			self::$api_context->resetRequestId();
		}
		   
		return self::$api_context;
	}
	
	
	/**
	 * Set PayPal Client Credentials
	 *
	 * @param string $client_id PayPal APP Client ID
	 * @param string $client_secret PayPal APP Client Secret
	 * @return void
	 */
	public static function setClientCredentials($client_id, $client_secret)
	{
		self::$client_id = $client_id;
		self::$client_secret = $client_secret;
	}
	
	
	/**
	 * Get TIMEZONE
	 *
	 * @return string
	 */
	 public static function getTimezone()
	 {
		 return self::$timezone;
	 }
	
	
	/**
	 * Set TIMEZONE
	 *
	 * @param string $tz Timezone
	 * @return void
	 */
	public static function setTimezone($tz)
	{
		self::$timezone = $tz;
	}
}