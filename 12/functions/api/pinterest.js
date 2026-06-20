export async function onRequest(context) {
    const { request } = context;
    const url = new URL(request.url);
    const pinUrl = url.searchParams.get('url');

    if (!pinUrl) {
        return json({ error: 'missing_url', media: [] });
    }

    try {
        let finalUrl = pinUrl.trim();
        if (!finalUrl.startsWith('http')) finalUrl = 'https://' + finalUrl;

        // Expand pin.it short links
        if (/pin\.it/i.test(finalUrl)) {
            const uri = new URL(finalUrl);
            const resp = await fetch(`https://api.pinterest.com/url_shortener${uri.pathname}redirect/`, {
                method: 'HEAD', redirect: 'manual',
                headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' }
            });
            if (resp.status >= 300 && resp.status < 400) {
                finalUrl = resp.headers.get('Location') || finalUrl;
            }
        }

        if (!finalUrl.match(/pin\/(\d+)/i)) {
            return json({ error: 'invalid_url', media: [] });
        }

        // Call btch-downloader backend API
        const apiResp = await fetch('https://backend1.tioo.eu.org/pinterest?url=' + encodeURIComponent(finalUrl), {
            headers: { 'User-Agent': 'Mozilla/5.0' }
        });

        if (apiResp.ok) {
            const result = await apiResp.json();
            if (result.success && result.result) {
                const data = result.result;
                const media = [];
                const seen = new Set();

                const videos = [];
                if (data.video_url) videos.push(data.video_url);
                if (data.videos && Array.isArray(data.videos)) {
                    data.videos.forEach(v => { if (v.url) videos.push(v.url); });
                }

                for (const v of videos) {
                    if (seen.has(v)) continue;
                    seen.add(v);
                    if (/\.m3u8/i.test(v)) continue;
                    media.push({ type: 'video', url: v, thumbnail: data.image || '', title: data.title || '' });
                }

                if (media.length === 0 && data.image) {
                    media.push({ type: 'image', url: data.image, thumbnail: data.image, title: data.title || '' });
                }

                if (media.length > 0) {
                    return json({ media });
                }
            }
        }

        return json({ error: 'no_media', media: [] });
    } catch (e) {
        return json({ error: e.message, media: [] });
    }
}

function json(data) {
    return new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
