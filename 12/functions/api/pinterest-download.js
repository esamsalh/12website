const ALLOWED_MEDIA_HOST = /^(?:i|v\d*)\.pinimg\.com$/i;
const MAX_REDIRECTS = 3;

function isAllowedMediaUrl(value) {
    try {
        const url = value instanceof URL ? value : new URL(value);
        return url.protocol === 'https:' && ALLOWED_MEDIA_HOST.test(url.hostname);
    } catch (error) {
        return false;
    }
}

function inferMediaType(url, upstreamType) {
    const normalizedType = String(upstreamType || '').split(';')[0].trim().toLowerCase();
    if (/^(?:video|image)\/[a-z0-9.+-]+$/i.test(normalizedType)) return normalizedType;

    const path = url.pathname.toLowerCase();
    if (path.endsWith('.mp4')) return 'video/mp4';
    if (path.endsWith('.webm')) return 'video/webm';
    if (path.endsWith('.jpg') || path.endsWith('.jpeg')) return 'image/jpeg';
    if (path.endsWith('.png')) return 'image/png';
    if (path.endsWith('.webp')) return 'image/webp';
    if (path.endsWith('.gif')) return 'image/gif';
    return '';
}

function sanitizeFilename(value, contentType) {
    const fallbackExtension = contentType === 'video/webm' ? '.webm' :
        contentType.startsWith('video/') ? '.mp4' :
        contentType === 'image/png' ? '.png' :
        contentType === 'image/webp' ? '.webp' :
        contentType === 'image/gif' ? '.gif' : '.jpg';

    let filename = String(value || 'Pinterest')
        .replace(/[\u0000-\u001f\u007f]/g, '')
        .replace(/[\\/:*?"<>|]/g, '_')
        .replace(/\s+/g, '_')
        .replace(/_+/g, '_')
        .replace(/^\.+|\.+$/g, '')
        .slice(0, 110);

    if (!filename) filename = 'Pinterest';
    if (!/\.[a-z0-9]{2,5}$/i.test(filename)) filename += fallbackExtension;
    return filename;
}

function encodeRfc5987(value) {
    return encodeURIComponent(value).replace(/['()*]/g, function(character) {
        return '%' + character.charCodeAt(0).toString(16).toUpperCase();
    });
}

function plainResponse(message, status, extraHeaders) {
    return new Response(message, {
        status,
        headers: {
            'Content-Type': 'text/plain; charset=UTF-8',
            'Cache-Control': 'no-store',
            'X-Content-Type-Options': 'nosniff',
            'Content-Security-Policy': "default-src 'none'",
            ...(extraHeaders || {})
        }
    });
}

async function fetchAllowedMedia(initialUrl, request) {
    let mediaUrl = initialUrl;

    for (let redirectCount = 0; redirectCount <= MAX_REDIRECTS; redirectCount++) {
        if (!isAllowedMediaUrl(mediaUrl)) {
            return { error: plainResponse('Invalid Pinterest media URL.', 400) };
        }

        const headers = new Headers({
            'Accept': 'video/*, image/*;q=0.9, */*;q=0.8',
            'Referer': 'https://www.pinterest.com/'
        });
        const range = request.headers.get('Range');
        const ifRange = request.headers.get('If-Range');
        if (range) headers.set('Range', range);
        if (ifRange) headers.set('If-Range', ifRange);

        let upstream;
        try {
            upstream = await fetch(mediaUrl.href, {
                method: request.method === 'HEAD' ? 'HEAD' : 'GET',
                headers,
                redirect: 'manual'
            });
        } catch (error) {
            return { error: plainResponse('Unable to reach Pinterest media.', 502) };
        }

        if (upstream.status >= 300 && upstream.status < 400) {
            const location = upstream.headers.get('Location');
            if (!location || redirectCount === MAX_REDIRECTS) {
                return { error: plainResponse('Pinterest media redirect failed.', 502) };
            }

            let redirectedUrl;
            try {
                redirectedUrl = new URL(location, mediaUrl);
            } catch (error) {
                return { error: plainResponse('Invalid Pinterest media redirect.', 502) };
            }
            if (!isAllowedMediaUrl(redirectedUrl)) {
                return { error: plainResponse('Blocked Pinterest media redirect.', 400) };
            }
            mediaUrl = redirectedUrl;
            continue;
        }

        return { upstream, mediaUrl };
    }

    return { error: plainResponse('Pinterest media request failed.', 502) };
}

export async function onRequest(context) {
    const request = context.request;
    if (request.method !== 'GET' && request.method !== 'HEAD') {
        return plainResponse('Method not allowed.', 405, { 'Allow': 'GET, HEAD' });
    }

    const requestUrl = new URL(request.url);
    const target = requestUrl.searchParams.get('url');
    if (!target) return plainResponse('Missing Pinterest media URL.', 400);

    let mediaUrl;
    try {
        mediaUrl = new URL(target);
    } catch (error) {
        return plainResponse('Invalid Pinterest media URL.', 400);
    }
    if (!isAllowedMediaUrl(mediaUrl)) {
        return plainResponse('Invalid Pinterest media URL.', 400);
    }

    const result = await fetchAllowedMedia(mediaUrl, request);
    if (result.error) return result.error;

    const upstream = result.upstream;
    if (upstream.status !== 200 && upstream.status !== 206) {
        return plainResponse('Pinterest media is unavailable.', upstream.status === 404 ? 404 : 502);
    }

    const contentType = inferMediaType(result.mediaUrl, upstream.headers.get('Content-Type'));
    if (!contentType) return plainResponse('Unsupported Pinterest media type.', 415);

    const filename = sanitizeFilename(requestUrl.searchParams.get('filename'), contentType);
    const asciiFallback = contentType.startsWith('video/') ? 'pinterest-video.mp4' : 'pinterest-image.jpg';
    const responseHeaders = new Headers();

    ['Accept-Ranges', 'Content-Range', 'ETag', 'Last-Modified'].forEach(function(name) {
        const value = upstream.headers.get(name);
        if (value) responseHeaders.set(name, value);
    });
    responseHeaders.set('Content-Type', contentType);
    responseHeaders.set(
        'Content-Disposition',
        'attachment; filename="' + asciiFallback + '"; filename*=UTF-8\'\'' + encodeRfc5987(filename)
    );
    responseHeaders.set('Cache-Control', 'private, no-store, max-age=0');
    responseHeaders.set('X-Content-Type-Options', 'nosniff');
    responseHeaders.set('Content-Security-Policy', "default-src 'none'");

    return new Response(request.method === 'HEAD' ? null : upstream.body, {
        status: upstream.status,
        headers: responseHeaders
    });
}
