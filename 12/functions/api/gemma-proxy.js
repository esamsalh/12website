export async function onRequest(context) {
    const { request } = context;

    if (request.method !== 'POST') {
        return new Response('POST only', { status: 405 });
    }

    try {
        const input = await request.json();
        const prompt = (input.prompt || '').trim();
        const maxTokens = Math.min(parseInt(input.maxTokens) || 300, 1000);

        if (!prompt) {
            return json({ error: 'يرجى إدخال النص المطلوب' }, 400);
        }

        const payload = {
            messages: [{ role: 'user', content: prompt }],
            model: 'gemma-3-12b'
        };

        const apiResp = await fetch('https://gemma3.cc/api/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                'User-Agent': 'Mozilla/5.0'
            },
            body: JSON.stringify(payload)
        });

        if (!apiResp.ok) {
            return json({ error: 'خطأ من الخادم (' + apiResp.status + ')' }, apiResp.status);
        }

        const responseText = await apiResp.text();

        // Parse Vercel AI SDK SSE format: lines like 0:"text"
        let fullText = '';
        const lines = responseText.split('\n');
        for (const line of lines) {
            const trimmed = line.trim();
            if (trimmed.startsWith('0:')) {
                try {
                    const decoded = JSON.parse(trimmed.substring(2));
                    if (typeof decoded === 'string') {
                        fullText += decoded;
                    }
                } catch (e) {}
            }
        }

        fullText = fullText.trim();
        if (!fullText) {
            fullText = 'لم يتم إنشاء نص. حاول بصياغة مختلفة.';
        }

        return json({ text: fullText });
    } catch (e) {
        return json({ error: 'خطأ في الاتصال: ' + e.message }, 502);
    }
}

function json(data, status = 200) {
    return new Response(JSON.stringify(data), {
        status,
        headers: { 'Content-Type': 'application/json; charset=utf-8', 'Access-Control-Allow-Origin': '*' }
    });
}
