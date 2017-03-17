<?php

// Include the abstract base
if (!class_exists('acm_memory'))
{
	require("{$root_path}lib/acm/acm_memory.$phpEx");
}

if (!defined('SITE_ACM_MEMCACHE_PORT'))
{
	define('SITE_ACM_MEMCACHE_PORT', 11211);
}

if (!defined('SITE_ACM_MEMCACHE_COMPRESS'))
{
	define('SITE_ACM_MEMCACHE_COMPRESS', false);
}

if (!defined('SITE_ACM_MEMCACHE_HOST'))
{
	define('SITE_ACM_MEMCACHE_HOST', 'localhost');
}

if (!defined('SITE_ACM_MEMCACHE'))
{
	//can define multiple servers with host1/port1,host2/port2 format
	define('SITE_ACM_MEMCACHE', SITE_ACM_MEMCACHE_HOST . '/' . SITE_ACM_MEMCACHE_PORT);
}

/**
* ACM for Memcached
* @package acm
*/
class acm extends acm_memory
{
	var $extension = 'memcache';

	var $memcache;
	var $flags = 0;

	function acm()
	{
		// Call the parent constructor
		parent::acm_memory();

		$this->memcache = new Memcache;
		foreach(explode(',', SITE_ACM_MEMCACHE) as $u)
		{
			$parts = explode('/', $u);
			$this->memcache->addServer(trim($parts[0]), trim($parts[1]));
		}
		$this->flags = (SITE_ACM_MEMCACHE_COMPRESS) ? MEMCACHE_COMPRESSED : 0;
	}

	/**
	* Unload the cache resources
	*
	* @return null
	*/
	function unload()
	{
		parent::unload();

		$this->memcache->close();
	}

	/**
	* Purge cache data
	*
	* @return null
	*/
	function purge()
	{
		$this->memcache->flush();

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
		return $this->memcache->get($this->key_prefix . $var);
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
		if (!$this->memcache->replace($this->key_prefix . $var, $data, $this->flags, $ttl))
		{
			return $this->memcache->set($this->key_prefix . $var, $data, $this->flags, $ttl);
		}
		return true;
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
		return $this->memcache->delete($this->key_prefix . $var);
	}
}

?>
