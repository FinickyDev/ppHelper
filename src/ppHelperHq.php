<?php
/**
 * PayPal Helper Headquarters
 *
 * @package FinickyDev\ppHelper
 * @author FinickyDev <finickydev@gmail.com>
 */

namespace FinickyDev\ppHelper;


class ppHelperHq
{
	/**
	 * Singleton Object
	 *
	 * @var $this
	 */
	private static $instance;
	
	
	/**
	 * Private Constructor
	 * - This is a static class, do not instantiate it.
	 */
	private function __construct()
	{
	}
	
	
	/**
	 * Disabling __clone call.
	 */
	public function __clone()
	{
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}
	
	
	/**
	 * We are controlling methods that are not included in this class from `FinickyDev\ppHelper\Library\Core\ConnectionModel`.
	 */
	public static function __callStatic($name, $arguments)
	{
		if(self::is_callableMethod('FinickyDev\ppHelper\Library\Core\ConnectionModel', $name, true))
		{
			return call_user_func_array('FinickyDev\ppHelper\Library\Core\ConnectionModel::' . $name, $arguments);
		}
		
		throw new \Exception('Call to undefined method ' . __CLASS__ . '::' . $name . '()');
	}
	
	
	/**
	 * Checks if the method is callable from the outside.
	 *
	 * @param mixed $class Target class
	 * @param string $methodName Method name
	 * @param boolean $isMethodStatic Is the method static ?
	 * @return boolean
	 */
	private static function is_callableMethod($class, $methodName, $isMethodStatic)
	{
		$reflectionClass = new \ReflectionClass($class);
		
		try
		{
			$method = $reflectionClass->getMethod($methodName);
			
			if($method->isPublic() && $isMethodStatic === $method->isStatic())
			{
				return true;
			}
		}
		catch(\ReflectionException $e)
		{}
		
		return false;
	}
	
	
	/**
	 * Returns the singleton object
	 *
	 * @return $this
	 */
	public static function getInstance()
	{
		if(! isset(self::$instance))
		{
			self::$instance = new self();
		}
		 
		 return self::$instance;
	}
	
	
	/**
	 * Create and get `BillingPlan` object
	 *
	 * @return FinickyDev\ppHelper\Library\RecurringPayments\BillingPlan
	 */
	public static function newBillingPlan()
	{
		return new FinickyDev\ppHelper\Library\RecurringPayments\BillingPlan();
	}
	
	
	/**
	 * Create and get `BillingAgreement` object
	 *
	 * @return FinickyDev\ppHelper\Library\RecurringPayments\BillingAgreement
	 */
	public static function newBillingAgreement()
	{
		return new FinickyDev\ppHelper\Library\RecurringPayments\BillingAgreement();
	}
}