<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\EventListener;

use Contao\Backend;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Contao\Widget;
use Doctrine\DBAL\Connection;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class DcaAjaxOperationsListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Packages $packages,
        private readonly RequestStack $requestStack,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly Security $security,
        private readonly ContaoCsrfTokenManager $tokenManager,
    )
    {
    }

    #[AsHook('executePostActions')]
    public function onExecutePostActions(string $action, DataContainer $dc): void
    {
        if ('hasteAjaxOperation' !== $action) {
            return;
        }

        $operation = Input::post('operation');
        $settings = $GLOBALS['TL_DCA'][$dc->table]['list']['operations'][$operation]['haste_ajax_operation'];

        if (!isset($settings)) {
            return;
        }

        // Check permissions
        if (!$this->checkPermission($dc->table, $settings)) {
            throw new AccessDeniedException(sprintf('Not enough permissions to toggle field %s::%s', $dc->table, $settings['field']));
        }

        $id = $dc->id = (int) Input::post('id');
        $currentValue = Input::post('value');

        // Initialize versioning
        $versions = new Versions($dc->table, $id);
        $versions->setEditUrl($this->getVersionEditUrl($id, $operation));
        $versions->initialize();

        // Determine next value and icon
        $options = (array) $settings['options'];
        $nextIndex = 0;

        foreach ($options as $k => $option) {
            if ($option['value'] === $currentValue) {
                $nextIndex = $k + 1;
            }
        }

        // Make sure that if $nextIndex does not exist it's the first
        if (!isset($options[$nextIndex])) {
            $nextIndex = 0;
        }

        $value = $options[$nextIndex]['value'];
        $value = $this->executeSaveCallback($dc, $value, $settings);

        // Set the correct empty value
        if (!\is_array($value) && '' === (string) $value ) {
            $value = Widget::getEmptyValueByFieldType($GLOBALS['TL_DCA'][$dc->table]['fields'][$settings['field']]['sql'] ?? []);
        }

        // Update the database
        $this->connection->update($dc->table, [$settings['field'] => $value], ['id' => $id]);

        // Create a new version
        $versions->create();

        $response = new JsonResponse([
            'nextValue' => $options[$nextIndex]['value'],
            'nextIcon' => $options[$nextIndex]['icon'],
        ]);

        throw new ResponseException($response);
    }

    #[AsHook('loadDataContainer')]
    public function onLoadDataContainer(string $table): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$this->scopeMatcher->isBackendRequest($request) || !\is_array($GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null)) {
            return;
        }

        foreach ($GLOBALS['TL_DCA'][$table]['list']['operations'] as $name => $settings) {
            if (!isset($settings['haste_ajax_operation'])) {
                continue;
            }

            $operation = &$GLOBALS['TL_DCA'][$table]['list']['operations'][$name];

            // Add the JavaScript
            $GLOBALS['TL_JAVASCRIPT'][] = $this->packages->getUrl('dca-ajax-operations.js', 'codefog_haste');

            // Add default button callback to display the correct initial state but only add it if not already present
            if (!isset($settings['button_callback'])) {
                $operation['button_callback'] = $this->getDefaultButtonCallback($name, $table, $settings['haste_ajax_operation']);

                // Make sure an icon is set to prevent DC_Table errors (set to '' as the button_callback will return the correct icon)
                $operation['icon'] = '';

                // Add the onclick attribute
                $this->addOnClickAttribute($operation);
            }
        }
    }

    /**
     * Adds the "onclick" attribute to the operation DCA.
     */
    private function addOnClickAttribute(array & $operation): void
    {
        $clickEventString = 'return Haste.toggleAjaxOperation(this, %s);';

        if (!isset($operation['attributes'])) {
            $operation['attributes'] = sprintf('onclick="%s"', $clickEventString);
        } else {
            // onclick attribute already present
            if (str_contains($operation['attributes'], 'onclick="')) {
                $operation['attributes'] = str_replace('onclick="', 'onclick="'.$clickEventString, $operation['attributes']);
            } else {
                $operation['attributes'] = $clickEventString.$operation['attributes'];
            }
        }
    }

    /**
     * Checks if user has the permissions for the field.
     */
    private function checkPermission(string $table, array $settings): bool
    {
        $hasPermission = true;

        // Check the permissions
        if (($GLOBALS['TL_DCA'][$table]['fields'][$settings['field']]['exclude'] ?? false) && $this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, $table.'::'.$settings['field'])) {
            $hasPermission = false;
        }

        $callback = $settings['check_permission_callback'] ?? null;

        if (\is_array($callback)) {
            System::importStatic($callback[0])->{$callback[1]}($table, $settings, $hasPermission);
        } elseif (\is_callable($callback)) {
            $callback($table, $settings, $hasPermission);
        }

        return $hasPermission;
    }

    /**
     * Executes the save_callback.
     */
    private function executeSaveCallback(DataContainer $dc, mixed $value, array $settings): mixed
    {
        $field = $settings['field'];

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['save_callback'] ?? null)) {
            foreach ($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $value = System::importStatic($callback[0])->{$callback[1]}($value, $dc);
                } elseif (\is_callable($callback)) {
                    $value = $callback($value, $dc);
                }
            }
        }

        return $value;
    }

    /**
     * Gets the default button callback.
     */
    private function getDefaultButtonCallback(string $name, string $table, array $settings): callable
    {
        return function (array $row, $href, $label, $title, $icon, $attributes) use ($name, $table, $settings) {
            // If the user doesn't have access, hide the button
            if (!$this->checkPermission($table, $settings)) {
                return '';
            }

            $value = $row[$settings['field']];
            $options = (array) $settings['options'];
            $icon = null;

            foreach ($options as $option) {
                if ($option['value'] === $value) {
                    $icon = $option['icon'];
                }
            }

            // Default is the first value in the options array
            if (null === $icon) {
                $icon = $options[0]['icon'];
            }

            return sprintf(
                '<a data-haste-ajax-operation-value="%s" data-haste-ajax-operation-name="%s" href="%s" title="%s"%s>%s</a> ',
                $value,
                $name,
                Backend::addToUrl($href),
                StringUtil::specialchars($title),
                $attributes,
                Image::getHtml($icon, $label)
            );
        };
    }

    /**
     * Get the versioning edit URL.
     */
    private function getVersionEditUrl(int $id, string $operation): ?string
    {
        if ('toggle' !== $operation) {
            return null;
        }

        $url = Environment::get('requestUri');
        $url = preg_replace('/&(amp;)?id=[^&]+/', '', $url);

        return $url . sprintf('&act=edit&id=%s&rt=%s', $id, $this->tokenManager->getDefaultTokenValue());
    }
}
