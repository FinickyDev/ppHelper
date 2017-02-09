# ppHelper
A helper library written in PHP, for PayPal API. <br />
This library is supporting only `PayPal Recurring Payments` for the present.

## Installation

Install the latest version with

```bash
$ composer require finickydev/pphelper
```

## Basic Usage

```php
<?php

use FinickyDev\ppHelper\ppHelperHq;

// Set timezone
ppHelperHq::setTimezone('Europe/Istanbul');

// Set PayPal Client Credentials
ppHelperHq::setClientCredentials('pp_client_id', 'pp_client_secret');

// Set PayPal Client Settings
ppHelperHq::updateClientSettings(['mode' => 'sandbox']);

// Get PayPal Client Settings
$clientSettings = ppHelperHq::getClientSettings();

// Get billing plan details
$billingPlan = ppHelperHq::newBillingPlan()->get('billing_plan_id');

// Get billing agreement details
$billingAgreement = ppHelperHq::newBillingAgreement()->get('billing_agreement_id');

...
```

## About

### Author

FinickyDev - <finickydev@gmail.com>

### License

ppHelper is licensed under the MIT License - see the `LICENSE` file for details
