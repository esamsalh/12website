export async function onRequest(context) {
    const url = new URL(context.request.url);
    const mediaUrl = url.searchParams.get('url');
    const type = url.searchParams.get('type') || '';

    if (!mediaUrl) {
        return new Response('Invalid URL', { status: 400 });
    }

    try {
        const resp = await fetch(mediaUrl, {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept': '*/*',
                'Referer': 'https://www.instagram.com/',
                'Origin': 'https://www.instagram.com'
            }
        });

        if (!resp.ok) {
            return json({ error: 'Failed to fetch media', code: resp.status }, resp.status);
        }

        const contentType = resp.headers.get('Content-Type') || 'video/mp4';
        const body = await resp.arrayBuffer();

        const ext = type === 'video' || contentType.includes('video') ? '.mp4' : '.jpg';
        const filename = 'instagram_' + Date.now() + ext;

        return new Response(body, {
            headers: {
                'Content-Type': ext === '.mp4' ? 'video/mp4' : contentType,
                'Content-Disposition': 'attachment; filename="' + filename + '"',
                'Cache-Control': 'public, max-age=3600',
                'Access-Control-Allow-Origin': '*'
            }
        });
    } catch (e) {
        return json({ error: e.message }, 500);
    }
}

function json(data, status = 200) {
    return new Response(JSON.stringify(data), {
        status, headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
