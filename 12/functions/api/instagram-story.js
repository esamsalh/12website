export async function onRequest(context) {
    const url = new URL(context.request.url);
    let username = url.searchParams.get('username') || '';

    if (!username) {
        return json({ success: false, message: 'الرجاء إدخال اسم المستخدم', items: [] });
    }

    username = username.replace(/[^a-zA-Z0-9_.]/g, '');
    if (username.length < 2) {
        return json({ success: false, message: 'اسم المستخدم غير صالح', items: [] });
    }

    const result = {
        success: false, message: 'لم نتمكن من جلب القصص',
        items: [], user: { username, full_name: '', profile_pic: '' }
    };

    const headers = {
        'Accept': '*/*',
        'X-Requested-With': 'XMLHttpRequest',
        'X-IG-App-ID': '936619743392459',
        'Referer': 'https://www.instagram.com/',
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    };

    // Method 1: Try Instagram web profile API
    try {
        const profileResp = await fetch('https://i.instagram.com/api/v1/users/web_profile_info/?username=' + username, { headers });
        if (profileResp.ok) {
            const profileData = await profileResp.json();
            const user = profileData?.data?.user;
            if (user) {
                result.user.full_name = user.full_name || '';
                result.user.profile_pic = user.profile_pic_url || user.profile_pic_url_hd || '';
                const userId = user.id;

                if (userId) {
                    // Try feed/reels_media
                    const storyResp = await fetch('https://i.instagram.com/api/v1/feed/reels_media/?reel_ids=' + userId, { headers });
                    if (storyResp.ok) {
                        const storyData = await storyResp.json();
                        const items = getStoryItems(storyData);
                        if (items.length > 0) {
                            result.success = true;
                            result.message = 'تم جلب القصص بنجاح';
                            result.items = items;
                            return json(result);
                        }
                    }
                }
            }
        }
    } catch (e) {}

    // Method 2: Try fastdl.app API
    try {
        const fastdlResp = await fetch('https://fastdl.app/api/v1/instagram/stories', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Referer': 'https://fastdl.app/ar/story-saver',
                'Origin': 'https://fastdl.app',
                'User-Agent': 'Mozilla/5.0'
            },
            body: JSON.stringify({ username })
        });

        if (fastdlResp.ok) {
            const fastdlData = await fastdlResp.json();
            if (fastdlData?.success && fastdlData?.data?.items) {
                const items = [];
                for (const it of fastdlData.data.items) {
                    const itemUrl = it.video_url || it.url || it.download_url || '';
                    const thumb = it.thumbnail || it.thumb || '';
                    const itemType = itemUrl && itemUrl.match(/\.mp4/i) ? 'video' : 'image';
                    if (itemUrl) {
                        items.push({ type: itemType, url: itemUrl, thumbnail: thumb, id: it.id || '' });
                    }
                }
                if (items.length > 0) {
                    result.success = true;
                    result.message = 'تم جلب القصص بنجاح';
                    result.items = items;
                    if (fastdlData.data.user) {
                        result.user.full_name = fastdlData.data.user.full_name || '';
                        result.user.profile_pic = fastdlData.data.user.profile_pic || '';
                    }
                    return json(result);
                }
            }
        }
    } catch (e) {}

    return json(result);
}

function getStoryItems(apiData) {
    const items = [];
    if (!apiData) return items;

    const processItem = (it) => {
        let url = '', thumb = '', type = 'image';
        if (it.video_versions?.[0]?.url) {
            url = it.video_versions[0].url;
            type = 'video';
            thumb = it.image_versions2?.candidates?.[0]?.url || '';
        } else if (it.image_versions2?.candidates?.[0]?.url) {
            url = it.image_versions2.candidates[0].url;
            thumb = url;
        }
        if (url) items.push({ type, url, thumbnail: thumb, id: it.id || '', taken_at: it.taken_at || 0 });
    };

    if (apiData.reels) {
        for (const userId of Object.keys(apiData.reels)) {
            if (apiData.reels[userId]?.items) {
                apiData.reels[userId].items.forEach(processItem);
            }
        }
    } else if (apiData.items) {
        apiData.items.forEach(processItem);
    } else if (apiData.reel?.items) {
        apiData.reel.items.forEach(processItem);
    }

    return items;
}

function json(data) {
    return new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
