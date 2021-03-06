<?php

/**
 * This file is part of the Networking package.
 *
 * (c) net working AG <info@networking.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Networking\InitCmsBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use Networking\InitCmsBundle\Model\ContentRouteManager;
use Networking\InitCmsBundle\Model\PageInterface;
use Networking\InitCmsBundle\Model\PageManagerInterface;
use Networking\InitCmsBundle\Model\PageSnapshotInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LanguageSwitcherHelper.
 *
 * @author Yorkie Chadwick <y.chadwick@networking.ch>
 */
class LanguageSwitcherHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var PageManagerInterface
     */
    protected $pageManager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $om;

    /**
     * @var string
     */
    protected $fallbackRoute;

    /**
     * @var PageHelper
     */
    protected $pageHelper;


    /**
     * LanguageSwitcherHelper constructor.
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $om
     * @param PageHelper $pageHelper
     * @param PageManagerInterface $pageManager
     * @param RouterInterface $router
     * @param SerializerInterface $serializer
     * @param $fallbackRoute
     */
    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $om,
        PageHelper $pageHelper,
        PageManagerInterface $pageManager,
        RouterInterface $router,
        SerializerInterface $serializer,
        $fallbackRoute
    ) {
        $this->requestStack = $requestStack;
        $this->om = $om;
        $this->fallbackRoute = $fallbackRoute;
        $this->pageHelper = $pageHelper;
        $this->pageManager = $pageManager;
        $this->router = $router;
        $this->serializer = $serializer;
    }

    /**
     * Returns the corresponding route of the given URL for the locale supplied
     * If none is found it returns the original route object.
     *
     * @param $oldUrl
     * @param $oldLocale
     * @param $locale
     *
     * @return array|\Networking\InitCmsBundle\Component\Routing\Route|string
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getTranslationRoute($oldUrl, $oldLocale, $locale)
    {
        $cookies = $this->requestStack->getCurrentRequest()->cookies ? $this->requestStack->getCurrentRequest(
        )->cookies->all() : [];
        $oldRequest = Request::create($oldUrl, 'GET', [], $cookies);
        $oldRequest->setLocale($oldLocale);

        if ($this->requestStack->getCurrentRequest()->getSession()) {
            $oldRequest->setSession($this->requestStack->getCurrentRequest()->getSession());
        }

        try {
            $request = $this->pageHelper->matchContentRouteRequest($oldRequest);

        } catch (ResourceNotFoundException $e) {
            $request = $oldRequest;
        }

        if (!$content = $request->get('_content', false)) {
            try {
                $route = $this->router->matchRequest(Request::create($oldUrl));
            } catch (ResourceNotFoundException $e) {
                if ($route = $this->router->matchRequest(Request::create('/404'))) {
                    return $route;
                }
                throw new NotFoundHttpException(
                    sprintf('Could not find a translation to "%s" for this request"', $locale)
                );
            }

            if (!array_key_exists('_content', $route)) {
                return $route;
            }

            if (!$content = $route['_content']) {
                return $route;
            }
        }

        if ($content instanceof PageInterface) {
            $translation = $content->getAllTranslations()->get($locale);
            if (is_null($translation)) {
                //@todo does this make sense, or should we throw an exception
                return ['_route' => 'networking_init_cms_home'];
            }
            if (!is_null($translation)) {
                //return a contentRoute object
                $contentRoute = $translation->getContentRoute()->setContent($translation);

                return ContentRouteManager::generateRoute($contentRoute, $contentRoute->getPath(), '');
            }
        }

        if ($content instanceof PageSnapshotInterface) {
            $content = $this->pageHelper->unserializePageSnapshotData($content);

            $translation = $content->getAllTranslations()->get($locale);

            if ($translation && $snapshotId = $translation->getId()) {
                /** @var $snapshot PageSnapshotInterface */
                $snapshot = $this->om->getRepository($content->getSnapshotClassType())->findOneBy(
                    ['resourceId' => $snapshotId]
                );

                if ($snapshot) {
                    $contentRoute = $snapshot->getRoute();

                    return ContentRouteManager::generateRoute($contentRoute, $contentRoute->getPath(), '');
                }
            }
        }

        if ($this->fallbackRoute) {
            return $this->fallbackRoute;
        }

        if ($route = $this->router->matchRequest(Request::create('/404'))) {
            return $route;
        }

        //no valid translation found
        throw new NotFoundHttpException(
            sprintf('Could not find a translation to "%s" for content "%s"', $locale, $content->__toString())
        );
    }

    /**
     * Get the query string parameters for the current request
     * Not used at present.
     *
     * @return null|string
     */
    public function getQueryString()
    {
        $qs = Request::normalizeQueryString($this->requestStack->getCurrentRequest()->server->get('QUERY_STRING'));

        return '' === $qs ? null : $qs;
    }

    /**
     * Returns the uri Path for the current uri if no referrer given,
     * otherwise it returns the path for the URL given.
     *
     * @param null $referrer
     *
     * @return null|string
     */
    public function getPathInfo($referrer = null)
    {
        $baseUrl = $this->prepareBaseUrl($referrer);

        if (null === ($referrer)) {
            return '/';
        }

        $pathInfo = '/';

        // Remove the query string from REQUEST_URI
        if ($pos = strpos($referrer, '?')) {
            $referrer = substr($referrer, 0, $pos);
        }

        $host = $this->requestStack->getCurrentRequest()->getHost();
        if ((null !== $baseUrl) && (false === ($pathInfo = substr($referrer, strlen($baseUrl))))) {
            // If substr() returns false then PATH_INFO is set to an empty string
            return '/';
        } elseif (null === $baseUrl) {
            return $referrer;
        } elseif ($pos = strpos($pathInfo, $host)) {
            $pathInfo = substr($pathInfo, $pos + strlen($host));
        }

        return (string)$pathInfo;
    }

    /**
     * Prepares the Base URL.
     *
     * @param $referrer
     *
     * @return bool|string
     */
    public function prepareBaseUrl($referrer)
    {
        $filename = basename($this->requestStack->getCurrentRequest()->server->get('SCRIPT_FILENAME'));

        if (basename($this->requestStack->getCurrentRequest()->server->get('SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->requestStack->getCurrentRequest()->server->get('SCRIPT_NAME');
        } elseif (basename($this->requestStack->getCurrentRequest()->server->get('PHP_SELF')) === $filename) {
            $baseUrl = $this->requestStack->getCurrentRequest()->server->get('PHP_SELF');
        } elseif (basename($this->requestStack->getCurrentRequest()->server->get('ORIG_SCRIPT_NAME')) === $filename) {
            $baseUrl = $this->requestStack->getCurrentRequest()->server->get(
                'ORIG_SCRIPT_NAME'
            ); // 1and1 shared hosting compatibility
        } else {
            // Backtrack up the script_filename to find the portion matching
            // php_self
            $path = $this->requestStack->getCurrentRequest()->server->get('PHP_SELF', '');
            $file = $this->requestStack->getCurrentRequest()->server->get('SCRIPT_FILENAME', '');
            $segs = explode('/', trim($file, '/'));
            $segs = array_reverse($segs);
            $index = 0;
            $last = count($segs);
            $baseUrl = '';
            do {
                $seg = $segs[$index];
                $baseUrl = '/'.$seg.$baseUrl;
                ++$index;
            } while (($last > $index) && (false !== ($pos = strpos($path, $baseUrl))) && (0 != $pos));
        }

        // Does the baseUrl have anything in common with the request_uri?
        $requestUri = $referrer;

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, $baseUrl)) {
            // full $baseUrl matches
            return $prefix;
        }

        if ($baseUrl && false !== $prefix = $this->getUrlencodedPrefix($requestUri, dirname($baseUrl))) {
            // directory portion of $baseUrl matches
            return rtrim($prefix, '/');
        }

        $truncatedRequestUri = $requestUri;
        if (($pos = strpos($requestUri, '?')) !== false) {
            $truncatedRequestUri = substr($requestUri, 0, $pos);
        }

        $basename = basename($baseUrl);

        if (empty($basename) || !strpos(rawurldecode($truncatedRequestUri), $basename)) {
            // no match whatsoever; set it blank
            return '';
        }

        // If using mod_rewrite or ISAPI_Rewrite strip the script filename
        // out of baseUrl. $pos !== 0 makes sure it is not matching a value
        // from PATH_INFO or QUERY_STRING
        if ((strlen($requestUri) >= strlen($baseUrl)) && ((false !== ($pos = strpos(
                        $requestUri,
                        $baseUrl
                    ))) && ($pos !== 0))) {
            $baseUrl = substr($requestUri, 0, $pos + strlen($baseUrl));
        }

        return rtrim($baseUrl, '/');
    }

    /*
     * Returns the prefix as encoded in the string when the string starts with
     * the given prefix, false otherwise.
     *
     * @param string $string The urlencoded string
     * @param string $prefix The prefix not encoded
     *
     * @return string|false The prefix as it is encoded in $string, or false
     */
    /**
     * @param $string
     * @param $prefix
     *
     * @return bool
     */
    protected function getUrlencodedPrefix($string, $prefix)
    {
        if (0 !== strpos(rawurldecode($string), $prefix)) {
            return false;
        }

        $len = strlen($prefix);

        if (preg_match("#^(%[[:xdigit:]]{2}|.){{$len}}#", $string, $match)) {
            return $match[0];
        }

        return false;
    }
}
