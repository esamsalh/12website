export async function onRequest(context) {
    const url = new URL(context.request.url);
    const domain = url.searchParams.get('domain');
    if (!domain) {
        return new Response(JSON.stringify({ error: 'no_domain' }), {
            headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
        });
    }

    const sources = [`https://rdap.org/domain/${encodeURIComponent(domain)}`];

    if (domain.endsWith('.com') || domain.endsWith('.net')) {
        sources.unshift(`https://rdap.verisign.com/com/v1/domain/${encodeURIComponent(domain)}`);
    }

    let lastError = null;
    for (const src of sources) {
        try {
            const resp = await fetch(src);
            if (resp.ok) {
                const data = await resp.json();
                return new Response(JSON.stringify(data), {
                    headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
                });
            }
            lastError = `HTTP ${resp.status}`;
        } catch (e) {
            lastError = e.message;
        }
    }

    return new Response(JSON.stringify({ error: lastError }), {
        headers: { 'Content-Type': 'application/json', 'Access-Control-Allow-Origin': '*' }
    });
}
