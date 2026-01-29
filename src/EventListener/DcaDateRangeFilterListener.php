<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\DataContainer;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

class DcaDateRangeFilterListener
{
    protected array $fieldsToFilter = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
    ) {
    }

    #[AsHook('loadDataContainer')]
    public function onLoadDataContainer(string $table): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isBackendRequest($request) || !\is_array($GLOBALS['TL_DCA'][$table]['fields'] ?? null)) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['fields'] as $name => $config) {
            if (!empty($config['rangeFilter']) && \in_array($config['eval']['rgxp'] ?? null, ['date', 'time', 'datim'], true)) {
                $this->fieldsToFilter[] = $name;
            }
        }

        if (\count($this->fieldsToFilter) > 0) {
            $GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'] = preg_replace('/filter/', 'haste_dateRangeFilter;filter', (string) $GLOBALS['TL_DCA'][$table]['list']['sorting']['panelLayout'], 1);
            $GLOBALS['TL_DCA'][$table]['list']['sorting']['panel_callback']['haste_dateRangeFilter'] = [static::class, 'onPanelCallback'];
            $GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = [static::class, 'onLoadCallback'];
        }
    }

    /**
     * On date range filter panel callback. Adds the filters to the panel.
     */
    public function onPanelCallback(DataContainer $dc): string
    {
        if (0 === \count($this->fieldsToFilter)) {
            return '';
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isBackendRequest($request)) {
            return '';
        }

        $filter = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? null) === DataContainer::MODE_PARENT ? $dc->table.'_'.$dc->currentPid : $dc->table;

        /** @var AttributeBagInterface $session */
        $session = $request->getSession()->getBag('contao_backend');
        $sessionData = $session->all();

        // Set filter from user input
        if ('tl_filters' === Input::post('FORM_SUBMIT')) {
            foreach ($this->fieldsToFilter as $field) {
                $key = 'haste_dateRangeFilter_'.$field;
                $from = Input::post($key.'_from');
                $to = Input::post($key.'_to');

                if ($from || $to) {
                    $sessionData['filter'][$filter][$key] = ['from' => $from, 'to' => $to];
                } else {
                    unset($sessionData['filter'][$filter][$key]);
                }
            }

            $session->replace($sessionData);
        }

        $return = '';

        foreach ($this->fieldsToFilter as $field) {
            $key = 'haste_dateRangeFilter_'.$field;

            $return .= '<div class="tl_subpanel haste-date-range-filter">';
            $return .= \sprintf('<strong>%s: </strong>', $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label'][0] ?? '');

            $return .= $this->createDatepickerInputField(
                'haste_dateRangeFilter_'.$field.'_from',
                $session['filter'][$filter][$key]['from'] ?? '',
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rgxp'] ?? '',
            );

            $return .= $this->createDatepickerInputField(
                'haste_dateRangeFilter_'.$field.'_to',
                $session['filter'][$filter][$key]['to'] ?? '',
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rgxp'] ?? '',
            );

            $return .= '</div>';
        }

        $GLOBALS['TL_CSS'][] = System::getContainer()->get('assets.packages')->getUrl('dca-date-range-filter.css', 'codefog_haste');

        if (isset($sessionData['filter'][$filter][$key])) {
            $dc->setPanelState(true);
        }

        return $return;
    }

    /**
     * On data container load callback. Filters the records by setting sorting->root
     * if filters are set.
     */
    public function onLoadCallback(DataContainer $dc): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isBackendRequest($request)) {
            return;
        }

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $request->getSession()->getBag('contao_backend');

        $filter = ($GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['mode'] ?? null) === DataContainer::MODE_PARENT ? $dc->table.'_'.$dc->currentPid : $dc->table;
        $sessionData = $sessionBag->all();

        $root = [];
        $filterRecords = false;

        foreach ($this->fieldsToFilter as $field) {
            $key = 'haste_dateRangeFilter_'.$field;
            $rgxp = $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['rgxp'] ?? '';
            $from = $sessionData['filter'][$filter][$key]['from'] ?? null;
            $to = $sessionData['filter'][$filter][$key]['to'] ?? null;

            if ($from) {
                $from = $this->validateAndGetTstamp($from, $rgxp);
            }

            if ($to) {
                $to = $this->validateAndGetTstamp($to, $rgxp, false);
            }

            if (null === $from && null === $to) {
                continue;
            }

            $filterRecords = true;
            $root = array_merge($root, $this->fetchValidRecordIds($dc, $field, $from, $to));
        }

        if ($filterRecords) {
            $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['root'] = 0 === \count($root) ? [0] : array_unique($root);
        }
    }

    /**
     * Fetch valid records within from->to range.
     */
    private function fetchValidRecordIds(DataContainer $dc, string $field, int $from, int $to): array
    {
        $where = [];

        if ($from) {
            $where[] = $field.'>='.$from;
        }

        if ($to) {
            $where[] = $field.'<='.$to;
        }

        if (0 === \count($where)) {
            return [];
        }

        return $this->connection->fetchFirstColumn(\sprintf('SELECT id FROM %s WHERE %s', $dc->table, implode(' AND ', $where)));
    }

    /**
     * Validates user input and turns it into a tstamp if it's valid.
     */
    private function validateAndGetTstamp(string $value, string $rgxp, bool $from = true): int|null
    {
        $method = 'is'.ucfirst($rgxp);

        if (!Validator::$method($value)) {
            return null;
        }

        // Determine the correct key for time and date formats
        if ('time' === $rgxp || 'datim' === $rgxp) {
            $key = 'tstamp';
        } else {
            $key = $from ? 'dayBegin' : 'dayEnd';
        }

        try {
            $date = new Date((int) $value, Date::getFormatFromRgxp($rgxp));
        } catch (\OutOfBoundsException) {
            return null;
        }

        return $date->$key;
    }

    /**
     * Creates a datepicker input field.
     *
     * @see DataContainer::row()
     */
    private function createDatepickerInputField(string $name, string $value, string $rgxp): string
    {
        $format = Date::formatToJs(Config::get($rgxp.'Format'));

        $time = match ($rgxp) {
            'datim' => ",\n      timePicker:true",
            'time' => ",\n      pickOnly:\"time\"",
            default => '',
        };

        return \sprintf(
            '<input id="ctrl_%s" name="%s" class="tl_text%s" value="%s" type="text">
            %s
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
            $value ? ' active' : '',
            $value,
            Image::getHtml('assets/datepicker/images/icon.svg', '', 'title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['datepicker'] ?? '').'" id="toggle_'.$name.'" style="vertical-align:-6px;cursor:pointer"'),
            $name,
            $name,
            $format,
            $time,
            $GLOBALS['TL_LANG']['MSC']['weekOffset'] ?? '',
            $GLOBALS['TL_LANG']['MSC']['titleFormat'] ?? '',
        );
    }
}
