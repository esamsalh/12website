# ToolRar Knowledge Project

## Project scope and paths

- Treat `C:\Users\pc\Desktop\knowledge` as the canonical project root for this conversation and all future requested changes unless the user explicitly supplies another path.
- Edit pages in place. Do not create ZIP files, download packages, duplicate deliverables, or download links unless the user explicitly asks for them.
- Preserve unrelated user files and existing changes.
- Keep every knowledge category in the directory that matches the live ToolRar category slug, with its landing page at `<category>\index.html`.
- Current category directories are `text-tools`, `developer`, `photo-editing`, `calculators`, `docs-tools`, `zip-tools`, `seo`, `general`, and `social-media`.
- Keep every ToolRar knowledge URL path fully lowercase. This applies to knowledge canonicals, Open Graph URLs, schema URLs, breadcrumbs, navigation, cards, and inline knowledge links. Preserve lowercase filesystem category names so deployment works on case-sensitive servers.
- For related ToolRar tool links outside `/knowledge/`, use the currently deployed canonical route exactly as served. The production tool categories still use legacy mixed-case routes (`/Calculators/`, `/Developer/`, `/Photo-Editing/`, `/General/`, and `/Social-media/`); changing those outbound paths to lowercase before a site-root routing migration creates real 404 responses. Migrate the main site with lowercase aliases and 301 redirects first, then update these links in one audited release.
- The root-server migration rules are prepared in `server-root-lowercase-migration.htaccess`. Merge them into the main document-root configuration before changing outbound tool links to lowercase.
- Keep individual text-tool knowledge articles as flat files: `text-tools\<page-slug>.html`. Do not create an extra directory for an article.
- Keep the local article files as `.html`, but publish every ToolRar knowledge URL, canonical, structured-data URL, breadcrumb, card link, and cross-article link without the `.html` suffix; for example use `/knowledge/text-tools/text-comparison` rather than `/knowledge/text-tools/text-comparison.html`.
- Preserve the root `.htaccess` clean-URL rules: requests without `.html` must resolve internally to the matching flat HTML file, while direct public `.html` requests redirect permanently to the clean canonical URL. If the production origin is not Apache/LiteSpeed, reproduce the same behavior in that server's routing configuration before deployment.
- Store every category's article illustrations in its own `<category>\img` directory. Reference them with `img/<filename>.svg`; do not mix image assets with HTML knowledge files.

## Shared visual system

- Preserve the established ToolRar knowledge design, shapes, spacing, colors, illustrations, and icon language unless a redesign is explicitly requested.
- Use the Cairo font throughout every page, including all footer headings, links, buttons, and labels.
- Category landing pages show exactly eight knowledge cards per section on the main overview; use four cards per row on desktop and two cards per row on phones and narrow screens.
- Knowledge cards represent guides, not tools. Do not display the phrase `فتح الأداة` under knowledge cards.
- Avoid oversized or repeated hero sections. Article heroes should be compact and distinct from category landing-page heroes.
- The shared header must follow the ToolRar header used in the project: pencil logo, desktop navigation, language control, dark-mode control, and responsive mobile controls.
- Preserve the core header links: `الرئيسية`, `جميع الأدوات`, `التصنيفات`, `المدونة`, `الأسعار`, and `من نحن`.
- Add `مركز المعرفة` as a desktop hover/focus dropdown linking to all knowledge categories, and add `المصطلحات` as a direct link to `/definitions/`.
- Use an accessible mobile menu containing the core links plus `مركز المعرفة` and `المصطلحات`.
- Footer section labels are text elements such as `<p class="footer-col-title">`, never `h2`. Footer social icons and the pencil logo must match the established ToolRar footer. Keep all footer text in Cairo.
- Every footer must link Telegram to `https://t.me/toolrar`, Facebook to `https://www.facebook.com/Toolrar`, X to `https://x.com/toolrar`, and LinkedIn to `https://www.linkedin.com/in/toolrar`. Use LinkedIn instead of Instagram. External social links open in a new tab and use `rel="nofollow noopener noreferrer"`.

## Knowledge article content requirements

