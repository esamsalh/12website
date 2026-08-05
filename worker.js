const PAGE_KEY = "falling-sand-game";
const PAGE_URL =
    "https://www.toolrar.com/en/games/falling-sand-game";

const RATING_API_PATH =
    "/api/ratings/falling-sand-game";

const MAX_BODY_BYTES = 512;

function jsonResponse(data, status = 200) {
    return new Response(JSON.stringify(data), {
        status,
        headers: {
            "Content-Type": "application/json; charset=UTF-8",
            "Cache-Control": "no-store, max-age=0",
            "X-Content-Type-Options": "nosniff",
            "Referrer-Policy": "same-origin"
        }
    });
}

async function sha256(value) {
    const bytes = new TextEncoder().encode(value);

    const digest = await crypto.subtle.digest(
        "SHA-256",
        bytes
    );

    return Array.from(new Uint8Array(digest))
        .map((byte) =>
            byte.toString(16).padStart(2, "0")
        )
        .join("");
}

function normalizeStatistics(row, userRating = null) {
    const ratingSum = Number(row?.rating_sum || 0);
    const ratingCount = Number(row?.rating_count || 0);

    const ratingValue =
        ratingCount > 0
            ? Number(
                (ratingSum / ratingCount).toFixed(2)
            )
            : null;

    return {
        ok: true,
        pageKey: PAGE_KEY,
        pageUrl: PAGE_URL,
        ratingValue,
        ratingCount,
        bestRating: 5,
        worstRating: 1,
        userRating,
        updatedAt: row?.updated_at || null
    };
}

async function getRatingStatistics(database) {
    return database
        .prepare(`
            SELECT
                rating_sum,
                rating_count,
                updated_at
            FROM rating_pages
            WHERE page_key = ?
            LIMIT 1
        `)
        .bind(PAGE_KEY)
        .first();
}

function hasValidOrigin(request) {
    const requestUrl = new URL(request.url);
    const origin = request.headers.get("Origin");

    /*
     * GET requests opened directly may not contain Origin.
     */
    if (!origin) {
        return request.method === "GET" ||
            request.method === "HEAD";
    }

    return origin === requestUrl.origin;
}

async function handleRatingGet(env) {
    if (!env.RATINGS_DB) {
        return jsonResponse(
            {
                ok: false,
                error: "database_binding_missing"
            },
            500
        );
    }

    const row = await getRatingStatistics(
        env.RATINGS_DB
    );

    if (!row) {
        return jsonResponse(
            {
                ok: false,
                error: "rating_page_not_found"
            },
            404
        );
    }

    return jsonResponse(normalizeStatistics(row));
}

async function handleRatingPost(request, env) {
    if (!hasValidOrigin(request)) {
        return jsonResponse(
            {
                ok: false,
                error: "invalid_origin"
            },
            403
        );
    }

    if (!env.RATINGS_DB) {
        return jsonResponse(
            {
                ok: false,
                error: "database_binding_missing"
            },
            500
        );
    }

    const contentType =
        request.headers.get("Content-Type") || "";

    if (
        !contentType
            .toLowerCase()
            .startsWith("application/json")
    ) {
        return jsonResponse(
            {
                ok: false,
                error: "content_type_must_be_json"
            },
            415
        );
    }

    const rawBody = await request.text();

    const bodyBytes =
        new TextEncoder().encode(rawBody).byteLength;

    if (bodyBytes > MAX_BODY_BYTES) {
        return jsonResponse(
            {
                ok: false,
                error: "request_body_too_large"
            },
            413
        );
    }

    let body;

    try {
        body = JSON.parse(rawBody);
    } catch {
        return jsonResponse(
            {
                ok: false,
                error: "invalid_json"
            },
            400
        );
    }

    const rating = Number(body.rating);

    const visitorId = String(
        body.visitorId || ""
    ).trim();

    if (
        !Number.isInteger(rating) ||
        rating < 1 ||
        rating > 5
    ) {
        return jsonResponse(
            {
                ok: false,
                error: "rating_must_be_between_1_and_5"
            },
            400
        );
    }

    /*
     * Accepts crypto.randomUUID() and similarly
     * generated anonymous browser identifiers.
     */
    if (
        !/^[a-zA-Z0-9_-]{20,100}$/.test(visitorId)
    ) {
        return jsonResponse(
            {
                ok: false,
                error: "invalid_visitor_id"
            },
            400
        );
    }

    /*
     * D1 stores only a one-way hash, not the original
     * browser identifier.
     */
    const voterHash = await sha256(
        `${PAGE_KEY}:${visitorId}`
    );

    const saveVote = env.RATINGS_DB
        .prepare(`
            INSERT INTO rating_votes (
                page_key,
                voter_hash,
                rating
            )
            VALUES (?, ?, ?)

            ON CONFLICT(page_key, voter_hash)
            DO UPDATE SET
                rating = excluded.rating,
                updated_at = CURRENT_TIMESTAMP
        `)
        .bind(
            PAGE_KEY,
            voterHash,
            rating
        );

    const readStatistics = env.RATINGS_DB
        .prepare(`
            SELECT
                rating_sum,
                rating_count,
                updated_at
            FROM rating_pages
            WHERE page_key = ?
            LIMIT 1
        `)
        .bind(PAGE_KEY);

    const results = await env.RATINGS_DB.batch([
        saveVote,
        readStatistics
    ]);

    const updatedRow =
        results?.[1]?.results?.[0] || null;

    if (!updatedRow) {
        return jsonResponse(
            {
                ok: false,
                error: "updated_rating_not_found"
            },
            500
        );
    }

    return jsonResponse(
        normalizeStatistics(
            updatedRow,
            rating
        )
    );
}

export default {
    async fetch(request, env) {
        try {
            const url = new URL(request.url);

            const pathname =
                url.pathname.replace(/\/+$/, "") || "/";

            /*
             * Keep the working test route temporarily.
             */
            if (pathname === "/api/rating-test") {
                return jsonResponse({
                    ok: true,
                    message:
                        "ToolRar API Worker is working",
                    databaseConnected:
                        Boolean(env.RATINGS_DB),
                    pathname
                });
            }

            if (pathname === RATING_API_PATH) {
                if (
                    request.method === "GET" ||
                    request.method === "HEAD"
                ) {
                    const response =
                        await handleRatingGet(env);

                    if (request.method === "HEAD") {
                        return new Response(null, {
                            status: response.status,
                            headers: response.headers
                        });
                    }

                    return response;
                }

                if (request.method === "POST") {
                    return handleRatingPost(
                        request,
                        env
                    );
                }

                if (request.method === "OPTIONS") {
                    return new Response(null, {
                        status: 204,
                        headers: {
                            "Allow":
                                "GET, HEAD, POST, OPTIONS",
                            "Cache-Control": "no-store"
                        }
                    });
                }

                return jsonResponse(
                    {
                        ok: false,
                        error: "method_not_allowed"
                    },
                    405
                );
            }

            /*
             * HTML, CSS, JavaScript, images and all
             * other static website files.
             */
            return env.ASSETS.fetch(request);
        } catch (error) {
            console.error(
                "ToolRar Worker error:",
                error
            );

            return jsonResponse(
                {
                    ok: false,
                    error: "internal_server_error"
                },
                500
            );
        }
    }
};