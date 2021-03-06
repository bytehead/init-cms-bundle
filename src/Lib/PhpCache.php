<?php
/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Lib;

umask(0002);

use Networking\InitCmsBundle\Cache\PageCache;
use Networking\InitCmsBundle\Cache\PageCacheInterface;
use phpFastCache;
use Symfony\Component\HttpFoundation\Request;

@trigger_error(sprintf('The "%s" class is deprecated since InitCms 4.1, use "%s" and type-hint for "%s" instead.', PhpCache::class, PageCache::class, PageCacheInterface::class), E_USER_DEPRECATED);

/**
 * Class PhpCache
 * @deprecated Will be removed in InitCMS 4.2. Using Symfony Cache basis instead for a more configurable app aligned caching experience.
 * @package Networking\InitCmsBundle\Lib
 */
class PhpCache implements PhpCacheInterface
{
    /**
     * @var phpFastCache
     */
    protected $phpFastCache;

    /**
     * @var string
     */
    protected $env;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string
     */
    protected $cacheTime;

    /**
     * @param string $type
     * @param string $rootDir
     * @param string $env
     * @param bool   $active
     * @param string $cacheTime
     * @param null   $securityKey possibilities are auto (domain name), a string or null
     *
     * @throws \Exception
     */
    public function __construct($type = '', $rootDir = '', $env = '', $active = false, $cacheTime = '', $securityKey = null)
    {
        $this->env = $env;

        $this->cacheDir = $this->createDir($rootDir, $env);
        $this->active = $active;
        $this->cacheTime = $cacheTime;
        if (is_null($securityKey)) {
            $securityKey = 'cache.storage.'.sha1(__FILE__);
        }

        phpFastCache::setup('storage', $type);
        phpFastCache::setup('path', $this->cacheDir);
        phpFastCache::setup('securityKey', $securityKey);
        $this->phpFastCache = new phpFastCache($type);
    }

    /**
     * @param $rootDir
     * @param $env
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function createDir($rootDir, $env)
    {
        if(substr($rootDir, -1) === '/'){
            $rootDir = substr($rootDir, 0, -1);
        }
        $cacheDir = sprintf('%s/../var/cache/%s/php_fast_cache',$rootDir, $env);
        if (!file_exists($cacheDir) || !is_writable($cacheDir)) {
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0777);
            }
            if (!is_writable($cacheDir)) {
                @chmod($cacheDir, 0777);
            }
            if (!file_exists($cacheDir) || !is_writable($cacheDir)) {
                throw new \Exception('Sorry, Please create '.$cacheDir.'/ and SET Mode 0777 or any Writable Permission!', 100);
            }
        }

        return $cacheDir;
    }

    /**
     * @param Request $request
     * @param $user
     *
     * @return bool
     */
    public function isCacheable(Request $request, $user)
    {
        if (!$this->active) {
            return false;
        }

        if ($user) {
            return false;
        }

        if ($request->hasSession() && $request->getSession()->get('no_cache', false)) {
            $request->getSession()->remove('no_cache');

            return false;
        }


        if ($request->getMethod() != 'GET') {
            return false;
        }

        return true;
    }

    /**
     * @param $keyword
     * @param array $option
     *
     * @return mixed|null|string
     */
    public function get($keyword, $option = [])
    {
        return $this->phpFastCache->instance->get($keyword, $option);
    }

    /**
     * @param $keyword
     * @param string $value
     * @param int    $time
     * @param array  $option
     *
     * @return array|bool|string
     */
    public function set($keyword, $value = '', $time = null, $option = [])
    {
        if (is_null($time)) {
            $time = $this->cacheTime;
        }

        return $this->phpFastCache->instance->set($keyword, $value, $time, $option);
    }

    /**
     * @param $keyword
     * @param array $options
     *
     * @return bool|\string[]
     */
    public function delete($keyword, $options = [])
    {
        return $this->phpFastCache->instance->delete($keyword, $options);
    }

    /**
     * @param array $option
     *
     * @return mixed
     */
    public function clean($option = [])
    {
        if ($this->active) {
            return $this->phpFastCache->instance->clean($option);
        }

        return false;
    }

    /**
     * @param $keyword
     * @param int   $time
     * @param array $option
     *
     * @return bool
     */
    public function touch($keyword, $time = null, $option = [])
    {
        if (is_null($time)) {
            $time = $this->cacheTime;
        }

        return $this->phpFastCache->instance->touch($keyword, $time, $option);
    }

    /**
     * @param $keyword
     * @param array $option
     */
    public function getInfo($keyword, $option = [])
    {
        return $this->phpFastCache->instance->getInfo($keyword, $option);
    }

    /**
     * @return phpFastCache
     */
    public function getPhpFastCache()
    {
        return $this->phpFastCache;
    }

    /**
     * @param phpFastCache $phpFastCache
     */
    public function setPhpFastCache($phpFastCache)
    {
        $this->phpFastCache = $phpFastCache;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->env;
    }

    /**
     * @param string $env
     */
    public function setEnv(string $env)
    {
        $this->env = $env;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * @param string $cacheDir
     */
    public function setCacheDir(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * @return string
     */
    public function getCacheTime(): string
    {
        return $this->cacheTime;
    }

    /**
     * @param string $cacheTime
     */
    public function setCacheTime(string $cacheTime)
    {
        $this->cacheTime = $cacheTime;
    }
}
