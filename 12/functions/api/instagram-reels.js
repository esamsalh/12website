export async function onRequest(context) {
    const url = new URL(context.request.url);
    const reelUrl = url.searchParams.get('url');

    if (!reelUrl) {
        return json({ success: false, message: 'الرجاء إدخال رابط ريلز انستقرام', items: [] });
    }

    const m = reelUrl.match(/instagram\.com\/(?:reel|reels|p)\/([a-zA-Z0-9_-]+)/);
    if (!m) {
        return json({ success: false, message: 'رابط ريلز انستقرام غير صالح', items: [] });
    }

    const shortcode = m[1];
    const result = { success: false, message: 'لم نتمكن من جلب الفيديو', items: [], user: { username: '', full_name: '', profile_pic: '' } };

    try {
        // Method 1: oEmbed API
        const oembedResp = await fetch('https://api.instagram.com/oembed?url=https://www.instagram.com/reel/' + shortcode + '/', {
            headers: { 'Accept': 'application/json' }
        });

        if (oembedResp.ok) {
            const oembed = await oembedResp.json();
            result.user.full_name = oembed.author_name || '';
            result.user.username = oembed.author_name || '';
            result.user.profile_pic = oembed.author_url || '';

            // Method 2: Instagram internal API
            const apiResp = await fetch('https://i.instagram.com/api/v1/media/' + shortcode + '/info/', {
                headers: {
                    'Accept': '*/*',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-IG-App-ID': '936619743392459',
                    'Referer': 'https://www.instagram.com/',
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                }
            });

            if (apiResp.ok) {
                const apiData = await apiResp.json();
                if (apiData && apiData.items && apiData.items[0]) {
                    const media = apiData.items[0];
                    if (media.user && media.user.username) {
                        result.user.username = media.user.username;
                        result.user.full_name = media.user.full_name || media.user.username;
                        result.user.profile_pic = media.user.profile_pic_url || '';
                    }

                    if (media.video_versions) {
                        let bestUrl = '';
                        let bestWidth = 0;
                        for (const vv of media.video_versions) {
                            if ((vv.width || 0) > bestWidth) {
                                bestWidth = vv.width;
                                bestUrl = vv.url || '';
                            }
                        }
                        if (bestUrl) {
                            result.items.push({
                                type: 'video',
                                url: bestUrl,
                                thumbnail: (media.image_versions2?.candidates?.[0]?.url) || oembed.thumbnail_url || '',
                                quality: bestWidth >= 1080 ? 'HD' : (bestWidth >= 720 ? 'SD' : 'normal'),
                                width: bestWidth
                            });
                            result.success = true;
                            result.message = 'تم جلب الفيديو بنجاح';
                            return json(result);
                        }
                    }
                }
            }
        }
    } catch (e) {}

    // Method 3: Scrape the reel page
    try {
        const pageResp = await fetch('https://www.instagram.com/reel/' + shortcode + '/', {
            headers: {
                'Accept': 'text/html,*/*',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'X-Requested-With': 'XMLHttpRequest',
                'X-IG-App-ID': '936619743392459',
                'Referer': 'https://www.instagram.com/'
            }
        });

        if (pageResp.ok) {
            const html = await pageResp.text();

            // Try __INITIAL_STATE__
            const stateMatch = html.match(/window\.__INITIAL_STATE__\s*=\s*({.+?});\s*</s);
            if (stateMatch) {
                try {
                    const data = JSON.parse(stateMatch[1]);
                    const sm = data.shortcode_media;
                    if (sm) {
                        if (sm.owner) {
                            result.user.username = sm.owner.username || '';
                            result.user.full_name = sm.owner.full_name || '';
                            result.user.profile_pic = sm.owner.profile_pic_url || '';
                        }
                        if (sm.video_url) {
                            result.items.push({
                                type: 'video', url: sm.video_url,
                                thumbnail: sm.display_url || '',
                                quality: sm.is_video ? 'HD' : 'normal',
                                width: sm.dimensions?.width || 0
                            });
                        } else if (sm.display_url) {
                            result.items.push({
                                type: 'image', url: sm.display_url,
                                thumbnail: sm.display_url, quality: 'HD',
                                width: sm.dimensions?.width || 0
                            });
                        }
                        if (result.items.length > 0) {
                            result.success = true;
                            result.message = 'تم جلب المحتوى بنجاح';
                            return json(result);
                        }
                    }
                } catch (e) {}
            }

            // Try __NEXT_DATA__
            const nextMatch = html.match(/<script[^>]*id="__NEXT_DATA__"[^>]*>({.+?})<\/script>/s);
            if (nextMatch) {
                try {
                    const nd = JSON.parse(nextMatch[1]);
                    const pp = nd.props?.pageProps;
                    if (pp?.media) {
                        const media = pp.media;
                        if (media.owner) {
                            result.user.username = media.owner.username || '';
                            result.user.full_name = media.owner.full_name || '';
                            result.user.profile_pic = media.owner.profile_pic_url || '';
                        }
                        if (media.video_url) {
                            result.items.push({
                                type: 'video', url: media.video_url,
                                thumbnail: media.display_url || '', quality: 'HD',
                                width: media.dimensions?.width || 0
                            });
                        }
                        if (result.items.length > 0) {
                            result.success = true;
                            result.message = 'تم جلب المحتوى بنجاح';
                            return json(result);
                        }
                    }
                } catch (e) {}
            }
        }
    } catch (e) {}

    // Method 4: Direct API call as last resort
    try {
        const apiResp = await fetch('https://i.instagram.com/api/v1/media/' + shortcode + '/info/', {
            headers: {
                'Accept': '*/*',
                'X-Requested-With': 'XMLHttpRequest',
                'X-IG-App-ID': '936619743392459',
                'Referer': 'https://www.instagram.com/',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        });

        if (apiResp.ok) {
            const mediaData = await apiResp.json();
            if (mediaData?.items?.[0]) {
                const media = mediaData.items[0];
                if (media.user?.username) {
                    result.user.username = media.user.username;
                    result.user.full_name = media.user.full_name || '';
                    result.user.profile_pic = media.user.profile_pic_url || '';
                }
                if (media.video_versions) {
                    let bestUrl = '';
                    let bestWidth = 0;
                    for (const vv of media.video_versions) {
                        if ((vv.width || 0) > bestWidth) {
                            bestWidth = vv.width;
                            bestUrl = vv.url || '';
                        }
                    }
                    if (bestUrl) {
                        result.items.push({
                            type: 'video', url: bestUrl,
                            thumbnail: media.image_versions2?.candidates?.[0]?.url || '',
                            quality: bestWidth >= 1080 ? 'HD' : (bestWidth >= 720 ? 'SD' : 'normal'),
                            width: bestWidth
                        });
                        result.success = true;
                        result.message = 'تم جلب الفيديو بنجاح';
                        return json(result);
                    }
                }
            }
        }
    } catch (e) {}

    return json(result);
}

function json(data) {
    return new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
