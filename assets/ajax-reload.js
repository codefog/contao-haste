(function () {
    var elementsInProgress = {};
    var eventsInProgress = {};

    /**
     * Dispatch the events
     */
    function dispatchEvents() {
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

            if (found) {
                events.push(event);
            }
        });

        if (Object.keys(els).length > 0 && events.length > 0) {
            sendRequest(els, events);
        }
    }

    /**
     * Send the request
     * @param {Object} els
     * @param {Array} events
     */
    function sendRequest(els, events) {
        els = filterElementsInProgress(els);
        events = filterEventsInProgress(events);

        // Return if everything is in progress and there is nothing to update
        if (Object.keys(els).length < 1 || events.length < 1) {
            return;
        }

        toggleElementsInProgress(els, true);
        toggleEventsInProgress(events, true);

        // Add the CSS class
        for (var key in els) {
            els[key].className += ' haste-ajax-reloading';
        }

        var xhr = new XMLHttpRequest();

        xhr.open('GET', encodeURI(window.location.href));
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Haste-Ajax-Reload', events);

        xhr.onload = function () {
            if (xhr.status === 200) {
                JSON.parse(xhr.responseText).forEach(function (module) {
                    if (els[module.id]) {
                        var tmp = document.createElement('div');
                        tmp.innerHTML = module.buffer;
                        els[module.id].parentNode.replaceChild(tmp.childNodes[0], els[module.id]);
                    }
                });
            } else {
                console.error('The request for events "' + events.join(', ') + '" has failed');
                console.error(xhr);
            }

            toggleElementsInProgress(els, false);
            toggleEventsInProgress(events, false);
        };

        xhr.send();
    }

    /**
     * Filter elements in progress
     * @param {Object} els
     * @return {Object}
     */
    function filterElementsInProgress(els) {
        var filtered = {};

        for (var key in els) {
            if (!elementsInProgress[key]) {
                filtered[key] = els[key];
            }
        }

        return filtered;
    }

    /**
     * Toggle elements in progress
     * @param {Object} els
     * @param {Boolean} status
     */
    function toggleElementsInProgress(els, status) {
        for (var key in els) {
            elementsInProgress[key] = status;
        }
    }

    /**
     * Filter the events in progress
     * @param {Array} events
     * @return {Array}
     */
    function filterEventsInProgress(events) {
        return events.filter(function (event) {
            return !eventsInProgress[event];
        });
    }

    /**
     * Toggle events in progress
     * @param {Array} events
     * @param {Boolean} status
     */
    function toggleEventsInProgress(events, status) {
        events.forEach(function (event) {
            eventsInProgress[event] = status;
        });
    }

    // Public API
    window.HasteAjaxReload = {
        dispatchEvents: dispatchEvents
    };
})();
