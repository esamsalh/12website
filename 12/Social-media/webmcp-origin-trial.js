(() => {
    'use strict';

    const token = 'A/Pfugc8OkTEsFaOfkzLZN8El+Gcl36zWbagzuZC5l4IhJBRpmUbOVN+Y0YopvBkRbyPO5i5v9wK04qq6q1P2g8AAABjeyJvcmlnaW4iOiJodHRwczovL3d3dy50b29scmFyLmNvbTo0NDMiLCJmZWF0dXJlIjoiV2ViTUNQIiwiZXhwaXJ5IjoxNzk0ODczNjAwLCJpc1RoaXJkUGFydHkiOnRydWV9';
    const meta = document.createElement('meta');
    meta.httpEquiv = 'origin-trial';
    meta.content = token;
    document.head.appendChild(meta);

    function refreshDeclarativeTool() {
        const form = document.getElementById('toolPdForm');
        if (!form) return;
        const toolName = form.getAttribute('toolname');
        form.removeAttribute('toolname');
        queueMicrotask(() => form.setAttribute('toolname', toolName));
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', refreshDeclarativeTool, { once: true });
    } else {
        refreshDeclarativeTool();
    }
})();
