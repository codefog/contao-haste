<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Dca;

class DateRangeFilter
{
    /**
     * Current object instance (Singleton)
     * @var DateRangeFilter
     */
    protected static $objInstance;

    /**
     * Fields to filter
     * @var array
     */
    protected $arrFieldsToFilter = array();

    /**
     * Add the callbacks to DCA
     *
     * @param string
     */
    public function addCallbacks($strTable)
    {
        if (TL_MODE !== 'BE'
            || !isset($GLOBALS['TL_DCA'][$strTable]['fields'])
        ) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strField => $arrField) {
            if (!empty($arrField['rangeFilter'])
                && in_array($arrField['eval']['rgxp'], array('date', 'time', 'datim'))
            ) {
                $this->arrFieldsToFilter[] = $strField;
            }
        }

        if (!empty($this->arrFieldsToFilter)) {
            $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['panelLayout'] = str_replace('filter', 'haste_dateRangeFilter;filter', $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['panelLayout']);
            $GLOBALS['TL_DCA'][$strTable]['list']['sorting']['panel_callback']['haste_dateRangeFilter'] = array('Haste\Dca\DateRangeFilter', 'addFiltersToPanel');
            $GLOBALS['TL_DCA'][$strTable]['config']['onload_callback'][] = array('Haste\Dca\DateRangeFilter', 'filterRecords');
        }
    }

    /**
     * Adds the filters to the panel
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function addFiltersToPanel($dc)
    {
        if (empty($this->arrFieldsToFilter)) {
            return '';
        }

        $GLOBALS['TL_CSS'][] = 'system/modules/haste/assets/haste.css';

        $filter = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] == 4) ? $dc->table.'_'.CURRENT_ID : $dc->table;
        $session = \Session::getInstance()->getData();

        // Set filter from user input
        if ('tl_filters' === \Input::post('FORM_SUBMIT')) {
            foreach ($this->arrFieldsToFilter as $field) {

                $key = 'haste_dateRangeFilter_' . $field;
                $from = \Input::post($key . '_from');
                $to = \Input::post($key . '_to');

                if ($from || $to) {
                    $session['filter'][$filter][$key] = array(
                        'from' => $from,
                        'to'   => $to
                    );
                } else {
                    unset($session['filter'][$filter][$key]);
                }
            }

            \Session::getInstance()->setData($session);
        }

        $return = '';

        foreach ($this->arrFieldsToFilter as $field) {

            $key = 'haste_dateRangeFilter_' . $field;
            $return .= '<div class="tl_subpanel haste_dateRangeFilter">';

            $return .= sprintf('<strong>%s: </strong>',
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label'][0]
            );

            $return .= $this->createDatepickerInputField(
                'haste_dateRangeFilter_' . $field . '_from',
                $session['filter'][$filter][$key]['from'],
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rgxp']
            );

            $return .= $this->createDatepickerInputField(
                'haste_dateRangeFilter_' . $field . '_to',
                $session['filter'][$filter][$key]['to'],
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rgxp']
            );

            $return .= '</div>';
        }

        return $return;
    }

    /**
     * Filters the records by setting sorting->root if filters are set
     *
     * @param \DataContainer $dc
     */
    public function filterRecords($dc)
    {
        $filter = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] == 4) ? $dc->table.'_'.CURRENT_ID : $dc->table;
        $session = \Session::getInstance()->getData();
        $root = array();
        $blnDoFilter = false;

        foreach ($this->arrFieldsToFilter as $field) {
            $key = 'haste_dateRangeFilter_' . $field;
            $rgxp = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rgxp'];

            $from = ($session['filter'][$filter][$key]['from']) ?: null;
            $to = ($session['filter'][$filter][$key]['to']) ?: null;

            if ($from) {
                $from = $this->validateAndGetTstamp(
                    $from,
                    $rgxp
                );
            }

            if ($to) {
                $to = $this->validateAndGetTstamp(
                    $to,
                    $rgxp,
                    false
                );
            }

            if ($from === null && $to === null) {
                continue;
            }

            $blnDoFilter = true;
            $root = array_merge($root, $this->fetchValidRecordIds($dc, $field, $from, $to));
        }

        if ($blnDoFilter) {
            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = empty($root) ? array(0) : array_unique($root);
        }
    }

    /**
     * Instantiate the object
     *
     * @return DateRangeFilter
     */
    public static function getInstance()
    {
        if (null === static::$objInstance) {
            static::$objInstance = new static();
        }

        return static::$objInstance;
    }

    /**
     * Fetch valid records within from->to range
     *
     * @param \DataContainer $dc
     * @param string         $field
     * @param int            $from
     * @param int            $to
     *
     * @return array
     */
    private function fetchValidRecordIds($dc, $field, $from, $to)
    {
        $where = [];

        if ($from) {
            $where[]  = $field . '>=' . $from;
        }

        if ($to) {
            $where[]  = $field . '<=' . $to;
        }

        if (empty($where)) {
            return [];
        }

        $sql = sprintf(
            'SELECT id FROM %s WHERE %s',
            $dc->table,
            implode(' AND ', $where)
        );

        return \Database::getInstance()->query($sql)->fetchEach('id');
    }

    /**
     * Validates user input and turns it into a tstamp if it's valid
     *
     * @param string $value User input
     * @param string $rgxp
     * @param bool   $from  True if beginning of the day, false if end of the day
     *
     * @return null|integer
     */
    private function validateAndGetTstamp($value, $rgxp, $from = true)
    {
        // Validate first
        $method = 'is' . ucfirst($rgxp);

        if (!\Validator::$method($value)) {
            return null;
        }

        $format = $GLOBALS['TL_CONFIG'][$rgxp . 'Format'];

        // Determine the correct key for time and date formats
        if ($rgxp === 'time' || $rgxp === 'datim') {
            $key = 'tstamp';
        } else {
            $key = ($from) ? 'dayBegin' : 'dayEnd';
        }

        try {
            $date = new \Date($value, $format);
        } catch(\Exception $e) {
            return null;
        }

        return $date->{$key};
    }

    /**
     * Creates a datepicker input field
     *
     * @param string $name
     * @param string $value
     * @param string $rgxp
     *
     * @return string
     */
    private function createDatepickerInputField($name, $value, $rgxp)
    {
        $icon = 'assets/datepicker/images/icon.svg';
        $format = \Date::formatToJs($GLOBALS['TL_CONFIG'][$rgxp . 'Format']);

        if (version_compare(VERSION, '4.2', '<')) {
            $icon = sprintf('assets/mootools/datepicker/%s/icon.gif', $GLOBALS['TL_ASSETS']['DATEPICKER']);
        }

        switch ($rgxp) {
            case 'datim':
                $time = ",\n      timePicker:true";
                break;

            case 'time':
                $time = ",\n      pickOnly:\"time\"";
                break;

            default:
                $time = '';
                break;
        }

        return sprintf('<input id="ctrl_%s" name="%s" class="tl_text%s" value="%s" type="text">
            <img src="%s" width="20" height="20" alt="" title="%s" id="toggle_%s" style="vertical-align:-6px;cursor:pointer">
            <script>
            window.addEvent("domready", function() {
              new Picker.Date($("ctrl_%s"), {
                draggable: false,
                toggle: $("toggle_%s"),
                format: "%s",
                positionOffset: {x:-211,y:-209}%s,
                pickerClass: "datepicker_bootstrap",
                useFadeInOut: !Browser.ie,
                startDay: %s,
                titleFormat: "%s"
              });
            });
          </script>',
            $name,
            $name,
            ($value) ? ' active' : '',
            $value,
            $icon,
            specialchars($GLOBALS['TL_LANG']['MSC']['datepicker']),
            $name,
            $name,
            $name,
            $format,
            $time,
            $GLOBALS['TL_LANG']['MSC']['weekOffset'],
            $GLOBALS['TL_LANG']['MSC']['titleFormat']
        );
    }
}
