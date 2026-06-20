export async function onRequest(context) {
    const url = new URL(context.request.url);
    const targetUrl = url.searchParams.get('url');

    if (!targetUrl) {
        return json({ error: 'URL is required' });
    }

    if (!targetUrl.startsWith('http://') && !targetUrl.startsWith('https://')) {
        return json({ error: 'Invalid URL' });
    }

    try {
        const startTime = Date.now();
        const resp = await fetch(targetUrl, {
            redirect: 'follow',
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        });
        const timeMs = Date.now() - startTime;

        const body = await resp.text();
        const server = resp.headers.get('Server') || '';
        const contentType = resp.headers.get('Content-Type') || '';

        let sslResult = {};
        if (targetUrl.startsWith('https://')) {
            sslResult = { valid: true, issuer: 'Unknown', note: 'SSL certificate info not available in Cloudflare Workers environment' };
        }

        return json({
            contents: body,
            http_status: resp.status,
            server: server,
            content_type: contentType,
            time_ms: timeMs,
            ssl: sslResult
        });
    } catch (e) {
        return json({ error: e.message });
    }
}

function json(data) {
    return new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
