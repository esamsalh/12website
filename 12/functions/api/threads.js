export async function onRequest(context) {
    const { searchParams } = new URL(context.request.url);
    let postUrl = searchParams.get('url') || '';

    // Clean URL: strip extra text and tracking params
    const m = postUrl.match(/(https?:\/\/(?:www\.)?threads\.(?:net|com)\/[^\s"'<>]+)/i);
    if (m) postUrl = m[1];
    postUrl = postUrl.split('?')[0];

    if (!/^https?:\/\/(www\.)?threads\.(net|com)\//i.test(postUrl)) {
        return new Response(JSON.stringify({ error: 'invalid_url', media: [] }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
    }

    try {
        // Normalize threads.com -> threads.net for lovethreads API
        const apiUrl = postUrl.replace(/threads\.com\//i, 'threads.net/');

        // Method 1: Use lovethreads.net API (reliable, handles auth)
        const body = new URLSearchParams({ q: apiUrl, t: 'media', lang: 'en' });
        const ltResp = await fetch('https://lovethreads.net/api/ajaxSearch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin': 'https://lovethreads.net',
                'Referer': 'https://lovethreads.net/en',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body.toString()
        });

        if (ltResp.ok) {
            const ltData = await ltResp.json();
            if (ltData.status === 'ok' && ltData.data) {
                const html = ltData.data;
                const media = [];
                const seen = new Set();

                function addMedia(type, url, thumb) {
                    if (!url || seen.has(url)) return;
                    seen.add(url);
                    media.push({ type, url, thumbnail: thumb || '' });
                }

                if (ltData.p === 'threads') {
                    const thumbM = html.match(/<img[^>]+src="([^"]+)"[^>]*alt="LoveThreads"/i);
                    const thumbUrl = thumbM ? thumbM[1] : '';

                    // Match download links with href before title (actual lovethreads order)
                    const dlRe = /<a[^>]*\bhref="([^"]+)"[^>]*\btitle="Download (Video|Thumbnail)"[^>]*>/gi;
                    let m;
                    while ((m = dlRe.exec(html)) !== null) {
                        addMedia(m[2] === 'Video' ? 'video' : 'image', m[1], thumbUrl);
                    }

                    // Also match if title comes before href
                    const dlRe2 = /<a[^>]*\btitle="Download (Video|Thumbnail)"[^>]*\bhref="([^"]+)"[^>]*>/gi;
                    while ((m = dlRe2.exec(html)) !== null) {
                        addMedia(m[1] === 'Video' ? 'video' : 'image', m[2], thumbUrl);
                    }

                    // Fallback: option values for image quality selection
                    if (media.length === 0) {
                        const optRe = /<option value="([^"]+)">(\d+x\d+)<\/option>/gi;
                        const options = [];
                        while ((m = optRe.exec(html)) !== null) {
                            options.push({ url: m[1], label: m[2] });
                        }
                        if (options.length > 0) {
                            const best = options.reduce((a, b) => {
                                const [aw, ah] = a.label.split('x').map(Number);
                                const [bw, bh] = b.label.split('x').map(Number);
                                return (aw * ah > bw * bh) ? a : b;
                            });
                            addMedia('image', best.url, thumbUrl);
                        } else if (thumbUrl) {
                            addMedia('image', thumbUrl, thumbUrl);
                        }
                    }
                }

                if (media.length > 0) {
                    // If video exists, keep only videos (hide duplicate images for video posts)
                    const hasVideo = media.some(m => m.type === 'video');
                    const result = hasVideo ? media.filter(m => m.type === 'video') : media;
                    return new Response(JSON.stringify({ media: result }), {
                        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
                    });
                }
            }
        }

        // Method 2: Fallback - scrape meta tags from page
        const pageResp = await fetch(postUrl, {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept': 'text/html,application/xhtml+xml',
                'Accept-Language': 'en-US,en;q=0.9'
            }
        });

        if (pageResp.ok) {
            const html = await pageResp.text();
            const media = [];
            const seen = new Set();

            function addMedia(type, url, thumb) {
                if (!url || seen.has(url)) return;
                seen.add(url);
                media.push({ type, url, thumbnail: thumb || '' });
            }

            const ogVid = html.match(/<meta[^>]+property="og:video"[^>]+content="([^"]+)"/i);
            const ogImg = html.match(/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i);
            if (ogVid) addMedia('video', ogVid[1], ogImg ? ogImg[1] : '');

            if (media.length === 0) {
                const mp4Re = /https?:\/\/[^"'\s<>]+?\.mp4[^"'\s<>]*/gi;
                let m;
                while ((m = mp4Re.exec(html)) !== null) addMedia('video', m[0], ogImg ? ogImg[1] : '');
            }

            if (media.length > 0) {
                const hasVideo = media.some(m => m.type === 'video');
                const result = hasVideo ? media.filter(m => m.type === 'video') : media;
                return new Response(JSON.stringify({ media: result }), {
                    headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
                });
            }
        }

        return new Response(JSON.stringify({ error: 'no_media', media: [] }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });

    } catch (e) {
        return new Response(JSON.stringify({ error: e.message, media: [] }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
    }
}
