<?php

/*
 * This file is part of the MobileDetectBundle.
 *
 * (c) Nikolay Ivlev <nikolay.kotovsky@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MobileDetectBundle\Helper;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author suncat2000 <nikolay.kotovsky@gmail.com>
 */
class DeviceView implements DeviceViewInterface
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
    protected $requestedViewType;

    /**
     * @var string
     */
    protected $viewType;

    /**
     * @var string
     */
    protected $cookieKey = self::COOKIE_KEY_DEFAULT;

    /**
     * @var string
     */
    protected $cookiePath = self::COOKIE_PATH_DEFAULT;

    /**
     * @var string
     */
    protected $cookieDomain = self::COOKIE_DOMAIN_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieSecure = self::COOKIE_SECURE_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieHttpOnly = self::COOKIE_HTTP_ONLY_DEFAULT;

    /**
     * @var bool
     */
    protected $cookieRaw = self::COOKIE_RAW_DEFAULT;

    /**
     * @var string|null
     */
    protected $cookieSameSite = self::COOKIE_SAMESITE_DEFAULT;

    /**
     * @var string
     */
    protected $cookieExpireDatetimeModifier = self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT;

    /**
     * @var string
     */
    protected $switchParam = self::SWITCH_PARAM_DEFAULT;

    /**
     * @var array
     */
    protected $redirectConfig = [];

    public function __construct(?RequestStack $requestStack = null)
    {
        if (!$requestStack || !$this->request = $requestStack->getMainRequest()) {
            $this->viewType = self::VIEW_NOT_MOBILE;

            return;
        }

        if ($this->request->query->has($this->switchParam)) {
            $this->viewType = $this->request->query->get($this->switchParam);
        } elseif ($this->request->cookies->has($this->cookieKey)) {
            $this->viewType = $this->request->cookies->get($this->cookieKey);
        }

        $this->requestedViewType = $this->viewType;
    }

    /**
     * Gets the view type for a device.
     */
    public function getViewType(): ?string
    {
        return $this->viewType;
    }

    /**
     * Gets the view type that has explicitly been requested either by switch param, or by cookie.
     *
     * @return string the requested view type or null if no view type has been explicitly requested
     */
    public function getRequestedViewType(): ?string
    {
        return $this->requestedViewType;
    }

    /**
     * Is the device in full view.
     */
    public function isFullView(): bool
    {
        return self::VIEW_FULL === $this->viewType;
    }

    public function isTabletView(): bool
    {
        return self::VIEW_TABLET === $this->viewType;
    }

    public function isMobileView(): bool
    {
        return self::VIEW_MOBILE === $this->viewType;
    }

    /**
     * Is not the device a mobile view type (PC, Mac, etc.).
     */
    public function isNotMobileView(): bool
    {
        return self::VIEW_NOT_MOBILE === $this->viewType;
    }

    /**
     * Has the Request the switch param in the query string (GET header).
     */
    public function hasSwitchParam(): bool
    {
        return $this->request && $this->request->query->has($this->switchParam);
    }

    public function setView(string $view): self
    {
        $this->viewType = $view;

        return $this;
    }

    /**
     * Sets the full (desktop) view type.
     */
    public function setFullView(): self
    {
        $this->viewType = self::VIEW_FULL;

        return $this;
    }

    public function setTabletView(): self
    {
        $this->viewType = self::VIEW_TABLET;

        return $this;
    }

    public function setMobileView(): self
    {
        $this->viewType = self::VIEW_MOBILE;

        return $this;
    }

    public function setNotMobileView(): self
    {
        $this->viewType = self::VIEW_NOT_MOBILE;

        return $this;
    }

    public function getRedirectConfig(): array
    {
        return $this->redirectConfig;
    }

    public function setRedirectConfig(array $redirectConfig): self
    {
        $this->redirectConfig = $redirectConfig;

        return $this;
    }

    /**
     * Get RedirectResponseWithCookie based on the switch parameter value.
     */
    public function getRedirectResponseBySwitchParam(string $redirectUrl): RedirectResponseWithCookie
    {
        switch ($this->getSwitchParamValue()) {
            case self::VIEW_MOBILE:
                $viewType = self::VIEW_MOBILE;
                break;
            case self::VIEW_TABLET:
                $viewType = self::VIEW_TABLET;

                if (isset($this->getRedirectConfig()['detect_tablet_as_mobile']) && true === $this->getRedirectConfig()['detect_tablet_as_mobile']) {
                    $viewType = self::VIEW_MOBILE;
                }
                break;
            default:
                $viewType = self::VIEW_FULL;
        }

        return new RedirectResponseWithCookie(
            $redirectUrl,
            $this->getStatusCode($viewType),
            $this->createCookie($viewType)
        );
    }

    /**
     * Gets the switch param value from the query string (GET header).
     */
    public function getSwitchParamValue(): ?string
    {
        if (!$this->request) {
            return null;
        }

        return $this->request->query->get($this->switchParam, self::VIEW_FULL);
    }

    public function getCookieExpireDatetimeModifier(): string
    {
        return $this->cookieExpireDatetimeModifier;
    }

    public function setCookieExpireDatetimeModifier(string $cookieExpireDatetimeModifier): self
    {
        $this->cookieExpireDatetimeModifier = $cookieExpireDatetimeModifier;

        return $this;
    }

    public function getCookieKey(): string
    {
        return $this->cookieKey;
    }

    public function setCookieKey(string $cookieKey): self
    {
        $this->cookieKey = $cookieKey;

        return $this;
    }

    public function getCookiePath(): string
    {
        return $this->cookiePath;
    }

    public function setCookiePath(string $cookiePath): self
    {
        $this->cookiePath = $cookiePath;

        return $this;
    }

    public function getCookieDomain(): string
    {
        return $this->cookieDomain;
    }

    public function setCookieDomain(string $cookieDomain): self
    {
        $this->cookieDomain = $cookieDomain;

        return $this;
    }

    public function isCookieSecure(): bool
    {
        return $this->cookieSecure;
    }

    public function setCookieSecure(bool $cookieSecure): self
    {
        $this->cookieSecure = $cookieSecure;

        return $this;
    }

    public function isCookieHttpOnly(): bool
    {
        return $this->cookieHttpOnly;
    }

    public function setCookieHttpOnly(bool $cookieHttpOnly): self
    {
        $this->cookieHttpOnly = $cookieHttpOnly;

        return $this;
    }

    public function isCookieRaw(): bool
    {
        return $this->cookieRaw;
    }

    public function setCookieRaw(bool $cookieRaw = false): self
    {
        $this->cookieRaw = $cookieRaw;

        return $this;
    }

    public function getCookieSameSite(): ?string
    {
        return $this->cookieSameSite;
    }

    public function setCookieSameSite(?string $cookieSameSite = Cookie::SAMESITE_LAX): self
    {
        $this->cookieSameSite = $cookieSameSite;

        return $this;
    }

    /**
     * Modifies the Response for the specified device view.
     *
     * @param string $view the device view for which the response should be modified
     */
    public function modifyResponse(string $view, Response $response): Response
    {
        $response->headers->setCookie($this->createCookie($view));

        return $response;
    }

    /**
     * Gets the RedirectResponse for the specified device view.
     *
     * @param string $view       The device view for which we want the RedirectResponse
     * @param string $host       Uri host
     * @param int    $statusCode Status code
     */
    public function getRedirectResponse(string $view, string $host, int $statusCode): RedirectResponseWithCookie
    {
        return new RedirectResponseWithCookie($host, $statusCode, $this->createCookie($view));
    }

    public function getSwitchParam(): string
    {
        return $this->switchParam;
    }

    public function setSwitchParam(string $switchParam): self
    {
        $this->switchParam = $switchParam;

        return $this;
    }

    /**
     * Get the status code for the specified device view.
     */
    protected function getStatusCode(string $view): int
    {
        return $this->getRedirectConfig()[$view]['status_code'] ?? Response::HTTP_FOUND;
    }

    /**
     * Create the Cookie object.
     */
    protected function createCookie(string $value): Cookie
    {
        try {
            $expire = new \DateTime($this->getCookieExpireDatetimeModifier());
        } catch (\Exception) {
            $expire = new \DateTime(self::COOKIE_EXPIRE_DATETIME_MODIFIER_DEFAULT);
        }

        return Cookie::create(
            $this->getCookieKey(),
            $value,
            $expire,
            $this->getCookiePath(),
            $this->getCookieDomain(),
            $this->isCookieSecure(),
            $this->isCookieHttpOnly(),
            $this->isCookieRaw(),
            $this->getCookieSameSite()
        );
    }
}
