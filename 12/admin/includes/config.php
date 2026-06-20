<?php
session_start();

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'toolrar2024');
define('SITE_NAME', 'ToolRar');
define('SITE_URL', '/12');
define('SITE_PATH', dirname(__DIR__, 2));
define('DATA_PATH', __DIR__ . '/../data');
define('CATEGORIES_FILE', DATA_PATH . '/categories.json');
define('TOOLS_FILE', DATA_PATH . '/tools.json');
define('ITEMS_PER_PAGE', 20);

function isLoggedIn() {
    return isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function jsonRead($file) {
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function jsonWrite($file, $data) {
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function slugify($str) {
    $str = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $str);
    $str = preg_replace('/[\s]+/', '-', trim($str));
    $str = preg_replace('/-+/', '-', $str);
    $str = strtolower(trim($str, '-'));
    if (empty($str)) $str = 'tool-' . uniqid();
    return $str;
}

function getCategories() {
    return jsonRead(CATEGORIES_FILE);
}

function saveCategories($data) {
    return jsonWrite(CATEGORIES_FILE, $data);
}

function getTools() {
    return jsonRead(TOOLS_FILE);
}

function saveTools($data) {
    return jsonWrite(TOOLS_FILE, $data);
}

function getCategoryById($id) {
    $cats = getCategories();
    foreach ($cats as $c) {
        if ($c['id'] === $id) return $c;
    }
    return null;
}

function getCategoryPhysicalDir($slug) {
    $dbToPhysical = [
        'text-tools' => 'text-tools',
        'code-tools' => 'Developer',
        'image-tools' => 'Photo-Editing',
        'calculator-tools' => 'Calculators',
        'pdf-tools' => 'docs-tools',
        'zip-tools' => 'zip-tools',
        'seo-tools' => 'seo',
        'misc-tools' => 'General',
        'share-tools' => 'Social-media'
    ];
    return $dbToPhysical[$slug] ?? $slug;
}


function getToolById($id) {
    $tools = getTools();
    foreach ($tools as $t) {
        if ($t['id'] === $id) return $t;
    }
    return null;
}

function cleanAndFormatHtml($html) {
    if (empty($html)) return '';

    // Remove markdown code fences if they surround the text
    $html = preg_replace('/^```(?:html)?\s*\n?/i', '', $html);
    $html = preg_replace('/\n?```\s*$/i', '', $html);

    // Convert markdown headings to h3
    $html = preg_replace('/(?:\r?\n|^)#{1,4}\s+(.*?)(?:\r?\n|$)/u', "\n<h3>$1</h3>\n", $html);

    // Convert existing H1 and H2 tags to H3 to satisfy sequential heading hierarchy in PageSpeed
    $html = preg_replace('/<h1([^>]*)>(.*?)<\/h1>/is', '<h3$1>$2</h3>', $html);
    $html = preg_replace('/<h2([^>]*)>(.*?)<\/h2>/is', '<h3$1>$2</h3>', $html);

    // Convert markdown bold **text** or __text__ to <strong>text</strong>
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
    $html = preg_replace('/__(.*?)__/s', '<strong>$1</strong>', $html);

    // Convert markdown bullet points to HTML list items
    $lines = explode("\n", $html);
    $inList = false;
    foreach ($lines as &$line) {
        $trimmed = trim($line);
        if (preg_match('/^[\-\*]\s+(.*)$/u', $trimmed, $matches)) {
            $content = $matches[1];
            if (!$inList) {
                $line = "<ul>\n<li>" . $content . "</li>";
                $inList = true;
            } else {
                $line = "<li>" . $content . "</li>";
            }
        } else {
            if ($inList && !empty($trimmed)) {
                $line = "</ul>\n" . $line;
                $inList = false;
            }
        }
    }
    unset($line);
    if ($inList) {
        $lines[] = "</ul>";
    }
    $html = implode("\n", $lines);

    // Clean up empty paragraphs
    $html = preg_replace('/<p>\s*<\/p>/is', '', $html);

    return trim($html);
}

function generateToolPage($tool) {
    $category = getCategoryById($tool['category_id']);
    if (!$category) return false;

    $catSlug = $category['slug'];
    $physicalSlug = getCategoryPhysicalDir($catSlug);
    $catDir = SITE_PATH . '/' . $physicalSlug;
    if (!is_dir($catDir)) mkdir($catDir, 0755, true);

    $subSlug = !empty($tool['sub_slug']) ? trim($tool['sub_slug'], '/') : '';
    $toolSlug = !empty($tool['tool_slug']) ? $tool['tool_slug'] : slugify($tool['title_ar']);
    $tool['page_slug'] = $toolSlug;

    $toolDir = $catDir;
    if ($subSlug) {
        $toolDir .= '/' . $subSlug;
        if (!is_dir($toolDir)) mkdir($toolDir, 0755, true);
    }
    $toolFilePath = $toolDir . '/' . $toolSlug . '.html';

    $relRoot = $subSlug ? '../../' : '../';
    $htmlCode = $tool['html_code'] ?? '';
    $cssCode = $tool['css_code'] ?? '';
    $jsCode = $tool['js_code'] ?? '';
    $longDescAr = cleanAndFormatHtml($tool['long_desc_ar'] ?? '');
    $longDescEn = cleanAndFormatHtml($tool['long_desc_en'] ?? '');
    $longDescFr = cleanAndFormatHtml($tool['long_desc_fr'] ?? '');
    $faq = $tool['faq'] ?? [];

    // FAQ HTML + Schema
    $faqSchema = [];
    $faqHtml = '';
    foreach ($faq as $i => $item) {
        $num = $i + 1;
        $qAr = htmlspecialchars($item['question_ar'] ?? '', ENT_QUOTES, 'UTF-8');
        $aAr = $item['answer_ar'] ?? '';
        $faqSchema[] = [
            '@type' => 'Question',
            'name' => $qAr,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($aAr)]
        ];
        $faqHtml .= <<<FAQ
            <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                    <span>{$num}. {$qAr}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
                <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <div itemprop="text">{$aAr}</div>
                </div>
            </div>
FAQ;
    }

    $faqSchemaJson = json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // SVG icons
    $catIcons = [
        'text-tools' => '<polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/>',
        'code-tools' => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'image-tools' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'calculator-tools' => '<rect width="20" height="20" x="2" y="2" rx="2"/><path d="M6 12h4"/><path d="M8 10v4"/><path d="M15 13h.01"/><path d="M18 11h.01"/>',
        'pdf-tools' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
        'zip-tools' => '<path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>',
        'seo-tools' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m9 8 5 3-5 3V8z"/>',
        'misc-tools' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
        'share-tools' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>'
    ];
    $catIconSvg = $catIcons[$catSlug] ?? '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>';

    $catColors = [
        'text-tools' => '#6366F1', 'code-tools' => '#10B981', 'image-tools' => '#F59E0B',
        'calculator-tools' => '#3B82F6', 'pdf-tools' => '#EC4899', 'zip-tools' => '#10B981',
        'seo-tools' => '#14B8A6', 'misc-tools' => '#F59E0B', 'share-tools' => '#8B5CF6'
    ];
    $catNameAr = htmlspecialchars($category['name_ar'], ENT_QUOTES, 'UTF-8');

    $titleAr = htmlspecialchars($tool['title_ar'], ENT_QUOTES, 'UTF-8');
    $shortDescAr = htmlspecialchars($tool['short_desc_ar'], ENT_QUOTES, 'UTF-8');
    $metaTitleAr = htmlspecialchars($tool['meta_title_ar'] ?? $tool['title_ar'], ENT_QUOTES, 'UTF-8');
    $metaDescAr = htmlspecialchars($tool['meta_desc_ar'] ?? $tool['short_desc_ar'], ENT_QUOTES, 'UTF-8');

    // Related tools from same category / subcategory
    $allTools = getTools();
    $relatedHtml = '';
    $relCount = 0;
    $toolSub = !empty($tool['sub_slug']) ? trim($tool['sub_slug'], '/') : '';
    foreach ($allTools as $rt) {
        $rtSubSlug = !empty($rt['sub_slug']) ? trim($rt['sub_slug'], '/') : '';
        if ($rt['category_id'] === $tool['category_id'] && $rt['id'] !== $tool['id'] && !empty($rt['page_slug']) && $rtSubSlug === $toolSub) {
            $rtCat = getCategoryById($rt['category_id']);
            $rtSlug = $rtCat ? $rt['page_slug'] : $rt['page_slug'];
            $rtSub = !empty($rt['sub_slug']) ? trim($rt['sub_slug'], '/') . '/' : '';
            $rtIcon = $catIcons[$catSlug] ?? '';
            $rtTitle = htmlspecialchars($rt['title_ar'] ?? '', ENT_QUOTES, 'UTF-8');
            $rtUrl = $relRoot . getCategoryPhysicalDir($catSlug) . '/' . $rtSub . $rtSlug . '.html';
            $relatedHtml .= <<<REL
                <a href="{$rtUrl}" class="related-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18">{$rtIcon}</svg>
                    <span>{$rtTitle}</span>
                </a>
REL;
            $relCount++;
        }
    }
    if ($relCount === 0) {
        $relatedHtml = '<div class="related-empty">لا توجد أدوات أخرى في هذا القسم</div>';
    }

    // Dates
    $createdDate = !empty($tool['created_at']) ? date('Y-m-d', strtotime($tool['created_at'])) : date('Y-m-d');
    $updatedDate = !empty($tool['updated_at']) ? date('Y-m-d', strtotime($tool['updated_at'])) : date('Y-m-d');

    // TOC from long description headings
    $tocItems = [];
    $longDescProcessed = preg_replace_callback(
        '/<h([1-4])([^>]*)>(.*?)<\/h\1>/is',
        function($m) use (&$tocItems) {
            $level = (int)$m[1];
            $attrs = $m[2];
            $text = strip_tags($m[3]);
            $cleanText = trim(str_replace('&nbsp;', '', $text));
            if (empty($cleanText)) return $m[0];
            $id = 'heading-' . count($tocItems);
            // Try to use text-based ID if attrs don't have id
            if (strpos($attrs, 'id=') === false) {
                $textId = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $cleanText);
                $textId = preg_replace('/[\s]+/', '-', trim($textId));
                $textId = preg_replace('/-+/', '-', $textId);
                $textId = trim($textId, '-');
                if (!empty($textId)) $id = $textId;
                $idAttr = " id=\"{$id}\"{$attrs}";
            } else {
                preg_match('/id="([^"]+)"/', $attrs, $mId);
                $id = $mId[1] ?? $id;
                $idAttr = $attrs;
            }
            $tocItems[] = ['level' => $level, 'text' => $cleanText, 'id' => $id];
            return "<h{$level}{$idAttr}>{$text}</h{$level}>";
        },
        $longDescAr
    );
    if (empty($longDescProcessed)) $longDescProcessed = $longDescAr;

    $tocHtml = '';
    if (count($tocItems) > 1) {
        $tocHtml .= '<nav class="toc"><ul>';
        foreach ($tocItems as $item) {
            $pad = ($item['level'] - 1) * 14;
            $tocHtml .= '<li style="padding-right:' . $pad . 'px"><a href="#' . $item['id'] . '">' . htmlspecialchars($item['text']) . '</a></li>';
        }
        $tocHtml .= '</ul></nav>';
    }

    $tocSectionHtml = '';
    if (count($tocItems) > 1) {
        $tocSectionHtml = '<div class="toc-wrap">' . $tocHtml . '</div>';
    }

    $toolPage = <<<PAGE
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$metaTitleAr}</title>
    <meta name="description" content="{$metaDescAr}">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": "{$titleAr}",
        "description": "{$shortDescAr}",
        "applicationCategory": "{$catNameAr}",
        "operatingSystem": "All",
        "browserRequirements": "HTML5, CSS3, JavaScript",
        "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": {$faqSchemaJson}
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "{$metaTitleAr}",
        "description": "{$metaDescAr}",
        "datePublished": "{$createdDate}",
        "dateModified": "{$updatedDate}",
        "author": {
            "@type": "Organization",
            "name": "ToolRar"
        }
    }
    </script>
    <link rel="preload" href="{$relRoot}admin/assets/fonts/cairo/cairo-v31-arabic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{$relRoot}admin/assets/fonts/cairo/cairo-v31-latin.woff2" as="font" type="font/woff2" crossorigin>
    <style>
        /* Cairo font - locally hosted WOFF2 variable font with full Arabic support */
        @font-face {
          font-family: 'Cairo';
          font-style: normal;
          font-weight: 200 900;
          font-display: swap;
          src: url({$relRoot}admin/assets/fonts/cairo/cairo-v31-arabic.woff2) format('woff2');
          unicode-range: U+0600-06FF, U+0750-077F, U+0870-088E, U+0890-0891, U+0897-08E1, U+08E3-08FF, U+200C-200E, U+2010-2011, U+204F, U+2E41, U+FB50-FDFF, U+FE70-FE74, U+FE76-FEFC, U+102E0-102FB, U+10E60-10E7E, U+10EC2-10EC4, U+10EFC-10EFF, U+1EE00-1EE03, U+1EE05-1EE1F, U+1EE21-1EE22, U+1EE24, U+1EE27, U+1EE29-1EE32, U+1EE34-1EE37, U+1EE39, U+1EE3B, U+1EE42, U+1EE47, U+1EE49, U+1EE4B, U+1EE4D-1EE4F, U+1EE51-1EE52, U+1EE54, U+1EE57, U+1EE59, U+1EE5B, U+1EE5D, U+1EE5F, U+1EE61-1EE62, U+1EE64, U+1EE67-1EE6A, U+1EE6C-1EE72, U+1EE74-1EE77, U+1EE79-1EE7C, U+1EE7E, U+1EE80-1EE89, U+1EE8B-1EE9B, U+1EEA1-1EEA3, U+1EEA5-1EEA9, U+1EEAB-1EEBB, U+1EEF0-1EEF1;
        }
        @font-face {
          font-family: 'Cairo';
          font-style: normal;
          font-weight: 200 900;
          font-display: swap;
          src: url({$relRoot}admin/assets/fonts/cairo/cairo-v31-latin-ext.woff2) format('woff2');
          unicode-range: U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF;
        }
        @font-face {
          font-family: 'Cairo';
          font-style: normal;
          font-weight: 200 900;
          font-display: swap;
          src: url({$relRoot}admin/assets/fonts/cairo/cairo-v31-latin.woff2) format('woff2');
          unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; overflow-x: hidden; width: 100%; }
        body { overflow-x: hidden; width: 100%; position: relative; }
        body {
            font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; min-height: 100vh; display: flex; flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
            background: #ffffff; color: #1e293b;
            -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; line-height: 1.6;
        }
        img, svg, video, canvas, iframe { max-width: 100%; height: auto; }
        pre, code { white-space: pre-wrap; word-break: break-word; }
        .dark body { background: #080C1A; color: #e2e8f0; }
        .dark .header { background: #080C1A; border-color: rgba(255,255,255,0.03); }
        .dark .hero-section { background: #192746 !important; }
        .dark .hero-section::before { opacity: 0.03; }
        .dark .page-section { background: #0F172A; }
        .dark .tool-interface { background: #1E293B; border-color: #334155; }
        .dark .tool-desc-text { color: #e2e8f0; }
        .dark .related-btn { background: #1E293B; border-color: #334155; color: #cbd5e1; }
        .dark .related-btn:hover { background: #334155; border-color: #6366F1; }
        .dark .related-empty { color: #cbd5e1; }
        .dark .faq-item { border-color: #334155; }
        .dark .faq-question { color: #e2e8f0; }
        .dark .faq-question:hover { background: #1E293B; }
        .dark .faq-answer { color: #94a3b8; }
        .dark .footer { background: #080C1A; }
        .container { max-width: 1280px; margin: 0 auto; padding: 0 1rem; width: 100%; }
        @media(min-width:640px) { .container { padding: 0 1.5rem; } }
        @media(min-width:1024px) { .container { padding: 0 2rem; } }
        .header {
            background: #0F172A; position: sticky; top: 0; z-index: 50;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .header-inner { display: flex; align-items: center; justify-content: space-between; height: 64px; }
        .logo { display: flex; align-items: center; gap: 10px; text-decoration: none; flex-shrink: 0; }
        .logo-icon {
            width: 36px; height: 36px; background: #6366F1; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(99,102,241,0.3);
        }
        .logo-icon svg { width: 20px; height: 20px; color: #fff; }
        .logo-text { font-size: 1.4rem; font-weight: 900; color: #fff; letter-spacing: -0.5px; }
        .desktop-nav { display: none; flex: 1; gap: 4px; margin: 0 8px; }
        @media(min-width:1024px) { .desktop-nav { display: flex; align-items: center; justify-content: space-between; } }
        .nav-links-center { display: flex; align-items: center; gap: 2px; margin: 0 auto; }
        .nav-link {
            color: #94a3b8; text-decoration: none; font-size: 0.82rem; font-weight: 500;
            padding: 7px 12px; border-radius: 8px; transition: all 0.2s;
            display: flex; align-items: center; gap: 4px; background: none; border: none;
            font-family: inherit; cursor: pointer; white-space: nowrap;
        }
        .nav-link:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .dropdown-chevron { width: 13px; height: 13px; transition: transform 0.2s; }
        .dropdown-wrap { position: relative; }
        .dropdown-menu {
            position: absolute; top: 100%; right: 0; margin-top: 8px;
            background: #1E293B; border: 1px solid rgba(55,65,81,0.5);
            border-radius: 12px; padding: 8px; min-width: 220px;
            opacity: 0; visibility: hidden; transform: translateY(8px);
            transition: all 0.2s ease; z-index: 100;
        }
        .dropdown-wrap.open .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .cat-dropdown { display: grid; grid-template-columns: 1fr; gap: 4px; min-width: 260px; }
        @media(min-width:1200px) { .cat-dropdown { grid-template-columns: 1fr 1fr; min-width: 500px; } }
        .cat-item {
            display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px;
            text-decoration: none; transition: background 0.15s; color: #e2e8f0; font-size: 0.82rem; font-weight: 500;
        }
        .cat-item:hover { background: rgba(99,102,241,0.1); color: #6366F1; }
        .cat-item-icon {
            width: 28px; height: 28px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .cat-item-icon svg { width: 14px; height: 14px; color: #fff; }
        .nav-controls { display: none; align-items: center; gap: 4px; }
        @media(min-width:1024px) { .nav-controls { display: flex; } }
        .lang-dropdown { min-width: 155px; }
        .lang-item {
            display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 12px;
            border: none; background: none; color: #cbd5e1; font-size: 0.85rem;
            font-family: inherit; cursor: pointer; border-radius: 8px; transition: background 0.15s;
        }
        .lang-item:hover { background: rgba(255,255,255,0.05); }
        .lang-item.active { color: #6366F1; }
        .lang-flag { font-size: 1.05rem; }
        .lang-check { width: 16px; height: 16px; color: #6366F1; }
        .dark-toggle {
            display: flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 8px;
            background: none; border: none; color: #94a3b8; font-family: inherit;
            font-size: 0.78rem; cursor: pointer; transition: all 0.2s; white-space: nowrap;
        }
        .dark-toggle:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .dark-toggle svg { width: 16px; height: 16px; }
        .mobile-controls { display: flex; align-items: center; gap: 6px; }
        @media(min-width:1024px) { .mobile-controls { display: none; } }
        .mobile-btn {
            width: 36px; height: 36px; border-radius: 8px; display: flex;
            align-items: center; justify-content: center; background: rgba(255,255,255,0.06);
            border: none; cursor: pointer; color: #94a3b8; transition: background 0.2s;
        }
        .mobile-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .mobile-btn svg { width: 20px; height: 20px; }
        .mobile-lang-dropdown { min-width: 140px; }
        .mobile-lang-item {
            display: block; width: 100%; padding: 10px 14px; border: none;
            background: none; color: #cbd5e1; font-size: 0.85rem;
            font-family: inherit; cursor: pointer; text-align: right; border-radius: 8px;
        }
        .mobile-lang-item:hover { background: rgba(255,255,255,0.05); }
        .mobile-lang-item.active { color: #6366F1; }
        .mobile-menu {
            display: none; flex-direction: column; padding: 8px 0;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
        .mobile-menu.open { display: flex; }
        .mobile-menu a {
            padding: 12px 16px; color: #94a3b8; text-decoration: none;
            font-size: 0.9rem; font-weight: 500; border-radius: 8px; transition: all 0.2s;
        }
        .mobile-menu a:hover { color: #fff; background: rgba(255,255,255,0.06); }

        /* Breadcrumb */
        .tool-breadcrumb {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 0; margin-bottom: 20px; flex-wrap: wrap; gap: 8px;
        }
        .breadcrumb-nav { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; font-size: 0.82rem; }
        .breadcrumb-nav a { color: #475569; text-decoration: none; transition: color 0.2s; }
        .breadcrumb-nav a:hover { color: #6366F1; }
        .breadcrumb-nav span { color: #475569; }
        .bc-sep { color: #475569; font-size: 1rem; }
        .breadcrumb-dates { display: none; align-items: center; gap: 16px; }
        @media(min-width:768px) { .breadcrumb-dates { display: flex; } }
        .bc-date { display: flex; align-items: center; gap: 4px; font-size: 0.7rem; color: #475569; direction: ltr; white-space: nowrap; font-weight: 500; }
        .bc-date svg { width: 13px; height: 13px; flex-shrink: 0; }
        .bc-date .bc-label { font-size: 0.65rem; color: #475569; margin-left: 2px; }
        .dark .breadcrumb-nav a { color: #cbd5e1; }
        .dark .breadcrumb-nav a:hover { color: #6366F1; }
        .dark .breadcrumb-nav span { color: #94a3b8; }
        .dark .bc-date { color: #cbd5e1; }
        .dark .bc-date .bc-label { color: #94a3b8; }

        /* TOC */
        .toc-wrap {
            background: #F8FAFC; border: 1px solid #E2E8F0;
            border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;
        }
        .dark .toc-wrap { background: #1E293B; border-color: #334155; }
        .toc ul { list-style: none; }
        .toc li { margin-bottom: 6px; }
        .toc a {
            color: #4f46e5; text-decoration: none; font-size: 0.85rem;
            font-weight: 500; transition: color 0.2s;
        }
        .toc a:hover { color: #4338ca; text-decoration: underline; }
        .dark .toc a { color: #a5b4fc; }
        .dark .toc a:hover { color: #c7d2fe; }

        /* Hero Section */
        .hero-section {
            position: relative; overflow: hidden;
            background: #192746;
            padding: 48px 0 40px; width: 100%;
        }
        .hero-section::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.06) 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.04) 0%, transparent 50%);
            pointer-events: none;
        }
        .hero-section .container { position: relative; z-index: 1; }
        .hero-section .hero-inner { max-width: 800px; margin: 0 auto; text-align: center; }
        .hero-section .hero-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 16px; border-radius: 9999px;
            background: rgba(255,255,255,0.15); color: #fff;
            font-size: 0.75rem; font-weight: 700; margin-bottom: 16px;
            backdrop-filter: blur(4px);
        }
        .hero-section .hero-badge svg { width: 13px; height: 13px; }
        .hero-section h1 {
            font-size: 1.6rem; font-weight: 900; margin-bottom: 10px;
            line-height: 1.35; color: #fff;
        }
        @media(min-width:640px) { .hero-section h1 { font-size: 2rem; } }
        .hero-section p {
            color: rgba(255,255,255,0.8); font-size: 0.92rem;
            max-width: 580px; margin: 0 auto; line-height: 1.7;
        }

        /* Page Section */
        .page-section { background: #F8FAFC; flex: 1; width: 100%; overflow-x: hidden; }
        .page-inner { padding: 32px 1rem; max-width: 1100px; margin: 0 auto; width: 100%; }
        @media(min-width:640px) { .page-inner { padding: 40px 1.5rem; } }
        @media(min-width:768px) { .page-inner { padding: 48px 1.5rem; } }
        .tool-body { max-width: 960px; margin: 0 auto; width: 100%; }

        /* Tool Interface */
        .tool-interface {
            background: #ffffff; border: 1px solid #E2E8F0; border-radius: 18px;
            padding: 24px; margin-bottom: 36px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            transition: box-shadow 0.3s, border-color 0.3s;
            overflow: hidden;
        }
        @media(min-width:640px) { .tool-interface { padding: 32px; } }

        /* Description */
        /* Split Layout */
        .content-split {
            display: flex; flex-direction: column; gap: 32px;
            margin-bottom: 36px; width: 100%;
        }
        @media(min-width:768px) { .content-split { flex-direction: row; } }
        .content-main { flex: 0 0 60%; max-width: 100%; }
        @media(min-width:768px) { .content-main { max-width: 60%; } }
        .content-side { flex: 0 0 40%; max-width: 100%; }
        @media(min-width:768px) {
            .content-side { max-width: 40%; }
            .content-side-inner { position: sticky; top: 80px; z-index: 1; }
        }

        .tool-desc-section { }
        .tool-desc-section h2 {
            font-size: 1.15rem; font-weight: 800; margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .tool-desc-text { color: #475569; line-height: 1.9; font-size: 0.95rem; word-wrap: break-word; overflow-wrap: break-word; }
        .tool-desc-text img { max-width: 100%; height: auto; }
        .tool-desc-text h3 {
            font-size: 1.15rem; font-weight: 800; color: #1e293b;
            margin-top: 24px; margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }
        .dark .tool-desc-text h3 { color: #e2e8f0; }
        .tool-desc-text h4 {
            font-size: 1.05rem; font-weight: 700; color: #1e293b;
            margin-top: 20px; margin-bottom: 10px;
        }
        .dark .tool-desc-text h4 { color: #e2e8f0; }
        .tool-desc-text p { margin-bottom: 16px; line-height: 1.8; text-align: justify; }
        .tool-desc-text ul, .tool-desc-text ol {
            margin-right: 20px; margin-bottom: 16px; list-style-type: square;
        }
        .tool-desc-text li {
            margin-bottom: 8px; line-height: 1.7;
        }
        .tool-desc-text table {
            width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 0.9rem;
        }
        .tool-desc-text th, .tool-desc-text td {
            border: 1px solid #E2E8F0; padding: 10px 12px; text-align: right;
        }
        .dark .tool-desc-text th, .dark .tool-desc-text td {
            border-color: #334155;
        }
        .tool-desc-text th {
            background-color: #F8FAFC; font-weight: 700; color: #1e293b;
        }
        .dark .tool-desc-text th {
            background-color: #1E293B; color: #e2e8f0;
        }

        /* Related Tools */
        .related-section { margin-bottom: 36px; width: 100%; clear: both; }
        .related-section h2 {
            font-size: 1.15rem; font-weight: 800; margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .related-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; width: 100%; }
        @media(min-width:600px) { .related-grid { grid-template-columns: repeat(4, 1fr); } }
        .related-btn {
            display: flex; align-items: center; gap: 8px;
            padding: 12px 16px; border-radius: 12px;
            background: #fff; border: 1px solid #E2E8F0;
            text-decoration: none; color: #1e293b; font-size: 0.82rem; font-weight: 600;
            transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .related-btn:hover {
            border-color: #6366F1; color: #6366F1;
            box-shadow: 0 4px 12px rgba(99,102,241,0.15);
            transform: translateY(-2px);
        }
        .related-btn svg { flex-shrink: 0; width: 18px; height: 18px; color: #6366F1; }
        .related-empty { color: #475569; font-size: 0.85rem; }

        /* EEAT Trust Section */
        .eeat-section { margin-bottom: 36px; width: 100%; clear: both; }
        .eeat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media(min-width:640px) { .eeat-grid { grid-template-columns: repeat(4, 1fr); } }
        .eeat-card {
            background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
            padding: 18px 16px; text-align: center; transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        }
        .eeat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); transform: translateY(-2px); }
        .dark .eeat-card { background: #1E293B; border-color: #334155; }
        .eeat-card-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 10px;
        }
        .eeat-card-icon svg { width: 20px; height: 20px; }
        .eeat-card h3 { font-size: 0.85rem; font-weight: 800; margin-bottom: 4px; color: #1e293b; }
        .dark .eeat-card h3 { color: #e2e8f0; }
        .eeat-card p { font-size: 0.72rem; color: #64748b; line-height: 1.5; }
        .dark .eeat-card p { color: #94a3b8; }

        /* FAQ */
        .faq-section { }
        .faq-section h2 {
            font-size: 1.15rem; font-weight: 800; margin-bottom: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .faq-item { border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 10px; overflow: hidden; transition: box-shadow 0.2s; }
        .faq-item:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .faq-question {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px; cursor: pointer; font-weight: 600; font-size: 0.92rem;
            transition: background 0.2s; color: #1e293b; gap: 8px;
        }
        .faq-question:hover { background: #F8FAFC; }
        .faq-question svg { transition: transform 0.3s; flex-shrink: 0; color: #10B981; }
        .faq-item.open .faq-question svg { transform: rotate(180deg); }
        .faq-answer { padding: 0 18px; max-height: 0; overflow: hidden; transition: all 0.3s ease; color: #64748b; line-height: 1.8; font-size: 0.9rem; }
        .faq-item.open .faq-answer { padding: 0 18px 14px; max-height: 600px; }

        /* Footer */
        .footer { background: #0F172A; }
        .footer-inner { padding: 40px 1rem; }
        @media(min-width:640px) { .footer-inner { padding: 48px 1.5rem; } }
        @media(min-width:1024px) { .footer-inner { padding: 48px 2rem; } }
        .footer-grid { display: grid; grid-template-columns: 1fr; gap: 32px; }
        @media(min-width:640px) { .footer-grid { grid-template-columns: repeat(2, 1fr); } }
        @media(min-width:1024px) { .footer-grid { grid-template-columns: repeat(4, 1fr); gap: 48px; } }
        .footer-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .footer-desc { color: #e2e8f0; font-size: 0.875rem; line-height: 1.625; margin-bottom: 20px; max-width: 300px; }
        .footer-social { display: flex; gap: 10px; }
        .footer-social a {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.08); display: flex; align-items: center; justify-content: center;
            transition: background 0.2s; text-decoration: none;
        }
        .footer-social a:hover { background: #6366F1; }
        .footer-social a svg { width: 16px; height: 16px; color: #fff; }
        .footer-col-title { color: #fff; font-weight: 700; font-size: 1rem; margin-bottom: 18px; }
        .footer-col ul { list-style: none; }
        .footer-col li { margin-bottom: 10px; }
        .footer-link { color: #cbd5e1; text-decoration: none; font-size: 0.85rem; transition: color 0.2s; }
        .footer-link:hover { color: #fff; }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); margin-top: 32px; padding-top: 20px; display: flex; flex-direction: column; gap: 12px; align-items: center; }
        @media(min-width:640px) { .footer-bottom { margin-top: 40px; padding-top: 24px; flex-direction: row; justify-content: space-between; } }
        .footer-bottom p { color: #cbd5e1; font-size: 0.78rem; }
        @media(min-width:640px) { .footer-bottom p { font-size: 0.84rem; } }
        .footer-bottom-links { display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; }
        .footer-bottom-links a { color: #cbd5e1; text-decoration: none; font-size: 0.78rem; transition: color 0.2s; }
        @media(min-width:640px) { .footer-bottom-links a { font-size: 0.84rem; } }
        .footer-bottom-links a:hover { color: #fff; }

        /* Responsive */
        @media(max-width:1023px) {
            .nav-link { padding: 8px 10px; font-size: 0.78rem; }
            .hero-section { padding: 36px 0 28px; }
            .hero-section h1 { font-size: 1.3rem; }
            .content-split { flex-direction: column; gap: 24px; }
            .content-main, .content-side { flex: 0 0 100%; max-width: 100%; }
            .tool-interface { padding: 20px; border-radius: 14px; }
        }
        @media(max-width:480px) {
            html { font-size: 15px; }
            .hero-section h1 { font-size: 1.15rem; }
            .hero-section { padding: 28px 0 20px; }
            .hero-section p { font-size: 0.85rem; }
            .breadcrumb-nav { font-size: 0.72rem; }
            .tool-breadcrumb { padding: 6px 0; margin-bottom: 10px; flex-direction: column; align-items: flex-start; gap: 4px; }
            .related-grid { grid-template-columns: 1fr 1fr; gap: 8px; }
            .related-btn { padding: 10px 12px; font-size: 0.78rem; }
            .tool-interface { padding: 14px; border-radius: 12px; margin-bottom: 20px; }
            .page-inner { padding: 20px 0.6rem; }
            .container { padding: 0 0.6rem; }
            .faq-question { padding: 12px 14px; font-size: 0.85rem; }
            .faq-answer { font-size: 0.85rem; }
            .tool-desc-text { font-size: 0.88rem; }
        }
        svg { max-width: 100%; height: auto; }
        body { overflow-x: hidden; }
    </style>
    <style>{$cssCode}</style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="{$relRoot}index.html" class="logo">
                    <div class="logo-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></div>
                    <span class="logo-text">ToolRar</span>
                </a>
                <nav class="desktop-nav">
                    <div class="nav-links-center">
                        <a href="{$relRoot}index.html" class="nav-link">الرئيسية</a>
                        <a href="{$relRoot}all-tools.html" class="nav-link">جميع الأدوات</a>
                        <div class="dropdown-wrap" id="catDropdown">
                            <a href="#" class="nav-link" onmouseenter="openCatDD()" onmouseleave="scheduleCloseCatDD()">التصنيفات<svg class="dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></a>
                            <div class="dropdown-menu cat-dropdown" onmouseenter="cancelCloseCatDD()" onmouseleave="closeCatDD()">
                                <a href="{$relRoot}text-tools/" class="cat-item"><div class="cat-item-icon" style="background:#6366F1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg></div><span>أدوات النصوص والكلمات</span></a>
                                <a href="{$relRoot}Developer/" class="cat-item"><div class="cat-item-icon" style="background:#10B981"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div><span>أدوات البرمجة</span></a>
                                <a href="{$relRoot}Photo-Editing/" class="cat-item"><div class="cat-item-icon" style="background:#F59E0B"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></div><span>أدوات الصور والرسوم</span></a>
                                <a href="{$relRoot}Calculators/" class="cat-item"><div class="cat-item-icon" style="background:#3B82F6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="2"/><path d="M6 12h4"/><path d="M8 10v4"/><path d="M15 13h.01"/><path d="M18 11h.01"/></svg></div><span>أدوات الحاسبة</span></a>
                                <a href="{$relRoot}docs-tools/" class="cat-item"><div class="cat-item-icon" style="background:#EC4899"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg></div><span>أدوات PDF</span></a>
                                <a href="{$relRoot}zip-tools/" class="cat-item"><div class="cat-item-icon" style="background:#10B981"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg></div><span>أدوات ZIP والضغط</span></a>
                                <a href="{$relRoot}seo/" class="cat-item"><div class="cat-item-icon" style="background:#14B8A6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m9 8 5 3-5 3V8z"/></svg></div><span>أدوات SEO</span></a>
                                <a href="{$relRoot}General/" class="cat-item"><div class="cat-item-icon" style="background:#F59E0B"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div><span>أدوات متنوعة</span></a>
                                <a href="{$relRoot}Social-media/" class="cat-item"><div class="cat-item-icon" style="background:#8B5CF6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg></div><span>أدوات المشاركة</span></a>
                            </div>
                        </div>
                        <a href="{$relRoot}blog.html" class="nav-link">المدونة</a>
                        <a href="{$relRoot}pricing.html" class="nav-link">الأسعار</a>
                        <a href="{$relRoot}about.html" class="nav-link">من نحن</a>
                    </div>
                </nav>
                <div class="nav-controls">
                    <div class="dropdown-wrap" id="langDropdown">
                        <button class="nav-link" onclick="toggleLangDD()" onmouseenter="openLangDD()" onmouseleave="scheduleCloseLangDD()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg> العربية<svg class="dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></button>
                        <div class="dropdown-menu lang-dropdown" onmouseenter="cancelCloseLangDD()" onmouseleave="closeLangDD()">
                            <button class="lang-item active"><span class="lang-flag">🇸🇦</span><span>العربية</span><svg class="lang-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg></button>
                            <button class="lang-item" onclick="closeLangDD()"><span class="lang-flag">🇬🇧</span><span>English</span></button>
                            <button class="lang-item" onclick="closeLangDD()"><span class="lang-flag">🇫🇷</span><span>Français</span></button>
                        </div>
                    </div>
                    <button class="dark-toggle" id="darkToggle"><svg id="darkIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg><span id="darkLabel">الوضع الليلي</span></button>
                </div>
                <div class="mobile-controls">
                    <div class="dropdown-wrap" id="mobileLangDropdown">
                        <button class="mobile-btn" onclick="toggleMobileLangDD()" aria-label="اختيار اللغة"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg></button>
                        <div class="dropdown-menu mobile-lang-dropdown" id="mobileLangMenu">
                            <button class="mobile-lang-item active" onclick="closeMobileLangDD()">🇸🇦 العربية</button>
                            <button class="mobile-lang-item" onclick="closeMobileLangDD()">🇬🇧 English</button>
                            <button class="mobile-lang-item" onclick="closeMobileLangDD()">🇫🇷 Français</button>
                        </div>
                    </div>
                    <button class="mobile-btn" id="mobileDarkToggle" aria-label="تبديل الوضع الليلي"><svg id="mobileDarkIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></button>
                    <button class="mobile-btn hamburger" id="hamburgerBtn" aria-label="القائمة"><svg id="hamburgerIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg></button>
                </div>
            </div>
            <div class="mobile-menu" id="mobileMenu">
                <a href="{$relRoot}index.html">الرئيسية</a>
                <a href="{$relRoot}all-tools.html">جميع الأدوات</a>
                <a href="#">التصنيفات</a>
                <a href="{$relRoot}blog.html">المدونة</a>
                <a href="{$relRoot}pricing.html">الأسعار</a>
                <a href="{$relRoot}about.html">من نحن</a>
            </div>
        </div>
    </header>

    <main>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-inner">
                <div class="hero-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{$catIconSvg}</svg>
                    <span>{$catNameAr}</span>
                </div>
                <h1>{$titleAr}</h1>
                <p>{$shortDescAr}</p>
            </div>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="page-inner">
                <div class="tool-body">
                    <div class="tool-breadcrumb">
                        <nav class="breadcrumb-nav" aria-label="شجرة التنقل">
                            <a href="{$relRoot}index.html">الرئيسية</a>
                            <span class="bc-sep">›</span>
                            <a href="{$relRoot}{$catSlug}/">{$catNameAr}</a>
                            <span class="bc-sep">›</span>
                            <span>{$titleAr}</span>
                        </nav>
                        <div class="breadcrumb-dates">
                            <time datetime="{$createdDate}" class="bc-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg><span class="bc-label">تاريخ الإنشاء</span> {$createdDate}</time>
                            <time datetime="{$updatedDate}" class="bc-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg><span class="bc-label">آخر تحديث</span> {$updatedDate}</time>
                        </div>
                    </div>
                    <div class="tool-interface">
                        {$htmlCode}
                    </div>
                    <div class="content-split">
                        <div class="content-main">
                            <div class="tool-desc-section">
                                <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg> نبذة عن الأداة</h2>
                                <div class="tool-desc-text">{$longDescProcessed}</div>
                            </div>
                        </div>
                        <div class="content-side">
                            <div class="content-side-inner">
                            {$tocSectionHtml}
                            <div class="faq-section">
                                <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg> الأسئلة الشائعة</h2>
                                {$faqHtml}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="related-section">
                    <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg> أدوات مشابهة</h2>
                    <div class="related-grid">
                        {$relatedHtml}
                    </div>
                </div>
                <div class="eeat-section">
                        <h2 style="font-size:1.15rem;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg> لماذا تثق في ToolRar؟</h2>
                        <div class="eeat-grid">
                            <div class="eeat-card">
                                <div class="eeat-card-icon" style="background:#EEF2FF;"><svg viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div>
                                <h3>خبرة عالية</h3>
                                <p>أدوات مطورة بخبرة متخصصة في تحسين الإنتاجية والأداء الرقمي</p>
                            </div>
                            <div class="eeat-card">
                                <div class="eeat-card-icon" style="background:#D1FAE5;"><svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></div>
                                <h3>دقة احترافية</h3>
                                <p>نتائج دقيقة وموثوقة مع تجربة مستخدم سلسة وآمنة للجميع</p>
                            </div>
                            <div class="eeat-card">
                                <div class="eeat-card-icon" style="background:#FEF3C7;"><svg viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                                <h3>أدوات مجانية</h3>
                                <p>جميع الأدوات متاحة مجاناً بدون حدود استخدام أو اشتراكات شهرية</p>
                            </div>
                            <div class="eeat-card">
                                <div class="eeat-card-icon" style="background:#F3E8FF;"><svg viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                                <h3>خصوصية تامة</h3>
                                <p>بياناتك آمنة ولا تُشارك مع أي طرف ثالث. جميع العمليات مشفّرة</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-inner">
                <div class="footer-grid">
                    <div>
                        <div class="footer-logo"><div class="logo-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></div><span class="logo-text">ToolRar</span></div>
                        <p class="footer-desc">منصة مجانية تقدم مجموعة متنوعة من الأدوات التي تساعدك في مهامك اليومية</p>
                        <div class="footer-social">
                            <a href="#" aria-label="Telegram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></a>
                            <a href="#" aria-label="Twitter"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/></svg></a>
                            <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/></svg></a>
                            <a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                        </div>
                    </div>
                    <div class="footer-col">
                        <h4 class="footer-col-title">روابط سريعة</h4>
                        <ul>
                            <li><a href="{$relRoot}index.html" class="footer-link">الرئيسية</a></li>
                            <li><a href="{$relRoot}all-tools.html" class="footer-link">جميع الأدوات</a></li>
                            <li><a href="#" class="footer-link">التصنيفات</a></li>
                            <li><a href="{$relRoot}blog.html" class="footer-link">المدونة</a></li>
                            <li><a href="{$relRoot}pricing.html" class="footer-link">الأسعار</a></li>
                            <li><a href="{$relRoot}about.html" class="footer-link">من نحن</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4 class="footer-col-title">أدوات</h4>
                        <ul>
                            <li><a href="{$relRoot}text-tools/" class="footer-link">أدوات النصوص والكلمات</a></li>
                            <li><a href="{$relRoot}Developer/" class="footer-link">أدوات البرمجة</a></li>
                            <li><a href="{$relRoot}Photo-Editing/" class="footer-link">أدوات الصور والرسوم</a></li>
                            <li><a href="{$relRoot}Calculators/" class="footer-link">أدوات الحاسبة</a></li>
                            <li><a href="{$relRoot}docs-tools/" class="footer-link">أدوات PDF</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h4 class="footer-col-title">معلومات</h4>
                        <ul>
                            <li><a href="{$relRoot}about.html" class="footer-link">من نحن</a></li>
                            <li><a href="{$relRoot}contact.html" class="footer-link">تواصل معنا</a></li>
                            <li><a href="{$relRoot}pricing.html" class="footer-link">الأسعار</a></li>
                            <li><a href="{$relRoot}blog.html" class="footer-link">المدونة</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer-bottom">
                    <div class="footer-bottom-links">
                        <a href="{$relRoot}terms.html">شروط الاستخدام</a>
                        <a href="{$relRoot}privacy.html">سياسة الخصوصية</a>
                        <a href="{$relRoot}sitemap.html">خريطة الموقع</a>
                    </div>
                    <p>ToolRar &copy; 2024 جميع الحقوق محفوظة</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        let isDark = false;
        function updateDarkUI() {
            const darkIcon = document.getElementById('darkIcon');
            const darkLabel = document.getElementById('darkLabel');
            const mobileDarkIcon = document.getElementById('mobileDarkIcon');
            if (isDark) {
                document.documentElement.classList.add('dark');
                localStorage.setItem('toolrar-theme', 'dark');
                darkIcon.innerHTML = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>';
                mobileDarkIcon.innerHTML = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>';
                darkLabel.textContent = 'الوضع النهاري';
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('toolrar-theme', 'light');
                darkIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
                mobileDarkIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
                darkLabel.textContent = 'الوضع الليلي';
            }
        }
        function toggleDark() { isDark = !isDark; updateDarkUI(); }
        let langCloseTimer, catCloseTimer;
        function openLangDD() { clearTimeout(langCloseTimer); document.getElementById('langDropdown').classList.add('open'); }
        function scheduleCloseLangDD() { langCloseTimer = setTimeout(closeLangDD, 150); }
        function closeLangDD() { document.getElementById('langDropdown').classList.remove('open'); }
        function toggleLangDD() { document.getElementById('langDropdown').classList.toggle('open'); }
        function cancelCloseLangDD() { clearTimeout(langCloseTimer); }
        function openCatDD() { clearTimeout(catCloseTimer); document.getElementById('catDropdown').classList.add('open'); }
        function scheduleCloseCatDD() { catCloseTimer = setTimeout(closeCatDD, 150); }
        function closeCatDD() { document.getElementById('catDropdown').classList.remove('open'); }
        function cancelCloseCatDD() { clearTimeout(catCloseTimer); }
        function toggleMobileLangDD() { document.getElementById('mobileLangDropdown').classList.toggle('open'); }
        function closeMobileLangDD() { document.getElementById('mobileLangDropdown').classList.remove('open'); }
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const icon = document.getElementById('hamburgerIcon');
            menu.classList.toggle('open');
            icon.innerHTML = menu.classList.contains('open')
                ? '<line x1="18" x2="6" y1="6" y2="18"/><line x1="6" x2="18" y1="6" y2="18"/>'
                : '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>';
        }
        document.addEventListener('mousedown', function(e) {
            ['langDropdown','catDropdown','mobileLangDropdown'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el && !el.contains(e.target)) el.classList.remove('open');
            });
        });
        document.getElementById('darkToggle').addEventListener('click', toggleDark);
        document.getElementById('mobileDarkToggle').addEventListener('click', toggleDark);
        document.getElementById('hamburgerBtn').addEventListener('click', toggleMobileMenu);
        (function init() {
            if (localStorage.getItem('toolrar-theme') === 'dark') { isDark = true; }
            updateDarkUI();
        })();
    </script>
    <script>{$jsCode}</script>
</body>
</html>
PAGE;

    file_put_contents($toolFilePath, $toolPage);

    return $toolSlug;
}

/**
 * Converts root-level links to relative links for subdirectories.
 */
function makeCategoryRelative($html) {
    // 1. Convert static asset links
    $html = str_replace('href="admin/', 'href="../admin/', $html);
    $html = str_replace('src="admin/', 'src="../admin/', $html);
    $html = str_replace('href="fonts/', 'href="../fonts/', $html);
    
    // 2. Convert root level HTML files in navigation and footer
    $html = str_replace('href="index.html"', 'href="../index.html"', $html);
    $html = str_replace('href="about.html"', 'href="../about.html"', $html);
    $html = str_replace('href="all-tools.html"', 'href="../all-tools.html"', $html);
    $html = str_replace('href="blog.html"', 'href="../blog.html"', $html);
    $html = str_replace('href="pricing.html"', 'href="../pricing.html"', $html);
    $html = str_replace('href="contact.html"', 'href="../contact.html"', $html);
    $html = str_replace('href="privacy.html"', 'href="../privacy.html"', $html);
    $html = str_replace('href="terms.html"', 'href="../terms.html"', $html);
    $html = str_replace('href="sitemap.html"', 'href="../sitemap.html"', $html);
    
    // 3. Convert categories listing in the header dropdown
    $html = str_replace('href="text-tools/"', 'href="../text-tools/"', $html);
    $html = str_replace('href="Calculators/"', 'href="../Calculators/"', $html);
    $html = str_replace('href="Developer/"', 'href="../Developer/"', $html);
    $html = str_replace('href="docs-tools/"', 'href="../docs-tools/"', $html);
    $html = str_replace('href="zip-tools/"', 'href="../zip-tools/"', $html);
    $html = str_replace('href="seo/"', 'href="../seo/"', $html);
    $html = str_replace('href="General/"', 'href="../General/"', $html);
    $html = str_replace('href="Photo-Editing/"', 'href="../Photo-Editing/"', $html);
    $html = str_replace('href="Social-media/"', 'href="../Social-media/"', $html);

    return $html;
}

/**
 * Generates the portal index.html page for a category, dynamically merging
 * static placeholder tools with actual active tools from the tools.json database.
 */
function generateCategoryIndex($categoryInput) {
    $dirName = getCategoryPhysicalDir($categoryInput);
    
    $dbToPhysical = [
        'text-tools' => 'text-tools',
        'code-tools' => 'Developer',
        'image-tools' => 'Photo-Editing',
        'calculator-tools' => 'Calculators',
        'pdf-tools' => 'docs-tools',
        'zip-tools' => 'zip-tools',
        'seo-tools' => 'seo',
        'misc-tools' => 'General',
        'share-tools' => 'Social-media'
    ];
    
    // Determine target category and directory
    $categoryId = $categoryInput;
    if (!isset($dbToPhysical[$categoryInput])) {
        $foundId = array_search($categoryInput, $dbToPhysical);
        if ($foundId !== false) {
            $categoryId = $foundId;
        }
    }
    
    // Full Categories Data with Main SVGs & initial planned tools
    $categoriesData = [
        'text-tools' => [
            'title' => 'أدوات النصوص والكتابة',
            'color' => '#6366F1',
            'bgColor' => '#F3F4FF',
            'darkBgColor' => '#1E1B4B',
            'description' => 'أدوات متخصصة في معالجة النصوص وتحريرها وتبسيطها بسهولة',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
            'initial_tools' => [
                ['name' => 'عداد الكلمات والحروف', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"/><line x1="4" y1="15" x2="20" y2="15"/><line x1="10" y1="3" x2="8" y2="21"/><line x1="16" y1="3" x2="14" y2="21"/></svg>'],
                ['name' => 'اداة توليد النصوص', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>'],
                ['name' => 'أداة هنا', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>'],
                ['name' => 'تحويل نص إلى كلام', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>'],
                ['name' => 'محرر النصوص', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>'],
                ['name' => 'مُنقِّح HTML', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'],
                ['name' => 'مُنقِّح CSS', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.04-.23-.29-.38-.63-.38-1.04 0-.93.76-1.69 1.69-1.69H16c3.31 0 6-2.69 6-6 0-5.5-4.5-9.83-10-9.83Z"/></svg>'],
                ['name' => 'مُنقِّح JSON', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H7a2 2 0 0 0-2 2v4a2 2 0 0 1-2 2 2 2 0 0 1 2 2v4a2 2 0 0 0 2 2h1"/><path d="M16 3h1a2 2 0 0 1 2 2v4a2 2 0 0 0 2 2 2 2 0 0 1-2 2v4a2 2 0 0 1-2 2h-1"/></svg>'],
                ['name' => 'تحويل الحالة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 16 4-8 4 8"/><path d="M5 13h4"/><path d="M16 8v8"/><path d="M16 8h2.5a2.5 2.5 0 0 1 0 5H16"/></svg>'],
                ['name' => 'إزالة التكرار', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><polyline points="23 20 23 14 17 14"/><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15"/></svg>'],
                ['name' => 'تنسيق النص', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>'],
            ]
        ],
        'code-tools' => [
            'title' => 'أدوات المطورين',
            'color' => '#10B981',
            'bgColor' => '#F0FDF4',
            'darkBgColor' => '#052E16',
            'description' => 'أدوات وحاسبات متطورة لمساعدتك في كتابة وتصحيح وتنسيق الشيفرات البرمجية',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>',
            'initial_tools' => [
                ['name' => 'JSON مُنسِّق', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H7a2 2 0 0 0-2 2v4a2 2 0 0 1-2 2 2 2 0 0 1 2 2v4a2 2 0 0 0 2 2h1"/><path d="M16 3h1a2 2 0 0 1 2 2v4a2 2 0 0 0 2 2 2 2 0 0 1-2 2v4a2 2 0 0 1-2 2h-1"/></svg>'],
                ['name' => 'HTML/CSS مُنسِّق', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="m10 13-2 2 2 2"/><path d="m14 17 2-2-2-2"/></svg>'],
                ['name' => 'JavaScript مُنسِّق', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'],
                ['name' => 'UUID مُولِّد', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.779-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>'],
                ['name' => 'التعبيرات النمطية', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93 4.93 19.07"/></svg>'],
                ['name' => 'UNIX Timestamp', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'],
                ['name' => 'Base64 مُشفِّر', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'],
                ['name' => 'SQL مُنسِّق', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>'],
                ['name' => 'كود مُلوَّن', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>'],
            ]
        ],
        'image-tools' => [
            'title' => 'أدوات الصور والتصميم',
            'color' => '#F59E0B',
            'bgColor' => '#FFFBEB',
            'darkBgColor' => '#422006',
            'description' => 'أدوات لمعالجة الصور وتحويلها وتعديلها بسهولة وبأعلى جودة',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
            'initial_tools' => [
                ['name' => 'مُحرر الصور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>'],
                ['name' => 'ضغط الصور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="14" y1="10" x2="21" y2="3"/><line x1="3" y1="21" x2="10" y2="14"/></svg>'],
                ['name' => 'تحويل الصور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 12 3 18 9 18"/><polyline points="21 12 21 6 15 6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 18"/></svg>'],
                ['name' => 'تحويل إلى أيقونة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M10 4v16"/></svg>'],
                ['name' => 'إزالة الخلفية', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7 21-4.3-4.3c-1-1-1-2.5 0-3.4l9.6-9.6c1-1 2.5-1 3.4 0l5.6 5.6c1 1 1 2.5 0 3.4L13 21"/><path d="M22 21H7"/><path d="m5 11 9 9"/></svg>'],
                ['name' => 'تغيير الحجم', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>'],
                ['name' => 'قص الصور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>'],
                ['name' => 'تدوير الصور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>'],
                ['name' => 'تعديل الألوان', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7Z"/></svg>'],
            ]
        ],
        'calculator-tools' => [
            'title' => 'المحولات الرياضية',
            'color' => '#3B82F6',
            'bgColor' => '#EFF6FF',
            'darkBgColor' => '#172554',
            'description' => 'أدوات حسابية متعددة الاستخدامات لتسهيل الحسابات والتقديرات اليومية',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="20" x="2" y="2" rx="2"/><path d="M6 12h4"/><path d="M8 10v4"/><path d="M15 13h.01"/><path d="M18 11h.01"/></svg>',
            'initial_tools' => [
                ['name' => 'حاسبة التاريخ', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'],
                ['name' => 'حاسبة الوقت', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="10" y1="2" x2="14" y2="2"/><line x1="12" y1="14" x2="12" y2="8"/><circle cx="12" cy="14" r="8"/></svg>'],
                ['name' => 'حاسبة الحرارة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4v10.54a4 4 0 1 1-4 0V4a2 2 0 0 1 4 0Z"/></svg>'],
                ['name' => 'حاسبة البايت', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="9" r="4"/><line x1="18" y1="5" x2="18" y2="13"/></svg>'],
                ['name' => 'حاسبة المساحة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/></svg>'],
                ['name' => 'حاسبة السرعة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 18a10 10 0 0 1 14 0"/><path d="M12 12V6"/><circle cx="12" cy="12" r="2"/></svg>'],
                ['name' => 'حاسبة النسبة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>'],
                ['name' => 'حاسبة العمر', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>'],
                ['name' => 'حاسبة الطول', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>'],
            ]
        ],
        'pdf-tools' => [
            'title' => 'أدوات ملفات PDF والمستندات',
            'color' => '#EC4899',
            'bgColor' => '#FDF2F8',
            'darkBgColor' => '#500724',
            'description' => 'أدوات متخصصة في معالجة ملفات PDF وتحويلها وتعديلها بسهولة',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>',
            'initial_tools' => [
                ['name' => 'دمج PDF', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>'],
                ['name' => 'تقسيم PDF', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><line x1="20" y1="4" x2="8.12" y2="15.88"/><line x1="14.47" y1="14.48" x2="20" y2="20"/><line x1="8.12" y1="8.12" x2="12" y2="12"/></svg>'],
                ['name' => 'ضغط PDF', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>'],
                ['name' => 'PDF إلى صورة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><circle cx="10" cy="13" r="2"/><path d="m20 17-1.5-1.5a2 2 0 0 0-2.8 0L12 19"/></svg>'],
                ['name' => 'صورة إلى PDF', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>'],
                ['name' => 'حماية PDF', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>'],
                ['name' => 'إزالة حماية', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>'],
                ['name' => 'علامة مائية', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16Z"/></svg>'],
                ['name' => 'تدوير PDF', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>'],
            ]
        ],
        'zip-tools' => [
            'title' => 'أدوات ضغط وفك الملفات ZIP & RAR',
            'color' => '#10B981',
            'bgColor' => '#F0FDF4',
            'darkBgColor' => '#052E16',
            'description' => 'أدوات لضغط وفك ضغط الملفات بسهولة وبأقصى كفاءة',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>',
            'initial_tools' => [
                ['name' => 'استخراج ZIP', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 19a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4l2 2h6a2 2 0 0 1 2 2v1"/><path d="M2 10h20l-2.5 9H4.5L2 10Z"/></svg>'],
                ['name' => 'إنشاء ZIP', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/><line x1="12" y1="10" x2="12" y2="16"/><line x1="9" y1="13" x2="15" y2="13"/></svg>'],
                ['name' => 'محول الملفات', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15l3-3-3-3"/></svg>'],
                ['name' => 'استخراج RAR', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 9.4 7.55 4.24"/><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><polyline points="3.29 7 12 12 20.71 7"/><line x1="12" y1="22" x2="12" y2="12"/></svg>'],
                ['name' => 'محول ويب', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z"/></svg>'],
                ['name' => 'ضغط الملفات', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/></svg>'],
                ['name' => 'استخراج 7z', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>'],
                ['name' => 'تحويل TAR', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><line x1="10" y1="10" x2="10" y2="12"/><line x1="10" y1="14" x2="10" y2="16"/><line x1="10" y1="18" x2="10" y2="18.01"/></svg>'],
                ['name' => 'تحويل GZ', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>'],
            ]
        ],
        'seo-tools' => [
            'title' => 'أدوات SEO',
            'color' => '#14B8A6',
            'bgColor' => '#F0FDFA',
            'darkBgColor' => '#042f2e',
            'description' => 'أدوات تساعد في تحسين محركات البحث وزيادة ظهور موقعك في نتائج البحث الأولى أونلاين',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m9 8 5 3-5 3V8z"/></svg>',
            'initial_tools' => [
                ['name' => 'فحص الروابط', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'],
                ['name' => 'تحليل الروابط', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>'],
                ['name' => 'الكلمات المفتاحية', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/><path d="M7 7h.01"/></svg>'],
                ['name' => 'أداة Sitemap', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>'],
                ['name' => 'فحص Meta Tags', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><circle cx="11" cy="14" r="2"/><path d="m16 18-2.5-2.5"/></svg>'],
                ['name' => 'تحليل المنافسين', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>'],
                ['name' => 'فحص السرعة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'],
                ['name' => 'روابط مكسورة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18.84 12.25 1.72-1.71a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="m5.16 11.75-1.72 1.71a5 5 0 0 0 7.07 7.07l1.72-1.71"/><line x1="2" y1="2" x2="22" y2="22"/></svg>'],
                ['name' => 'تحليل الصفحة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>'],
            ]
        ],
        'misc-tools' => [
            'title' => 'أدوات متنوعة',
            'color' => '#F59E0B',
            'bgColor' => '#FFFBEB',
            'darkBgColor' => '#422006',
            'description' => 'أدوات متنوعة تساعد في تسهيل مهامك اليومية بكفاءة تامة',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
            'initial_tools' => [
                ['name' => 'تحويل العملات', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>'],
                ['name' => 'تحويل القياسات', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/></svg>'],
                ['name' => 'تحويل الأحجام', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S7.29 6.75 7 5.5c-.29 1.26-1.16 2.51-2.29 3.56C3.57 10.09 3 11.14 3 12.25c0 2.22 1.8 4.05 4 4.05Z"/><path d="M17 16.3c2.2 0 4-1.83 4-4.05 0-1.16-.57-2.26-1.71-3.19S17.29 6.75 17 5.5c-.29 1.26-1.16 2.51-2.29 3.56C13.57 10.09 13 11.14 13 12.25c0 2.22 1.8 4.05 4 4.05Z"/></svg>'],
                ['name' => 'تحويل الطاقة', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>'],
                ['name' => 'مُولِّد كلمات المرور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.779-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>'],
                ['name' => 'تحويل التاريخ', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>'],
                ['name' => 'تحويل الأوزان', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7h20"/><path d="M12 7v14"/><path d="M5 7l-2 6h4L5 7"/><path d="M19 7l-2 6h4l-2-6"/></svg>'],
                ['name' => 'مُولِّد الألوان', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5" fill="currentColor"/><circle cx="17.5" cy="10.5" r=".5" fill="currentColor"/><circle cx="8.5" cy="7.5" r=".5" fill="currentColor"/><circle cx="6.5" cy="12.5" r=".5" fill="currentColor"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.93 0 1.5-.67 1.5-1.5 0-.39-.15-.74-.39-1.04-.23-.29-.38-.63-.38-1.04 0-.93.76-1.69 1.69-1.69H16c3.31 0 6-2.69 6-6 0-5.5-4.5-9.83-10-9.83Z"/></svg>'],
                ['name' => 'لغز النص', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>'],
            ]
        ],
        'share-tools' => [
            'title' => 'سوشيال ميديا',
            'color' => '#8B5CF6',
            'bgColor' => '#F3F4FF',
            'darkBgColor' => '#2E1065',
            'description' => 'أدوات تساعد في مشاركة الملفات والمحتوى بسهولة وبشكل آمن',
            'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
            'initial_tools' => [
                ['name' => 'مشاركة الملفات', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>'],
                ['name' => 'إنشاء رمز QR', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><rect x="7" y="7" width="4" height="4"/><rect x="13" y="7" width="4" height="4"/><rect x="7" y="13" width="4" height="4"/><rect x="13" y="13" width="2" height="2"/><rect x="16" y="16" width="2" height="2"/></svg>'],
                ['name' => 'تقصير الروابط', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>'],
                ['name' => 'مشاركة النصوص', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>'],
                ['name' => 'مشاركة الصور', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>'],
                ['name' => 'مشاركة الأكواد', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>'],
                ['name' => 'نسخ النص', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>'],
                ['name' => 'مشاركة بريد', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>'],
                ['name' => 'إنشاء رابط', 'slug' => '#', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>'],
            ]
        ]
    ];

    if (!isset($categoriesData[$categoryId])) {
        return false;
    }

    $cat = $categoriesData[$categoryId];
    $title = $cat['title'];
    $color = $cat['color'];
    $bgColor = $cat['bgColor'];
    $darkBg = $cat['darkBgColor'];
    $desc = $cat['description'];
    $iconSvg = $cat['icon'];

    // 1. Get tools from database
    $dbTools = getTools();
    $dbActiveTools = [];
    foreach ($dbTools as $dbTool) {
        if (($dbTool['category_id'] ?? '') === $categoryId && !empty($dbTool['page_slug'])) {
            $dbActiveTools[] = $dbTool;
        }
    }

    // 2. Merge static planned/placeholder tools with database active tools
    // 2. ONLY include active database tools in the compiled list, reusing custom icons if matched
    $compiledToolsList = [];
    foreach ($dbActiveTools as $actTool) {
        $icon = $iconSvg; // default to category main icon
        foreach ($cat['initial_tools'] as $initTool) {
            if ($initTool['name'] === $actTool['title_ar']) {
                $icon = $initTool['icon'];
                break;
            }
        }
        $subSlug = !empty($actTool['sub_slug']) ? trim($actTool['sub_slug'], '/') . '/' : '';
        $compiledToolsList[] = [
            'name' => $actTool['title_ar'],
            'slug' => $subSlug . $actTool['page_slug'] . '.html',
            'icon' => $icon
        ];
    }

    // 3. Compile dynamic tool cards HTML
    $toolsGridHtml = '';
    if ($categoryId === 'calculator-tools') {
        $categoriesDataList = [
            [
                'name' => 'التاريخ والوقت',
                'match' => function($slug) {
                    return strpos($slug, 'date-and-time/') === 0 || $slug === 'age-calculator.html' || $slug === 'lunar-age.html';
                },
                'color' => 'teal',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
                'items' => []
            ],
            [
                'name' => 'المالية والاستثمار',
                'match' => function($slug) { return strpos($slug, 'finance/') === 0; },
                'color' => 'green',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
                'items' => []
            ],
            [
                'name' => 'التعليم والدرجات',
                'match' => function($slug) { return strpos($slug, 'educational/') === 0; },
                'color' => 'purple',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"></path><path d="M6 12v5c0 2 2 3 6 3s6-1 6-3v-5"></path></svg>',
                'items' => []
            ],
            [
                'name' => 'الرياضيات والجبر',
                'match' => function($slug) { return strpos($slug, 'math/') === 0; },
                'color' => 'indigo',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="9" x2="15" y2="15"></line><circle cx="9" cy="15" r="1"></circle><circle cx="15" cy="9" r="1"></circle></svg>',
                'items' => []
            ],
            [
                'name' => 'الصحة واللياقة',
                'match' => function($slug) { return strpos($slug, 'health-and-fitness/') === 0; },
                'color' => 'red',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>',
                'items' => []
            ],
            [
                'name' => 'الحاسبات الإسلامية',
                'match' => function($slug) { return strpos($slug, 'islamic/') === 0 || strpos($slug, 'islamic-calculators/') === 0; },
                'color' => 'orange',
                'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>',
                'items' => []
            ]
        ];

        foreach ($compiledToolsList as $t) {
            $categorized = false;
            foreach ($categoriesDataList as &$cat) {
                if ($cat['match']($t['slug'])) {
                    $cat['items'][] = $t;
                    $categorized = true;
                    break;
                }
            }
            if (!$categorized) {
                $categoriesDataList[3]['items'][] = $t;
            }
        }
        unset($cat);

        $colorClasses = [
            'teal' => 'bg-teal-50 dark:bg-teal-950/30 text-teal-600 dark:text-teal-400',
            'green' => 'bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400',
            'purple' => 'bg-purple-50 dark:bg-purple-950/30 text-purple-600 dark:text-purple-400',
            'indigo' => 'bg-indigo-50 dark:bg-indigo-950/30 text-indigo-600 dark:text-indigo-400',
            'red' => 'bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400',
            'orange' => 'bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400'
        ];

        foreach ($categoriesDataList as $cat) {
            if (empty($cat['items'])) continue;
            
            $iconClass = $colorClasses[$cat['color']] ?? 'bg-indigo-50 text-indigo-600';
            
            $toolsGridHtml .= "\n                    <!-- {$cat['name']} -->\n";
            $toolsGridHtml .= "                    <h2 class=\"category-section-title\">\n";
            $toolsGridHtml .= "                        <span class=\"category-title-icon {$iconClass}\">\n";
            $toolsGridHtml .= "                            {$cat['icon']}\n";
            $toolsGridHtml .= "                        </span>\n";
            $toolsGridHtml .= "                        {$cat['name']}\n";
            $toolsGridHtml .= "                    </h2>\n";
            $toolsGridHtml .= "                    <div class=\"category-tools-grid\">\n";
            
            foreach ($cat['items'] as $t) {
                $tName = htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8');
                $tSlug = $t['slug'];
                $tIcon = $t['icon'];
                $tIconBgColor = $color . '12';
                
                $toolsGridHtml .= <<<CARD
        <a href="{$tSlug}" class="category-tool-card">
            <div class="category-tool-icon-wrap" style="background: {$tIconBgColor}; color: {$color};">
                {$tIcon}
            </div>
            <span class="category-tool-name">{$tName}</span>
        </a>\n
CARD;
            }
            $toolsGridHtml .= "                    </div>\n";
        }
    } else {
        $innerHtml = '';
        foreach ($compiledToolsList as $t) {
            $tName = htmlspecialchars($t['name'], ENT_QUOTES, 'UTF-8');
            $tSlug = $t['slug'];
            $tIcon = $t['icon'];
            $tIconBgColor = $color . '12';
            
            $innerHtml .= <<<CARD
        <a href="{$tSlug}" class="category-tool-card">
            <div class="category-tool-icon-wrap" style="background: {$tIconBgColor}; color: {$color};">
                {$tIcon}
            </div>
            <span class="category-tool-name">{$tName}</span>
        </a>\n
CARD;
        }
        $toolsGridHtml = <<<GRID
                    <div class="category-tools-grid">
                        {$innerHtml}
                    </div>
GRID;
    }

    // 4. Load SEO Descriptions and FAQs from files, falling back to basic defaults
    $seoPortalsDir = SITE_PATH . '/admin/data/seo_portals';
    $seoTitle = '🔍 دليل ' . $title . ' والخدمات المجانية أونلاين';
    $seoContent = '<p>' . $desc . '. الباقة الكاملة المتاحة للجميع مجاناً وبأعلى درجات الأمان والسرية.</p>';
    $faqs = [
        ['q' => 'ما هي هذه الأدوات وكيف أستخدمها مجاناً؟', 'a' => 'تقدم منصة ToolRar مجموعة متكاملة من الأدوات والخدمات الرقمية لمساعدتك في معالجة الملفات والصور والبرمجة والحسابات مجاناً بالكامل وبأعلى كفاءة.'],
        ['q' => 'هل عمليات المعالجة آمنة وتحافظ على خصوصيتي؟', 'a' => 'نعم، تتم كافة العمليات الحسابية وتعديل النصوص والصور محلياً 100% داخل جهازك دون رفع أي بيانات حساسة إلى الخوادم الخارجية لضمان أمانك الكامل.'],
        ['q' => 'هل تدعم الأدوات الهواتف وشاشات الأجهزة المحمولة؟', 'a' => 'نعم، تم تصميم وبرمجة المنصة والصفحات لتكون متجاوبة بالكامل وبأعلى درجات السرعة لتناسب كافة الهواتف والأجهزة الذكية.'],
        ['q' => 'كيف يمكنني مشاركة نتائج الأدوات مع الآخرين؟', 'a' => 'تتيح لك المنصة نسخ النصوص والنتائج بضغطة زر واحدة لتتمكن من مشاركتها بسهولة عبر شتى وسائل التواصل الاجتماعي أو برامج العمل.'],
        ['q' => 'هل تحتاج المنصة إلى تسجيل دخول أو اشتراكات شهرية؟', 'a' => 'قطعاً لا. جميع الأدوات والصفحات مجانية بالكامل ومتاحة بدون قيود أو اشتراكات أو الحاجة لإنشاء حساب أو تسجيل دخول.']
    ];

    $descFile = $seoPortalsDir . '/' . $dirName . '_desc.html';
    $faqsFile = $seoPortalsDir . '/' . $dirName . '_faqs.json';

    if (file_exists($descFile)) {
        $seoContent = file_get_contents($descFile);
    }
    if (file_exists($faqsFile)) {
        $loadedFaqs = json_decode(file_get_contents($faqsFile), true);
        if (is_array($loadedFaqs) && count($loadedFaqs) >= 5) {
            $faqs = $loadedFaqs;
        }
    }

    // Compile dynamic FAQs Accordion HTML
    $faqHtml = '';
    foreach ($faqs as $i => $faq) {
        $q = htmlspecialchars($faq['q'], ENT_QUOTES, 'UTF-8');
        $a = $faq['a'];
        
        $faqHtml .= <<<FAQ
        <div class="cat-faq-item">
            <div class="cat-faq-question" onclick="toggleFaq(this)">
                <span>{$q}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><polyline points="6 9 12 15 18 9"/></svg>
            </div>
            <div class="cat-faq-answer">
                <p>{$a}</p>
            </div>
        </div>
FAQ;
    }

    // 5. Extract structural layout from index.html
    $sourceFile = SITE_PATH . '/index.html';
    if (!file_exists($sourceFile)) {
        return false;
    }
    $htmlSource = file_get_contents($sourceFile);

    if (!preg_match('/<head>(.*?)<\/head>/is', $htmlSource, $matchesHead)) return false;
    $headInner = makeCategoryRelative($matchesHead[1]);

    if (!preg_match('/(<header class="header">.*?<\/header>)/is', $htmlSource, $matchesHeader)) return false;
    $headerHtml = makeCategoryRelative($matchesHeader[1]);

    if (!preg_match('/(<footer class="footer">.*?<\/footer>)/is', $htmlSource, $matchesFooter)) return false;
    $footerHtml = makeCategoryRelative($matchesFooter[1]);

    // Portal custom CSS
    $categoryStyles = <<<CSS
        /* Breadcrumb Styles */
        .category-breadcrumb {
            display: flex; align-items: center; gap: 6px; 
            font-size: 0.85rem; color: rgba(255,255,255,0.7); 
            margin-bottom: 12px; justify-content: center;
        }
        .category-breadcrumb a { color: #fff; text-decoration: none; font-weight: 500; opacity: 0.9; }
        .category-breadcrumb a:hover { text-decoration: underline; opacity: 1; }
        .category-breadcrumb span { opacity: 0.7; }
        
        /* Category Hero Styles (Smaller Hero) */
        .category-hero {
            padding: 36px 0 28px; text-align: center; color: #fff; position: relative; overflow: hidden;
            background-color: #192746;
        }
        .category-hero::before {
            content: ''; position: absolute; inset: 0;
            background: radial-gradient(circle at 10% 90%, rgba(255,255,255,0.05) 0%, transparent 40%),
                        radial-gradient(circle at 90% 10%, rgba(255,255,255,0.03) 0%, transparent 40%);
            pointer-events: none;
        }
        .category-hero .container { position: relative; z-index: 2; }
        
        .category-hero-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 50px; height: 50px; border-radius: 12px;
            background: {$color}; color: #fff; margin-bottom: 14px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }
        .category-hero-badge svg { width: 24px; height: 24px; }
        .category-hero h1 { font-size: 1.6rem; font-weight: 900; line-height: 1.3; margin-bottom: 8px; }
        .category-hero p { font-size: 0.92rem; max-width: 600px; margin: 0 auto; line-height: 1.6; color: rgba(255,255,255,0.9); }
        
        /* Portal Body Styling */
        .category-body { background: #F8FAFC; padding: 40px 0; flex: 1; }
        .dark .category-body { background: #0F172A; }
        .category-body-inner { max-width: 960px; margin: 0 auto; width: 100%; }
        
        /* Category Tools Grid */
        .category-tools-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px;
            width: 100%; margin-bottom: 40px;
        }
        
        /* Interactive Cards */
        .category-tool-card {
            background: #fff; border: 1px solid #E2E8F0; border-radius: 14px;
            padding: 20px; display: flex; align-items: center; gap: 14px;
            text-decoration: none; color: #1e293b; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .dark .category-tool-card { background: #1E293B; border-color: #334155; color: #e2e8f0; }
        
        .category-tool-card:hover {
            border-color: {$color}; color: {$color};
            box-shadow: 0 10px 20px -5px rgba(99,102,241,0.08);
            transform: translateY(-3px);
        }
        .dark .category-tool-card:hover {
            box-shadow: 0 10px 20px -5px rgba(0,0,0,0.25);
        }
        
        .category-tool-icon-wrap {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            transition: transform 0.2s;
        }
        .category-tool-card:hover .category-tool-icon-wrap { transform: scale(1.08); }
        .category-tool-icon-wrap svg { width: 20px; height: 20px; }
        .category-tool-name { font-size: 0.95rem; font-weight: 700; line-height: 1.4; }
        
        /* SEO Content & FAQ Styles */
        .category-seo-wrap {
            margin-top: 48px; padding-top: 36px; border-top: 1px solid #E2E8F0;
        }
        .dark .category-seo-wrap { border-color: #334155; }
        .category-seo-wrap h2 {
            font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .dark .category-seo-wrap h2 { color: #fff; }
        
        .category-seo-content {
            color: #475569; font-size: 0.95rem; line-height: 1.85; text-align: justify;
        }
        .dark .category-seo-content { color: #cbd5e1; }
        .category-seo-content p { margin-bottom: 16px; }
        .category-seo-content h3 {
            font-size: 1.15rem; font-weight: 800; color: #1e293b; margin-top: 28px; margin-bottom: 12px;
        }
        .dark .category-seo-content h3 { color: #fff; }
        .category-seo-content ul, .category-seo-content ol {
            margin-right: 20px; margin-bottom: 16px; list-style-type: square;
        }
        .category-seo-content li { margin-bottom: 8px; }
        
        /* FAQs Accordion Styles */
        .category-faq-wrap { margin-top: 48px; }
        .category-faq-wrap h2 {
            font-size: 1.4rem; font-weight: 800; color: #1e293b; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .dark .category-faq-wrap h2 { color: #fff; }
        
        .category-faq-accordion { display: flex; flex-direction: column; gap: 12px; }
        
        .cat-faq-item {
            border: 1px solid #E2E8F0; border-radius: 12px; background: #fff; overflow: hidden;
            transition: all 0.2s ease;
        }
        .dark .cat-faq-item { border-color: #334155; background: #1E293B; }
        .cat-faq-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        
        .cat-faq-question {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px; cursor: pointer; font-weight: 700; font-size: 0.95rem;
            color: #1e293b; transition: background 0.2s; gap: 12px; user-select: none;
        }
        .dark .cat-faq-question { color: #e2e8f0; }
        .cat-faq-question:hover { background: #F8FAFC; }
        .dark .cat-faq-question:hover { background: rgba(255,255,255,0.02); }
        
        .cat-faq-question svg { transition: transform 0.3s ease; flex-shrink: 0; color: {$color}; }
        .cat-faq-item.open .cat-faq-question svg { transform: rotate(180deg); }
        
        .cat-faq-answer {
            padding: 0 20px; max-height: 0; overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: #475569; line-height: 1.8; font-size: 0.9rem; text-align: justify;
        }
        .dark .cat-faq-answer { color: #94a3b8; }
        .cat-faq-item.open .cat-faq-answer { padding: 0 20px 16px; max-height: 500px; }
        
        /* Responsive tweaks */
        }
        /* Category Section Title Styles */
        .category-section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e293b;
            margin-top: 36px;
            margin-bottom: 16px;
            padding-top: 10px;
        }
        .dark .category-section-title {
            color: #ffffff;
        }
        .category-title-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            flex-shrink: 0;
        }
        .category-title-icon svg {
            width: 18px;
            height: 18px;
        }
        /* Color themes for category icons */
        .bg-teal-50 { background-color: rgba(20, 184, 166, 0.1); }
        .text-teal-600 { color: #0d9488; }
        .dark .text-teal-400 { color: #2dd4bf; }
        .dark .bg-teal-950\/30 { background-color: rgba(20, 184, 166, 0.15); }

        .bg-green-50 { background-color: rgba(34, 197, 94, 0.1); }
        .text-green-600 { color: #16a34a; }
        .dark .text-green-400 { color: #4ade80; }
        .dark .bg-green-950\/30 { background-color: rgba(34, 197, 94, 0.15); }

        .bg-purple-50 { background-color: rgba(168, 85, 247, 0.1); }
        .text-purple-600 { color: #9333ea; }
        .dark .text-purple-400 { color: #c084fc; }
        .dark .bg-purple-950\/30 { background-color: rgba(168, 85, 247, 0.15); }

        .bg-indigo-50 { background-color: rgba(99, 102, 241, 0.1); }
        .text-indigo-600 { color: #4f46e5; }
        .dark .text-indigo-400 { color: #818cf8; }
        .dark .bg-indigo-950\/30 { background-color: rgba(99, 102, 241, 0.15); }

        .bg-red-50 { background-color: rgba(239, 68, 68, 0.1); }
        .text-red-600 { color: #dc2626; }
        .dark .text-red-400 { color: #f87171; }
        .dark .bg-red-950\/30 { background-color: rgba(239, 68, 68, 0.15); }

        .bg-orange-50 { background-color: rgba(249, 115, 22, 0.1); }
        .text-orange-600 { color: #ea580c; }
        .dark .text-orange-400 { color: #fb923c; }
        .dark .bg-orange-950\/30 { background-color: rgba(249, 115, 22, 0.15); }
CSS;

    // Build the final complete index.html file!
    $pageHtml = <<<PAGE
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} - ToolRar | أدوات مجانية وسريعة وموثوقة</title>
    <meta name="description" content="{$desc}. أدوات ويب احترافية وسريعة وآمنة 100% مجاناً على منصة ToolRar.">
    <link rel="preload" href="../admin/assets/fonts/cairo/cairo-v31-arabic.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="../admin/assets/fonts/cairo/cairo-v31-latin.woff2" as="font" type="font/woff2" crossorigin>
    
    {$headInner}
    
    <style>
        body { font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        {$categoryStyles}
    </style>
</head>
<body>
    {$headerHtml}
    
    <main>
        <!-- Category Hero Section -->
        <section class="category-hero">
            <div class="container">
                <div class="category-breadcrumb" aria-label="شجرة التنقل">
                    <a href="../index.html">الرئيسية</a>
                    <span>›</span>
                    <span>{$title}</span>
                </div>
                <div class="category-hero-badge">
                    {$iconSvg}
                </div>
                <h1>{$title}</h1>
                <p>{$desc}</p>
            </div>
        </section>
        
        <!-- Category Body Section -->
        <section class="category-body">
            <div class="container">
                <div class="category-body-inner">
                    <!-- Tool Buttons Grid -->
                    {$toolsGridHtml}
                    
                    <!-- Extensive SEO Description Section -->
                    <div class="category-seo-wrap">
                        <h2>{$seoTitle}</h2>
                        <div class="category-seo-content">
                            {$seoContent}
                        </div>
                    </div>
                    
                    <!-- Category FAQ Accordions -->
                    <div class="category-faq-wrap">
                        <h2>❓ الأسئلة الشائعة حول {$title}</h2>
                        <div class="category-faq-accordion">
                            {$faqHtml}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    {$footerHtml}
    
    <script>
        // FAQ Accordion Toggle Script
        function toggleFaq(el) {
            const item = el.parentElement;
            const isOpen = item.classList.contains('open');
            
            // Close all FAQ items
            document.querySelectorAll('.cat-faq-item').forEach(i => {
                i.classList.remove('open');
            });
            
            // Toggle clicked item
            if (!isOpen) {
                item.classList.add('open');
            }
        }
    </script>
</body>
</html>
PAGE;

    $targetFile = SITE_PATH . '/' . $dirName . '/index.html';
    return file_put_contents($targetFile, $pageHtml) !== false;
}
