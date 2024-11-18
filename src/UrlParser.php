<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;

class UrlParser
{
    public function __construct(private readonly RequestStack|null $requestStack = null)
    {
    }

    /**
     * Add a query string to the given URI string or page ID.
     */
    public function addQueryString(string $query, string|null $url = null): string
    {
        $url = $this->prepareUrl($url);
        $query = trim(StringUtil::ampersand($query, false), '&');

        [$script, $queryString] = explode('?', $url, 2) + ['', ''];

        parse_str($queryString, $queries);

        $queries = array_filter($queries);
        unset($queries['language']);

        $href = '';

        if (\count($queries) > 0) {
            parse_str($query, $new);
            $href = '?'.http_build_query(array_merge($queries, $new), '', '&');
        } elseif ($query) {
            $href = '?'.$query;
        }

        return $script.$href;
    }

    /**
     * Remove query parameters from the current URL.
     */
    public function removeQueryString(array $params, string|null $url = null): string
    {
        $url = $this->prepareUrl($url);

        if (0 === \count($params)) {
            return $url;
        }

        [$script, $queryString] = explode('?', $url, 2) + ['', ''];

        parse_str($queryString, $queries);

        $queries = array_filter($queries);
        $queries = array_diff_key($queries, array_flip($params));

        $href = '';

        if (\count($queries) > 0) {
            $href .= '?'.http_build_query($queries, '', '&');
        }

        return $script.$href;
    }

    /**
     * Remove query parameters from the current URL using a callback method.
     */
    public function removeQueryStringCallback(callable $callback, string|null $url = null): string
    {
        $url = $this->prepareUrl($url);

        [$script, $queryString] = explode('?', $url, 2) + ['', ''];

        parse_str($queryString, $queries);

        // Cannot use array_filter because flags ARRAY_FILTER_USE_BOTH is only supported
        // in PHP 5.6
        foreach ($queries as $k => $v) {
            if (true !== $callback($v, $k)) {
                unset($queries[$k]);
            }
        }

        $href = '';

        if (\count($queries) > 0) {
            $href .= '?'.http_build_query($queries, '', '&');
        }

        return $script.$href;
    }

    /**
     * Prepare URL from ID and keep query string from current string.
     */
    protected function prepareUrl(string|null $url = null): string
    {
        if (null === $url) {
            if (null !== $this->requestStack) {
                $url = $this->requestStack->getCurrentRequest()?->getUri();
            } else {
                // Fallback for manually created UrlParser instances
                trigger_deprecation('codefog/contao-haste', '5.2', 'Instantiating "%s" without the RequestStack has been deprecated and will no longer work in Haste 6. Retrieve the "%s" service from the container instead.', __CLASS__);

                $container = System::getContainer();

                if ($container->has('request_stack')) {
                    $url = $container->get('request_stack')->getCurrentRequest()?->getUri();
                }
            }
        }

        return StringUtil::ampersand((string) $url, false);
    }
}
