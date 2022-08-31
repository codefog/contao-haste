<?php

declare(strict_types=1);

namespace Codefog\HasteBundle;

use Contao\ArrayUtil;
use Contao\Config;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Date;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

class Formatter
{
    public function __construct(private readonly Connection $connection, private readonly RequestStack $requestStack,)
    {
    }

    /**
     * Format date according to the system config.
     */
    public function date(int $timestamp): string
    {
        return $this->formatDate($timestamp, 'dateFormat');
    }

    /**
     * Format time according to the system config.
     */
    public function time(int $timestamp): string
    {
        return $this->formatDate($timestamp, 'timeFormat');
    }

    /**
     * Format date & time according to the system config.
     */
    public function datim(int $timestamp): string
    {
        return $this->formatDate($timestamp, 'datimFormat');
    }

    /**
     * Get field label based on DCA config.
     */
    public function dcaLabel(string $table, string $field): string
    {
        System::loadLanguageFile($table);
        Controller::loadDataContainer($table);

        $fieldConfig = $GLOBALS['TL_DCA'][$table]['fields'][$field] ?? [];

        // Add the "name" key (backwards compatibility)
        if (!isset($fieldConfig['name'])) {
            $fieldConfig['name'] = $field;
        }

        return $this->dcaLabelFromArray($fieldConfig);
    }

    /**
     * Get field label based on field config.
     */
    public function dcaLabelFromArray(array $fieldConfig): string
    {
        if (!empty($fieldConfig['label'])) {
            $label = \is_array($fieldConfig['label']) ? $fieldConfig['label'][0] : $fieldConfig['label'];
        } else {
            $label = \is_array($GLOBALS['TL_LANG']['MSC'][$fieldConfig['name']] ?? null) ? $GLOBALS['TL_LANG']['MSC'][$fieldConfig['name']][0] : $GLOBALS['TL_LANG']['MSC'][$fieldConfig['name']] ?? '';
        }

        if (!$label) {
            $label = $fieldConfig['name'];
        }

        return $label;
    }

    /**
     * Format DCA field value according to Contao Core standard.
     */
    public function dcaValue(string $table, string $field, mixed $value, DataContainer $dc = null): mixed
    {
        System::loadLanguageFile('default');
        System::loadLanguageFile($table);

        Controller::loadDataContainer($table);

        if (!isset($GLOBALS['TL_DCA'][$table]['fields'][$field])) {
            return '';
        }

        $fieldConfig = $GLOBALS['TL_DCA'][$table]['fields'][$field];

        // Add the "name" key (backwards compatibility)
        if (!isset($fieldConfig['name'])) {
            $fieldConfig['name'] = $field;
        }

        return $this->dcaValueFromArray($fieldConfig, $value, $dc);
    }

    /**
     * Format field value according to Contao Core standard.
     */
    public function dcaValueFromArray(array $fieldConfig, mixed $value, DataContainer $dc = null): mixed
    {
        $value = StringUtil::deserialize($value);

        // Options callback (array)
        if (\is_array($fieldConfig['options_callback'] ?? null) && null !== $dc) {
            $callback = $fieldConfig['options_callback'];
            $fieldConfig['options'] = System::importStatic($callback[0])->{$callback[1]}($dc);
        } elseif (\is_callable($fieldConfig['options_callback'] ?? null) && null !== $dc) {
            // Options callback (callable)
            $fieldConfig['options'] = $fieldConfig['options_callback']($dc);
        } elseif (isset($fieldConfig['foreignKey']) && $value) {
            // foreignKey
            $chunks = explode('.', $fieldConfig['foreignKey'], 2);

            $fieldConfig['options'] = [];

            $options = $this->connection->fetchAllAssociative('SELECT id, '.$chunks[1].' AS value FROM '.$chunks[0].' WHERE id IN ('.implode(',', array_map('intval', (array) $value)).')');

            foreach ($options as $option) {
                $fieldConfig['options'][$option['id']] = $option['value'];
            }
        }

        if (\is_array($value)) {
            foreach ($value as $kk => $vv) {
                $value[$kk] = $this->dcaValueFromArray($fieldConfig, $vv);
            }

            return implode(', ', $value);
        }

        if ('date' === ($fieldConfig['eval']['rgxp'] ?? null)) {
            return $this->date($value);
        }

        if ('time' === ($fieldConfig['eval']['rgxp'] ?? null)) {
            return $this->time($value);
        }

        if ('datim' === ($fieldConfig['eval']['rgxp'] ?? null) || \in_array(($fieldConfig['flag'] ?? null), [5, 6, 7, 8, 9, 10], true) || 'tstamp' === ($fieldConfig['name'] ?? null)) {
            return $this->datim($value);
        }

        if (($fieldConfig['eval']['isBoolean'] ?? false) || ('checkbox' === ($fieldConfig['inputType'] ?? null) && !($fieldConfig['eval']['multiple'] ?? false))) {
            return !empty($value) ? $GLOBALS['TL_LANG']['MSC']['yes'] : $GLOBALS['TL_LANG']['MSC']['no'];
        }

        if ('textarea' === ($fieldConfig['inputType'] ?? null) && (($fieldConfig['eval']['allowHtml'] ?? false) || ($fieldConfig['eval']['preserveTags'] ?? false))) {
            return StringUtil::specialchars($value);
        }

        if (\is_array($fieldConfig['reference'] ?? null) && isset($fieldConfig['reference'][$value])) {
            return \is_array($fieldConfig['reference'][$value]) ? $fieldConfig['reference'][$value][0] : $fieldConfig['reference'][$value];
        }

        if (($fieldConfig['eval']['isAssociative'] ?? null) || ArrayUtil::isAssoc($fieldConfig['options'] ?? null) && isset($fieldConfig['options'][$value])) {
            return \is_array($fieldConfig['options'][$value]) ? $fieldConfig['options'][$value][0] : $fieldConfig['options'][$value];
        }

        return $value;
    }

    /**
     * Format the date.
     */
    private function formatDate(int $timestamp, string $type): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && ($pageModel = $request->attributes->get('pageModel')) instanceof PageModel) {
            $format = $pageModel->$type;
        }

        return Date::parse($format ?? Config::get($type), $timestamp);
    }
}