- Write for search intent first and use natural, human Arabic suitable for the target audience.
- Each complete internal knowledge article must contain at least 1,000 words unless the user explicitly requests a different length.
- Use the main keyword in the H1 and naturally within the first 100 words. Keep keyword usage natural, generally around 1–2%, without stuffing.
- Use related and semantically relevant terms naturally. Do not repeat wording or use the same rigid article structure across pages.
- Use one H1 only. Organize the content with logical H2 and H3 headings, short paragraphs of roughly 3–4 lines, short sentences, transitions, numbered steps, and lists where useful.
- Long articles should include a table of contents, a concise introduction, a practical usage guide, professional comparison tables, troubleshooting solutions, FAQ, and a conclusion with a relevant call to action.
- Add useful visuals inside the guide, especially around steps, comparisons, and troubleshooting. Give every meaningful image accurate Arabic alt text that describes its purpose and naturally includes the topic where appropriate.
- All visible labels inside SVG illustrations for Arabic knowledge pages must be clear Arabic text, with RTL direction and an Arabic-capable font stack. Do not leave English UI labels in article illustrations unless the English term itself is the subject being explained.
- Give each article a genuinely distinct SVG visual concept that explains its own workflow. Do not repeat one hero composition or merely swap labels and colors between pages; vary the information hierarchy, shapes, diagrams, and subject-specific visual metaphor while preserving the shared ToolRar design language.
- Calculator SVGs must visually explain the calculation through its inputs, relationship or formula, change over time, comparison, or output. Do not fill them with unrelated icons or scattered labels; understand the calculator first and use one coherent diagram that can be interpreted without the surrounding paragraph.
- Use a meta title no longer than 60 characters and a meta description of 150–160 characters containing the main keyword naturally.
- Meta descriptions must be complete, natural sentences. Never end them with an ellipsis or cut a word or clause merely to hit the length target. Keep the visible meta description, Open Graph description, Twitter description, and the main page schema description consistent.
- Include appropriate canonical, Open Graph, Twitter Card, Article, BreadcrumbList, HowTo, and FAQ structured data when supported by visible page content.
- Demonstrate E-E-A-T through practical guidance, limitations, review notes, clear update dates, verifiable claims, and authoritative references. Do not make unverifiable claims.
- Add 4–10 genuinely relevant internal definition/knowledge links. Use live ToolRar URLs from `https://www.toolrar.com/definitions/` or existing planned knowledge URLs that match the topic.
- Distribute definition links according to reader need and paragraph context, not a fixed quota. Use descriptive, varied anchor text that explains what the reader will learn, avoid repeating the same anchor, and never insert two semantically unrelated definition links merely to increase link count.
- Never repeat the same ToolRar definition URL within one article. Place each definition link inside the paragraph where the concept is needed, and write a natural Arabic anchor that completes the sentence rather than repeating the definition title mechanically.
- Add enough authoritative external sources for the article depth, normally 2–4 for a long technical guide. Every external content reference must use `rel="nofollow noopener noreferrer"` and open safely when appropriate.
- For calculator knowledge pages, prefer exactly four authoritative external references per page. Keep all four visible in the sources section and apply `rel="nofollow noopener noreferrer"` to every external source link.
- Recommend four real ToolRar tools closely related to the article topic, using their actual live URLs from `https://www.toolrar.com/`.
- Near the table of contents, include four related knowledge cards with stable planned URLs so future articles form a deliberate internal-link network.

## Performance and accessibility

- Do not add Cloudflare Analytics, `beacon.min.js`, `/cdn-cgi/rum`, tracking scripts, or other third-party runtime scripts to source HTML. Cloudflare Beacon/RUM is injected at the edge and must be disabled in the Cloudflare dashboard if PageSpeed reports its cache or critical-chain cost.
- Do not add a CSP rule solely to block Cloudflare Beacon; that creates a browser console error. Configure the Cloudflare feature instead.
- Add `preconnect` only when Lighthouse identifies a genuinely useful external origin. Do not add empty or unnecessary connection hints.
- Preload the actual LCP image, specify intrinsic `width` and `height`, use `fetchpriority="high"`, eager loading, and asynchronous decoding. Lazy-load and asynchronously decode below-the-fold images.
- Avoid forced synchronous layout. Cache geometry during load/resize, schedule visual writes with `requestAnimationFrame`, and animate progress indicators with `transform` rather than repeatedly changing layout properties such as `width` during scroll.
- Keep the DOM lean: prefer pseudo-elements for decorative numbering/arrows, remove unnecessary wrappers, avoid deeply nested markup, and group large sections logically.
- Use `content-visibility:auto` with a suitable intrinsic size for heavy below-the-fold article sections when it preserves behavior and design.
- Maintain adequate WCAG contrast in both themes, including small labels, numbered section badges, code samples, and muted text.
- Preserve responsive behavior and prevent page-level horizontal overflow. Wide tables may scroll only inside their own wrapper.
- Minify or compact inline CSS and JavaScript after functional and visual work is complete, without changing the design, content, structured data, or accessibility.

## Project-wide content and SEO audit standard

