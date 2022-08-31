<?php

namespace Codefog\HasteBundle;

use Contao\Environment;
use Contao\StringUtil;

class UrlParser
{
    /**
     * Add a query string to the given URI string or page ID
     */
    public function addQueryString(string $query, string $url = null): string
    {
        $url = $this->prepareUrl($url);
        $query = trim(StringUtil::ampersand($query, false), '&');

        [$script, $queryString] = explode('?', $url, 2) + ['', ''];

        parse_str($queryString, $queries);

        $queries = array_filter($queries);
        unset($queries['language']);

        $href = '';

        if (count($queries) > 0) {
            parse_str($query, $new);
            $href = '?' . http_build_query(array_merge($queries, $new), '', '&');
        } elseif ($query) {
            $href = '?' . $query;
        }

        return $script . $href;
    }

    /**
     * Remove query parameters from the current URL.
     */
    public function removeQueryString(array $params, string $url = null): string
    {
        $url = $this->prepareUrl($url);

        if (count($params) === 0) {
            return $url;
        }

        [$script, $queryString] = explode('?', $url, 2) + array('', '');

        parse_str($queryString, $queries);

        $queries = array_filter($queries);
        $queries = array_diff_key($queries, array_flip($params));

        $href = '';

        if (count($queries) > 0) {
            $href .= '?' . http_build_query($queries, '', '&');
        }

        return $script . $href;
    }

    /**
     * Remove query parameters from the current URL using a callback method.
     */
    public function removeQueryStringCallback(callable $callback, string $url = null): string
    {
        $url = $this->prepareUrl($url);

        [$script, $queryString] = explode('?', $url, 2) + ['', ''];

        parse_str($queryString, $queries);

        // Cannot use array_filter because flags ARRAY_FILTER_USE_BOTH is only supported in PHP 5.6
        foreach ($queries as $k => $v) {
            if (true !== call_user_func($callback, $v, $k)) {
                unset($queries[$k]);
            }
        }

        $href = '';

        if (count($queries) > 0) {
            $href .= '?' . http_build_query($queries, '', '&');
        }

        return $script . $href;
    }

    /**
     * Prepare URL from ID and keep query string from current string.
     */
    protected function prepareUrl(string $url = null): string
    {
        if ($url === null) {
            $url = Environment::get('requestUri');
        }

        return StringUtil::ampersand($url, false);
    }
}
