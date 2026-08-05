export default {
    async fetch(request, env) {
        const url = new URL(request.url);

        /*
         * اختبار أن Worker يعمل.
         */
        if (url.pathname === "/api/rating-test") {
            return new Response(
                JSON.stringify({
                    ok: true,
                    message: "ToolRar API Worker is working",
                    databaseConnected: Boolean(env.RATINGS_DB),
                    pathname: url.pathname
                }),
                {
                    status: 200,
                    headers: {
                        "Content-Type": "application/json; charset=UTF-8",
                        "Cache-Control": "no-store",
                        "X-Content-Type-Options": "nosniff"
                    }
                }
            );
        }

        /*
         * بقية ملفات الموقع HTML وCSS وJS والصور.
         */
        return env.ASSETS.fetch(request);
    }
};