<?php

use App\Models\Slot;
use App\Providers\Date\Facade\DateProvider;
use App\Repositories\PaymentRequestRepository;
use Models\User;



/**
*  PaymentHelper
*/
class PaymentHelper
{

    static $SAMPLE_ADDRESS_OFFSET = 1;

    function __construct(PaymentRequestRepository $payment_request_repository) {
        $this->payment_request_repository = $payment_request_repository;
    }

    public function newPayment(Slot $slot=null, $override_vars=[]) {
        if ($slot === null) { $slot = app('SlotHelper')->newSlot() ; }

        $create_vars = array_merge($this->sampleVars(), $override_vars);

        $create_vars['slotId'] = $slot['id'];

        return $this->payment_request_repository->create($create_vars);
    }

    public function sampleVars($override_vars=[]) {
        return array_merge([
            'address'   => '1MYSAMPLEADDRESS'.sprintf('%03d', self::$SAMPLE_ADDRESS_OFFSET++),
            'token'     => 'TOKENLY',
            'init_date' => DateProvider::now(),
            'IP'        => '127.0.0.1',

        ], $override_vars);
    }

    public function sampleDBVars($override_vars=[]) {
        return $this->sampleVars($override_vars);
    }


}

/*
CREATE TABLE `payment_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slotId` int(10) unsigned NOT NULL,
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `total` bigint(20) NOT NULL DEFAULT '0',
  `received` bigint(20) NOT NULL DEFAULT '0',
  `complete` tinyint(1) NOT NULL DEFAULT '0',
  `init_date` datetime NOT NULL,
  `complete_date` datetime DEFAULT NULL,
  `tx_info` mediumtext COLLATE utf8_unicode_ci,
  `IP` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `reference` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `payment_uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `monitor_uuid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cancelled` tinyint(1) NOT NULL DEFAULT '0',
  `cancel_time` datetime DEFAULT NULL,
  `swept` tinyint(1) NOT NULL DEFAULT '0',
  `sweep_info` text COLLATE utf8_unicode_ci,
  `peg` text COLLATE utf8_unicode_ci,
  `peg_value` bigint(20) DEFAULT NULL,
  `forward_address` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_requests_address_unique` (`address`),
  KEY `payment_requests_slotid_foreign` (`slotId`),
  KEY `payment_requests_reference_index` (`reference`),
  KEY `payment_requests_address_index` (`address`),
  KEY `payment_requests_payment_uuid_index` (`payment_uuid`),
  KEY `payment_requests_monitor_uuid_index` (`monitor_uuid`),
  CONSTRAINT `payment_requests_slotid_foreign` FOREIGN KEY (`slotId`) REFERENCES `slots` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 */