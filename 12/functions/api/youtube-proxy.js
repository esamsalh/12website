export async function onRequest(context) {
    const { request } = context;
    const url = new URL(request.url);
    const videoUrl = url.searchParams.get('url');
    const format = url.searchParams.get('format') || 'mp3';
    const quality = url.searchParams.get('quality') || '320';

    if (!videoUrl) {
        return json({ success: false, message: 'الرجاء إدخال رابط فيديو يوتيوب' });
    }

    const videoId = getYouTubeId(videoUrl);
    if (!videoId) {
        return json({ success: false, message: 'رابط يوتيوب غير صالح' });
    }

    try {
        const params = new URLSearchParams({
            url: 'https://www.youtube.com/watch?v=' + videoId,
            id: videoId,
            format: format,
            quality: quality
        });

        const apiResp = await fetch('https://youtube-to-mp3-converter2.p.rapidapi.com/?' + params.toString(), {
            headers: {
                'x-rapidapi-key': '6d4244b945msh051ffbb761dd90ep160e4bjsne8888f0e3c9b',
                'x-rapidapi-host': 'youtube-to-mp3-converter2.p.rapidapi.com',
                'Content-Type': 'application/json'
            }
        });

        if (!apiResp.ok) {
            return json({ success: false, message: 'خادم التحويل غير متاح حالياً (رمز الخطأ: ' + apiResp.status + ')' });
        }

        const data = await apiResp.json();

        if (data && data.messages && data.messages.includes('unreachable')) {
            return json({ success: false, message: 'عذراً، خادم RapidAPI غير متاح حالياً. يرجى استخدام روابط التحميل البديلة بالأسفل.' });
        }

        let downloadUrl = '';
        if (data) {
            const keys = ['link', 'url', 'download', 'downloadUrl', 'mp3', 'audio', 'download_url', 'href'];
            for (const key of keys) {
                if (data[key] && typeof data[key] === 'string' && data[key].startsWith('http')) {
                    downloadUrl = data[key];
                    break;
                }
            }
        }

        if (downloadUrl) {
            return json({
                success: true,
                downloadUrl: downloadUrl,
                title: data.title || 'YouTube Audio',
                data: data
            });
        } else {
            return json({
                success: false,
                message: 'لم نتمكن من الحصول على رابط تحميل مباشر. يرجى استخدام الروابط البديلة بالأسفل.',
                data: data
            });
        }
    } catch (e) {
        return json({ success: false, message: 'تعذر الاتصال بخادم التحويل: ' + e.message });
    }
}

function getYouTubeId(str) {
    const m = str.match(/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/|youtube-nocookie\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
    return m ? m[1] : null;
}

function json(data) {
    return new Response(JSON.stringify(data), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
