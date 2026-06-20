export async function onRequest(context) {
    const url = new URL(context.request.url);
    let input = (url.searchParams.get('channelId') || '').trim();
    const apiKey = context.env.YOUTUBE_API_KEY || '';

    if (!input) {
        return new Response(JSON.stringify({ error: 'no_channel_id' }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
    }

    async function fetchJson(apiUrl) {
        const resp = await fetch(apiUrl);
        if (!resp.ok) return null;
        return resp.json();
    }

    // --- Resolve input to channel ID ---
    let channelId = '';

    if (/^UC[\w-]{22}$/.test(input)) {
        channelId = input;
    } else if (/youtube\.com\/channel\/(UC[\w-]+)/i.test(input)) {
        channelId = input.match(/youtube\.com\/channel\/(UC[\w-]+)/i)[1];
    } else if (/youtube\.com\/(?:user\/|c\/|@)([\w.-]+)/i.test(input)) {
        const username = input.match(/youtube\.com\/(?:user\/|c\/|@)([\w.-]+)/i)[1];
        let data = await fetchJson(`https://www.googleapis.com/youtube/v3/channels?part=id&forUsername=${encodeURIComponent(username)}&key=${encodeURIComponent(apiKey)}`);
        if (data && data.items && data.items.length > 0) {
            channelId = data.items[0].id;
        } else {
            data = await fetchJson(`https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(username)}&type=channel&maxResults=5&key=${encodeURIComponent(apiKey)}`);
            if (data && data.items && data.items.length > 0) {
                const searchStr = username.replace(/[^a-z0-9]/gi, '').toLowerCase();
                for (const item of data.items) {
                    const itemTitle = (item.snippet.title || '').replace(/[^a-z0-9]/gi, '').toLowerCase();
                    if (itemTitle === searchStr || item.snippet.title.toLowerCase().includes(username.toLowerCase())) {
                        channelId = item.snippet.channelId;
                        break;
                    }
                }
                if (!channelId) channelId = data.items[0].snippet.channelId;
            }
        }
    } else {
        let username = input.replace(/^@/, '');
        let data = await fetchJson(`https://www.googleapis.com/youtube/v3/channels?part=id&forUsername=${encodeURIComponent(username)}&key=${encodeURIComponent(apiKey)}`);
        if (data && data.items && data.items.length > 0) {
            channelId = data.items[0].id;
        } else {
            data = await fetchJson(`https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(username)}&type=channel&maxResults=5&key=${encodeURIComponent(apiKey)}`);
            if (data && data.items && data.items.length > 0) {
                const searchStr = username.replace(/[^a-z0-9]/gi, '').toLowerCase();
                for (const item of data.items) {
                    const itemTitle = (item.snippet.title || '').replace(/[^a-z0-9]/gi, '').toLowerCase();
                    if (itemTitle === searchStr || item.snippet.title.toLowerCase().includes(username.toLowerCase())) {
                        channelId = item.snippet.channelId;
                        break;
                    }
                }
                if (!channelId) channelId = data.items[0].snippet.channelId;
            }
        }
    }

    if (!channelId) {
        return new Response(JSON.stringify({ error: 'channel_not_found' }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
    }

    // --- Fetch channel stats ---
    const apiUrl = `https://www.googleapis.com/youtube/v3/channels?part=statistics,snippet&id=${encodeURIComponent(channelId)}&key=${encodeURIComponent(apiKey)}`;

    try {
        const resp = await fetch(apiUrl);
        const data = await resp.json();

        if (!resp.ok || data.error) {
            return new Response(JSON.stringify({
                error: 'api_error',
                message: data.error ? (data.error.message || JSON.stringify(data.error)) : 'HTTP ' + resp.status
            }), {
                headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
            });
        }

        if (!data.items || data.items.length === 0) {
            return new Response(JSON.stringify({ error: 'channel_not_found' }), {
                headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
            });
        }

        const channel = data.items[0];
        const stats = channel.statistics || {};
        const snippet = channel.snippet || {};

        return new Response(JSON.stringify({
            channelId: channel.id,
            name: snippet.title || 'Unknown',
            avatar: snippet.thumbnails?.medium?.url || snippet.thumbnails?.default?.url || '',
            subscriberCount: parseInt(stats.subscriberCount || 0),
            viewCount: parseInt(stats.viewCount || 0),
            videoCount: parseInt(stats.videoCount || 0),
            hiddenSubscriberCount: stats.hiddenSubscriberCount || false,
            publishedAt: snippet.publishedAt || ''
        }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });

    } catch (e) {
        return new Response(JSON.stringify({ error: 'fetch_failed', message: e.message }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
    }
}
