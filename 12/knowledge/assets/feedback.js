(() => {
  "use strict";

  const box = document.querySelector("[data-guide-feedback]");
  if (!box) return;

  const endpoint = window.TOOLRAR_FEEDBACK_ENDPOINT || "/api/knowledge-feedback";
  const page = normalizePage(location.pathname);
  const buttons = [...box.querySelectorAll("[data-feedback-vote]")];
  const status = box.querySelector("[data-feedback-status]");
  const summary = box.querySelector("[data-feedback-summary]");
  const storageKey = `toolrar-guide-vote:${page}`;
  let selectedVote = readStoredVote();

  if (!page || buttons.length !== 2) return;
  renderSelection();
  loadTotals();

  buttons.forEach((button) => {
    button.addEventListener("click", () => submitVote(button.dataset.feedbackVote));
  });

  function normalizePage(value) {
    const normalized = String(value || "")
      .split(/[?#]/, 1)[0]
      .replace(/\.html$/i, "")
      .replace(/\/+$/, "")
      .toLowerCase();
    return /^\/knowledge\/[a-z0-9-]+\/[a-z0-9-]+$/.test(normalized) ? normalized : "";
  }

  function readStoredVote() {
    try {
      const value = localStorage.getItem(storageKey);
      return value === "up" || value === "down" ? value : "";
    } catch {
      return "";
    }
  }

  function storeVote(vote) {
    try {
      localStorage.setItem(storageKey, vote);
    } catch {
      // The server remains authoritative when storage is blocked.
    }
  }

  function renderSelection() {
    buttons.forEach((button) => {
      button.setAttribute("aria-pressed", String(button.dataset.feedbackVote === selectedVote));
    });
  }

  function setPending(pending) {
    buttons.forEach((button) => {
      button.disabled = pending;
    });
    box.setAttribute("aria-busy", String(pending));
  }

  async function loadTotals() {
    try {
      const response = await fetch(`${endpoint}?page=${encodeURIComponent(page)}`, {
        headers: { Accept: "application/json" },
        credentials: "same-origin",
      });
      if (!response.ok) return;
      renderTotals(await response.json());
    } catch {
      // Feedback stays usable even if the optional counter endpoint is unavailable.
    }
  }

  async function submitVote(vote) {
    if (vote !== "up" && vote !== "down") return;
    const previous = selectedVote;
    setPending(true);
    status.removeAttribute("data-state");
    status.textContent = "جارٍ حفظ رأيك…";

    try {
      const response = await fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ page, vote }),
      });
      const data = await response.json().catch(() => ({}));
      if (!response.ok) throw new Error(data.error || "request_failed");

      selectedVote = vote;
      storeVote(vote);
      renderSelection();
      renderTotals(data);
      status.textContent =
        previous && previous !== vote
          ? "شكرًا، تم تحديث تقييمك."
          : "شكرًا، تم تسجيل رأيك وسنستخدمه لتحسين الدليل.";
    } catch {
      status.dataset.state = "error";
      status.textContent = "تعذر حفظ التقييم الآن. يرجى المحاولة مرة أخرى بعد قليل.";
    } finally {
      setPending(false);
    }
  }

  function renderTotals(data) {
    const up = toCount(data?.up);
    const down = toCount(data?.down);
    if (up === null || down === null) return;
    const total = up + down;

    if (total > 0) {
      const percent = Math.round((up / total) * 100);
      summary.hidden = false;
      summary.textContent = `وجد ${percent}٪ من ${formatNumber(total)} مشاركًا أن هذا الدليل مفيد.`;
    } else {
      summary.hidden = true;
      summary.textContent = "";
    }
    updateArticleSchema(up, down);
  }

  function toCount(value) {
    const count = Number(value);
    return Number.isInteger(count) && count >= 0 ? count : null;
  }

  function formatNumber(value) {
    return new Intl.NumberFormat("ar").format(value);
  }

  function updateArticleSchema(up, down) {
    for (const script of document.querySelectorAll('script[type="application/ld+json"]')) {
      let data;
      try {
        data = JSON.parse(script.textContent);
      } catch {
        continue;
      }
      const nodes = Array.isArray(data?.["@graph"]) ? data["@graph"] : [data];
      const article = nodes.find((node) => node && node["@type"] === "Article");
      if (!article) continue;
      article.interactionStatistic = [
        {
          "@type": "InteractionCounter",
          interactionType: { "@type": "LikeAction" },
          userInteractionCount: up,
        },
        {
          "@type": "InteractionCounter",
          interactionType: { "@type": "DislikeAction" },
          userInteractionCount: down,
        },
      ];
      script.textContent = JSON.stringify(data);
      break;
    }
  }
})();
