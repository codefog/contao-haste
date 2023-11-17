(() => {
    const elementsInProgress = {};
    const eventsInProgress = {};

    function dispatchEvents() {
        if (arguments.length === 0) {
            console.error('Please provide at least one event');
        }

        const els = {};
        const events = [];
        const listeners = [...document.querySelectorAll('[data-haste-ajax-listeners]')];

        [...arguments].forEach(event => {
            let eventData = event;

            if (typeof event === 'string') {
                eventData = { name: event };
            }

            let found = false;

            // Find the elements that listen to particular event
            listeners.forEach(el => {
                if (el.dataset.hasteAjaxListeners.split(' ').indexOf(eventData.name) !== -1) {
                    found = true;
                    els[el.dataset.hasteAjaxId] = el;
                }
            });

            if (found) {
                events.push(eventData);
            }
        });

        if (Object.keys(els).length > 0 && events.length > 0) {
            events.forEach(event => sendRequest(els, event));
        }
    }

    function sendRequest(els, event) {
        // Abort the current request, if any
        if (eventsInProgress[event.name]) {
            eventsInProgress[event.name].abort();
        }

        for (const key in els) {
            // Mark the events to be updated by this event
            elementsInProgress[key] = event.name;

            // Add the CSS class
            els[key].classList.add('haste-ajax-reloading');
        }

        const xhr = new XMLHttpRequest();

        xhr.open('GET', window.location.href);
        xhr.setRequestHeader('Cache-Control', 'no-cache');
        xhr.setRequestHeader('Pragma', 'no-cache');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Haste-Ajax-Reload', event.name);

        // Set the custom headers
        for (const header in event.headers || {}) {
            xhr.setRequestHeader(header, event.headers[header]);
        }

        xhr.onload = () => {
            if (xhr.status === 200) {
                const newEls = {};
                const entries = JSON.parse(xhr.responseText);

                Object.keys(entries).forEach(id => {
                    // Replace the entry only if it's marked to be updated by this event
                    if (els[id] && elementsInProgress[id] === event.name) {
                        els[id].outerHTML = entries[id];
                        elementsInProgress[id] = null;

                        // Add new element
                        newEls[id] = document.querySelector(`[data-haste-ajax-id="${id}"]`);

                        // Execute the <script> tags inside the new element
                        [...newEls[id].getElementsByTagName('script')].forEach(script => eval(script.innerHTML));
                    }
                });

                // Dispatch a global custom event
                document.dispatchEvent(new CustomEvent('HasteAjaxReloadComplete', {
                    bubbles: false,
                    cancelable: false,
                    detail: {
                        entries,
                        event: event.name,
                        eventData: event,
                        oldElements: els,
                        newElements: newEls
                    }
                }));
            } else {
                console.error(`The request for event "${event.name}" has failed`);
                console.error(xhr);
            }

            eventsInProgress[event.name] = null;
        };

        xhr.send();
        eventsInProgress[event.name] = xhr;
    }

    // Public API
    window.HasteAjaxReload = { dispatchEvents };
})();