- Audit the whole knowledge project after every project-wide template, URL, footer, metadata, or schema change. Do not assume a mechanically successful replacement means the site is correct.
- Evaluate content depth, originality, factual accuracy, readability, logical organization, complete search-intent coverage, practical examples, useful tables and visuals, absence of filler, natural Arabic, consistent editorial tone, useful summaries, direct FAQ answers, and a relevant CTA.
- Keep paragraphs short enough for comfortable mobile reading. Split long paragraphs at natural sentence boundaries while preserving meaning, links, and the established visual design.
- Confirm every article has one H1, a logical H1–H6 sequence without skipped levels, descriptive subheadings, one complete meta title, one complete meta description, a canonical, robots directives, Open Graph metadata, Twitter Card metadata, breadcrumbs, and matching structured data.
- Confirm schema matches visible content exactly. Article headline and description must match the page, FAQ questions and answers must be visible, HowTo steps must be represented in the guide, breadcrumb URLs must resolve, and every schema URL must be clean and lowercase.
- Check keyword placement against search intent: the main phrase belongs in the H1 and first 100 words, with natural semantic terms and no forced density target or stuffing.
- Require descriptive Arabic alt text, intrinsic image dimensions, an eagerly loaded and preloaded LCP illustration, lazy loading below the fold, modern or vector assets where appropriate, and no missing local image.
- Preserve E-E-A-T with transparent limitations, an identifiable ToolRar editorial author or reviewing team, visible review/update information, verifiable claims, authoritative sources, practical advice, and clear trust/contact/policy routes.
- Do not claim personal testing, medical review, legal review, or first-hand experience unless it actually occurred and is documented. Demonstrate experience through reproducible workflows, examples, constraints, and verification steps.
- Check local clean URLs against the actual flat HTML files. Check live ToolRar tool and definition URLs, and authoritative external references, for 404/410 responses. Replace or remove a broken link rather than leaving a planned route that does not exist.
- Maintain a deliberate internal-link network and shallow click depth: every article links to its category landing page, relevant ToolRar tools, contextual definitions or knowledge pages, and four genuinely related knowledge articles.
- Check duplicate titles, descriptions, canonicals, repeated body templates, broken anchors, direct `.html` links, mixed-case paths, redirect loops, and crawl traps.
- Verify production integration for `sitemap.xml` and `robots.txt`; include every lowercase canonical in the sitemap and never block required page or image assets. Add `hreflang` only when a real alternate-language version exists.
- Assess mobile usability, horizontal overflow, console errors, missing assets, dark mode, menus, tables, contrast, DOM size, third-party scripts, cache headers, compression, LCP, CLS, and long main-thread work. Treat lab metrics as environment-dependent and report server- or edge-only issues separately.
- Protect users from disruptive overlays, manipulative SEO patterns, hidden text, scaled low-quality content, unsafe downloads, and unsupported certainty. Helpful content and trust take priority over keyword quotas.
- Metrics such as backlinks, CTR, bounce rate, dwell time, pogo-sticking, crawl frequency, HTTPS certificate health, malware status, and field Core Web Vitals require production Search Console, analytics, security, or CrUX data. Never fabricate them; verify them from the relevant production source when access is available.

## Medical and health knowledge pages

- Explain clearly how the health or medical tool performs its calculation, including the inputs, the formula or recognized method when appropriate, the meaning of the result, and the limits of that result.
- Apply the highest practical E-E-A-T standard: medically reviewed concepts, precise scope, transparent assumptions, clear limitations, current review dates, and no unsupported diagnostic or therapeutic claims.
- Be fully transparent about what the tool can and cannot determine. Distinguish estimates, screening indicators, reference ranges, and medical diagnoses in plain Arabic.
- Cite every material medical or health claim to a trustworthy primary or authoritative source, and include those sources in a visible sources list near the end of the page.
- Every external medical or health source link must use `rel="nofollow noopener noreferrer"` and open safely when appropriate.
- Add a prominent, readable disclaimer stating that medical pages are educational only and do not replace visiting a physician, while general health pages do not replace personalized advice from a qualified healthcare professional.
- Place that disclaimer visibly near the top of every medical or health calculator page, not only in the footer or source list. The calculation section must name the formula or recognized method, explain each input, show how the result is produced, and state what the result cannot determine.
- When a result could indicate an urgent risk, tell the reader to seek appropriate professional or emergency care without attempting to diagnose the condition from the calculator alone.

## Verification checklist

- Validate all local image references and confirm every image loads from its intended `img` directory.
- Check desktop and mobile rendering, header dropdowns, mobile menu, dark mode, tables, and page-level horizontal overflow.
- Confirm there are no browser console errors caused by page source, no missing local assets, one H1, and no H2 elements inside the footer.
- Recheck meta title/description lengths, headings, alt text, structured-data URLs, internal links, external `nofollow` attributes, article word count, and natural keyword distribution.
- Keep source free of Cloudflare Beacon references and report separately when a remaining PageSpeed warning can only be resolved in the Cloudflare dashboard.
