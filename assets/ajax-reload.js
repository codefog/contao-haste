window.HasteAjaxReload = {
    elementsInProgress: {},
    eventsInProgress: {},

    /**
     * Dispatch the events
     */
    dispatchEvents: function () {
        if (arguments.length < 1) {
            console.error('Please provide at least one event');
        }

        var els = {};
        var events = [];
        var listeners = document.querySelectorAll('[data-haste-ajax-listeners]');

        Array.from(arguments).forEach(function (event) {
            var found = false;

            // Find the elements that listen to particular event
            Array.from(listeners).forEach(function (el) {
                if (el.dataset.hasteAjaxListeners.split(' ').indexOf(event) !== -1) {
                    found = true;
                    els[el.dataset.hasteAjaxId] = el;
                }
            });

            if (!found) {
                console.warn('There are no eligible elements for event "' + event + '"');
            } else {
                events.push(event);
            }
        });

        if (Object.keys(els).length > 0 && events.length > 0) {
            this._sendRequest(els, events);
        }
    },

    /**
     * Send the request
     * @param {Object} els
     * @param {Array} events
     * @private
     */
    _sendRequest: function (els, events) {
        els = this._filterElementsInProgress(els);
        events = this._filterEventsInProgress(events);

        // Return if everything is in progress and there is nothing to update
        if (Object.keys(els).length < 1 || events.length < 1) {
            return;
        }

        this._toggleElementsInProgress(els, true);
        this._toggleEventsInProgress(events, true);

        // Add the CSS class
        for (var key in els) {
            els[key].className += ' haste-ajax-reloading';
        }

        var url = window.location.href + (document.location.search ? '&' : '?') + 'haste_ajax_reload=' + events.join(',');
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

            this._toggleElementsInProgress(els, false);
            this._toggleEventsInProgress(events, false);
        }.bind(this);

        xhr.send();
    },

    /**
     * Filter elements in progress
     * @param {Object} els
     * @return {Object}
     * @private
     */
    _filterElementsInProgress: function (els) {
        var filtered = {};

        for (var key in els) {
            if (!this.elementsInProgress[key]) {
                filtered[key] = els[key];
            }
        }

        return filtered;
    },

    /**
     * Toggle elements in progress
     * @param {Object} els
     * @param {Boolean} status
     * @private
     */
    _toggleElementsInProgress: function (els, status) {
        for (var key in els) {
            this.elementsInProgress[key] = status;
        }
    },

    /**
     * Filter the events in progress
     * @param {Array} events
     * @return {Array}
     * @private
     */
    _filterEventsInProgress: function (events) {
        return events.filter(function (event) {
            return !this.eventsInProgress[event];
        }.bind(this));
    },

    /**
     * Toggle events in progress
     * @param {Array} events
     * @param {Boolean} status
     * @private
     */
    _toggleEventsInProgress: function (events, status) {
        events.forEach(function (event) {
            this.eventsInProgress[event] = status;
        }.bind(this));
    }
};

