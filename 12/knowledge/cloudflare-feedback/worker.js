const JSON_HEADERS = {
  "Content-Type": "application/json; charset=utf-8",
  "Cache-Control": "no-store",
  "X-Content-Type-Options": "nosniff",
};

export default {
  async fetch(request, env) {
    const originError = validateOrigin(request, env);
    if (originError) return originError;

    if (request.method === "OPTIONS") {
      return new Response(null, { status: 204, headers: corsHeaders(env) });
    }

    try {
      if (request.method === "GET") return await getTotals(request, env);
      if (request.method === "POST") return await saveVote(request, env);
      return json({ error: "method_not_allowed" }, 405, env, { Allow: "GET, POST, OPTIONS" });
    } catch (error) {
      console.error("knowledge_feedback_error", error);
      return json({ error: "server_error" }, 500, env);
    }
  },
};

async function getTotals(request, env) {
  const page = normalizePage(new URL(request.url).searchParams.get("page"));
  if (!page) return json({ error: "invalid_page" }, 400, env);
  return json(await readTotals(env.DB, page), 200, env);
}

async function saveVote(request, env) {
  if (!String(request.headers.get("Content-Type") || "").toLowerCase().includes("application/json")) {
    return json({ error: "content_type_must_be_json" }, 415, env);
  }
  const contentLength = Number(request.headers.get("Content-Length") || 0);
  if (contentLength > 2048) return json({ error: "payload_too_large" }, 413, env);

  let body;
  try {
    body = await request.json();
  } catch {
    return json({ error: "invalid_json" }, 400, env);
  }

  const page = normalizePage(body?.page);
  const vote = body?.vote === "up" ? 1 : body?.vote === "down" ? -1 : 0;
  if (!page || !vote) return json({ error: "invalid_vote" }, 400, env);
  if (!env.FEEDBACK_SALT) return json({ error: "missing_server_secret" }, 500, env);

  const voterHash = await createVoterHash(request, env.FEEDBACK_SALT);
  await env.DB.prepare(
    `INSERT INTO guide_feedback (page_path, voter_hash, vote)
     VALUES (?, ?, ?)
     ON CONFLICT(page_path, voter_hash) DO UPDATE SET
       vote = excluded.vote,
       updated_at = datetime('now')`,
  )
    .bind(page, voterHash, vote)
    .run();

  return json(await readTotals(env.DB, page), 200, env);
}

async function readTotals(db, page) {
  const row = await db
    .prepare(
      `SELECT
         COUNT(*) AS total,
         COALESCE(SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END), 0) AS up,
         COALESCE(SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END), 0) AS down
       FROM guide_feedback
       WHERE page_path = ?`,
    )
    .bind(page)
    .first();

  return {
    page,
    up: Number(row?.up || 0),
    down: Number(row?.down || 0),
    total: Number(row?.total || 0),
  };
}

function normalizePage(value) {
  const page = String(value || "")
    .split(/[?#]/, 1)[0]
    .replace(/\.html$/i, "")
    .replace(/\/+$/, "")
    .toLowerCase();
  return /^\/knowledge\/[a-z0-9-]+\/[a-z0-9-]+$/.test(page) ? page : "";
}

async function createVoterHash(request, salt) {
  const ip = request.headers.get("CF-Connecting-IP") || "unknown";
  const userAgent = request.headers.get("User-Agent") || "unknown";
  const bytes = new TextEncoder().encode(`${salt}\n${ip}\n${userAgent}`);
  const digest = await crypto.subtle.digest("SHA-256", bytes);
  return [...new Uint8Array(digest)].map((byte) => byte.toString(16).padStart(2, "0")).join("");
}

function validateOrigin(request, env) {
  const origin = request.headers.get("Origin");
  const allowed = env.ALLOWED_ORIGIN || "https://www.toolrar.com";
  if (origin && origin !== allowed) return json({ error: "origin_not_allowed" }, 403, env);
  return null;
}

function corsHeaders(env) {
  return {
    "Access-Control-Allow-Origin": env.ALLOWED_ORIGIN || "https://www.toolrar.com",
    "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
    "Access-Control-Allow-Headers": "Content-Type",
    "Access-Control-Max-Age": "86400",
    Vary: "Origin",
  };
}

function json(payload, status, env, extra = {}) {
  return new Response(JSON.stringify(payload), {
    status,
    headers: { ...JSON_HEADERS, ...corsHeaders(env), ...extra },
  });
}
