<?php

// Include the abstract base
if (!class_exists('acm_memory'))
{
	require("{$root_path}lib/acm/acm_memory.$phpEx");
}

if (!defined('SITE_ACM_REDIS_PORT'))
{
	define('SITE_ACM_REDIS_PORT', 6379);
}

if (!defined('SITE_ACM_REDIS_HOST'))
{
	define('SITE_ACM_REDIS_HOST', 'localhost');
}

/**
* ACM for Redis
*
* Compatible with the php extension phpredis available
* at https://github.com/nicolasff/phpredis
*
* @package acm
*/
class acm extends acm_memory
{
	var $extension = 'redis';

	var $redis;

	function acm()
	{
		// Call the parent constructor
		parent::acm_memory();

		$this->redis = new Redis();
		$this->redis->connect(SITE_ACM_REDIS_HOST, SITE_ACM_REDIS_PORT);

		if (defined('SITE_ACM_REDIS_PASSWORD'))
		{
			if (!$this->redis->auth(SITE_ACM_REDIS_PASSWORD))
			{
				global $acm_type;

				trigger_error("Incorrect password for the ACM module $acm_type.", E_USER_ERROR);
			}
		}

		$this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
		$this->redis->setOption(Redis::OPT_PREFIX, $this->key_prefix);

		if (defined('SITE_ACM_REDIS_DB'))
		{
			if (!$this->redis->select(SITE_ACM_REDIS_DB))
			{
				global $acm_type;

				trigger_error("Incorrect database for the ACM module $acm_type.", E_USER_ERROR);
			}
		}
	}

	/**
	* Unload the cache resources
	*
	* @return null
	*/
	function unload()
	{
		parent::unload();

		$this->redis->close();
	}

	/**
	* Purge cache data
	*
	* @return null
	*/
	function purge()
	{
		$this->redis->flushDB();

		parent::purge();
	}

	/**
	* Fetch an item from the cache
	*
	* @access protected
	* @param string $var Cache key
	* @return mixed Cached data
	*/
	function _read($var)
	{
		return $this->redis->get($var);
	}

	/**
	* Store data in the cache
	*
	* @access protected
	* @param string $var Cache key
	* @param mixed $data Data to store
	* @param int $ttl Time-to-live of cached data
	* @return bool True if the operation succeeded
	*/
	function _write($var, $data, $ttl = 2592000)
	{
		return $this->redis->setex($var, $ttl, $data);
	}

	/**
	* Remove an item from the cache
	*
	* @access protected
	* @param string $var Cache key
	* @return bool True if the operation succeeded
	*/
	function _delete($var)
	{
		if ($this->redis->delete($var) > 0)
		{
			return true;
		}
		return false;
	}
}
