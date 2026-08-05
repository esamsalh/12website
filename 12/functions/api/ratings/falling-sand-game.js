const PAGE_KEY = "falling-sand-game";
const PAGE_URL =
  "https://www.toolrar.com/en/games/falling-sand-game";

const MAX_BODY_BYTES = 512;

const RESPONSE_HEADERS = {
  "content-type": "application/json; charset=UTF-8",
  "cache-control": "no-store, max-age=0",
  "x-content-type-options": "nosniff",
  "referrer-policy": "same-origin"
};

function jsonResponse(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: RESPONSE_HEADERS
  });
}

function formatRating(row, userRating = null) {
  const ratingSum = Number(row?.rating_sum || 0);
  const ratingCount = Number(row?.rating_count || 0);

  const ratingValue =
    ratingCount > 0
      ? Number((ratingSum / ratingCount).toFixed(2))
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

async function sha256Hex(value) {
  const encoded = new TextEncoder().encode(value);
  const hashBuffer = await crypto.subtle.digest(
    "SHA-256",
    encoded
  );

  return Array.from(new Uint8Array(hashBuffer))
    .map((byte) => byte.toString(16).padStart(2, "0"))
    .join("");
}

function requestIsSameOrigin(request) {
  const requestOrigin = new URL(request.url).origin;
  const sentOrigin = request.headers.get("Origin");

  return sentOrigin === requestOrigin;
}

async function getRatingRow(database) {
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

/*
 * GET /api/ratings/falling-sand-game
 *
 * Returns the current average and rating count.
 */
export async function onRequestGet(context) {
  try {
    const database = context.env.RATINGS_DB;

    if (!database) {
      return jsonResponse(
        {
          ok: false,
          error: "database_binding_missing"
        },
        500
      );
    }

    const row = await getRatingRow(database);

    if (!row) {
      return jsonResponse(
        {
          ok: false,
          error: "rating_page_not_found"
        },
        404
      );
    }

    return jsonResponse(formatRating(row));
  } catch (error) {
    console.error("Rating GET error:", error);

    return jsonResponse(
      {
        ok: false,
        error: "rating_read_failed"
      },
      500
    );
  }
}

/*
 * POST /api/ratings/falling-sand-game
 *
 * Expected body:
 * {
 *   "rating": 5,
 *   "visitorId": "browser-generated-uuid"
 * }
 */
export async function onRequestPost(context) {
  try {
    const { request, env } = context;

    if (!requestIsSameOrigin(request)) {
      return jsonResponse(
        {
          ok: false,
          error: "invalid_origin"
        },
        403
      );
    }

    const contentType =
      request.headers.get("content-type") || "";

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
    const bodySize = new TextEncoder().encode(rawBody).byteLength;

    if (bodySize > MAX_BODY_BYTES) {
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
    const visitorId = String(body.visitorId || "").trim();

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
     * crypto.randomUUID() generates a 36-character value.
     * This also accepts similarly safe browser identifiers.
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

    if (!env.RATINGS_DB) {
      return jsonResponse(
        {
          ok: false,
          error: "database_binding_missing"
        },
        500
      );
    }

    if (!env.RATING_HASH_SECRET) {
      return jsonResponse(
        {
          ok: false,
          error: "rating_secret_missing"
        },
        500
      );
    }

    const voterHash = await sha256Hex(
      `${PAGE_KEY}:${visitorId}:${env.RATING_HASH_SECRET}`
    );

    const upsertVote = env.RATINGS_DB
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
      .bind(PAGE_KEY, voterHash, rating);

    const readUpdatedRating = env.RATINGS_DB
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

    /*
     * Both statements run sequentially as one D1 batch.
     * If one statement fails, the batch is rolled back.
     */
    const results = await env.RATINGS_DB.batch([
      upsertVote,
      readUpdatedRating
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
      formatRating(updatedRow, rating),
      200
    );
  } catch (error) {
    console.error("Rating POST error:", error);

    return jsonResponse(
      {
        ok: false,
        error: "rating_write_failed"
      },
      500
    );
  }
}