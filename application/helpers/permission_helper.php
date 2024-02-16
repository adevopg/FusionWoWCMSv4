<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Check if a user has permission to do a certain task
 *
 * @param String $permissionName
 * @param false|String $moduleName
 * @param false|Int $userId
 * @return Boolean
 */
function hasPermission(string $permissionName, false|string $moduleName = false, false|int $userId = false): bool
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->acl->hasPermission($permissionName, $moduleName, $userId);
}

/**
 * Check if a user has permission to see a menu link
 *
 * @param String $permissionName
 * @param String $moduleName
 * @return Boolean
 */
function hasViewPermission(string $permissionName, string $moduleName): bool|null
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->acl->hasViewPermission($permissionName, $moduleName);
}

/**
 * Check if a user has permission to do a certain task
 *
 * @param String $permissionName
 * @param false|String $moduleName
 * @return Boolean
 */
function requirePermission(string $permissionName, false|string $moduleName = false): bool
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->acl->requirePermission($permissionName, $moduleName);
}
