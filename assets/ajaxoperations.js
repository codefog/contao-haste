var HasteAjaxOperations = {

    /**
     * Toggle operation
     *
     * @param {object} el   The DOM element
     * @param {string} id   The ID of the target element
     *
     * @returns {boolean}
     */
    toggleOperation: function(el, id) {
        el.blur();

        var image = $(el).getFirst('img'),
            operation = el.getAttribute('data-haste-ajax-operation-name'),
            value = el.getAttribute('data-haste-ajax-operation-value');

        // Send the request
        new Request.JSON({
            followRedirects: true,
            url: window.location.href,
            onSuccess: function(json) {
                image.src = json.nextIcon;
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
