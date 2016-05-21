<?php

use App\Repositories\SlotRepository;
use Models\User;

/**
*  SlotHelper
*/
class SlotHelper
{
    static $SLOT_ID = 10001;

    function __construct(SlotRepository $slot_repository) {
        $this->slot_repository = $slot_repository;
    }

    public function newSlot(User $user=null, $override_vars=[]) {
        if ($user === null) { $user = app('UserHelper')->getSampleUser() ; }

        $create_vars = array_merge($this->sampleVars(), $override_vars);

        $create_vars['userId'] = $user['id'];

        return $this->slot_repository->create($create_vars);
    }

    public function sampleVars($override_vars=[]) {
        return array_merge([
            'public_id' => (isset($override_vars['public_id']) ? null : ++self::$SLOT_ID),
            'tokens'    => json_encode(['TOKENLY']),
        ], $override_vars);
    }

    public function sampleDBVars($override_vars=[]) {
        return $this->sampleVars($override_vars);
    }


}

/*
CREATE TABLE `slots` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `userId` int(10) unsigned NOT NULL,
    `public_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `tokens` text COLLATE utf8_unicode_ci NOT NULL,
    `webhook` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `min_conf` int(11) NOT NULL DEFAULT '0',
    `forward_address` text COLLATE utf8_unicode_ci,
    `label` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    `nickname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slots_public_id_unique` (`public_id`),
    KEY `slots_userid_foreign` (`userId`),
    KEY `slots_nickname_index` (`nickname`),
    CONSTRAINT `slots_userid_foreign` FOREIGN KEY (`userId`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 */