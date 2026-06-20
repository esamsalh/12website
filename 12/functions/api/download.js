export async function onRequest(context) {
    const { request } = context;
    const url = new URL(request.url);
    const fileUrl = url.searchParams.get('url');

    if (!fileUrl) {
        return new Response('missing_url', { status: 400 });
    }

    try {
        const resp = await fetch(fileUrl, {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept': '*/*',
                'Referer': 'https://www.pinterest.com/'
            }
        });

        if (!resp.ok) {
            return new Response('download_failed', { status: 502 });
        }

        const contentType = resp.headers.get('Content-Type') || 'application/octet-stream';
        const contentDisp = resp.headers.get('Content-Disposition') || '';

        const path = new URL(fileUrl).pathname;
        const filename = path.split('/').pop() || 'download.mp4';

        const headers = new Headers(resp.headers);
        headers.set('Access-Control-Allow-Origin', '*');
        headers.set('Content-Disposition', 'attachment; filename="' + filename + '"');

        return new Response(resp.body, {
            status: resp.status,
            headers: headers
        });
    } catch (e) {
        return new Response(e.message, { status: 502 });
    }
}
