# Change Log
All notable changes to CacheSomething will be documented in this file.

## [1.1.7] 2026-10-27

* 5423fb6 - ParamCacheSomething::paramsToKey() - add support for array $params

## [1.1.6] 2026-10-27

* 1dd7e00 - CacheSomething::clearCache() - fix clearing by ids never actually removing anything from the cache
* 1647647 - ParamCacheSomething::clearCache() - fix clearing by ids corrupting that params bucket instead of removing the given ids
* 48fe5e3 - ParamCacheSomething::clearCache() - fix clearing by $params only wiping the whole cache instead of just that params bucket
* 51721e5 - ParamCacheSomething::getCached() - fix fatal error by overriding it with the correct ($params, $ids, $options) signature

## [1.1.5] 2026-10-26

* 9dacbe2 - ParamCacheSomething::paramsToKey() - use SplObjectStorage instead of spl_object_id() to avoid cache key collisions after object destruction
* 97b912e - ParamCacheSomething::paramsToKey() - use spl_object_id() instead of serialize() for objects without getId()

## [1.1.4] 2026-10-26

* 2862c9b - ParamCacheSomething::paramsToKey() fixed

## [1.1.3] 2026-10-19

* 889f21b - Fixed PHP deprecation: Using null as an array offset is deprecated, use an empty string instead

## [1.1.2] 2026-07-19

* 0c847cb - Added method CacheSomething::CacheObjectProperty()

## [1.1.1] 2026-06-08

* 539bb53 - Packahe atk14/cache-something is compatible with PHP>=7.1
* e7fc0fe - Fix

## [1.1] 2026-05-26

* 056fb3a - Added ParamCacheSomething class

## [1.0.1] 2026-01-28

* 971e361 - Fix for PHP8.5

## [1.0] 2026-01-08
