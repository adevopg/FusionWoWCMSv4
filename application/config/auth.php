<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @package FusionCMS
 * @since 8.3.2
 * @version 8.3.2
 * @link    https://github.com/FusionWowCMS/FusionCMS
 */

$config['account_encryption'] = "SPH"; // SPH, SRP, SRP6

$config['rbac'] = true;

$config['battle_net'] = true;


$config['legion_core'] = false; // https://github.com/dufernst/LegionCore-7.3.5

$config['battle_net_encryption'] = "SPH"; // SRP6_V2, SRP6_V1, SPH

$config['totp_secret'] = true;

$config['totp_secret_name'] = "token_key"; // token_key, totp_secret
