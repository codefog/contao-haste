var Haste = {

    /**
     * Toggle ajax operation
     *
     * @param {object} el   The DOM element
     * @param {string} id   The ID of the target element
     *
     * @returns {boolean}
     */
    toggleAjaxOperation: function(el, id) {
        el.blur();

        var image = $(el).getFirst('img'),
            operation = el.getAttribute('data-haste-ajax-operation-name'),
            value = el.getAttribute('data-haste-ajax-operation-value');

        // Send the request
        new Request.JSON({
            followRedirects: true,
            url: window.location.href,
            onSuccess: function(json) {

                var iconPath = json.nextIcon;
                if (iconPath.indexOf('/') == -1) {
                    iconPath = Contao.script_url + 'system/themes/' + Contao.theme + '/images/' + json.nextIcon;
                }

                image.src = iconPath;

                el.setAttribute('data-haste-ajax-operation-value', json.nextValue);
            }
        }).post({
            'action': 'hasteAjaxOperation',
            'operation': operation,
            'id': id,
            'value': value,
            'REQUEST_TOKEN': Contao.request_token
        });

        return false;
    }
};
