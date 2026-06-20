export async function onRequest(context) {
    const url = new URL(context.request.url);
    const go = url.searchParams.get('go');
    const save = url.searchParams.get('save');

    // Handle redirect: ?go=CODE
    if (go) {
        try {
            const resp = await fetch('https://jsonblob.com/api/jsonBlob/' + go);
            if (resp.ok) {
                const data = await resp.json();
                if (data.url) {
                    return Response.redirect(data.url, 302);
                }
            }
        } catch (e) {}
        return new Response('الرابط غير صالح أو منتهي الصلاحية', { status: 404 });
    }

    // Handle save: ?save=URL
    if (save) {
        const code = generateCode();
        const blob = { url: save, created: Date.now() };

        try {
            const resp = await fetch('https://jsonblob.com/api/jsonBlob', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(blob)
            });

            if (resp.ok) {
                const location = resp.headers.get('Location') || '';
                const id = location.split('/').pop();
                return json({ success: true, code: id });
            }
        } catch (e) {}

        return json({ success: true, code: code });
    }

    return json({ error: 'invalid_request' });
}

function generateCode() {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < 6; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

function json(data) {
    return new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
