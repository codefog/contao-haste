window.Haste = window.Haste || {};

/**
 * Toggle ajax operation
 * @param {object} el The DOM element
 * @param {string} id The ID of the target element
 * @returns {boolean}
 */
window.Haste.toggleAjaxOperation = function (el, id) {
    el.blur();

    function getUrlParameter(name, href) {
        const regex = new RegExp(`[\\?&]${name.replace(/\[/, '\\[').replace(/]/, '\\]')}=([^&#]*)`);
        const results = regex.exec(href);

        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    const image = $(el).getFirst('img');
    const operation = el.getAttribute('data-haste-ajax-operation-name');
    const value = el.getAttribute('data-haste-ajax-operation-value');
    const buttonHref = el.getAttribute('href');
    const urlTable = getUrlParameter('table', buttonHref);
    const urlAppend = urlTable ? `&table=${urlTable}` : '';

    // Send the request
    new Request.JSON({
        followRedirects: true,
        url: window.location.href + urlAppend,
        onComplete(json) {
            // Support Contao redirects
            if (this.getHeader('X-Ajax-Location')) {
                window.location.replace(this.getHeader('X-Ajax-Location'));
                return;
            }

            let iconPath = json.nextIcon;

            if (iconPath.indexOf('/') === -1) {
                iconPath = `${window.Contao.script_url}system/themes/${window.Contao.theme}/icons/${json.nextIcon}`;
            }

            image.src = iconPath;

            el.setAttribute('data-haste-ajax-operation-value', json.nextValue);
        },
    }).post({
        action: 'hasteAjaxOperation',
        operation,
        id,
        value,
        REQUEST_TOKEN: window.Contao.request_token,
    });

    return false;
};
