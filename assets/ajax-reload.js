window.HasteAjax = {
    /**
     * Requests in progress
     */
    inProgress: {},

    /**
     * Fire the event
     * @param {String} event
     */
    fireEvent: function (event) {
        var els = {};

        Array.from(document.querySelectorAll('[data-haste-ajax-listeners]')).forEach(function (el) {
            if (el.dataset.hasteAjaxListeners.split(' ').indexOf(event) !== -1) {
                els[el.dataset.hasteAjaxId] = el;
            }
        });

        if (Object.keys(els).length < 1) {
            console.warn('There are no eligible elements for event "' + event + '"');
            return;
        }

        this._sendRequest(els, event);
    },

    /**
     * Send the request
     * @param {Array} els
     * @param {String} event
     * @private
     */
    _sendRequest: function (els, event) {
        if (this.inProgress[event]) {
            return;
        }

        this.inProgress[event] = true;

        var url = window.location.href + (document.location.search ? '&' : '?') + '&haste_ajax_reload=' + event;
        var xhr = new XMLHttpRequest();

        xhr.open('GET', encodeURI(url));
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function () {
            if (xhr.status === 200) {
                JSON.parse(xhr.responseText).forEach(function (module) {
                    var tmp = document.createElement('div');
                    tmp.innerHTML = module.buffer;
                    els[module.id].parentNode.replaceChild(tmp.childNodes[0], els[module.id]);
                });
            } else {
                console.error('The request for event "' + event + '" has failed');
                console.error(xhr);
            }

            this.inProgress[event] = false;
        }.bind(this);

        xhr.send();
    }
};

