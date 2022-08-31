<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\EventListener;

use Codefog\HasteBundle\Formatter;
use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Date;
use Contao\FormFieldModel;
use Contao\StringUtil;

#[AsHook('replaceInsertTags')]
class InsertTagsListener
{
    public function __construct(private readonly Formatter $formatter,)
    {
    }

    public function __invoke(string $tag): mixed
    {
        $chunks = StringUtil::trimsplit('::', $tag);

        switch ($chunks[0]) {
            case 'convert_dateformat':
                return $this->replaceConvertedDateFormat($chunks);

            case 'dca_label':
                return $this->replaceDcaLabel($chunks);

            case 'dca_value':
                return $this->replaceDcaValue($chunks);

            case 'formatted_datetime':
                return $this->replaceFormattedDateTime($chunks);

            case 'rand':
                return 3 === \count($chunks) ? random_int((int) $chunks[1], (int) $chunks[2]) : mt_rand();

            case 'flag':
                return (string) $chunks[1];

            case 'options_label':
                return $this->replaceOptionsLabel($chunks);
        }

        return false;
    }

    /**
     * Replace {{convert_dateformat::*}} insert tag.
     *
     * Format:
     *
     * {{convert_dateformat::<value>::<source_format>::<target_format>}}
     *
     * Description:
     *
     * The source_format and target_format can be any format from php date()
     * or "date", "datim" or "time" to take the the format from the root page settings
     * (or system settings, in case not defined).
     *
     * Possible use cases:
     *
     * {{convert_dateformat::2018-11-21 10:00::datim::date}} –> outputs 2018-11-21
     * {{convert_dateformat::21.03.2018::d.m.Y::j. F Y}} –> outputs 21. März 2018
     */
    private function replaceConvertedDateFormat(array $chunks): string|false
    {
        if (4 !== \count($chunks)) {
            return false;
        }

        $determineFormat = static function ($format) {
            if (\in_array($format, ['datim', 'date', 'time'], true)) {
                $key = $format.'Format';

                return $GLOBALS['objPage']->{$key} ?? Config::get($key);
            }

            return $format;
        };

        try {
            $date = new Date($chunks[1], $determineFormat($chunks[2]));
        } catch (\OutOfBoundsException $e) {
            return false;
        }

        return Date::parse($determineFormat($chunks[3]), $date->tstamp);
    }

    /**
     * Replace {{formatted_datetime::*}} insert tag.
     *
     * 5 possible use cases:
     *
     * {{formatted_datetime::timestamp}}
     *      or
     * {{formatted_datetime::timestamp::datim}} - formats a given timestamp with the global date and time (datim) format
     * {{formatted_datetime::timestamp::date}} - formats a given timestamp with the global date format
     * {{formatted_datetime::timestamp::time}} - formats a given timestamp with the global time format
     * {{formatted_datetime::timestamp::Y-m-d H:i}} - formats a given timestamp with the specified format
     * {{formatted_datetime::+1 day::Y-m-d H:i}} - formats a given php date/time format (see http://php.net/manual/en/function.strtotime.php) with the specified format
     */
    private function replaceFormattedDateTime(array $chunks): string
    {
        $timestamp = $chunks[1];

        // Support strtotime()
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        $strFormat = $chunks[2];

        // Fallback
        if (null === $strFormat) {
            $strFormat = 'datim';
        }

        // Custom format
        if (!\in_array($strFormat, ['datim', 'date', 'time'], true)) {
            return Date::parse($strFormat, $timestamp);
        }

        return $this->formatter->$strFormat($timestamp);
    }

    /**
     * Replace {{dca_label::*}} insert tag.
     *
     * use case:
     *
     * {{dca_label::table::field}}
     */
    private function replaceDcaLabel(array $chunks): string
    {
        return $this->formatter->dcaLabel($chunks[1], $chunks[2]);
    }

    /**
     * Replace {{dca_value::*}} insert tag.
     *
     * use case:
     *
     * {{dca_value::table::field::value}}
     */
    private function replaceDcaValue(array $chunks): string
    {
        return $this->formatter->dcaValue($chunks[1], $chunks[2], $chunks[3]);
    }

    /**
     * Replace {{option_label::*}} insert tag.
     *
     * use case:
     *
     * {{option_label::ID::value}}
     */
    private function replaceOptionsLabel(array $chunks): string
    {
        $value = $chunks[2];

        if (null === ($field = FormFieldModel::findByPk($chunks[1]))) {
            return $value;
        }

        $options = StringUtil::deserialize($field->options);

        if (!\is_array($options) || 0 === \count($options)) {
            return $value;
        }

        foreach ($options as $option) {
            if ($value === $option['value']) {
                return $option['label'];
            }
        }

        return $value;
    }
}
