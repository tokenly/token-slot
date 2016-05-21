<?php

use App\Repositories\UserRepository;
use Rhumsaa\Uuid\Uuid;

/**
*  UserHelper
*/
class UserHelper
{

    function __construct(UserRepository $user_repository) {
        $this->user_repository = $user_repository;
    }

    public function getSampleUser($email='sample@tokenly.co', $token=null, $username=null) {
        $user = $this->user_repository->findByEmail($email);
        if (!$user) {
            if ($token === null) { $token = $this->testingTokenFromEmail($email); }
            // if ($username === null) { $username = $this->usernameFromEmail($email); }
            $user = $this->newSampleUser(['email' => $email, 'api_key' => $token]);
        }
        return $user;
    }

    public function newRandomUser($override_vars=[]) {
        return $this->newSampleUser($override_vars, true);
    }

    public function createSampleUser($override_vars=[]) { return $this->newSampleUser($override_vars); }

    public function newSampleUser($override_vars=[], $randomize=false) {
        $create_vars = array_merge($this->sampleVars(), $override_vars);

        if ($randomize) {
            $create_vars['email'] = $this->randomEmail();
            // $create_vars['username'] = $this->usernameFromEmail($create_vars['email']);
            // $create_vars['apitoken'] = $this->testingTokenFromEmail($create_vars['email']);
            // $create_vars['tokenly_uuid'] = Uuid::uuid4()->toString();
        }

        return $this->user_repository->create($create_vars);
    }

    public function sampleVars($override_vars=[]) {
        return array_merge([
            // 'name'                => 'Sample User',
            'email'               => 'sample@tokenly.co',
            'forward_address'               => '1SAMPLEFORWARDHADDRESS',

            'api_key'             => 'TESTAPITOKEN',

        ], $override_vars);
    }

    public function sampleDBVars($override_vars=[]) {
        return $this->sampleVars($override_vars);
    }

    public function testingTokenFromEmail($email) {
        switch ($email) {
            case 'sample@tokenly.co': return 'TESTAPITOKEN';
            default:
                // user2@tokenly.co => TESTUSER2TOKENLYCO
                return substr('TEST'.strtoupper(preg_replace('!^[^a-z0-9]$!i', '', $email)), 0, 16);
        }
        // code
    }

    // public function usernameFromEmail($email) {
    //     return substr('t_'.strtoupper(preg_replace('!^[^a-z0-9]$!i', '', $email)), 0, 16);
    // }

    // public function randomEmail() {
    //     return 'u'.substr(md5(uniqid('', true)), 0, 6).'@tokenly.co';
    // }

/*
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `forward_address` text COLLATE utf8_unicode_ci NOT NULL,
  `activated` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_api_key_unique` (`api_key`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 */
}