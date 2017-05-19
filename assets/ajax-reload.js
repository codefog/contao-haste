// CustomEvent polyfill, see https://developer.mozilla.org/en-US/docs/Web/API/CustomEvent/CustomEvent
(function () {
    if (typeof window.CustomEvent === "function") return false;

    function CustomEvent(event, params) {
        params = params || {bubbles: false, cancelable: false, detail: undefined};
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    window.CustomEvent = CustomEvent;
})();

// Array.from polyfill, see https://developer.mozilla.org/en/docs/Web/JavaScript/Reference/Global_Objects/Array/from#Polyfill
if (!Array.from) {
    Array.from = (function () {
        var toStr = Object.prototype.toString;
        var isCallable = function (fn) {
            return typeof fn === 'function' || toStr.call(fn) === '[object Function]';
        };
        var toInteger = function (value) {
            var number = Number(value);
            if (isNaN(number)) { return 0; }
            if (number === 0 || !isFinite(number)) { return number; }
            return (number > 0 ? 1 : -1) * Math.floor(Math.abs(number));
        };
        var maxSafeInteger = Math.pow(2, 53) - 1;
        var toLength = function (value) {
            var len = toInteger(value);
            return Math.min(Math.max(len, 0), maxSafeInteger);
        };

        // The length property of the from method is 1.
        return function from(arrayLike/*, mapFn, thisArg */) {
            // 1. Let C be the this value.
            var C = this;

            // 2. Let items be ToObject(arrayLike).
            var items = Object(arrayLike);

            // 3. ReturnIfAbrupt(items).
            if (arrayLike == null) {
                throw new TypeError('Array.from requires an array-like object - not null or undefined');
            }

            // 4. If mapfn is undefined, then let mapping be false.
            var mapFn = arguments.length > 1 ? arguments[1] : void undefined;
            var T;
            if (typeof mapFn !== 'undefined') {
                // 5. else
                // 5. a If IsCallable(mapfn) is false, throw a TypeError exception.
                if (!isCallable(mapFn)) {
                    throw new TypeError('Array.from: when provided, the second argument must be a function');
                }

                // 5. b. If thisArg was supplied, let T be thisArg; else let T be undefined.
                if (arguments.length > 2) {
                    T = arguments[2];
                }
            }

            // 10. Let lenValue be Get(items, "length").
            // 11. Let len be ToLength(lenValue).
            var len = toLength(items.length);

            // 13. If IsConstructor(C) is true, then
            // 13. a. Let A be the result of calling the [[Construct]] internal method
            // of C with an argument list containing the single item len.
            // 14. a. Else, Let A be ArrayCreate(len).
            var A = isCallable(C) ? Object(new C(len)) : new Array(len);

            // 16. Let k be 0.
            var k = 0;
            // 17. Repeat, while k < len… (also steps a - h)
            var kValue;
            while (k < len) {
                kValue = items[k];
                if (mapFn) {
                    A[k] = typeof T === 'undefined' ? mapFn(kValue, k) : mapFn.call(T, kValue, k);
                } else {
                    A[k] = kValue;
                }
                k += 1;
            }
            // 18. Let putStatus be Put(A, "length", len, true).
            A.length = len;
            // 20. Return A.
            return A;
        };
    }());
}

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
            var eventData = event;

            if (typeof event === 'string') {
                eventData = { name: event };
            }

            var found = false;

            // Find the elements that listen to particular event
            Array.from(listeners).forEach(function (el) {
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
            events.forEach(function (event) {
                sendRequest(els, event);
            });
        }
    }

    /**
     * Send the request
     * @param {Object} els
     * @param {Object} event
     */
    function sendRequest(els, event) {
        // Abort the current request, if any
        if (eventsInProgress[event.name]) {
            eventsInProgress[event.name].abort();
        }

        for (var key in els) {
            // Mark the events to be updated by this event
            elementsInProgress[key] = event.name;

            // Add the CSS class
            els[key].classList.add('haste-ajax-reloading');
        }

        var xhr = new XMLHttpRequest();

        xhr.open('GET', window.location.href);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Haste-Ajax-Reload', event.name);

        // Set the custom headers
        for (var header in event.headers || {}) {
            xhr.setRequestHeader(header, event.headers[header]);
        }

        xhr.onload = function () {
            if (xhr.status === 200) {
                var newEls = {};
                var entries = JSON.parse(xhr.responseText);

                Object.keys(entries).forEach(function (id) {
                    // Replace the entry only if it's marked to be updated by this event
                    if (els[id] && elementsInProgress[id] === event.name) {
                        els[id].outerHTML = entries[id];
                        elementsInProgress[id] = null;

                        // Add new element
                        newEls[id] = document.querySelector('[data-haste-ajax-id="' + id + '"]');

                        // Execute the <script> tags inside the new element
                        Array.from(newEls[id].getElementsByTagName('script')).forEach(function (script) {
                            eval(script.innerHTML);
                        });
                    }
                });

                // Dispatch a global custom event
                document.dispatchEvent(new CustomEvent('HasteAjaxReloadComplete', {
                    bubbles: false,
                    cancelable: false,
                    detail: {
                        entries: entries,
                        event: event.name,
                        eventData: event,
                        oldElements: els,
                        newElements: newEls
                    }
                }));
            } else {
                console.error('The request for event "' + event.name + '" has failed');
                console.error(xhr);
            }

            eventsInProgress[event.name] = null;
        };

        xhr.send();
        eventsInProgress[event.name] = xhr;
    }

    // Public API
    window.HasteAjaxReload = {
        dispatchEvents: dispatchEvents
    };
})();
