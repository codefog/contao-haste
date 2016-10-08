var Haste = Haste || {};

/**
 * Toggle ajax operation
 *
 * @param {object} el   The DOM element
 * @param {string} id   The ID of the target element
 *
 * @returns {boolean}
 */
Haste.toggleAjaxOperation = function(el, id) {
    el.blur();

    var image = $(el).getFirst('img'),
        operation = el.getAttribute('data-haste-ajax-operation-name'),
        value = el.getAttribute('data-haste-ajax-operation-value');

    // Send the request
    new Request.JSON({
        followRedirects: true,
        url: window.location.href,
        onComplete: function(json) {

            // Support Contao redirects
            if (this.getHeader('X-Ajax-Location')) {
                window.location.replace(this.getHeader('X-Ajax-Location'));
                return;
            }

            var iconPath = json.nextIcon;

            if (iconPath.indexOf('/') == -1) {
                var folder = 'images';

                // Support SVG images in Contao 4
                if (/\.svg$/i.test(iconPath)) {
                    folder = 'icons';
                }

                iconPath = Contao.script_url + 'system/themes/' + Contao.theme + '/' + folder + '/' + json.nextIcon;
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
};