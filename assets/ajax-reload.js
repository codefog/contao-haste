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
            events.forEach(function (event) {
                sendRequest(els, event);
            });
        }
    }

    /**
     * Send the request
     * @param {Object} els
     * @param {String} event
     */
    function sendRequest(els, event) {
        // Abort the current request, if any
        if (eventsInProgress[event]) {
            eventsInProgress[event].abort();
        }

        for (var key in els) {
            // Mark the events to be updated by this event
            elementsInProgress[key] = event;

            // Add the CSS class
            els[key].classList.add('haste-ajax-reloading');
        }

        var xhr = new XMLHttpRequest();

        xhr.open('GET', encodeURI(window.location.href));
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Haste-Ajax-Reload', event);

        xhr.onload = function () {
            if (xhr.status === 200) {
                JSON.parse(xhr.responseText).forEach(function (module) {
                    // Replace the module only if it's marked to be updated by this event
                    if (els[module.id] && elementsInProgress[module.id] === event) {
                        // Update the DOM
                        var tmp = document.createElement('div');
                        tmp.innerHTML = module.buffer;
                        els[module.id].parentNode.replaceChild(tmp.childNodes[0], els[module.id]);

                        // The element is no longer in progress
                        elementsInProgress[module.id] = null;
                    }
                });
            } else {
                console.error('The request for event "' + event + '" has failed');
                console.error(xhr);
            }

            eventsInProgress[event] = null;
        };

        xhr.send();
        eventsInProgress[event] = xhr;
    }

    // Public API
    window.HasteAjaxReload = {
        dispatchEvents: dispatchEvents
    };
})();
