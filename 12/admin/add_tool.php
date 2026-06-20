<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

// AI generation handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ai_generate'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $provider = $_POST['ai_provider'] ?? 'openrouter';
    $model = $_POST['ai_model'] ?? '';

    $keys = [
        'google' => 'AQ.Ab8RN6KhduQtbtmII7u0ijcaZDzc7xb8iZCTnPETVnZ2F3nfog',
        'opencode' => 'sk-KQSYcYOXAr5YDBCzFh7jlVlxoSR5ZI4cnc8y56RlJJ9YjROqQhkmuG6fvKLwsWrR',
        'openrouter' => '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',
        'groq' => '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',
        'cerebras' => 'csk-8hnwewjmfy46xthhwcn3t22n94tt9t349e2rdkw9xvnf28pc',
        'siliconflow' => 'sk-uksfscrxjhaynjmlcpbhcvykqqkukmjwqdidjbvsczsjdsge',
        'routeway' => 'sk-Mrl1csStecbVXviGdJaae157NT5Whg9WVY6ZMqy8wg05AcmJhokX0l9e5SHQjjltcsTlaLhwN8H8HDf1-w5O',
        'featherless' => 'rc_ad3f761e6bcecf4c751c248e549bc2a045438e268d4c995172d338155615db0d',
        'github' => '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'
    ];
    $apiKey = $keys[$provider] ?? $keys['openrouter'];
    $title = $_POST['ai_title'] ?? '';
    $metaTitle = $_POST['ai_meta_title'] ?? $title;
    $type = $_POST['ai_type'] ?? 'short_desc';
    $lang = $_POST['ai_lang'] ?? 'ar';

    if (empty($title) && $type !== 'generate_code') { echo json_encode(['error' => 'عنوان الأداة مطلوب']); exit; }

    $customPrompt = $_POST['ai_custom_prompt'] ?? '';

    $systemPrompts = [
        'short_desc' => 'أنت كاتب محتوى متخصص في أدوات الويب. مهمتك كتابة وصف قصير احترافي لا يتجاوز 160 حرفاً. كن مباشراً وواضحاً.',
        'meta_desc' => 'أنت خبير تحسين محركات البحث (SEO). اكتب وصفاً تعريفياً (Meta Description) بين 300-320 حرفاً مع كلمات مفتاحية مناسبة.',
        'long_desc' => 'أنت كاتب محتوى محترف وخبير سيو (SEO) متقدم للغاية. اكتب نبذة تعريفية احترافية شاملة ومفصلة عن الأداة المطلوبة باللغة العربية الفصحى. يجب أن تلتزم التزاماً صارماً بالشروط التالية:
1. طول النص: يجب أن يكون طول النص المولد بين 600 إلى 800 كلمة حصراً دون أي زيادة أو نقصان.
2. الرموز والماركداون: يمنع منعاً باتاً استخدام أي نجوم مثل (* أو **) أو علامات ماركداون أو رموز غريبة في النص. استخدم فقط وسوم HTML النظيفة للتنسيق (h2, h3, p, ul, li, strong).
3. أسلوب الكتابة: يجب أن يكون الأسلوب احترافياً، طبيعياً، وسلساً للغاية وكأنه مكتوب بواسطة كاتب بشري خبير، وبطريقة تفاعلية وممتعة تمنع ملل القارئ تماماً.
4. توزيع الكلمات المفتاحية والسيو: وزع الكلمة المفتاحية الأساسية والكلمات الرئيسية بذكاء وتوازن بناءً على طول النص، على ألا تتجاوز كثافة تكرار أي كلمة مفتاحية نسبة 3% من إجمالي النص لضمان توافق السيو وتفادي الحشو (Keyword Stuffing). أضف كلمات مرادفة وكلمات مساندة ومصطلحات LSI تدعم المعنى وتقوي النص سياقياً.
5. معايير E-E-A-T: حقق بدقة المعايير الأربعة لمحركات البحث E-E-A-T (الخبرة العملية Experience بتقديم إرشادات مفيدة، التخصص والخبرة العلمية Expertise، ومصداقية وسلطة المحتوى Authoritativeness، والموثوقية والأمان Trustworthiness)، بحيث لا تقل نتيجة تقييم كل معيار عن 9/10 بشكل طبيعي ومقنع جداً.
6. سكيما البيانات المنظمة: في نهاية النص، أضف كود سكيما مقال Article بصيغة JSON-LD داخل وسم <script type="application/ld+json"> يحتوي على headline و description و datePublished و author و publisher.',
        'faq' => 'أنشئ 5 إلى 8 أسئلة شائعة متقدمة مع إجاباتها عن الأداة. أعد النتيجة بتنسيق JSON فقط: [{"q":"السؤال","a":"الإجابة"}]. يجب أن تحتوي الأسئلة على الكلمة المفتاحية الأساسية أو كلمات مفتاحية واضحة. يجب أن تحتوي الإجابات على كلمات مفتاحية مشابهة ومساندة ومرادفة. الأسئلة تغطي: طريقة الاستخدام خطوة بخطوة، الفوائد والمميزات، المشاكل الشائعة والحلول، التوافق والمتطلبات، مقارنة مع أدوات مشابهة.',
        'generate_code' => 'أنت مبرمج متخصص في أدوات الويب. مهمتك إنشاء كود HTML/CSS/JS للأداة المطلوبة فقط. لا تضف أي كود لتنسيق الصفحة كاملة (لا html, body, header, footer, layout عام). أعد النتيجة بتنسيق JSON فقط: {"html":"...","css":"...","js":"..."}. الكود خاص بالأداة فقط باستخدام كلاسات مميزة مثل .tool-... لتجنب التداخل مع تصميم الصفحة. كود نظيف ومتجاوب مع تصميم عصري.'
    ];

    $userPrompts = [
        'short_desc' => "اكتب وصفاً قصيراً من 150-160 حرفاً فقط (بدون زيادة) للأداة \"{$title}\" باللغة العربية.",
        'meta_desc' => "اكتب وصفاً لمحركات البحث (Meta Description) من 300-320 حرفاً فقط عن أداة \"{$title}\" باللغة العربية.",
        'long_desc' => "اكتب نبذة تعريفية شاملة ومقال سيو متكامل عن أداة \"{$title}\" باللغة العربية الفصحى. الكلمة المفتاحية الأساسية هي \"{$title}\". الشروط الهامة التي يجب تطبيقها بحذافيرها:
1. طول النص الكلي يجب أن يتراوح بين 600 إلى 800 كلمة حصراً.
2. استخدم وسوم HTML لتنسيق النص (h2, h3, p, ul, li, strong).
3. يمنع استخدام النجوم (* أو **) أو علامات الماركداون أو أي رموز غريبة تماماً.
4. يجب أن يكون أسلوب الصياغة بشرياً، طبيعياً، احترافياً، ومنسقاً بشكل يسهل قراءته دون ملل.
5. وزّع الكلمات المفتاحية طبيعياً، بحيث لا تتجاوز نسبة تكرار الكلمة المفتاحية الأساسية أو أي كلمة مفتاحية 3% من النص. استخدم مرادفات لغوية وكلمات مساندة ومصطلحات LSI تدعم الفكرة.
6. يجب إظهار جودة معايير E-E-A-T الأربعة (الخبرة، التخصص، السلطة، الموثوقية) بشكل احترافي يضمن تقييماً لا يقل عن 9/10 لكل معيار.
7. أضف سكيما Article بصيغة JSON-LD داخل وسم <script type=\"application/ld+json\"> في نهاية النص.",
        'faq' => "أنشئ 5 إلى 8 أسئلة شائعة متقدمة مع إجاباتها عن أداة \"{$title}\". الأسئلة تحتوي على كلمات مفتاحية واضحة والإجابات تحتوي على كلمات مشابهة ومساندة. أعد JSON فقط: [{\"q\":\"السؤال\",\"a\":\"الإجابة\"}] باللغة العربية.",
        'generate_code' => "أنشئ كود كامل لأداة: \"{custom_prompt}\". أعد JSON فقط: {\"html\":\"...\",\"css\":\"...\",\"js\":\"...\"}. مهم جداً: لا تضف html, body, div wrapper عام أو تنسيق صفحة كاملة. استخدم كلاسات خاصة مثل .tool-... فقط للأداة. كود HTML معاصر، CSS أنيق ومتجاوب بدون تداخل مع الصفحة."
    ];

    $systemMsg = $systemPrompts[$type] ?? $systemPrompts['short_desc'];
    $userMsg = $userPrompts[$type] ?? $userPrompts['short_desc'];
    if ($type === 'generate_code') {
        $userMsg = str_replace('{custom_prompt}', $customPrompt ?: $title, $userMsg);
    }

    if ($lang === 'en') {
        $systemMsg = str_replace('باللغة العربية', 'in English', $systemMsg);
        $systemMsg = str_replace('اللغة', 'language', $systemMsg);
        $userMsg = str_replace('باللغة العربية', 'in English', $userMsg);
        $userMsg = str_replace('اكتب', 'Write', $userMsg);
        $userMsg = str_replace('أنشئ', 'Create', $userMsg);
    } elseif ($lang === 'fr') {
        $systemMsg = str_replace('باللغة العربية', 'en français', $systemMsg);
        $systemMsg = str_replace('اللغة', 'langue', $systemMsg);
        $userMsg = str_replace('باللغة العربية', 'en français', $userMsg);
        $userMsg = str_replace('اكتب', 'Écris', $userMsg);
        $userMsg = str_replace('أنشئ', 'Crée', $userMsg);
    }

    $maxTokens = ($type === 'generate_code') ? 16384 : 4096;

    if ($provider === 'google') {
        if (empty($model)) {
            $model = 'gemini-1.5-flash';
        }
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apiKey;
        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $userMsg]
                    ]
                ]
            ],
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemMsg]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => $maxTokens
            ]
        ]);
        $headers = [
            'Content-Type: application/json'
        ];
    } elseif ($provider === 'opencode') {
        if (empty($model)) {
            $model = 'deepseek-v4-flash-free';
        }
        $url = 'https://opencode.ai/zen/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    } elseif ($provider === 'groq') {
        if (empty($model)) {
            $model = 'llama-3.3-70b-versatile';
        }
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    } elseif ($provider === 'cerebras') {
        if (empty($model)) {
            $model = 'gpt-oss-120b';
        }
        $url = 'https://api.cerebras.ai/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    } elseif ($provider === 'siliconflow') {
        if (empty($model)) {
            $model = 'deepseek-ai/DeepSeek-V4-Flash';
        }
        $url = 'https://api.siliconflow.com/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    } elseif ($provider === 'routeway') {
        if (empty($model)) {
            $model = 'deepseek-v4-flash:free';
        }
        $url = 'https://api.routeway.ai/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    } elseif ($provider === 'github') {
        if (empty($model)) {
            $model = 'openai/gpt-4.1';
        }
        $url = 'https://models.github.ai/inference/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $apiKey
        ];
    } elseif ($provider === 'featherless') {
        if (empty($model)) {
            $model = 'zai-org/GLM-5.1';
        }
        $url = 'https://api.featherless.ai/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ];
    } else { // openrouter
        if (empty($model)) {
            $model = 'deepseek/deepseek-v4-flash:free';
        }
        $url = 'https://openrouter.ai/api/v1/chat/completions';
        $payload = json_encode([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMsg],
                ['role' => 'user', 'content' => $userMsg]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'include_reasoning' => true
        ]);
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'HTTP-Referer: https://toolrar.com',
            'X-Title: ToolRar'
        ];
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        if (strpos($curlError, 'timed out') !== false || strpos($curlError, 'Timeout') !== false) {
            $errMsg = '⏱️ استغرق الطلب وقتاً أطول من المتوقع. ';
            $errMsg .= ($type === 'generate_code') ? 'توليد الكود قد يستغرق حتى 3 دقائق، حاول مرة أخرى.' : 'حاول مرة أخرى أو استخدم برومبت أقصر.';
        } elseif (strpos($curlError, 'Connection refused') !== false) {
            $errMsg = '🔌 تعذر الاتصال بالخادم. تحقق من اتصالك بالإنترنت.';
        } else {
            $errMsg = '❌ خطأ في الاتصال: ' . $curlError;
        }
        echo json_encode(['error' => $errMsg]);
        exit;
    }

    if ($httpCode !== 200) {
        $errData = json_decode($response, true);
        $rawMsg = $errData['error']['message'] ?? ($response ?: '');
        $log = 'HTTP ' . $httpCode . ' | ' . $rawMsg;

        if ($httpCode === 429) {
            $errMsg = '❌ تم تجاوز حد الاستخدام. يرجى المحاولة لاحقاً. (كود: 429)';
        } elseif ($httpCode === 401) {
            $errMsg = '❌ مفتاح API غير صالح. تحقق من المفتاح. (كود: 401)';
        } elseif ($httpCode === 400) {
            $errMsg = '❌ طلب غير صحيح. ' . ($rawMsg ?: '');
        } else {
            $errMsg = '❌ خطأ غير متوقع (كود: ' . $httpCode . ')';
            if ($rawMsg) $errMsg .= "\n" . mb_substr($rawMsg, 0, 300);
        }
        echo json_encode(['error' => $errMsg, 'debug' => $log]);
        exit;
    }

    $data = json_decode($response, true);
    if ($provider === 'google') {
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    } else {
        $text = $data['choices'][0]['message']['content'] ?? '';
    }

    // Clean markdown code fences and extract JSON if present
    $text = preg_replace('/^```(?:json)?\s*\n?/i', '', $text);
    $text = preg_replace('/\n?```\s*$/i', '', $text);
    // Extract JSON object if mixed with surrounding text
    if ($type === 'generate_code') {
        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $candidate = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($candidate, true);
            if ($parsed && (isset($parsed['html']) || isset($parsed['css']) || isset($parsed['js']))) {
                $text = $candidate;
            }
        }
    }

    echo json_encode(['text' => $text, 'type' => $type]);
    exit;
}

$categories = getCategories();
$editMode = false;
$tool = [
    'id' => '', 'category_id' => '',
    'title_ar' => '', 'title_en' => '', 'title_fr' => '',
    'meta_title_ar' => '', 'meta_title_en' => '', 'meta_title_fr' => '',
    'meta_desc_ar' => '', 'meta_desc_en' => '', 'meta_desc_fr' => '',
    'short_desc_ar' => '', 'short_desc_en' => '', 'short_desc_fr' => '',
    'html_code' => '', 'css_code' => '', 'js_code' => '',
    'long_desc_ar' => '', 'long_desc_en' => '', 'long_desc_fr' => '',
    'tool_slug' => '', 'sub_slug' => '', 'faq' => []
];

if (isset($_GET['edit']) && isset($_SESSION['edit_tool_data'])) {
    $editMode = true;
    $tool = $_SESSION['edit_tool_data'];
    unset($_SESSION['edit_tool_data']);
}

if (isset($_GET['action']) && $_GET['action'] === 'generate' && isset($_GET['id'])) {
    $tools = getTools();
    foreach ($tools as &$t) {
        if ($t['id'] === $_GET['id']) {
            $slug = generateToolPage($t);
            if ($slug) { 
                $t['page_slug'] = $slug; 
                saveTools($tools);
                generateCategoryIndex($t['category_id']);
                $_SESSION['flash'] = 'تم إنشاء الصفحة بنجاح'; 
                $_SESSION['flash_type'] = 'success'; 
            }
            else { $_SESSION['flash'] = 'فشل إنشاء الصفحة'; $_SESSION['flash_type'] = 'error'; }
            break;
        }
    }
    header('Location: tools.php'); exit;
}

function slugifyAr($text, $fallback = 'tool') {
    $text = trim($text);
    if (empty($text)) return $fallback;
    $map = [
        'ا'=>'a','أ'=>'a','إ'=>'e','آ'=>'a','ب'=>'b','ت'=>'t','ث'=>'th',
        'ج'=>'j','ح'=>'h','خ'=>'kh','د'=>'d','ذ'=>'dh','ر'=>'r','ز'=>'z',
        'س'=>'s','ش'=>'sh','ص'=>'s','ض'=>'d','ط'=>'t','ظ'=>'z',
        'ع'=>'a','غ'=>'gh','ف'=>'f','ق'=>'q','ك'=>'k','ل'=>'l','م'=>'m',
        'ن'=>'n','ه'=>'h','و'=>'w','ي'=>'y','ة'=>'h','ى'=>'a','ئ'=>'e',
        'ؤ'=>'o',' '=>'-',' '=>'-',
    ];
    $text = mb_strtolower($text, 'UTF-8');
    $result = '';
    $len = mb_strlen($text, 'UTF-8');
    for ($i = 0; $i < $len; $i++) {
        $ch = mb_substr($text, $i, 1, 'UTF-8');
        $result .= $map[$ch] ?? $ch;
    }
    $result = preg_replace('/[^\w\s-]/', '', $result);
    $result = preg_replace('/[\s_]+/', '-', $result);
    $result = preg_replace('/-+/', '-', $result);
    $result = trim($result, '-');
    return $result ?: $fallback;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tool'])) {
    $tools = getTools();
    $faq = [];
    foreach ($_POST['faq_question_ar'] ?? [] as $idx => $qAr) {
        if (trim($qAr)) {
            $faq[] = [
                'question_ar' => $qAr,
                'answer_ar' => $_POST['faq_answer_ar'][$idx] ?? '',
                'question_en' => $_POST['faq_question_en'][$idx] ?? '',
                'answer_en' => $_POST['faq_answer_en'][$idx] ?? '',
                'question_fr' => $_POST['faq_question_fr'][$idx] ?? '',
                'answer_fr' => $_POST['faq_answer_fr'][$idx] ?? '',
            ];
        }
    }

    $toolSlug = trim($_POST['tool_slug']);
    if (empty($toolSlug)) {
        $toolSlug = slugifyAr($_POST['title_ar'] ?? '', 'tool-' . uniqid());
    }

    $toolData = [
        'id' => $_POST['tool_id'] ?: uniqid('tool_'),
        'category_id' => $_POST['category_id'],
        'title_ar' => trim($_POST['title_ar']),
        'title_en' => trim($_POST['title_en']),
        'title_fr' => trim($_POST['title_fr']),
        'meta_title_ar' => trim($_POST['meta_title_ar']),
        'meta_title_en' => trim($_POST['meta_title_en']),
        'meta_title_fr' => trim($_POST['meta_title_fr']),
        'meta_desc_ar' => trim($_POST['meta_desc_ar']),
        'meta_desc_en' => trim($_POST['meta_desc_en']),
        'meta_desc_fr' => trim($_POST['meta_desc_fr']),
        'short_desc_ar' => trim($_POST['short_desc_ar']),
        'short_desc_en' => trim($_POST['short_desc_en']),
        'short_desc_fr' => trim($_POST['short_desc_fr']),
        'tool_slug' => $toolSlug,
        'sub_slug' => trim($_POST['sub_slug'] ?? ''),
        'created_at' => $_POST['created_at'] ?? date('Y-m-d'),
        'updated_at' => $_POST['updated_at'] ?? date('Y-m-d'),
        'html_code' => $_POST['html_code'],
        'css_code' => $_POST['css_code'],
        'js_code' => $_POST['js_code'],
        'long_desc_ar' => $_POST['long_desc_ar'],
        'long_desc_en' => $_POST['long_desc_en'],
        'long_desc_fr' => $_POST['long_desc_fr'],
        'faq' => $faq,
    ];

    $isUpdate = false;
    $oldCategoryId = '';
    foreach ($tools as &$t) {
        if ($t['id'] === $toolData['id']) {
            $oldCategoryId = $t['category_id'] ?? '';
            if (empty($toolData['created_at'])) $toolData['created_at'] = $t['created_at'];
            $t = $toolData; $isUpdate = true; break;
        }
    }
    if (!$isUpdate) $tools[] = $toolData;
    saveTools($tools);

    $slug = generateToolPage($toolData);
    if ($slug) {
        $toolData['page_slug'] = $slug;
        foreach ($tools as &$t) {
            if ($t['id'] === $toolData['id']) { $t['page_slug'] = $slug; break; }
        }
        saveTools($tools);
    }

    // Regenerate Category Portals
    generateCategoryIndex($toolData['category_id']);
    if ($oldCategoryId && $oldCategoryId !== $toolData['category_id']) {
        generateCategoryIndex($oldCategoryId);
    }

    $_SESSION['flash'] = $isUpdate ? 'تم تحديث الأداة وإعادة إنشاء الصفحة' : 'تم إضافة الأداة وإنشاء الصفحة بنجاح';
    $_SESSION['flash_type'] = 'success';
    header('Location: tools.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
<?php

$catIndex = [];
foreach ($categories as $c) {
    $catIndex[$c['id']] = $c;
}
$currentCat = !empty($tool['category_id']) && isset($catIndex[$tool['category_id']]) ? $catIndex[$tool['category_id']] : null;
$currentCatSlug = $currentCat ? getCategoryPhysicalDir($currentCat['slug']) : '{category}';
$pageUrl = '/' . $currentCatSlug . '/' . (!empty($tool['tool_slug']) ? $tool['tool_slug'] : 'tool-name') . '.html';

$catColors = ['text-tools'=>'#6366F1','code-tools'=>'#10B981','image-tools'=>'#F59E0B','calculator-tools'=>'#3B82F6','pdf-tools'=>'#EC4899','zip-tools'=>'#10B981','seo-tools'=>'#14B8A6','misc-tools'=>'#F59E0B','share-tools'=>'#8B5CF6'];
$catIcons = ['text-tools'=>'📝','code-tools'=>'💻','image-tools'=>'🖼️','calculator-tools'=>'🧮','pdf-tools'=>'📄','zip-tools'=>'📦','seo-tools'=>'🔍','misc-tools'=>'🔧','share-tools'=>'🔗'];
?>
<style>
:root {
    --primary: #6366F1; --primary-light: #EEF2FF; --primary-dark: #4F46E5;
    --success: #10B981; --success-light: #D1FAE5;
    --danger: #EF4444; --danger-light: #FEE2E2;
    --warning: #F59E0B; --warning-light: #FEF3C7;
    --dark: #0F172A; --gray: #64748B; --gray-light: #94A3B8; --gray-lighter: #E2E8F0;
    --border: #E2E8F0; --bg: #F8FAFC; --bg-alt: #F1F5F9;
    --radius: 12px; --radius-sm: 8px; --radius-lg: 16px;
    --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 6px rgba(0,0,0,0.05), 0 2px 4px rgba(0,0,0,0.04);
    --shadow-lg: 0 10px 15px rgba(0,0,0,0.08), 0 4px 6px rgba(0,0,0,0.04);
    --transition: 0.2s ease;
}
* { box-sizing: border-box; }

/* Page Header */
.page-header-modern {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 28px; flex-wrap: wrap; gap: 12px;
}
.page-header-modern h1 {
    font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px;
}
.page-header-modern .header-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.breadcrumb { font-size: 0.78rem; color: var(--gray-light); margin-bottom: 6px; display: flex; gap: 6px; align-items: center; }
.breadcrumb a { color: var(--gray-light); text-decoration: none; }
.breadcrumb a:hover { color: var(--primary); }

/* Card */
.card-modern {
    background: #fff; border: 1px solid var(--border); border-radius: var(--radius-lg);
    margin-bottom: 24px; box-shadow: var(--shadow); overflow: hidden;
    transition: box-shadow var(--transition);
}
.card-modern:hover { box-shadow: var(--shadow-md); }
.card-modern-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px; background: var(--bg); cursor: pointer;
    border-bottom: 1px solid var(--border); user-select: none;
}
.card-modern-header h3 {
    font-size: 0.9rem; font-weight: 700; display: flex; align-items: center; gap: 8px;
}
.card-modern-header .badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 10px; border-radius: 9999px; font-size: 0.65rem;
    font-weight: 700; background: var(--primary-light); color: var(--primary);
}
.card-modern-header .toggle-icon {
    width: 22px; height: 22px; display: flex; align-items: center; justify-content: center;
    transition: transform 0.3s; color: var(--gray-light);
    font-size: 0.7rem;
}
.card-modern.closed .card-modern-header .toggle-icon { transform: rotate(-180deg); }
.card-modern.closed .card-modern-body { display: none; }
.card-modern-body { padding: 24px; }

/* Form layout */
.form-row { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px; }
@media(min-width:768px) { .form-row { grid-template-columns: 1fr 1fr; } }
.form-row-3 { display: grid; grid-template-columns: 1fr; gap: 16px; margin-bottom: 16px; }
@media(min-width:768px) { .form-row-3 { grid-template-columns: 1fr 1fr 1fr; } }
.form-group { margin-bottom: 0; }
.form-group label {
    display: flex; align-items: center; gap: 6px;
    font-weight: 600; font-size: 0.8rem; margin-bottom: 6px; color: #374151;
}
.form-group label .required { color: var(--danger); }
.form-control-mod {
    width: 100%; padding: 10px 14px; border: 1.5px solid var(--border);
    border-radius: var(--radius-sm); font-size: 0.88rem; font-family: inherit;
    transition: all var(--transition); background: #fff; color: #1e293b;
}
.form-control-mod:focus {
    outline: none; border-color: var(--primary); 
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.form-control-mod::placeholder { color: #cbd5e1; }
textarea.form-control-mod { min-height: 80px; resize: vertical; }
select.form-control-mod { appearance: auto; }
.char-count {
    text-align: left; font-size: 0.68rem; color: var(--gray-light); margin-top: 4px;
    direction: ltr;
}

/* Slug input */
.slug-input-group {
    display: flex; align-items: center; gap: 6px;
    border: 1.5px solid var(--border); border-radius: var(--radius-sm);
    padding: 0 0 0 14px; transition: border-color var(--transition);
    background: #fff;
}
.slug-input-group:focus-within { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.slug-input-group .slug-prefix {
    font-size: 0.78rem; color: var(--gray-light); white-space: nowrap;
    font-family: 'Consolas', monospace; direction: ltr;
}
.slug-input-group input {
    flex: 1; border: none; padding: 10px 14px; font-size: 0.88rem;
    font-family: 'Consolas', monospace; background: none; direction: ltr;
}
.slug-input-group input:focus { outline: none; box-shadow: none; }
.slug-preview {
    font-size: 0.72rem; color: var(--gray-light); margin-top: 4px;
    direction: ltr; font-family: 'Consolas', monospace;
}
.slug-preview strong { color: var(--primary); }

/* Language Tabs */
.lang-tabs-wrap {
    display: flex; gap: 0; margin-bottom: 20px;
    background: var(--bg); border-radius: 10px; overflow: hidden; padding: 3px;
    border: 1px solid var(--border);
}
.lang-tab-mod {
    padding: 8px 20px; border: none; background: none; font-family: inherit;
    font-size: 0.82rem; font-weight: 600; color: var(--gray-light);
    cursor: pointer; border-radius: 8px; transition: all var(--transition);
}
.lang-tab-mod.active { background: #fff; color: var(--primary); box-shadow: var(--shadow); }
.lang-content-mod { display: none; }
.lang-content-mod.active { display: block; }

/* Code editor */
.code-editor-wrap { margin-bottom: 20px; }
.code-editor-wrap:last-child { margin-bottom: 0; }
.code-editor-label {
    display: flex; align-items: center; gap: 8px;
    font-weight: 700; font-size: 0.82rem; margin-bottom: 8px; color: #374151;
}
.code-editor-label .mono-badge {
    font-size: 0.65rem; padding: 2px 8px; border-radius: 4px;
    background: var(--bg-alt); color: var(--gray-light); font-weight: 600;
    font-family: 'Consolas', monospace; letter-spacing: 0.5px;
}
.editor-toolbar-mod {
    display: flex; gap: 3px; margin-bottom: 6px; flex-wrap: wrap;
}
.editor-toolbar-mod button {
    padding: 5px 10px; background: var(--bg); border: 1px solid var(--border);
    border-radius: 6px; font-size: 0.7rem; cursor: pointer; font-family: inherit;
    transition: all 0.15s; color: #475569; font-weight: 500;
}
.editor-toolbar-mod button:hover { background: #E2E8F0; border-color: var(--gray-light); color: var(--dark); }
.code-textarea-mod {
    position: relative;
}
.code-textarea-mod .code-lang {
    position: absolute; top: 8px; left: 12px; z-index: 2;
    font-size: 0.65rem; color: var(--gray-light); background: var(--bg);
    padding: 2px 8px; border-radius: 4px; font-weight: 700;
    font-family: 'Consolas', monospace; letter-spacing: 0.5px; direction: ltr;
    border: 1px solid var(--border);
}
.code-textarea-mod textarea {
    padding-top: 34px; font-family: 'Consolas', 'Monaco', monospace;
    direction: ltr; text-align: left; font-size: 0.82rem; line-height: 1.6;
    min-height: 200px; tab-size: 2;
}
.code-textarea-mod textarea.code-sm { min-height: 120px; }

/* FAQ Item */
.faq-card {
    background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 18px; margin-bottom: 12px; position: relative;
}
.faq-card .faq-num {
    position: absolute; top: -10px; right: 16px;
    width: 26px; height: 26px; border-radius: 50%;
    background: var(--primary); color: #fff; display: flex;
    align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700; box-shadow: var(--shadow);
}
.faq-card .faq-delete {
    position: absolute; top: 12px; left: 12px;
    width: 28px; height: 28px; border-radius: 50%; border: none;
    background: var(--danger-light); color: var(--danger); cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; transition: all var(--transition);
}
.faq-card .faq-delete:hover { background: var(--danger); color: #fff; }
.faq-card .faq-lang-tabs {
    display: flex; gap: 0; margin-bottom: 12px;
    background: #fff; border-radius: 8px; overflow: hidden; padding: 2px;
    border: 1px solid var(--border); max-width: 260px;
}

/* SEO Preview */
.seo-preview-box {
    background: #fff; border: 1px solid var(--border);
    border-radius: var(--radius); padding: 14px 18px;
    margin-top: 8px; direction: ltr;
}
.seo-preview-box .seo-url { font-size: 0.75rem; color: #006621; margin-bottom: 2px; }
.seo-preview-box .seo-title {
    font-size: 0.85rem; color: #1a0dab; font-weight: 600;
    margin-bottom: 2px; cursor: pointer; text-decoration: underline;
}
.seo-preview-box .seo-desc { font-size: 0.75rem; color: #545454; line-height: 1.4; }

/* Action bar */
.action-bar {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 20px 24px; background: var(--bg); border-top: 1px solid var(--border);
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
.action-bar .btn-mod {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 32px; border-radius: var(--radius-sm); font-size: 0.9rem;
    font-weight: 700; font-family: inherit; border: none; cursor: pointer;
    transition: all var(--transition); text-decoration: none;
}
.btn-mod-primary { background: var(--primary); color: #fff; }
.btn-mod-primary:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: var(--shadow-md); }
.btn-mod-secondary { background: var(--gray-lighter); color: #475569; }
.btn-mod-secondary:hover { background: #CBD5E1; }
.btn-mod-success { background: var(--success); color: #fff; }
.btn-mod-success:hover { background: #059669; transform: translateY(-1px); box-shadow: var(--shadow-md); }
.btn-mod-danger { background: var(--danger); color: #fff; }
.btn-mod-danger:hover { background: #DC2626; }
.btn-mod-sm { padding: 8px 16px; font-size: 0.78rem; }
.action-bar .hint-text { font-size: 0.75rem; color: var(--gray-light); }

/* Status indicators */
.status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-left: 6px; }
.status-dot.green { background: var(--success); }
.status-dot.yellow { background: var(--warning); }
.status-dot.red { background: var(--danger); }

/* Floating messages */
.toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    padding: 14px 24px; border-radius: var(--radius); font-weight: 600;
    font-size: 0.85rem; box-shadow: var(--shadow-lg);
    transform: translateY(20px); opacity: 0;
    transition: all 0.3s ease; max-width: 380px;
}
.toast.show { transform: translateY(0); opacity: 1; }
.toast-success { background: var(--success-light); color: #065F46; border: 1px solid #A7F3D0; }
.toast-error { background: var(--danger-light); color: #991B1B; border: 1px solid #FECACA; }

/* Responsive */
@media(max-width:768px) {
    .card-modern-body { padding: 16px; }
    .card-modern-header { padding: 12px 16px; }
    .action-bar { padding: 16px; }
    .action-bar .btn-mod { width: 100%; justify-content: center; }
    .lang-tabs-wrap { flex-wrap: wrap; }
    .lang-tab-mod { flex: 1; text-align: center; padding: 8px 12px; }
    .page-header-modern h1 { font-size: 1.15rem; }
}
.ai-btn {
    background: none; border: none; cursor: pointer; font-size: 1rem; padding: 2px 4px;
    border-radius: 6px; transition: all 0.2s; line-height: 1;
}
.ai-btn:hover { background: #f0f0f0; transform: scale(1.15); }
.ai-btn:disabled { cursor: wait; transform: none; }
.ai-btn-sm { font-size: 0.85rem; padding: 1px 4px; }
.ai-btn-mini { font-size: 0.75rem; cursor: pointer; padding: 0 2px; display: inline-block; }
.ai-btn-mini:hover { transform: scale(1.2); }/* SEO Analyzer */
.seo-analyzer { }
.seo-donut-wrap { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
.seo-donut { position: relative; width: 100px; height: 100px; flex-shrink: 0; }
.seo-donut-svg { width: 100px; height: 100px; }
.seo-donut-center {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    text-align: center; direction: ltr;
}
.seo-score { display: block; font-size: 1.4rem; font-weight: 800; color: #1e293b; line-height: 1; }
.seo-score-label { font-size: 0.6rem; color: var(--gray-light); font-weight: 600; }
.seo-verdict { display: flex; align-items: center; gap: 10px; flex: 1; min-width: 180px; }
.seo-verdict-icon { font-size: 1.6rem; }
.seo-verdict-text { font-size: 0.82rem; color: var(--gray); line-height: 1.5; }

.seo-grid-section { margin-bottom: 14px; }
.seo-grid-title {
    font-size: 0.72rem; font-weight: 800; color: var(--gray-light); text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;
}

.seo-details-grid {
    display: grid; grid-template-columns: 1fr; gap: 6px; margin-bottom: 12px;
}
@media(min-width:640px) { .seo-details-grid { grid-template-columns: 1fr 1fr; } }

.seo-detail-card {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius-sm);
    transition: all 0.2s; background: #fff; min-height: 44px;
}
.seo-detail-card:hover { border-color: #cbd5e1; }
.seo-detail-icon { font-size: 0.85rem; flex-shrink: 0; width: 20px; text-align: center; }
.seo-detail-info { flex: 1; min-width: 0; }
.seo-detail-label { font-size: 0.63rem; font-weight: 700; color: var(--gray-light); text-transform: uppercase; letter-spacing: 0.2px; }
.seo-detail-value { font-size: 0.75rem; color: #1e293b; font-weight: 600; direction: rtl; }
.seo-detail-status { font-size: 0.85rem; flex-shrink: 0; }
.seo-detail-status.pass { color: var(--success); }
.seo-detail-status.fail { color: var(--danger); }
.seo-detail-status.warn { color: var(--warning); }

.seo-eeat-grid { display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 12px; }
@media(min-width:768px) { .seo-eeat-grid { grid-template-columns: repeat(2,1fr); } }
@media(min-width:1024px) { .seo-eeat-grid { grid-template-columns: repeat(4,1fr); } }
.seo-eeat-item {
    padding: 14px; border: 1px solid var(--border); border-radius: var(--radius);
    background: #fff; transition: all 0.2s; display: flex; flex-direction: column; justify-content: space-between;
}
.seo-eeat-item:hover { border-color: #cbd5e1; box-shadow: var(--shadow-md); }
.seo-eeat-label { font-size: 0.72rem; font-weight: 700; color: #334155; margin-bottom: 6px; }
.seo-eeat-score { font-size: 1.5rem; font-weight: 800; line-height: 1; margin-bottom: 4px; }
.seo-eeat-score.excellent { color: var(--success); }
.seo-eeat-score.good { color: #3B82F6; }
.seo-eeat-score.fair { color: var(--warning); }
.seo-eeat-score.poor { color: var(--danger); }

/* أشرطة التقدم لمعيار E-E-A-T */
.eeat-progress-bg { background: #f1f5f9; border-radius: 6px; height: 6px; overflow: hidden; margin: 6px 0; border: 1px solid #e2e8f0; }
.eeat-progress-fill { height: 100%; transition: width 0.4s ease; border-radius: 6px; }
#eeatExpProgress { background-color: var(--success); }
#eeatExpRiseProgress { background-color: #3B82F6; }
#eeatAuthProgress { background-color: #8B5CF6; }
#eeatTrustProgress { background-color: #EC4899; }

.eeat-checklist { margin-top: 8px; font-size: 0.68rem; color: var(--gray); text-align: right; line-height: 1.4; display: flex; flex-direction: column; gap: 3px; border-top: 1px dashed #e2e8f0; padding-top: 8px; }
.eeat-check-item { display: flex; align-items: center; gap: 4px; }
.eeat-check-item.passed { color: #059669; }
.eeat-check-item.failed { color: #94a3b8; }

/* متتبع الكلمات الدلالية و LSI */
.keyword-tracker-container {
    background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 16px;
    box-shadow: var(--shadow);
}
.keyword-tracker-group { }
.keyword-tracker-title { font-size: 0.75rem; font-weight: 700; color: #475569; margin-bottom: 8px; }
.keyword-tracker-box { display: flex; flex-wrap: wrap; gap: 6px; }
.keyword-badge {
    font-size: 0.72rem; padding: 4px 10px; border-radius: 20px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s;
    user-select: none;
}
.keyword-badge.present { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
.keyword-badge.missing { background: #FFF7ED; color: #C2410C; border: 1px solid #FFEDD5; cursor: pointer; }
.keyword-badge.missing:hover { background: #FFEDD5; color: #9A3412; transform: translateY(-1px); }
.keyword-badge.lsi-missing { background: #F3F4F6; color: #4B5563; border: 1px solid #E5E7EB; cursor: pointer; }
.keyword-badge.lsi-missing:hover { background: #E5E7EB; color: #1F2937; transform: translateY(-1px); }

.seo-notes {
    background: #F8FAFC; border: 1px solid var(--border); border-radius: var(--radius);
    padding: 14px; direction: rtl;
}
.seo-notes-header { font-size: 0.78rem; font-weight: 700; margin-bottom: 8px; color: #1e293b; }
.seo-notes-list { list-style: none; padding: 0; margin: 0; }
.seo-note {
    font-size: 0.72rem; padding: 5px 8px; border-radius: 5px; margin-bottom: 3px;
    display: flex; align-items: flex-start; gap: 5px; line-height: 1.4;
}
.seo-note:last-child { margin-bottom: 0; }
.seo-note-pass { background: #D1FAE5; color: #065F46; }
.seo-note-fail { background: #FEE2E2; color: #991B1B; }
.seo-note-warn { background: #FEF3C7; color: #92400E; }
.seo-note-info { background: #EFF6FF; color: #1E40AF; }

/* ===== Local WYSIWYG Editor ===== */
.editor-wrap { border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; background: #fff; }
.editor-toolbar { display: flex; flex-wrap: wrap; gap: 2px; padding: 6px 8px; background: var(--bg-alt); border-bottom: 1px solid var(--border); user-select: none; }
.editor-toolbar button { display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; border: none; background: transparent; border-radius: 4px; cursor: pointer; font-size: 0.82rem; color: var(--dark); transition: all 0.15s; font-family: inherit; }
.editor-toolbar button:hover { background: var(--gray-lighter); }
.editor-toolbar button.active { background: var(--primary-light); color: var(--primary); }
.editor-toolbar .sep { width: 1px; height: 24px; background: var(--border); margin: 0 4px; align-self: center; }
.editor-content { min-height: 250px; max-height: 500px; overflow-y: auto; padding: 12px 16px; outline: none; line-height: 1.8; font-size: 0.92rem; color: var(--dark); background: #fff; }
.editor-content:focus { box-shadow: inset 0 0 0 2px var(--primary); }
.editor-content h2 { font-size: 1.3rem; font-weight: 700; margin: 16px 0 8px; }
.editor-content h3 { font-size: 1.1rem; font-weight: 600; margin: 12px 0 6px; }
.editor-content p { margin: 0 0 8px; }
.editor-content ul, .editor-content ol { margin: 0 0 8px; padding-right: 24px; }
.editor-content li { margin-bottom: 4px; }
.editor-content a { color: var(--primary); text-decoration: underline; }
.editor-content blockquote { border-right: 3px solid var(--primary); margin: 12px 0; padding: 8px 16px; background: var(--primary-light); border-radius: 4px; }
</style>

<div class="page-header-modern">
    <div>
        <div class="breadcrumb">
            <a href="dashboard.php">الرئيسية</a>
            <span>›</span>
            <a href="tools.php">الأدوات</a>
            <span>›</span>
            <span><?= $editMode ? 'تعديل' : 'إضافة' ?> أداة</span>
        </div>
        <h1>
            <?= $editMode ? '✏️ تعديل الأداة' : '➕ إضافة أداة جديدة' ?>
            <?php if ($editMode && !empty($tool['page_slug'])): ?>
                <span class="status-dot green"></span><span style="font-size:0.75rem;font-weight:500;color:var(--success);">منشور</span>
            <?php elseif ($editMode): ?>
                <span class="status-dot yellow"></span><span style="font-size:0.75rem;font-weight:500;color:var(--warning);">مسودة</span>
            <?php endif; ?>
        </h1>
    </div>
    <div class="header-actions">
        <?php if ($editMode && !empty($tool['page_slug'])): $cat = getCategoryById($tool['category_id'] ?? ''); if ($cat): 
            $subSlug = !empty($tool['sub_slug']) ? trim($tool['sub_slug'], '/') . '/' : '';
        ?>
            <a href="../<?= htmlspecialchars(getCategoryPhysicalDir($cat['slug'])) ?>/<?= $subSlug ?><?= htmlspecialchars($tool['page_slug']) ?>.html" target="_blank" class="btn-mod btn-mod-success btn-mod-sm">👁️ معاينة</a>
        <?php endif; endif; ?>
        <a href="tools.php" class="btn-mod btn-mod-secondary btn-mod-sm">← العودة للأدوات</a>
    </div>
</div>

<form method="post" id="toolForm">
    <input type="hidden" name="tool_id" value="<?= htmlspecialchars($tool['id']) ?>">

    <!-- إعدادات الذكاء الاصطناعي -->
    <div class="card-modern" id="section-ai-settings" style="margin-bottom: 24px; border: 1px dashed var(--primary-light);">
        <div class="card-modern-header" onclick="toggleSection(this)">
            <h3>⚙️ إعدادات توليد المحتوى بالذكاء الاصطناعي (AI Settings)</h3>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="card-modern-body">
            <p style="font-size: 0.85rem; color: var(--gray); margin-top: 0; margin-bottom: 15px;">
                قم باختيار المزود ونموذج الذكاء الاصطناعي المجاني لعملية التوليد. يتم حفظ اختياراتك تلقائياً في المتصفح.
            </p>
            <div class="form-row">
                <div class="form-group">
                    <label for="aiProviderSelect">المزود / الشركة المانحة للمفتاح</label>
                    <select class="form-control-mod" id="aiProviderSelect" name="ai_provider" style="padding: 10px 12px; font-weight: 500;" onchange="onAiProviderChange()">
                        <option value="openrouter">OpenRouter (أوبن راوتر)</option>
                        <option value="google">Google AI Studio (قوقل AI ستوديو)</option>
                        <option value="opencode">OpenCode Zen (خطة زين)</option>
                        <option value="groq">Groq (منصة جروك)</option>
                        <option value="cerebras">Cerebras (منصة سيريبراس السريعة)</option>
                        <option value="siliconflow">SiliconFlow (سيليكون فلو - GLM)</option>
                        <option value="routeway">Routeway (منصة راوت واي)</option>
                        <option value="featherless">Featherless (مخدم GLM)</option>
                        <option value="github">GitHub Models (نماذج GPT)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="aiModelSelect">النموذج (الموديل المجاني)</label>
                    <select class="form-control-mod" id="aiModelSelect" name="ai_model" style="padding: 10px 12px; font-weight: 500;">
                        <!-- سيتم تعبئتها ديناميكياً بواسطة جافا سكريبت -->
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- القسم 1: المعلومات الأساسية -->
    <div class="card-modern" id="section-basic">
        <div class="card-modern-header" onclick="toggleSection(this)">
            <h3>📋 المعلومات الأساسية</h3>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="card-modern-body">
            <div class="lang-tabs-wrap">
                <button class="lang-tab-mod active" data-lang="ar" type="button">🇸🇦 العربية</button>
                <button class="lang-tab-mod" data-lang="en" type="button">🇬🇧 English</button>
                <button class="lang-tab-mod" data-lang="fr" type="button">🇫🇷 Français</button>
            </div>

            <div class="lang-content-mod active" data-lang="ar">
                <div class="form-row">
                    <div class="form-group">
                        <label>عنوان الأداة <span class="required">*</span></label>
                        <input class="form-control-mod" name="title_ar" id="titleAr" value="<?= htmlspecialchars($tool['title_ar']) ?>" required oninput="updateMetaTitle(this);autoSlug()">
                        <div class="char-count"><span id="titleArCount">0</span> / 60 حرف</div>
                    </div>
                    <div class="form-group">
                        <label>عنوان الصفحة (Title Tag) <span class="required">*</span></label>
                        <input class="form-control-mod" name="meta_title_ar" id="metaTitleAr" value="<?= htmlspecialchars($tool['meta_title_ar']) ?>" required oninput="updateSeoPreview()">
                        <div class="char-count"><span id="metaTitleCount">0</span> / 70 حرف</div>
                    </div>
                </div>
                <div class="form-group">
                    <label>الوصف القصير <span class="required">*</span>
                        <button type="button" class="ai-btn" onclick="aiGenerate('short_desc','ar')" title="توليد بالذكاء الاصطناعي">🤖</button>
                    </label>
                    <textarea class="form-control-mod" name="short_desc_ar" id="shortDescAr" required rows="2" oninput="updateSeoPreview()"><?= htmlspecialchars($tool['short_desc_ar']) ?></textarea>
                    <div class="char-count"><span id="shortDescCount">0</span> / 160 حرف</div>
                </div>
                <div class="form-group">
                    <label>الوصف لمحركات البحث (Meta Description)
                        <button type="button" class="ai-btn" onclick="aiGenerate('meta_desc','ar')" title="توليد بالذكاء الاصطناعي">🤖</button>
                    </label>
                    <textarea class="form-control-mod" name="meta_desc_ar" id="metaDescAr" rows="2" oninput="updateSeoPreview()"><?= htmlspecialchars($tool['meta_desc_ar']) ?></textarea>
                    <div class="char-count"><span id="metaDescCount">0</span> / 320 حرف</div>
                </div>
                <div id="seoPreviewBox" class="seo-preview-box">
                    <div class="seo-url" id="seoUrl">toolrar.com/<?= $currentCatSlug ?>/tool-name.html</div>
                    <div class="seo-title" id="seoTitle"><?= htmlspecialchars($tool['meta_title_ar'] ?: $tool['title_ar'] ?: 'عنوان الأداة') ?> - ToolRar</div>
                    <div class="seo-desc" id="seoDesc"><?= htmlspecialchars($tool['meta_desc_ar'] ?: $tool['short_desc_ar'] ?: 'وصف الأداة') ?></div>
                </div>
            </div>

            <div class="lang-content-mod" data-lang="en">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tool Title (H1)</label>
                        <input class="form-control-mod" name="title_en" value="<?= htmlspecialchars($tool['title_en']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Page Title Tag</label>
                        <input class="form-control-mod" name="meta_title_en" value="<?= htmlspecialchars($tool['meta_title_en']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Short Description</label>
                    <textarea class="form-control-mod" name="short_desc_en" rows="2"><?= htmlspecialchars($tool['short_desc_en']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea class="form-control-mod" name="meta_desc_en" rows="2"><?= htmlspecialchars($tool['meta_desc_en']) ?></textarea>
                </div>
            </div>

            <div class="lang-content-mod" data-lang="fr">
                <div class="form-row">
                    <div class="form-group">
                        <label>Titre de l'outil (H1)</label>
                        <input class="form-control-mod" name="title_fr" value="<?= htmlspecialchars($tool['title_fr']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Balise Titre</label>
                        <input class="form-control-mod" name="meta_title_fr" value="<?= htmlspecialchars($tool['meta_title_fr']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description courte</label>
                    <textarea class="form-control-mod" name="short_desc_fr" rows="2"><?= htmlspecialchars($tool['short_desc_fr']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Meta Description</label>
                    <textarea class="form-control-mod" name="meta_desc_fr" rows="2"><?= htmlspecialchars($tool['meta_desc_fr']) ?></textarea>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label>التصنيف <span class="required">*</span></label>
                    <select class="form-control-mod" name="category_id" id="catSelect" required onchange="updateSlug()">
                        <option value="">— اختر التصنيف —</option>
                        <?php foreach ($categories as $c): 
                            $color = $catColors[$c['id']] ?? '#6366F1';
                            $icon = $catIcons[$c['id']] ?? '🔧';
                        ?>
                            <option value="<?= htmlspecialchars($c['id']) ?>" <?= $tool['category_id'] === $c['id'] ? 'selected' : '' ?>>
                                <?= $icon ?> <?= htmlspecialchars($c['name_ar']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>قسم فرعي (اختياري)</label>
                    <input class="form-control-mod" name="sub_slug" id="subSlug" value="<?= htmlspecialchars($tool['sub_slug'] ?? '') ?>" oninput="this.value=this.value.replace(/[^\w-]/g,'').toLowerCase();updateSlug()" placeholder="sub-category" dir="ltr">
                    <div class="char-count">لإنشاء رابط متعدد المستويات: <strong>category/sub/tool.html</strong></div>
                </div>
                <div class="form-group">
                    <label>رابط الأداة (Slug) <span class="required">*</span></label>
                    <div class="slug-input-group">
                        <span class="slug-prefix" id="slugPrefix"><?= $currentCatSlug ?>/</span>
                        <input name="tool_slug" id="toolSlug" value="<?= htmlspecialchars($tool['tool_slug']) ?>" required oninput="this.value=this.value.replace(/[^\w-]/g,'').toLowerCase();updateSlugPreview()" placeholder="tool-name">
                    </div>
                    <div class="slug-preview" id="slugPreview">المسار الكامل: <strong>/<?= $currentCatSlug ?>/<?= !empty($tool['tool_slug']) ? htmlspecialchars($tool['tool_slug']) : 'tool-name' ?>.html</strong></div>
                </div>
            </div>
            <div class="form-row" style="margin-top:4px;">
                <div class="form-group">
                    <label>📅 تاريخ الإنشاء</label>
                    <input class="form-control-mod" type="date" name="created_at" value="<?= !empty($tool['created_at']) ? date('Y-m-d', strtotime($tool['created_at'])) : date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>✏️ تاريخ آخر تحديث</label>
                    <input class="form-control-mod" type="date" name="updated_at" value="<?= !empty($tool['updated_at']) ? date('Y-m-d', strtotime($tool['updated_at'])) : date('Y-m-d') ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- القسم 2: كود الأداة -->
    <div class="card-modern" id="section-code">
        <div class="card-modern-header" onclick="toggleSection(this)">
            <h3>⚙️ كود الأداة</h3>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="card-modern-body">
            <div id="codeLangTabs" class="lang-tabs-wrap" style="margin-bottom:16px;">
                <button class="lang-tab-mod active" onclick="switchCodeLang('html')" type="button">🔤 HTML</button>
                <button class="lang-tab-mod" onclick="switchCodeLang('css')" type="button">🎨 CSS</button>
                <button class="lang-tab-mod" onclick="switchCodeLang('js')" type="button">🧠 JavaScript</button>
                <button class="lang-tab-mod" onclick="switchCodeLang('preview')" type="button">👁️ معاينة</button>
            </div>

            <div id="codeEditorHtml" class="code-lang-content">
                <div class="code-editor-wrap">
                    <div class="code-editor-label">
                        <span>🔤 HTML</span>
                        <span class="mono-badge">&lt;html&gt;</span>
                    </div>
                    <div class="editor-toolbar-mod">
                        <button type="button" onclick="insertCode('html_code','<div class=&quot;tool-container&quot;>','</div>')">📦 حاوية</button>
                        <button type="button" onclick="insertCode('html_code','<input type=&quot;text&quot; class=&quot;form-input&quot; placeholder=&quot;...&quot;>','')">✏️ حقل إدخال</button>
                        <button type="button" onclick="insertCode('html_code','<button class=&quot;btn&quot; onclick=&quot;&quot;>','</button>')">🔘 زر</button>
                        <button type="button" onclick="insertCode('html_code','<textarea class=&quot;form-input&quot; rows=&quot;4&quot;>','</textarea>')">📄 مساحة نص</button>
                        <button type="button" onclick="insertCode('html_code','<select class=&quot;form-input&quot;><option>اختيار</option></select>','')">📋 قائمة</button>
                        <button type="button" onclick="insertCode('html_code','<label class=&quot;form-label&quot;>نص:</label>','')">🏷️ تسمية</button>
                        <button type="button" onclick="wrapTag('html_code')">🔄 تغليف</button>
                        <button type="button" onclick="clearEditor('html_code')">🗑️ مسح</button>
                    </div>
                    <div class="code-textarea-mod">
                        <span class="code-lang">HTML</span>
                        <textarea class="form-control-mod code-editor" name="html_code" id="html_code" placeholder="أدخل كود HTML هنا..." spellcheck="false"><?= htmlspecialchars($tool['html_code']) ?></textarea>
                    </div>
                </div>
            </div>

            <div id="codeEditorCss" class="code-lang-content" style="display:none;">
                <div class="code-editor-wrap">
                    <div class="code-editor-label">
                        <span>🎨 CSS</span>
                        <span class="mono-badge">.style</span>
                    </div>
                    <div class="editor-toolbar-mod">
                        <button type="button" onclick="insertCode('css_code','.tool-container {','}')">📦 كلاس</button>
                        <button type="button" onclick="insertCode('css_code','#tool-id {','}')">🎯 آيدي</button>
                        <button type="button" onclick="insertCode('css_code','display:flex;align-items:center;gap:10px;','')">📐 Flex</button>
                        <button type="button" onclick="insertCode('css_code','display:grid;grid-template-columns:1fr 1fr;gap:12px;','')">📊 Grid</button>
                        <button type="button" onclick="insertCode('css_code','@media(max-width:768px){','}')">📱 Responsive</button>
                        <button type="button" onclick="clearEditor('css_code')">🗑️ مسح</button>
                    </div>
                    <div class="code-textarea-mod">
                        <span class="code-lang">CSS</span>
                        <textarea class="form-control-mod code-editor code-sm" name="css_code" id="css_code" placeholder="/* أنماط CSS */" spellcheck="false"><?= htmlspecialchars($tool['css_code']) ?></textarea>
                    </div>
                </div>
            </div>

            <div id="codeEditorJs" class="code-lang-content" style="display:none;">
                <div class="code-editor-wrap">
                    <div class="code-editor-label">
                        <span>🧠 JavaScript</span>
                        <span class="mono-badge">.js</span>
                    </div>
                    <div class="editor-toolbar-mod">
                        <button type="button" onclick="insertCode('js_code','function handleTool(){','}')">🔧 دالة</button>
                        <button type="button" onclick="insertCode('js_code',"document.getElementById('').addEventListener('click',function(){","})")">🖱️ حدث نقر</button>
                        <button type="button" onclick="insertCode('js_code','const input=document.getElementById(&quot;&quot;);const output=document.getElementById(&quot;&quot;);','')">📥 متغيرات</button>
                        <button type="button" onclick="insertCode('js_code','try{','}catch(e){console.error(e);}')">⚠️ Try/Catch</button>
                        <button type="button" onclick="insertCode('js_code','if(){','}')">🔀 شرط</button>
                        <button type="button" onclick="insertCode('js_code','for(let i=0;i<;i++){','}')">🔄 حلقة</button>
                        <button type="button" onclick="clearEditor('js_code')">🗑️ مسح</button>
                    </div>
                    <div class="code-textarea-mod">
                        <span class="code-lang">JavaScript</span>
                        <textarea class="form-control-mod code-editor code-sm" name="js_code" id="js_code" placeholder="// كود JavaScript" spellcheck="false"><?= htmlspecialchars($tool['js_code']) ?></textarea>
                    </div>
                </div>
            </div>

            <div id="codeEditorPreview" class="code-lang-content" style="display:none;">
                <div class="code-editor-wrap">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                        <div class="code-editor-label" style="margin-bottom:0;">
                            <span>👁️ معاينة مباشرة</span>
                        </div>
                        <button type="button" class="btn-mod btn-mod-secondary btn-mod-sm" onclick="refreshPreview()">🔄 تحديث</button>
                    </div>
                    <div id="livePreview" style="border:1px solid var(--border);border-radius:var(--radius-sm);min-height:300px;padding:24px;background:#fff;overflow:auto;direction:ltr;">
                        <div style="color:var(--gray-light);text-align:center;padding:40px;">اضغط "تحديث" لرؤية المعاينة</div>
                    </div>
                </div>
            </div>
            <div class="ai-code-gen">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <span style="font-weight:700;font-size:0.82rem;">🤖 توليد كود الأداة بالذكاء الاصطناعي</span>
                </div>
                <div style="display:flex;gap:8px;align-items:flex-start;">
                    <textarea id="aiCodePrompt" class="form-control-mod" rows="2" placeholder="اكتب وصفاً للأداة التي تريدها... مثال: أداة لحساب عمر الإنسان بالسنوات والشهور والأيام"></textarea>
                    <button type="button" class="btn-mod btn-mod-primary btn-mod-sm" onclick="aiGenerateCode()" style="white-space:nowrap;flex-shrink:0;padding:10px 16px;">🚀 توليد</button>
                </div>
                <div style="margin-top:4px;font-size:0.7rem;color:var(--gray-light);">سيتم إنشاء كود HTML + CSS + JavaScript وتعبئتهم تلقائياً في الحقول أعلاه</div>
            </div>
        </div>
    </div>

    <!-- القسم 3: النبذة التعريفية -->
    <div class="card-modern" id="section-desc">
        <div class="card-modern-header" onclick="toggleSection(this)">
            <h3>📝 النبذة التعريفية</h3>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="card-modern-body">
            <div class="lang-tabs-wrap">
                <button class="lang-tab-mod active" data-lang="ar" type="button">🇸🇦 العربية <span class="ai-btn-mini" onclick="event.stopPropagation();aiGenerate('long_desc','ar')">🤖</span></button>
                <button class="lang-tab-mod" data-lang="en" type="button">🇬🇧 English <span class="ai-btn-mini" onclick="event.stopPropagation();aiGenerate('long_desc','en')">🤖</span></button>
                <button class="lang-tab-mod" data-lang="fr" type="button">🇫🇷 Français <span class="ai-btn-mini" onclick="event.stopPropagation();aiGenerate('long_desc','fr')">🤖</span></button>
            </div>
            <div class="lang-content-mod active" data-lang="ar">
                <div class="form-group">
                    <textarea class="form-control-mod" name="long_desc_ar" id="editor_ar" rows="12"><?= htmlspecialchars($tool['long_desc_ar']) ?></textarea>
                </div>
            </div>
            <div class="lang-content-mod" data-lang="en">
                <div class="form-group">
                    <textarea class="form-control-mod" name="long_desc_en" id="editor_en" rows="12"><?= htmlspecialchars($tool['long_desc_en']) ?></textarea>
                </div>
            </div>
            <div class="lang-content-mod" data-lang="fr">
                <div class="form-group">
                    <textarea class="form-control-mod" name="long_desc_fr" id="editor_fr" rows="12"><?= htmlspecialchars($tool['long_desc_fr']) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- القسم 4: الأسئلة الشائعة -->
    <div class="card-modern" id="section-faq">
        <div class="card-modern-header" onclick="toggleSection(this)">
            <h3>❓ الأسئلة الشائعة <button type="button" class="ai-btn ai-btn-sm" onclick="event.stopPropagation();aiGenerate('faq','ar')" title="توليد الأسئلة بالذكاء الاصطناعي">🤖</button></h3>
            <span class="badge"><?= count($tool['faq']) ?> سؤال</span>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="card-modern-body">
            <div id="faqContainer">
                <?php foreach ($tool['faq'] as $i => $faqItem): ?>
                <div class="faq-card">
                    <div class="faq-num"><?= $i+1 ?></div>
                    <button type="button" class="faq-delete" onclick="this.closest('.faq-card').remove()" title="حذف">✕</button>
                    <div class="faq-lang-tabs">
                        <button class="lang-tab-mod active" data-lang="ar" type="button">🇸🇦 عربي</button>
                        <button class="lang-tab-mod" data-lang="en" type="button">🇬🇧 EN</button>
                        <button class="lang-tab-mod" data-lang="fr" type="button">🇫🇷 FR</button>
                    </div>
                    <div class="lang-content-mod active" data-lang="ar">
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.78rem;color:var(--gray);">السؤال</label>
                            <input class="form-control-mod" name="faq_question_ar[]" value="<?= htmlspecialchars($faqItem['question_ar']) ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.78rem;color:var(--gray);">الإجابة</label>
                            <textarea class="form-control-mod" name="faq_answer_ar[]" rows="2"><?= htmlspecialchars($faqItem['answer_ar']) ?></textarea>
                        </div>
                    </div>
                    <div class="lang-content-mod" data-lang="en">
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.78rem;color:var(--gray);">Question</label>
                            <input class="form-control-mod" name="faq_question_en[]" value="<?= htmlspecialchars($faqItem['question_en'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.78rem;color:var(--gray);">Answer</label>
                            <textarea class="form-control-mod" name="faq_answer_en[]" rows="2"><?= htmlspecialchars($faqItem['answer_en'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="lang-content-mod" data-lang="fr">
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.78rem;color:var(--gray);">Question</label>
                            <input class="form-control-mod" name="faq_question_fr[]" value="<?= htmlspecialchars($faqItem['question_fr'] ?? '') ?>">
                        </div>
                        <div class="form-group" style="margin-bottom:8px;">
                            <label style="font-size:0.78rem;color:var(--gray);">Réponse</label>
                            <textarea class="form-control-mod" name="faq_answer_fr[]" rows="2"><?= htmlspecialchars($faqItem['answer_fr'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="addFaqBtn" class="btn-mod btn-mod-primary btn-mod-sm">+ إضافة سؤال جديد</button>
            <p class="hint" style="margin-top:8px;font-size:0.75rem;color:var(--gray-light);">الأسئلة الشائعة اختيارية. تُحسّن ظهور الموقع في محركات البحث.</p>
        </div>
    </div>

    <!-- تحليل SEO لحظي -->
    <div class="card-modern" id="section-seo">
        <div class="card-modern-header" onclick="toggleSection(this)">
            <h3>🔍 تحليل SEO متقدم</h3>
            <span class="badge" id="seoScoreBadge">0%</span>
            <span class="toggle-icon">▼</span>
        </div>
        <div class="card-modern-body">
            <div class="seo-analyzer">
                <div class="seo-donut-wrap">
                    <div class="seo-donut">
                        <svg viewBox="0 0 120 120" class="seo-donut-svg">
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#E2E8F0" stroke-width="10"/>
                            <circle cx="60" cy="60" r="50" fill="none" stroke="#6366F1" stroke-width="10" stroke-linecap="round" id="seoDonutArc" stroke-dasharray="314" stroke-dashoffset="314" transform="rotate(-90 60 60)"/>
                        </svg>
                        <div class="seo-donut-center">
                            <span class="seo-score" id="seoScoreNum">0</span>
                            <span class="seo-score-label">%</span>
                        </div>
                    </div>
                    <div class="seo-verdict" id="seoVerdict">
                        <div class="seo-verdict-icon">📝</div>
                        <div class="seo-verdict-text">أدخل عنوان الأداة والوصف لبدء التحليل</div>
                    </div>
                </div>

                <div class="seo-grid-section">
                    <div class="seo-grid-title">📋 المحتوى والعلامات</div>
                    <div class="seo-details-grid">
                        <div class="seo-detail-card" data-check="title">
                            <div class="seo-detail-icon">📌</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">عنوان SEO</div>
                                <div class="seo-detail-value" id="seoDetailTitle">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusTitle">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="meta">
                            <div class="seo-detail-icon">📋</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">Meta Description</div>
                                <div class="seo-detail-value" id="seoDetailMeta">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusMeta">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="short">
                            <div class="seo-detail-icon">✏️</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">الوصف القصير</div>
                                <div class="seo-detail-value" id="seoDetailShort">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusShort">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="long">
                            <div class="seo-detail-icon">📄</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">المحتوى الطويل</div>
                                <div class="seo-detail-value" id="seoDetailLong">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusLong">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="keywords">
                            <div class="seo-detail-icon">🔑</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">كثافة الكلمة المفتاحية</div>
                                <div class="seo-detail-value" id="seoDetailKeywords">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusKeywords">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="lsikeywords">
                            <div class="seo-detail-icon">🌐</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">كلمات LSI مقترحة</div>
                                <div class="seo-detail-value" id="seoDetailLsi">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusLsi">⏳</div>
                        </div>
                    </div>
                </div>

                <div class="seo-grid-section">
                    <div class="seo-grid-title">🏆 معيار E-E-A-T (قياس حي ولحظي)</div>
                    <div class="seo-eeat-grid">
                        <div class="seo-eeat-item" id="eeatExperience">
                            <div class="seo-eeat-label">🧪 Experience (الخبرة العملية)</div>
                            <div class="seo-eeat-score" id="eeatExpScore">-</div>
                            <div class="eeat-progress-bg"><div class="eeat-progress-fill" id="eeatExpProgress" style="width:0%"></div></div>
                            <div class="eeat-checklist" id="eeatExpList"></div>
                        </div>
                        <div class="seo-eeat-item" id="eeatExpertise">
                            <div class="seo-eeat-label">🎓 Expertise (الخبرة المعرفية)</div>
                            <div class="seo-eeat-score" id="eeatExpRiseScore">-</div>
                            <div class="eeat-progress-bg"><div class="eeat-progress-fill" id="eeatExpRiseProgress" style="width:0%"></div></div>
                            <div class="eeat-checklist" id="eeatExpRiseList"></div>
                        </div>
                        <div class="seo-eeat-item" id="eeatAuthority">
                            <div class="seo-eeat-label">🏛️ Authority (المصداقية والسلطة)</div>
                            <div class="seo-eeat-score" id="eeatAuthScore">-</div>
                            <div class="eeat-progress-bg"><div class="eeat-progress-fill" id="eeatAuthProgress" style="width:0%"></div></div>
                            <div class="eeat-checklist" id="eeatAuthList"></div>
                        </div>
                        <div class="seo-eeat-item" id="eeatTrust">
                            <div class="seo-eeat-label">🛡️ Trustworthiness (الموثوقية والأمان)</div>
                            <div class="seo-eeat-score" id="eeatTrustScore">-</div>
                            <div class="eeat-progress-bg"><div class="eeat-progress-fill" id="eeatTrustProgress" style="width:0%"></div></div>
                            <div class="eeat-checklist" id="eeatTrustList"></div>
                        </div>
                    </div>
                </div>

                <div class="seo-grid-section">
                    <div class="seo-grid-title">🔍 متتبع الكلمات الدلالية و LSI (تحديث لحظي - اضغط للإضافة للمحرر)</div>
                    <div class="keyword-tracker-container">
                        <div class="keyword-tracker-group">
                            <div class="keyword-tracker-title">📌 الكلمات المفتاحية الناقصة من العنوان الأساسي:</div>
                            <div class="keyword-tracker-box" id="missingTitleKeywords">
                                <!-- سيتم توليده بالجافا سكريبت -->
                            </div>
                        </div>
                        <div class="keyword-tracker-group" style="margin-top: 12px;">
                            <div class="keyword-tracker-title">🌐 كلمات LSI المقترحة والتي تحتاج لإضافتها:</div>
                            <div class="keyword-tracker-box" id="missingLsiKeywords">
                                <!-- سيتم توليده بالجافا سكريبت -->
                            </div>
                        </div>
                    </div>
                </div>

                <div class="seo-grid-section">
                    <div class="seo-grid-title">📊 تحليل متقدم</div>
                    <div class="seo-details-grid">
                        <div class="seo-detail-card" data-check="intent">
                            <div class="seo-detail-icon">🎯</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">نية البحث (Search Intent)</div>
                                <div class="seo-detail-value" id="seoDetailIntent">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusIntent">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="semantic">
                            <div class="seo-detail-icon">🧠</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">العمق الدلالي (Semantic Depth)</div>
                                <div class="seo-detail-value" id="seoDetailSemantic">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusSemantic">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="cwv">
                            <div class="seo-detail-icon">⚡</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">Core Web Vitals</div>
                                <div class="seo-detail-value" id="seoDetailCwv">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusCwv">⏳</div>
                        </div>
                        <div class="seo-detail-card" data-check="progseo">
                            <div class="seo-detail-icon">🏗️</div>
                            <div class="seo-detail-info">
                                <div class="seo-detail-label">Programmatic SEO</div>
                                <div class="seo-detail-value" id="seoDetailProg">في انتظار الإدخال</div>
                            </div>
                            <div class="seo-detail-status" id="seoStatusProg">⏳</div>
                        </div>
                    </div>
                </div>

                <div class="seo-notes" id="seoNotes">
                    <div class="seo-notes-header">📋 ملاحظات وتحسينات</div>
                    <ul class="seo-notes-list" id="seoNotesList">
                        <li class="seo-note seo-note-info">انتظر إدخال البيانات لبدء التحليل...</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الحفظ -->
    <div class="card-modern" style="overflow:visible;">
        <div class="action-bar">
            <button class="btn-mod btn-mod-success" type="submit" name="save_tool">
                💾 <?= $editMode ? 'تحديث وإعادة إنشاء الصفحة' : 'حفظ الأداة وإنشاء الصفحة' ?>
            </button>
            <button type="button" class="btn-mod btn-mod-secondary" onclick="window.location='tools.php'">إلغاء</button>
            <span class="hint-text">سيتم إنشاء صفحة HTML احترافية بنفس تنسيق الموقع الرئيسي</span>
        </div>
    </div>
</form>

<div id="toast" class="toast"></div>

<script>
// ===== الأدوات الأساسية =====
function insertCode(textareaId, before, after) {
    var ta = document.getElementById(textareaId);
    if (!ta) return;
    var start = ta.selectionStart, end = ta.selectionEnd;
    var selected = ta.value.substring(start, end);
    ta.value = ta.value.substring(0, start) + before + selected + after + ta.value.substring(end);
    ta.focus();
    ta.selectionStart = ta.selectionEnd = start + before.length + selected.length + after.length;
}

function wrapTag(textareaId) {
    var ta = document.getElementById(textareaId);
    if (!ta) return;
    var start = ta.selectionStart, end = ta.selectionEnd;
    var selected = ta.value.substring(start, end);
    if (!selected) return;
    var tag = prompt('اسم الوسم (مثال: div, span, p):', 'div');
    if (!tag) return;
    ta.value = ta.value.substring(0, start) + '<' + tag + '>\n' + selected + '\n</' + tag + '>' + ta.value.substring(end);
}

function clearEditor(textareaId) {
    if (confirm('هل أنت متأكد من مسح المحتوى؟')) {
        document.getElementById(textareaId).value = '';
    }
}

function toggleSection(header) {
    header.closest('.card-modern').classList.toggle('closed');
}

// ===== إدارة التبويبات =====
document.querySelectorAll('.lang-tab-mod').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var container = this.closest('.card-modern-body, .faq-card') || document;
        var tabGroup = this.closest('.lang-tabs-wrap, .faq-lang-tabs');
        if (!tabGroup) return;
        var lang = this.getAttribute('data-lang');
        if (!lang) return;
        tabGroup.querySelectorAll('.lang-tab-mod').forEach(function(t) { t.classList.remove('active'); });
        this.classList.add('active');
        container.querySelectorAll('.lang-content-mod').forEach(function(c) {
            c.classList.remove('active');
            if (c.getAttribute('data-lang') === lang) c.classList.add('active');
        });
    });
});

// ===== علامات التبويب بين HTML/CSS/JS/Preview =====
function switchCodeLang(lang) {
    var tabs = document.getElementById('codeLangTabs');
    tabs.querySelectorAll('.lang-tab-mod').forEach(function(t) { t.classList.remove('active'); });
    tabs.querySelector('[onclick="switchCodeLang(\'' + lang + '\')"]').classList.add('active');
    ['html','css','js','preview'].forEach(function(l) {
        var el = document.getElementById('codeEditor' + l.charAt(0).toUpperCase() + l.slice(1));
        if (el) el.style.display = (l === lang) ? 'block' : 'none';
    });
    if (lang === 'preview') refreshPreview();
}

// ===== السلاگ =====
function slugify(text) {
    var map = {
        'ا':'a','أ':'a','إ':'e','آ':'a','ب':'b','ت':'t','ث':'th',
        'ج':'j','ح':'h','خ':'kh','د':'d','ذ':'dh','ر':'r','ز':'z',
        'س':'s','ش':'sh','ص':'s','ض':'d','ط':'t','ظ':'z',
        'ع':'a','غ':'gh','ف':'f','ق':'q','ك':'k','ل':'l','م':'m',
        'ن':'n','ه':'h','و':'w','ي':'y','ة':'h','ى':'a','ئ':'e','ؤ':'o'
    };
    var result = '';
    text = text.toLowerCase().trim();
    for (var i = 0; i < text.length; i++) {
        var ch = text[i];
        result += map[ch] || ch;
    }
    return result.replace(/[^\w\s-]/g, '').replace(/[\s_]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '') || 'tool';
}

function autoSlug() {
    var slugInput = document.getElementById('toolSlug');
    if (slugInput.value.trim()) return;
    var title = document.getElementById('titleAr').value.trim();
    if (title) slugInput.value = slugify(title);
    updateSlugPreview();
}

function updateSlugPreview() {
    var cat = document.getElementById('catSelect');
    var catId = cat.value || '{category}';
    var slugMapping = {
        'code-tools': 'Developer',
        'image-tools': 'Photo-Editing',
        'calculator-tools': 'Calculators',
        'pdf-tools': 'docs-tools',
        'seo-tools': 'seo',
        'misc-tools': 'General',
        'share-tools': 'Social-media'
    };
    var physicalDir = slugMapping[catId] || catId;
    var sub = document.getElementById('subSlug').value.trim();
    var slug = document.getElementById('toolSlug').value.trim() || 'tool-name';
    var prefix = physicalDir;
    if (sub) prefix += '/' + sub;
    document.getElementById('slugPrefix').textContent = prefix + '/';
    var fullPath = '/' + prefix + '/' + slug + '.html';
    document.getElementById('slugPreview').innerHTML = 'المسار الكامل: <strong>' + fullPath + '</strong>';
    updateSeoUrl(prefix, slug);
}

function updateSeoUrl(path, slug) {
    var el = document.getElementById('seoUrl');
    if (el) el.textContent = 'toolrar.com/' + path + '/' + slug + '.html';
}

function updateSlug() {
    updateSlugPreview();
}

// ===== تحديث عنوان الميتا تلقائياً =====
function updateMetaTitle(input) {
    var val = input.value.trim();
    var metaInput = document.getElementById('metaTitleAr');
    if (metaInput && !metaInput.value.trim() && val) {
        metaInput.value = val + ' - ToolRar';
        updateSeoPreview();
    }
}

// ===== معاينة SEO =====
function updateSeoPreview() {
    var title = document.getElementById('metaTitleAr');
    var desc = document.getElementById('metaDescAr');
    var short = document.getElementById('shortDescAr');
    var seoTitle = document.getElementById('seoTitle');
    var seoDesc = document.getElementById('seoDesc');
    if (seoTitle) seoTitle.textContent = (title && title.value.trim()) ? title.value.trim() + ' - ToolRar' : 'عنوان الأداة - ToolRar';
    if (seoDesc) seoDesc.textContent = (desc && desc.value.trim()) ? desc.value.trim() : (short ? short.value.trim() || 'وصف الأداة' : 'وصف الأداة');
}

// ===== عدّاد الأحرف =====
document.querySelectorAll('.form-control-mod').forEach(function(el) {
    el.addEventListener('input', function() {
        var parent = this.closest('.form-group');
        if (parent) {
            var countEl = parent.querySelector('.char-count span');
            if (countEl) countEl.textContent = this.value.length;
        }
    });
    var parent = el.closest('.form-group');
    if (parent) {
        var countEl = parent.querySelector('.char-count span');
        if (countEl) countEl.textContent = el.value.length;
    }
});

// ===== التحميل الأولي =====
setTimeout(function() {
    updateSlugPreview();
    updateSeoPreview();
}, 100);

// ===== التحقق من صحة النموذج =====
document.getElementById('toolForm').addEventListener('submit', function(e) {
    if (window.tinymce) {
        tinymce.triggerSave();
    }
    var title = document.querySelector('[name="title_ar"]');
    var slug = document.getElementById('toolSlug');
    var cat = document.querySelector('[name="category_id"]');
    if (!title.value.trim()) { showToast('يرجى إدخال عنوان الأداة بالعربية', 'error'); title.focus(); e.preventDefault(); return; }
    if (!cat.value) { showToast('يرجى اختيار تصنيف للأداة', 'error'); cat.focus(); e.preventDefault(); return; }
    if (!slug.value.trim()) {
        slug.value = slugify(title.value.trim()) || 'tool-' + Date.now();
        updateSlugPreview();
    }
});

// ===== توست =====
function showToast(msg, type) {
    var t = document.getElementById('toast');
    t.textContent = msg;
    t.className = 'toast toast-' + type + ' show';
    clearTimeout(t._timer);
    t._timer = setTimeout(function() { t.classList.remove('show'); }, 3000);
}

// ===== معاينة حية =====
function refreshPreview() {
    var html = document.getElementById('html_code').value;
    var css = document.getElementById('css_code').value;
    var js = document.getElementById('js_code').value;
    var frame = document.getElementById('livePreview');
    frame.innerHTML = '<style>' + css + '</style>' + html;
    try { eval(js); } catch(e) { console.error('Preview JS Error:', e); }
}

// ===== FAQ =====
document.getElementById('addFaqBtn')?.addEventListener('click', function() {
    var container = document.getElementById('faqContainer');
    var num = container.querySelectorAll('.faq-card').length + 1;
    var div = document.createElement('div');
    div.className = 'faq-card';
    div.innerHTML = '' +
        '<div class="faq-num">' + num + '</div>' +
        '<button type="button" class="faq-delete" onclick="this.closest(\'.faq-card\').remove()" title="حذف">✕</button>' +
        '<div class="faq-lang-tabs">' +
        '<button class="lang-tab-mod active" data-lang="ar" type="button">🇸🇦 عربي</button>' +
        '<button class="lang-tab-mod" data-lang="en" type="button">🇬🇧 EN</button>' +
        '<button class="lang-tab-mod" data-lang="fr" type="button">🇫🇷 FR</button></div>' +
        '<div class="lang-content-mod active" data-lang="ar">' +
        '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">السؤال</label><input class="form-control-mod" name="faq_question_ar[]"></div>' +
        '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">الإجابة</label><textarea class="form-control-mod" name="faq_answer_ar[]" rows="2"></textarea></div></div>' +
        '<div class="lang-content-mod" data-lang="en">' +
        '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Question</label><input class="form-control-mod" name="faq_question_en[]"></div>' +
        '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Answer</label><textarea class="form-control-mod" name="faq_answer_en[]" rows="2"></textarea></div></div>' +
        '<div class="lang-content-mod" data-lang="fr">' +
        '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Question</label><input class="form-control-mod" name="faq_question_fr[]"></div>' +
        '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Réponse</label><textarea class="form-control-mod" name="faq_answer_fr[]" rows="2"></textarea></div></div>';
    container.appendChild(div);
    // Activate tab listeners for new FAQ
    div.querySelectorAll('.lang-tab-mod').forEach(function(t) {
        t.addEventListener('click', function() {
            var tg = this.closest('.faq-lang-tabs');
            var lang = this.getAttribute('data-lang');
            tg.querySelectorAll('.lang-tab-mod').forEach(function(x) { x.classList.remove('active'); });
            this.classList.add('active');
            div.querySelectorAll('.lang-content-mod').forEach(function(c) {
                c.classList.remove('active');
                if (c.getAttribute('data-lang') === lang) c.classList.add('active');
            });
        });
    });
});

// ===== AI Settings Management =====
var aiModelsByProvider = {
    'google': [
        { value: 'gemini-2.5-flash', text: 'gemini-2.5-flash (أحدث نموذج سريع - توليد نصوص وأكواد)' },
        { value: 'gemini-2.5-pro', text: 'gemini-2.5-pro (ذكاء فائق ومنطق عالي - نصوص وأكواد معقدة)' },
        { value: 'gemini-2.0-flash', text: 'gemini-2.0-flash (سريع جداً وذكي - نصوص وأكواد)' },
        { value: 'gemini-2.0-flash-thinking-exp', text: 'gemini-2.0-flash-thinking-exp (استدلال وتفكير عميق - حل مشكلات وبرمجة)' },
        { value: 'gemini-1.5-flash', text: 'gemini-1.5-flash (افتراضي متزن وسياق طويل - نصوص وأكواد)' },
        { value: 'gemini-1.5-pro', text: 'gemini-1.5-pro (تحليل عميق وسياق ضخم - نصوص وأكواد)' }
    ],
                'opencode': [
        // Free Models only (to avoid 401 errors with free API keys)
        { value: 'qwen3.6-plus-free', text: 'qwen3.6-plus-free (كوين 3.6 بلس مجاني - نصوص وبرمجة)' },
        { value: 'deepseek-v4-flash-free', text: 'deepseek-v4-flash-free (ديب سيك v4 فلاش مجاني - سريع جداً)' },
        { value: 'minimax-m3-free', text: 'minimax-m3-free (مينيمكس 3 مجاني - نصوص ومقالات وسياق كبير)' },
        { value: 'mimo-v2.5-free', text: 'mimo-v2.5-free (ميمو 2.5 مجاني - برمجة وتوليد أكواد)' },
        { value: 'big-pickle', text: 'big-pickle (بيج بيكل مجاني - برمجة وتوليد أكواد)' },
        { value: 'nemotron-3-ultra-free', text: 'nemotron-3-ultra-free (نيموترون 3 الترا مجاني)' },
        { value: 'nemotron-3-super-free', text: 'nemotron-3-super-free (نيموترون مجاني - نصوص ومقالات)' }
    ],
    'openrouter': [
        { value: 'deepseek/deepseek-v4-flash:free', text: 'DeepSeek V4 Flash (free) - ديب سيك V4 فلاش مجاني' },
        { value: 'z-ai/glm-4.5-air:free', text: 'GLM 4.5 Air (free) - جي إل إم 4.5 إير مجاني' },
        { value: 'moonshotai/kimi-k2.6:free', text: 'Kimi K2.6 (free) - كيمي K2.6 مجاني' },
        { value: 'poolside/laguna-m.1:free', text: 'Laguna M.1 (free) - لاجونا M.1 مجاني' },
        { value: 'poolside/laguna-xs.2:free', text: 'Laguna XS.2 (free) - لاجونا XS.2 مجاني' },
        { value: 'openai/gpt-oss-120b:free', text: 'gpt-oss-120b (free) - جي بي تي أوس 120b مجاني' }
    ],
    'groq': [
        { value: 'llama-3.3-70b-versatile', text: 'llama-3.3-70b-versatile (نموذج ميتا القوي - نصوص ومقالات ومحادثة)' },
        { value: 'deepseek-r1-distill-llama-70b', text: 'deepseek-r1-distill-llama-70b (تفكير واستدلال - برمجة وكود ونصوص معقدة)' },
        { value: 'deepseek-r1-distill-qwen-32b', text: 'deepseek-r1-distill-qwen-32b (تفكير واستدلال ممتاز - برمجة وكود ونصوص)' },
        { value: 'qwen-2.5-coder-32b', text: 'qwen-2.5-coder-32b (نموذج علي بابا المتخصص - برمجة وتوليد أكواد)' },
        { value: 'llama-3.1-8b-instant', text: 'llama-3.1-8b-instant (نموذج ميتا السريع - نصوص ومحادثة خفيفة)' },
        { value: 'mixtral-8x7b-32768', text: 'mixtral-8x7b-32768 (سياق طويل 32k - نصوص وبرمجة)' },
        { value: 'gemma2-9b-it', text: 'gemma2-9b-it (نموذج قوقل خفيف - نصوص ومحادثة)' }
    ],
    'cerebras': [
        { value: 'gpt-oss-120b', text: 'gpt-oss-120b (استدلال وتفكير عميق فائق السرعة - نصوص وأكواد)' },
        { value: 'zai-glm-4.7', text: 'zai-glm-4.7 (نموذج محادثة ذكي وفائق السرعة - نصوص وأكواد)' }
    ],
    'siliconflow': [
        { value: 'deepseek-ai/DeepSeek-R1', text: 'DeepSeek-R1 (برمجة/تفكير - الأقوى)' },
        { value: 'deepseek-ai/DeepSeek-V4-Pro', text: 'DeepSeek-V4-Pro (برمجة)' },
        { value: 'deepseek-ai/DeepSeek-V4-Flash', text: 'DeepSeek-V4-Flash (برمجة/سريع)' },
        { value: 'zai-org/GLM-5.1', text: 'GLM-5.1 (برمجة - GLM)' },
        { value: 'deepseek-ai/DeepSeek-V3.2', text: 'DeepSeek-V3.2 (برمجة/نصوص)' },
        { value: 'zai-org/GLM-5', text: 'GLM-5 (برمجة - GLM)' },
        { value: 'deepseek-ai/DeepSeek-V3.1', text: 'DeepSeek-V3.1 (برمجة/نصوص)' },
        { value: 'Qwen/Qwen3-Coder-30B-A3B-Instruct', text: 'Qwen3-Coder-30B (برمجة)' },
        { value: 'Qwen/Qwen3-32B', text: 'Qwen3-32B (نصوص/برمجة)' },
        { value: 'zai-org/GLM-4.5-Air', text: 'GLM-4.5-Air (نصوص - GLM)' },
        { value: 'zai-org/GLM-5V-Turbo', text: 'GLM-5V-Turbo (نصوص - GLM)' },
        { value: 'google/gemma-4-31B-it', text: 'Gemma-4-31B (نصوص)' },
        { value: 'MiniMaxAI/MiniMax-M3', text: 'MiniMax-M3 (نصوص)' },
        { value: 'moonshotai/Kimi-K2.6', text: 'Kimi-K2.6 (نصوص)' },
        { value: 'openai/gpt-oss-120b', text: 'GPT-OSS-120B (نصوص)' },
        { value: 'stepfun-ai/Step-3.5-Flash', text: 'Step-3.5-Flash (نصوص)' },
        { value: 'Qwen/Qwen3-14B', text: 'Qwen3-14B (نصوص)' },
        { value: 'Qwen/Qwen3-8B', text: 'Qwen3-8B (نصوص)' },
        { value: 'Qwen/Qwen2.5-72B-Instruct', text: 'Qwen2.5-72B (نصوص)' },
        { value: 'Qwen/Qwen2.5-7B-Instruct', text: 'Qwen2.5-7B (نصوص)' }
    ],
    'routeway': [
        { value: 'deepseek-v4-flash:free', text: 'DeepSeek V4 Flash (برمجة/سريع)' },
        { value: 'llama-3.3-70b-instruct:free', text: 'Llama 3.3 70B (نصوص/برمجة)' },
        { value: 'nemotron-3-nano-30b-a3b:free', text: 'Nemotron 3 Nano 30B (نصوص)' },
        { value: 'llama-3.1-8b-instruct:free', text: 'Llama 3.1 8B (نصوص)' },
        { value: 'llama-3.2-3b-instruct:free', text: 'Llama 3.2 3B (نصوص)' },
        { value: 'llama-3.2-1b-instruct:free', text: 'Llama 3.2 1B (نصوص)' },
        { value: 'mistral-nemo-instruct:free', text: 'Mistral Nemo (نصوص)' },
        { value: 'nemotron-nano-9b-v2:free', text: 'Nemotron Nano 9B (نصوص)' },
        { value: 'minimax-m2:free', text: 'MiniMax M2 (نصوص)' },
        { value: 'gpt-oss-120b:free', text: 'GPT-OSS 120B (نصوص)' },
        { value: 'step-3.5-flash:free', text: 'Step 3.5 Flash (نصوص)' },
        { value: 'ling-2.6-flash:free', text: 'Ling 2.6 Flash (نصوص)' },
        { value: 'laguna-xs.2:free', text: 'Laguna XS.2 (نصوص)' },
        { value: 'laguna-m.1:free', text: 'Laguna M.1 (نصوص)' }
    ],
    'github': [
        { value: 'openai/gpt-4.1', text: 'GPT-4.1 (برمجة - الأقوى)' },
        { value: 'deepseek/deepseek-r1', text: 'DeepSeek R1 (برمجة/تفكير)' },
        { value: 'deepseek/deepseek-r1-0528', text: 'DeepSeek R1-0528 (برمجة/تفكير)' },
        { value: 'openai/gpt-4o', text: 'GPT-4o (برمجة/نصوص)' },
        { value: 'deepseek/deepseek-v3-0324', text: 'DeepSeek V3-0324 (برمجة)' },
        { value: 'mistral-ai/codestral-2501', text: 'Codestral 2501 (برمجة - كود)' },
        { value: 'meta/meta-llama-3.1-405b-instruct', text: 'Llama 3.1 405B (برمجة/نصوص)' },
        { value: 'meta/llama-4-maverick-17b-128e-instruct-fp8', text: 'Llama 4 Maverick 17B (نصوص)' },
        { value: 'meta/llama-3.3-70b-instruct', text: 'Llama 3.3 70B (نصوص)' },
        { value: 'microsoft/phi-4-reasoning', text: 'Phi-4 Reasoning (برمجة/تفكير)' },
        { value: 'microsoft/phi-4-mini-reasoning', text: 'Phi-4 Mini Reasoning (برمجة/تفكير)' },
        { value: 'mistral-ai/mistral-medium-2505', text: 'Mistral Medium 2505 (نصوص)' },
        { value: 'microsoft/phi-4', text: 'Phi-4 (نصوص/برمجة)' },
        { value: 'mistral-ai/mistral-small-2503', text: 'Mistral Small 2503 (نصوص)' },
        { value: 'meta/llama-4-scout-17b-16e-instruct', text: 'Llama 4 Scout 17B (نصوص)' },
        { value: 'openai/gpt-4.1-mini', text: 'GPT-4.1 Mini (نصوص/سريع)' },
        { value: 'openai/gpt-4.1-nano', text: 'GPT-4.1 Nano (نصوص/سريع)' },
        { value: 'openai/gpt-4o-mini', text: 'GPT-4o Mini (نصوص/سريع)' },
        { value: 'meta/meta-llama-3.1-8b-instruct', text: 'Llama 3.1 8B (نصوص)' },
        { value: 'mistral-ai/ministral-3b', text: 'Ministral 3B (نصوص)' },
        { value: 'cohere/cohere-command-a', text: 'Cohere Command A (نصوص)' }
    ],
    'featherless': [
        { value: 'zai-org/GLM-5.1', text: 'GLM-5.1 (برمجة - GLM الأحدث)' },
        { value: 'zai-org/GLM-5', text: 'GLM-5 (برمجة - GLM)' },
        { value: 'zai-org/GLM-4.7', text: 'GLM-4.7 (نصوص/برمجة - GLM)' },
        { value: 'zai-org/GLM-4.6', text: 'GLM-4.6 (نصوص/برمجة - GLM)' }
    ]
};

function onAiProviderChange(selectedModel = null) {
    var providerSelect = document.getElementById('aiProviderSelect');
    var modelSelect = document.getElementById('aiModelSelect');
    if (!providerSelect || !modelSelect) return;

    var provider = providerSelect.value;
    var models = aiModelsByProvider[provider] || [];

    modelSelect.innerHTML = '';
    models.forEach(function(m) {
        var opt = document.createElement('option');
        opt.value = m.value;
        opt.textContent = m.text;
        modelSelect.appendChild(opt);
    });

    if (selectedModel) {
        modelSelect.value = selectedModel;
    } else {
        var savedModel = localStorage.getItem('ai_selected_model_' + provider);
        if (savedModel && models.some(function(m) { return m.value === savedModel; })) {
            modelSelect.value = savedModel;
        }
    }

    localStorage.setItem('ai_selected_provider', provider);
    localStorage.setItem('ai_selected_model_' + provider, modelSelect.value);
}

// Bind change listener for model select
document.addEventListener('DOMContentLoaded', function() {
    var modelSelect = document.getElementById('aiModelSelect');
    if (modelSelect) {
        modelSelect.addEventListener('change', function() {
            var provider = document.getElementById('aiProviderSelect').value;
            localStorage.setItem('ai_selected_model_' + provider, this.value);
        });
    }

    var savedProvider = localStorage.getItem('ai_selected_provider');
    var providerSelect = document.getElementById('aiProviderSelect');
    if (providerSelect && savedProvider) {
        providerSelect.value = savedProvider;
    }
    onAiProviderChange();
});

// ===== AI Generation =====
function aiGenerate(type, lang) {
    var title = document.getElementById('titleAr').value.trim();
    if (!title) { showToast('يرجى إدخال عنوان الأداة أولاً', 'error'); return; }
    var btn = event && event.target ? event.target.closest('button,span') : null;
    if (btn) { btn.disabled = true; btn.textContent = '⏳'; btn.style.opacity = '0.6'; }

    var formData = new FormData();
    formData.append('ai_generate', '1');
    formData.append('ai_title', title);
    formData.append('ai_type', type);
    formData.append('ai_lang', lang);
    formData.append('ai_meta_title', document.getElementById('metaTitleAr')?.value?.trim() || title);

    var providerSelect = document.getElementById('aiProviderSelect');
    var modelSelect = document.getElementById('aiModelSelect');
    if (providerSelect) formData.append('ai_provider', providerSelect.value);
    if (modelSelect) formData.append('ai_model', modelSelect.value);

    fetch('', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { showToast(data.error, 'error'); return; }
            var text = data.text;
            if (type === 'short_desc') {
                document.getElementById('shortDescAr').value = text.substring(0, 160);
                updateSeoPreview();
                showToast('تم توليد الوصف القصير', 'success');
            } else if (type === 'meta_desc') {
                document.getElementById('metaDescAr').value = text.substring(0, 320);
                updateSeoPreview();
                showToast('تم توليد وصف SEO', 'success');
            } else if (type === 'long_desc') {
                if (window.tinymce && tinymce.get('editor_' + lang)) {
                    tinymce.get('editor_' + lang).setContent(text);
                } else {
                    var ed = document.getElementById('editor_' + lang);
                    if (ed) {
                        ed.value = text;
                    }
                }
                if (lang === 'ar') { setTimeout(runSeoAnalysis, 100); }
                showToast('تم توليد النبذة التعريفية', 'success');
            } else if (type === 'faq') {
                try {
                    var faqs = JSON.parse(text);
                    if (Array.isArray(faqs)) {
                        var container = document.getElementById('faqContainer');
                        container.innerHTML = '';
                        faqs.forEach(function(f, i) {
                            var div = document.createElement('div');
                            div.className = 'faq-card';
                            div.innerHTML = '' +
                                '<div class="faq-num">' + (i+1) + '</div>' +
                                '<button type="button" class="faq-delete" onclick="this.closest(\'.faq-card\').remove()" title="حذف">✕</button>' +
                                '<div class="faq-lang-tabs">' +
                                '<button class="lang-tab-mod active" data-lang="ar" type="button">🇸🇦 عربي</button>' +
                                '<button class="lang-tab-mod" data-lang="en" type="button">🇬🇧 EN</button>' +
                                '<button class="lang-tab-mod" data-lang="fr" type="button">🇫🇷 FR</button></div>' +
                                '<div class="lang-content-mod active" data-lang="ar">' +
                                '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">السؤال</label><input class="form-control-mod" name="faq_question_ar[]" value="' + escHtml(f.q) + '"></div>' +
                                '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">الإجابة</label><textarea class="form-control-mod" name="faq_answer_ar[]" rows="2">' + escHtml(f.a) + '</textarea></div></div>' +
                                '<div class="lang-content-mod" data-lang="en">' +
                                '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Question</label><input class="form-control-mod" name="faq_question_en[]"></div>' +
                                '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Answer</label><textarea class="form-control-mod" name="faq_answer_en[]" rows="2"></textarea></div></div>' +
                                '<div class="lang-content-mod" data-lang="fr">' +
                                '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Question</label><input class="form-control-mod" name="faq_question_fr[]"></div>' +
                                '<div class="form-group" style="margin-bottom:8px;"><label style="font-size:0.78rem;color:var(--gray);">Réponse</label><textarea class="form-control-mod" name="faq_answer_fr[]" rows="2"></textarea></div></div>';
                            container.appendChild(div);
                        });
                        showToast('تم توليد ' + faqs.length + ' سؤال', 'success');
                    }
                } catch(e) {
                    showToast('فشل تحليل FAQ من الرد', 'error');
                }
            }
            triggerCharCounts();
        })
        .catch(function(err) {
            showToast('خطأ في الاتصال: ' + err.message, 'error');
        })
        .finally(function() {
            if (btn) { btn.disabled = false; btn.textContent = '🤖'; btn.style.opacity = '1'; }
        });
}

function escHtml(str) {
    if (!str) return '';
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function triggerCharCounts() {
    document.querySelectorAll('.form-control-mod').forEach(function(el) {
        var parent = el.closest('.form-group');
        if (parent) {
            var countEl = parent.querySelector('.char-count span');
            if (countEl) countEl.textContent = el.value.length;
        }
    });
}

// ===== AI Code Generation =====
function aiGenerateCode() {
    var prompt = document.getElementById('aiCodePrompt').value.trim();
    if (!prompt) { showToast('يرجى كتابة وصف الأداة أولاً', 'error'); return; }
    var btn = document.querySelector('.ai-code-gen .btn-mod');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ جاري...'; btn.style.opacity = '0.6'; }

    var formData = new FormData();
    formData.append('ai_generate', '1');
    formData.append('ai_title', document.getElementById('titleAr')?.value?.trim() || prompt);
    formData.append('ai_type', 'generate_code');
    formData.append('ai_lang', 'ar');
    formData.append('ai_custom_prompt', prompt);

    var providerSelect = document.getElementById('aiProviderSelect');
    var modelSelect = document.getElementById('aiModelSelect');
    if (providerSelect) formData.append('ai_provider', providerSelect.value);
    if (modelSelect) formData.append('ai_model', modelSelect.value);

    fetch('', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.error) { showToast(data.error, 'error'); return; }
            var text = data.text;
            try {
                var codeData = { html: '', css: '', js: '' };
                var parsedSuccess = false;
                
                // 1. Try to parse as JSON first
                try {
                    var firstBrace = text.indexOf('{');
                    var lastBrace = text.lastIndexOf('}');
                    var jsonStr = (firstBrace !== -1 && lastBrace > firstBrace) ? text.substring(firstBrace, lastBrace + 1) : text;
                    jsonStr = jsonStr.replace(/,\s*}/g, '}').replace(/,\s*\]/g, ']');
                    var parsed = JSON.parse(jsonStr);
                    if (parsed.html || parsed.css || parsed.js) {
                        codeData.html = parsed.html || '';
                        codeData.css = parsed.css || '';
                        codeData.js = parsed.js || '';
                        parsedSuccess = true;
                    }
                } catch(e2) {
                    console.warn("JSON parsing failed, falling back to regex block extraction", e2);
                }
                
                // 2. Fallback: Extract from markdown blocks
                if (!parsedSuccess) {
                    var htmlMatch = text.match(/```html([\s\S]*?)```/i);
                    if (htmlMatch) {
                        codeData.html = htmlMatch[1].trim();
                        parsedSuccess = true;
                    } else {
                        var rawHtmlMatch = text.match(/(<[a-z]+[^>]*>[\s\S]*?<\/[a-z]+>)/i);
                        if (rawHtmlMatch) {
                            codeData.html = rawHtmlMatch[1].trim();
                            parsedSuccess = true;
                        }
                    }
                    
                    var cssMatch = text.match(/```css([\s\S]*?)```/i);
                    if (cssMatch) {
                        codeData.css = cssMatch[1].trim();
                    } else {
                        var styleMatch = text.match(/<style>([\s\S]*?)<\/style>/i);
                        if (styleMatch) codeData.css = styleMatch[1].trim();
                    }
                    
                    var jsMatch = text.match(/```(?:javascript|js)([\s\S]*?)```/i);
                    if (jsMatch) {
                        codeData.js = jsMatch[1].trim();
                    } else {
                        var scriptMatch = text.match(/<script>([\s\S]*?)<\/script>/i);
                        if (scriptMatch) codeData.js = scriptMatch[1].trim();
                    }
                    
                    if (codeData.html) {
                        codeData.html = codeData.html.replace(/<style>[\s\S]*?<\/style>/gi, '');
                        codeData.html = codeData.html.replace(/<script>[\s\S]*?<\/script>/gi, '');
                    }
                }
                
                if (parsedSuccess && (codeData.html || codeData.css || codeData.js)) {
                    var htmlField = document.getElementById('html_code');
                    var cssField = document.getElementById('css_code');
                    var jsField = document.getElementById('js_code');
                    if (htmlField) htmlField.value = codeData.html;
                    if (cssField) cssField.value = codeData.css;
                    if (jsField) jsField.value = codeData.js;
                    
                    if (typeof refreshPreview === 'function') {
                        setTimeout(refreshPreview, 100);
                    }
                    showToast('✅ تم توليد كود HTML + CSS + JS بنجاح', 'success');
                } else {
                    throw new Error("Could not find any code blocks or parse JSON");
                }
            } catch(e) {
                showToast('❌ فشل تحليل الكود المُولّد. حاول مرة أخرى.', 'error');
            }
        })
        .catch(function(err) {
            showToast('❌ خطأ في الاتصال: ' + err.message, 'error');
        })
        .finally(function() {
            if (btn) { btn.disabled = false; btn.textContent = '🚀 توليد'; btn.style.opacity = '1'; }
        });
}

// ===== SEO Analyzer =====
var seoFields = ['titleAr','shortDescAr','metaDescAr','metaTitleAr'];
seoFields.forEach(function(id) {
    var el = document.getElementById(id);
    if (el) el.addEventListener('input', function() { runSeoAnalysis(); });
});
// TinyMCE handles editor changes via event listeners inside initialization

function runSeoAnalysis() {
    var title = (document.getElementById('titleAr')?.value || '').trim();
    var shortDesc = (document.getElementById('shortDescAr')?.value || '').trim();
    var metaDesc = (document.getElementById('metaDescAr')?.value || '').trim();
    var metaTitle = (document.getElementById('metaTitleAr')?.value || title).trim();
    var longDesc = '';
    var htmlCode = (document.getElementById('html_code')?.value || '').trim();
    var cssCode = (document.getElementById('css_code')?.value || '').trim();
    var jsCode = (document.getElementById('js_code')?.value || '').trim();
    if (window.tinymce && tinymce.get('editor_ar')) {
        longDesc = stripHtml(tinymce.get('editor_ar').getContent());
    } else {
        var edEl = document.getElementById('editor_ar');
        longDesc = stripHtml(edEl ? edEl.value : '');
    }

    if (!title) {
        resetSeo();
        return;
    }

    var score = 0;
    var maxScore = 100;
    var notes = [];
    var results = {};

    // Extract main keywords from title
    var titleWords = extractKeywords(title);
    var mainKeyword = titleWords[0] || '';

    // === 1. Meta Title Check ===
    if (metaTitle.length >= 30 && metaTitle.length <= 70) {
        results.title = { status: 'pass', text: metaTitle.length + ' حرف (مثالي 30-70)' };
        score += 12;
    } else if (metaTitle.length > 0) {
        if (metaTitle.length < 30) {
            results.title = { status: 'fail', text: metaTitle.length + ' حرف (قصير جداً)' };
            notes.push({ type: 'fail', text: 'عنوان SEO قصير جداً (' + metaTitle.length + ' حرف). يجب 30-70 حرفاً.' });
        } else {
            results.title = { status: 'warn', text: metaTitle.length + ' حرف (طويل)' };
            notes.push({ type: 'warn', text: 'عنوان SEO طويل (' + metaTitle.length + ' حرف). يفضل حتى 70 حرفاً.' });
        }
    } else {
        results.title = { status: 'fail', text: 'فارغ' };
        notes.push({ type: 'fail', text: 'عنوان SEO فارغ. مطلوب لتحسين الترتيب.' });
    }
    if (metaTitle && mainKeyword && metaTitle.includes(mainKeyword)) { score += 5; notes.push({ type: 'pass', text: 'الكلمة المفتاحية موجودة في عنوان SEO ✓' }); }
    else if (metaTitle) { notes.push({ type: 'warn', text: 'الكلمة "' + mainKeyword + '" غير موجودة في عنوان SEO.' }); }

    // === 2. Meta Description Check ===
    if (metaDesc.length >= 120 && metaDesc.length <= 320) {
        results.meta = { status: 'pass', text: metaDesc.length + ' حرف (مثالي)' };
        score += 8;
    } else if (metaDesc.length > 0) {
        if (metaDesc.length < 120) { results.meta = { status: 'fail', text: metaDesc.length + ' حرف (قصير)' }; notes.push({ type: 'fail', text: 'وصف SEO قصير (' + metaDesc.length + ' حرف). الأفضل 120-320.' }); }
        else { results.meta = { status: 'warn', text: metaDesc.length + ' حرف (طويل)' }; notes.push({ type: 'warn', text: 'وصف SEO طويل (' + metaDesc.length + ' حرف). يفضل حتى 320.' }); }
    } else {
        results.meta = { status: 'fail', text: 'فارغ' };
        notes.push({ type: 'warn', text: 'وصف SEO فارغ. يُفضل ملؤه لنسبة نقر أعلى.' });
    }
    if (metaDesc && mainKeyword && metaDesc.includes(mainKeyword)) { score += 3; }
    else if (metaDesc) { notes.push({ type: 'warn', text: 'الكلمة "' + mainKeyword + '" غير موجودة في وصف SEO.' }); }

    // === 3. Short Description Check ===
    if (shortDesc.length >= 100 && shortDesc.length <= 160) {
        results.short = { status: 'pass', text: shortDesc.length + ' حرف (مثالي)' };
        score += 5;
    } else if (shortDesc.length > 0) {
        results.short = { status: 'warn', text: shortDesc.length + ' حرف (الأفضل 100-160)' };
        if (shortDesc.length < 100) notes.push({ type: 'warn', text: 'الوصف القصير قصير (' + shortDesc.length + ' حرف). يفضل 100-160.' });
        else notes.push({ type: 'warn', text: 'الوصف القصير طويل (' + shortDesc.length + ' حرف). يفضل حتى 160.' });
    } else {
        results.short = { status: 'fail', text: 'فارغ' };
        notes.push({ type: 'fail', text: 'الوصف القصير فارغ. مطلوب.' });
    }
    if (shortDesc && mainKeyword && shortDesc.includes(mainKeyword)) { score += 3; }
    else if (shortDesc) { notes.push({ type: 'warn', text: 'الكلمة "' + mainKeyword + '" غير موجودة في الوصف القصير.' }); }

    // === 4. Long Content Check ===
    var wordCount = longDesc ? longDesc.split(/\s+/).filter(function(w){return w.length>0}).length : 0;
    if (wordCount >= 400) { results.long = { status: 'pass', text: wordCount + ' كلمة ✓' }; score += 8; notes.push({ type: 'pass', text: 'المحتوى غني (' + wordCount + ' كلمة) ✓' }); }
    else if (wordCount >= 200) { results.long = { status: 'warn', text: wordCount + ' كلمة (الأفضل 400+)' }; score += 4; notes.push({ type: 'warn', text: 'المحتوى قصير نسبياً (' + wordCount + ' كلمة). الأفضل 400+.' }); }
    else if (wordCount > 0) { results.long = { status: 'fail', text: wordCount + ' كلمة (قصير)' }; notes.push({ type: 'fail', text: 'المحتوى قصير جداً (' + wordCount + ' كلمة). يفضل 400+.' }); }
    else { results.long = { status: 'fail', text: 'فارغ' }; notes.push({ type: 'fail', text: 'المحتوى الطويل فارغ. ضروري للسو.' }); }

    // Keyword density
    if (longDesc && mainKeyword) {
        var re = new RegExp(regEscape(mainKeyword), 'gi');
        var kwCount = (longDesc.match(re) || []).length;
        var density = wordCount ? (kwCount / wordCount * 100) : 0;
        if (kwCount >= 3 && density <= 3) { results.keywords = { status: 'pass', text: kwCount + ' مرات (' + density.toFixed(1) + '%) ✓' }; score += 5; }
        else if (kwCount > 0) {
            if (density > 3) { results.keywords = { status: 'warn', text: kwCount + ' مرات (' + density.toFixed(1) + '%)' }; notes.push({ type: 'warn', text: 'كثافة عالية (' + density.toFixed(1) + '%). يفضل أقل من 3%.' }); }
            else { results.keywords = { status: 'warn', text: kwCount + ' مرات (' + density.toFixed(1) + '%)' }; notes.push({ type: 'warn', text: 'الكلمة تكررت ' + kwCount + ' مرة فقط. يفضل 3-5.' }); }
        } else { results.keywords = { status: 'fail', text: '0 مرات' }; notes.push({ type: 'fail', text: 'الكلمة "' + mainKeyword + '" غير موجودة في المحتوى.' }); }
    } else {
        results.keywords = { status: 'info', text: 'انتظر المحتوى' };
    }

    // === 5. LSI Keywords ===
    var lsiSuggestions = generateLsi(title);
    if (lsiSuggestions.length > 0) {
        results.lsikeywords = { status: 'pass', text: lsiSuggestions.slice(0,5).join(', ') };
        score += 3;
        notes.push({ type: 'info', text: 'كلمات LSI مقترحة: ' + lsiSuggestions.join('، ') });
    } else {
        results.lsikeywords = { status: 'info', text: 'أدخل عنواناً' };
    }

    // === 6. E-E-A-T Assessment ===
    var eeatDetails = {
        experience: { score: 0, items: [] },
        expertise: { score: 0, items: [] },
        authority: { score: 0, items: [] },
        trust: { score: 0, items: [] }
    };

    var eeatExp = 0, eeatExpRise = 0, eeatAuth = 0, eeatTrust = 0;

    if (longDesc) {
        // Experience Assessment
        var hasSteps = /كيف|طريقة|خطوة|مراحل|دليل/i.test(longDesc);
        var hasExamples = /مثال|نماذج|حالات|عملياً/i.test(longDesc);
        var hasTips = /نصائح|إرشادات|شرح|توضيح/i.test(longDesc);
        var expWordCount = wordCount >= 300;

        eeatDetails.experience.items.push({ text: 'خطوات وإرشادات عملية', passed: hasSteps });
        eeatDetails.experience.items.push({ text: 'أمثلة وحالات استخدام', passed: hasExamples });
        eeatDetails.experience.items.push({ text: 'نصائح وإرشادات للزوار', passed: hasTips });
        eeatDetails.experience.items.push({ text: 'محتوى كافٍ (>300 كلمة)', passed: expWordCount });

        var expScore = 0;
        if (hasSteps) expScore += 3;
        if (hasExamples) expScore += 2;
        if (hasTips) expScore += 2;
        if (expWordCount) expScore += 3;
        eeatExp = Math.min(10, expScore);
        eeatDetails.experience.score = eeatExp;

        // Expertise Assessment
        var hasTechTerms = /خوارزمية|برمجة|تنسيق|تحليل|بيانات|متطور|تعديل|معالجة/i.test(longDesc);
        var hasH2 = (longDesc.match(/<h2/gi) || []).length > 0;
        var hasH3 = (longDesc.match(/<h3/gi) || []).length > 0;
        var extWordCount = wordCount >= 500;

        eeatDetails.expertise.items.push({ text: 'مصطلحات تقنية متخصصة', passed: hasTechTerms });
        eeatDetails.expertise.items.push({ text: 'عناوين رئيسية H2 لتنظيم المحتوى', passed: hasH2 });
        eeatDetails.expertise.items.push({ text: 'عناوين فرعية H3 للتفصيل', passed: hasH3 });
        eeatDetails.expertise.items.push({ text: 'عمق علمي كافٍ (>500 كلمة)', passed: extWordCount });

        var expertScore = 0;
        if (hasTechTerms) expertScore += 3;
        if (hasH2) expertScore += 2;
        if (hasH3) expertScore += 2;
        if (extWordCount) expertScore += 3;
        eeatExpRise = Math.min(10, expertScore);
        eeatDetails.expertise.score = eeatExpRise;

        // Authority Assessment
        var hasNumbers = /\d+/g.test(longDesc);
        var hasStats = /%|نسبة|إحصاء|دراسة|مرجع|مصدر/i.test(longDesc);
        var hasCompare = /مقارنة|أفضل من|أسرع من|مزايا/i.test(longDesc);
        var authWordCount = wordCount >= 600;

        eeatDetails.authority.items.push({ text: 'أرقام وإحصائيات دقيقة', passed: hasNumbers });
        eeatDetails.authority.items.push({ text: 'نسب أو إشارات إلى مصادر ودراسات', passed: hasStats });
        eeatDetails.authority.items.push({ text: 'مقارنة مميزات أو أدوات بديلة', passed: hasCompare });
        eeatDetails.authority.items.push({ text: 'محتوى مرجعي وافٍ (>600 كلمة)', passed: authWordCount });

        var authScore = 0;
        if (hasNumbers) authScore += 2;
        if (hasStats) authScore += 3;
        if (hasCompare) authScore += 2;
        if (authWordCount) authScore += 3;
        eeatAuth = Math.min(10, authScore);
        eeatDetails.authority.score = eeatAuth;

        // Trustworthiness Assessment
        var hasSecureTerms = /خصوصية|آمن|مشفر|حماية|أمان/i.test(longDesc);
        var hasTransTerms = /ضمان|دقة|مجاني|شفاف|مسؤول/i.test(longDesc);
        var hasInteractive = htmlCode.length > 0;
        var hasFaqSchema = /schema.org|ld\+json/i.test(longDesc) || (document.querySelectorAll('.faq-card').length > 0);

        eeatDetails.trust.items.push({ text: 'إشارات الخصوصية وأمان البيانات', passed: hasSecureTerms });
        eeatDetails.trust.items.push({ text: 'شفافية الاستخدام والدقة والمجانية', passed: hasTransTerms });
        eeatDetails.trust.items.push({ text: 'أداة تفاعلية حقيقية تعمل في الصفحة', passed: hasInteractive });
        eeatDetails.trust.items.push({ text: 'البيانات المنظمة وسكيما الأسئلة الشائعة', passed: hasFaqSchema });

        var trustScore = 0;
        if (hasSecureTerms) trustScore += 3;
        if (hasTransTerms) trustScore += 2;
        if (hasInteractive) trustScore += 3;
        if (hasFaqSchema) trustScore += 2;
        eeatTrust = Math.min(10, trustScore);
        eeatDetails.trust.score = eeatTrust;
    }

    var eeatAvg = (eeatExp + eeatExpRise + eeatAuth + eeatTrust) / 4;
    score += Math.round(eeatAvg * 1.5);

    if (eeatAvg >= 9) notes.push({ type: 'pass', text: 'E-E-A-T: مستوى احترافي خارق ومطابق لمتطلبات جوجل ✓' });
    else if (eeatAvg >= 7) notes.push({ type: 'pass', text: 'E-E-A-T: مستوى جيد جداً من الجودة والمصداقية ✓' });
    else if (eeatAvg >= 4) notes.push({ type: 'warn', text: 'E-E-A-T: مقبول، ولكن يرجى استيفاء العناصر الناقصة لضمان أداء سيو أقوى.' });
    else notes.push({ type: 'fail', text: 'E-E-A-T: ضعيف جداً. أضف تفاصيل الخبرة والأمان والمصداقية في النص.' });

    // === 7. Search Intent ===
    var intentScore = 5;
    var allContent = (title + ' ' + shortDesc + ' ' + metaDesc + ' ' + longDesc);
    if (allContent && mainKeyword) {
        var actionWords = ['احسب', 'حول', 'انشئ', 'ولد', 'اكتب', 'ترجم', 'ضغط', 'قص', 'ادمج', 'قسّم', 'ابحث', 'قارن', 'حلل', 'حمّل'];
        var infoWords = ['ما', 'كيف', 'لماذا', 'متى', 'أين', 'من', 'شرح', 'تعريف', 'مفهوم', 'دليل', 'طريقة'];
        var hasAction = false, hasInfo = false;
        actionWords.forEach(function(w) { if (title.includes(w)) hasAction = true; });
        infoWords.forEach(function(w) { if (title.includes(w)) hasInfo = true; });
        if (hasAction && htmlCode.length > 0) intentScore = 10;
        else if (hasInfo && longDesc.length > 200) intentScore = 8;
        else if (htmlCode.length > 0) intentScore = 7;
        else if (longDesc.length > 200) intentScore = 6;
    }
    results.intent = { status: intentScore >= 7 ? 'pass' : (intentScore >= 4 ? 'warn' : 'fail'), text: intentScore >= 7 ? 'متوافق ✓' : (intentScore >= 4 ? 'ضعيف نوعاً ما' : 'غير متوافق') };
    score += intentScore;

    // === 8. Semantic Depth ===
    var semanticScore = 0;
    var headingCount = 0;
    var listCount = 0;
    if (longDesc) {
        headingCount = (longDesc.match(/<h[2-4][^>]*>/gi) || []).length;
        listCount = (longDesc.match(/<li[^>]*>/gi) || []).length;
        var paragraphCount = (longDesc.match(/<p[^>]*>/gi) || []).length;
        var uniqueWords = {};
        longDesc.split(/\s+/).forEach(function(w) { w = w.toLowerCase().replace(/[^\w\u0600-\u06FF]/g,''); if (w.length >= 3) uniqueWords[w] = true; });
        var uniqueCount = Object.keys(uniqueWords).length;
        semanticScore = Math.min(10, Math.round(headingCount * 1.5 + listCount * 0.3 + paragraphCount * 0.5 + (uniqueCount > 100 ? 3 : uniqueCount > 50 ? 1.5 : 0)));
    }
    results.semantic = { status: semanticScore >= 7 ? 'pass' : (semanticScore >= 4 ? 'warn' : 'fail'), text: semanticScore >= 7 ? 'غني دلالياً ✓' : (semanticScore >= 4 ? 'مقبول' : 'ضعيف') };
    score += Math.round(semanticScore * 0.8);

    // === 9. Core Web Vitals ===
    var cwvScore = 5;
    if (cssCode) {
        if (cssCode.includes('@media')) cwvScore += 1;
        if (cssCode.includes('flex') || cssCode.includes('grid')) cwvScore += 1;
        if (!cssCode.includes('!important')) cwvScore += 1;
    }
    if (htmlCode) {
        if (htmlCode.includes('loading="lazy"')) cwvScore += 1;
        if (!htmlCode.match(/<img[^>]+style="[^"]*width/i) && htmlCode.includes('<img')) cwvScore += 0.5;
    }
    cwvScore = Math.min(10, Math.round(cwvScore));
    results.cwv = { status: cwvScore >= 7 ? 'pass' : (cwvScore >= 4 ? 'warn' : 'fail'), text: cwvScore >= 7 ? 'جيد ✓' : (cwvScore >= 4 ? 'مقبول' : 'بحاجة تحسين') };
    score += Math.round(cwvScore * 0.6);

    // === 10. Programmatic SEO ===
    var progScore = 0;
    if (longDesc) {
        if (headingCount >= 3) progScore += 2;
        if (listCount >= 3) progScore += 2;
        if (longDesc.includes('<table') || longDesc.includes('<figure')) progScore += 1;
    }
    if (htmlCode) {
        if (htmlCode.includes('id=') || htmlCode.includes('class=')) progScore += 2;
        if (htmlCode.includes('onclick') || htmlCode.includes('addEventListener')) progScore += 1;
    }
    progScore = Math.min(10, Math.round(progScore));
    results.prog = { status: progScore >= 6 ? 'pass' : (progScore >= 3 ? 'warn' : 'fail'), text: progScore >= 6 ? 'منظم ✓' : (progScore >= 3 ? 'مقبول' : 'غير منظم') };
    score += Math.round(progScore * 0.5);

    // Title Keywords Tracker
    var missingTitleWords = [];
    var presentTitleWords = [];
    if (title) {
        var ignoreWords = ['في', 'من', 'على', 'إلى', 'عن', 'مع', 'أو', 'ثم', 'التي', 'الذي', 'هذا', 'هذه', 'بين', 'خلال', 'تحت', 'فوق', 'بعد', 'قبل', 'كل', 'أدوات', 'أداة', 'تحميل', 'برنامج', 'موقع', 'مجانا', 'مجاني', 'أونلاين', 'عبر', 'طريقة', 'كيفية', 'كيف'];
        var titleWords = title.split(/\s+/).map(function(w) {
            return w.replace(/[^\u0600-\u06FF\w]/g, '').trim();
        }).filter(function(w) {
            return w.length >= 2 && ignoreWords.indexOf(w) === -1;
        });

        var uniqueTitleWords = [];
        titleWords.forEach(function(w) {
            if (uniqueTitleWords.indexOf(w) === -1) uniqueTitleWords.push(w);
        });

        var searchContent = (longDesc + ' ' + (document.getElementById('shortDescAr')?.value || '') + ' ' + getFaqText()).toLowerCase();
        uniqueTitleWords.forEach(function(w) {
            var re = new RegExp(regEscape(w), 'i');
            if (re.test(searchContent)) {
                presentTitleWords.push(w);
            } else {
                missingTitleWords.push(w);
            }
        });
    }

    // LSI Keywords Tracker
    var missingLsiWords = [];
    var presentLsiWords = [];
    if (title) {
        var lsiSuggestions = generateLsi(title);
        var searchContent = (longDesc + ' ' + getFaqText()).toLowerCase();
        lsiSuggestions.forEach(function(w) {
            var re = new RegExp(regEscape(w), 'i');
            if (re.test(searchContent)) {
                presentLsiWords.push(w);
            } else {
                missingLsiWords.push(w);
            }
        });
    }

    // === Final ===
    score = Math.min(score, maxScore);
    updateSeoUi(score, results, notes, eeatExp, eeatExpRise, eeatAuth, eeatTrust, eeatDetails, missingTitleWords, missingLsiWords, presentTitleWords, presentLsiWords);
}

function resetSeo() {
    document.getElementById('seoScoreNum').textContent = '0';
    document.getElementById('seoScoreBadge').textContent = '0%';
    var arc = document.getElementById('seoDonutArc');
    if (arc) arc.setAttribute('stroke-dashoffset', '314');
    document.getElementById('seoVerdict').innerHTML = '<div class="seo-verdict-icon">📝</div><div class="seo-verdict-text">أدخل عنوان الأداة والوصف لبدء التحليل</div>';
    var allIds = { title:'Title', meta:'Meta', short:'Short', long:'Long', keywords:'Keywords', lsikeywords:'Lsi', intent:'Intent', semantic:'Semantic', cwv:'Cwv', prog:'Prog' };
    Object.keys(allIds).forEach(function(k) {
        var v = document.getElementById('seoDetail' + allIds[k]);
        var s = document.getElementById('seoStatus' + allIds[k]);
        if (v) v.textContent = 'في انتظار الإدخال';
        if (s) { s.textContent = '⏳'; s.className = 'seo-detail-status'; }
    });
    ['eeatExpScore','eeatExpRiseScore','eeatAuthScore','eeatTrustScore'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) { el.textContent = '-'; el.className = 'seo-eeat-score'; }
    });
    ['eeatExpProgress','eeatExpRiseProgress','eeatAuthProgress','eeatTrustProgress'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.style.width = '0%';
    });
    ['eeatExpList','eeatExpRiseList','eeatAuthList','eeatTrustList'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.innerHTML = '';
    });
    var missingTitle = document.getElementById('missingTitleKeywords');
    if (missingTitle) missingTitle.innerHTML = '<span style="font-size:0.75rem;color:var(--gray-light);">لا توجد كلمات مفتاحية للاستخراج. أدخل عنواناً للأداة.</span>';
    var missingLsi = document.getElementById('missingLsiKeywords');
    if (missingLsi) missingLsi.innerHTML = '<span style="font-size:0.75rem;color:var(--gray-light);">لا توجد كلمات LSI مقترحة حالياً. أدخل عنواناً مناسباً.</span>';
    document.getElementById('seoNotesList').innerHTML = '<li class="seo-note seo-note-info">انتظر إدخال البيانات لبدء التحليل...</li>';
}

function updateSeoUi(score, results, notes, eeatExp, eeatExpRise, eeatAuth, eeatTrust, eeatDetails, missingTitleWords, missingLsiWords, presentTitleWords, presentLsiWords) {
    // Score number
    document.getElementById('seoScoreNum').textContent = score;
    document.getElementById('seoScoreBadge').textContent = score + '%';

    // Donut arc
    var arc = document.getElementById('seoDonutArc');
    var circumference = 314;
    var offset = circumference - (score / 100 * circumference);
    if (arc) {
        arc.setAttribute('stroke-dashoffset', offset);
        arc.setAttribute('stroke', score >= 80 ? '#10B981' : (score >= 50 ? '#F59E0B' : '#EF4444'));
    }

    // Verdict
    var verdictEl = document.getElementById('seoVerdict');
    var verdict, icon;
    if (score >= 80) { verdict = 'ممتاز! المحتوى جاهز للنشر'; icon = '🎯'; }
    else if (score >= 60) { verdict = 'جيد، مع بعض التحسينات'; icon = '📈'; }
    else if (score >= 40) { verdict = 'ضعيف، يُنصح بالتحسين'; icon = '⚠️'; }
    else { verdict = 'غير كافٍ، أضف محتوى أكثر'; icon = '🔴'; }
    if (verdictEl) verdictEl.innerHTML = '<div class="seo-verdict-icon">' + icon + '</div><div class="seo-verdict-text">' + verdict + '</div>';

    // Detail cards
    var detailIds = { title:'Title', meta:'Meta', short:'Short', long:'Long', keywords:'Keywords', lsikeywords:'Lsi', intent:'Intent', semantic:'Semantic', cwv:'Cwv', prog:'Prog' };
    var statusMap = { pass: '✅', fail: '❌', warn: '⚠️', info: 'ℹ️' };
    var colorMap = { pass: 'pass', fail: 'fail', warn: 'warn', info: 'pass' };
    Object.keys(results).forEach(function(k) {
        var id = detailIds[k] || (k.charAt(0).toUpperCase() + k.slice(1));
        var v = document.getElementById('seoDetail' + id);
        var s = document.getElementById('seoStatus' + id);
        var r = results[k];
        if (v) v.textContent = r.text;
        if (s) { s.textContent = statusMap[r.status] || '✅'; s.className = 'seo-detail-status ' + (colorMap[r.status] || ''); }
    });

    // E-E-A-T Scores
    var eeatEls = { eeatExpScore: eeatExp, eeatExpRiseScore: eeatExpRise, eeatAuthScore: eeatAuth, eeatTrustScore: eeatTrust };
    Object.keys(eeatEls).forEach(function(id) {
        var el = document.getElementById(id);
        var val = eeatEls[id];
        if (el) {
            el.textContent = val != null ? val + '/10' : '-';
            el.className = 'seo-eeat-score ' + (val >= 9 ? 'excellent' : (val >= 7 ? 'good' : (val >= 4 ? 'fair' : 'poor')));
        }
    });

    // E-E-A-T Progress fills
    var expProgress = document.getElementById('eeatExpProgress');
    if (expProgress) expProgress.style.width = (eeatExp * 10) + '%';
    var expRiseProgress = document.getElementById('eeatExpRiseProgress');
    if (expRiseProgress) expRiseProgress.style.width = (eeatExpRise * 10) + '%';
    var authProgress = document.getElementById('eeatAuthProgress');
    if (authProgress) authProgress.style.width = (eeatAuth * 10) + '%';
    var trustProgress = document.getElementById('eeatTrustProgress');
    if (trustProgress) trustProgress.style.width = (eeatTrust * 10) + '%';

    // E-E-A-T Checklists
    if (eeatDetails) {
        renderChecklist('eeatExpList', eeatDetails.experience.items);
        renderChecklist('eeatExpRiseList', eeatDetails.expertise.items);
        renderChecklist('eeatAuthList', eeatDetails.authority.items);
        renderChecklist('eeatTrustList', eeatDetails.trust.items);
    }

    // Render Keyword Tracker
    var missingTitleBox = document.getElementById('missingTitleKeywords');
    if (missingTitleBox) {
        var html = '';
        if (presentTitleWords && presentTitleWords.length > 0) {
            presentTitleWords.forEach(function(w) {
                html += '<span class="keyword-badge present">✓ ' + w + '</span>';
            });
        }
        if (missingTitleWords && missingTitleWords.length > 0) {
            missingTitleWords.forEach(function(w) {
                html += '<span class="keyword-badge missing" onclick="insertKeywordIntoEditor(\'' + w + '\')" title="اضغط لإدراج الكلمة في المحتوى">+ ' + w + '</span>';
            });
        }
        if (html === '') html = '<span style="font-size:0.75rem;color:var(--gray-light);">لا توجد كلمات مفتاحية للاستخراج. أدخل عنواناً للأداة.</span>';
        missingTitleBox.innerHTML = html;
    }

    var missingLsiBox = document.getElementById('missingLsiKeywords');
    if (missingLsiBox) {
        var html = '';
        if (presentLsiWords && presentLsiWords.length > 0) {
            presentLsiWords.forEach(function(w) {
                html += '<span class="keyword-badge present">✓ ' + w + '</span>';
            });
        }
        if (missingLsiWords && missingLsiWords.length > 0) {
            missingLsiWords.forEach(function(w) {
                html += '<span class="keyword-badge lsi-missing" onclick="insertKeywordIntoEditor(\'' + w + '\')" title="اضغط لإدراج الكلمة في المحتوى">+ ' + w + '</span>';
            });
        }
        if (html === '') html = '<span style="font-size:0.75rem;color:var(--gray-light);">لا توجد كلمات LSI مقترحة حالياً. أدخل عنواناً مناسباً.</span>';
        missingLsiBox.innerHTML = html;
    }

    // Notes
    var list = document.getElementById('seoNotesList');
    if (list) {
        if (notes.length === 0) {
            list.innerHTML = '<li class="seo-note seo-note-pass">🎉 لا توجد ملاحظات! المحتوى متوافق مع معايير SEO.</li>';
        } else {
            list.innerHTML = notes.map(function(n) {
                var cls = 'seo-note seo-note-' + n.type;
                var icons = { pass: '✅', fail: '❌', warn: '⚠️', info: '💡' };
                return '<li class="' + cls + '">' + (icons[n.type] || '•') + ' ' + n.text + '</li>';
            }).join('');
        }
    }
}

function renderChecklist(elementId, items) {
    var el = document.getElementById(elementId);
    if (!el) return;
    el.innerHTML = items.map(function(item) {
        var cls = item.passed ? 'eeat-check-item passed' : 'eeat-check-item failed';
        var icon = item.passed ? '✓' : '✗';
        return '<div class="' + cls + '"><span>' + icon + '</span><span>' + item.text + '</span></div>';
    }).join('');
}

function insertKeywordIntoEditor(word) {
    if (window.tinymce && tinymce.activeEditor) {
        tinymce.activeEditor.execCommand('mceInsertContent', false, ' ' + word + ' ');
        showToast('تم إدراج الكلمة: ' + word, 'success');
        runSeoAnalysis();
    } else {
        showToast('يرجى النقر داخل محرر النصوص أولاً لإدراج الكلمة.', 'error');
    }
}

function getFaqText() {
    var txt = '';
    document.querySelectorAll('[name="faq_question_ar[]"]').forEach(function(el) { txt += ' ' + el.value; });
    document.querySelectorAll('[name="faq_answer_ar[]"]').forEach(function(el) { txt += ' ' + el.value; });
    return txt;
}

function extractKeywords(text) {
    var words = text.split(/\s+/).filter(function(w) { return w.length >= 2; });
    var freq = {};
    words.forEach(function(w) { w = w.toLowerCase().replace(/[^\w\u0600-\u06FF]/g,''); if (w.length >= 2) freq[w] = (freq[w] || 0) + 1; });
    return Object.keys(freq).sort(function(a,b) { return freq[b] - freq[a]; });
}

function generateLsi(title) {
    var lsiMap = {
        'أداة': ['أداة احترافية','أفضل أداة مجانية','أداة متعددة الوظائف','منصة متكاملة','حل مبتكر','تطبيق عملي','خدمة ذكية','أداة سريعة','وسيلة فعالة','مساعد رقمي'],
        'نص': ['تحرير النصوص','كتابة المحتوى','صياغة احترافية','تنسيق النص','معالجة الكلمات','إدارة المحتوى','هيكلة النصوص','تحسين الجمل','مراجعة لغوية','إنشاء مقالات'],
        'كتابة': ['كتابة إبداعية','تحرير المقالات','صياغة المحتوى','تأليف النصوص','تدوين احترافي','إنشاء محتوى','مراجعة النصوص','إملاء صوتي','كتابة تقارير','تحسين الأسلوب'],
        'عدد': ['عمليات حسابية','إحصاء دقيق','حساب إجمالي','تحليل إحصائي','قيمة رقمية','معادلات رياضية','نسبة مئوية','أرقام دقيقة','إحصاءات شاملة','كمية مضبوطة'],
        'حرف': ['عدد الحروف','تحليل النص','إحصاء الكلمات','مدقق إملائي','تنسيق الأحرف','تحويل الخطوط','محرر نصوص','تصحيح إملائي','ترقيم الصفحات','طباعة النص'],
        'صورة': ['معالجة الصور','تصميم جرافيك','تحرير الصور','اقتصاص ذكي','ضغط الصور','تحويل التنسيق','تعديل الخلفية','تحسين الجودة','رسوميات احترافية','فوتو شوب أونلاين'],
        'تحويل': ['محول ملفات','تحويل التنسيق','تغيير الصيغة','تحويل مستندات','تحويل صيغ','محول أونلاين','تحويل فوري','تحويل دفعة','تحويل مباشر','تغيير الامتداد'],
        'ضغط': ['ضغط الملفات','تصغير الحجم','تقليل المساحة','تحسين التخزين','ضاغط قوي','تقليص الحجم','زيادة السرعة','جودة عالية','حجم صغير','كيلوبايت أقل'],
        'pdf': ['تحرير PDF','دمج مستندات','تقسيم الصفحات','استخراج النصوص','توقيع إلكتروني','قارئ PDF','تحويل إلى PDF','ضغط PDF','تعديل PDF','إدارة المستندات'],
        'حاسبة': ['آلة حاسبة ذكية','حساب النسبة','ضريبة القيمة المضافة','خصم نسبة','حساب الفائدة','تحويل العملات','نسبة الربح','حساب الخصم','ضريبة دخل','ميزانية شهرية'],
        'ترجمة': ['ترجمة فورية','مترجم ذكي','ترجمة نصوص','قاموس عربي','ترجمة لغات','ترجمة احترافية','مفردات متقدمة','ترجمة دقيقة','مكتبة لغوية','فهم النصوص'],
        'محرر': ['محرر متقدم','تحرير احترافي','تعديل النصوص','مراجعة شاملة','تصحيح تلقائي','إدراج وسائط','حذف ذكي','إستبدال سريع','تنسيق متقدم','معالج نصوص'],
        'بحث': ['بحث ذكي','استعلام متقدم','نتائج دقيقة','تحسين البحث','فلاتر بحث','محرك بحث','بحث سريع','تصنيف نتائج','ترتيب أولويات','اقتراحات بحث'],
        'ربح': ['حساب الربح','هامش ربح','صافي الربح','إجمالي الربح','نسبة الربحية','عائد استثمار','تحليل مالي','تقدير أرباح','ميزانية تقديرية','جدول أرباح'],
        'وزن': ['حساب الوزن','تحويل وحدات','مقاييس وزن','كتلة حجم','ميزان رقمي','تحويل الكتلة','جدول تحويل','وحدات قياس','نظام متري','وزن مثالي'],
        'مساحة': ['حساب المساحة','تحويل مساحات','وحدات مساحة','مساحة أرض','متر مربع','هكتار','فدان','قياس مساحة','مساحة بناء','مساحة غرفة'],
        'طول': ['تحويل الطول','وحدات طول','قياس المسافة','متر سنتيمتر','بوصة قدم','مسافات دقيقة','جدول تحويل','نظام قياس','أبعاد دقيقة','حساب الطول'],
        'وقت': ['إدارة الوقت','تحويل الزمن','منطقة زمنية','فارق التوقيت','حساب المدة','جدول زمني','مواعيد دقيقة','توقيت عالمي','ساعة توقيت','تحويل تاريخ'],
        'سرعة': ['حساب السرعة','تحويل السرعة','وحدات السرعة','كيلومتر ساعة','متر ثانية','سرعة متوسطة','سرعة قصوى','مسافة زمن','تسارع','سرعة لحظية'],
        'توليد': ['توليد المحتوى','إنشاء نصوص','توليد تلقائي','ذكاء اصطناعي','كتابة ذكية','توليد أفكار','إنشاء تقارير','توليد كود','مساعد كتابة','إبداع رقمي']
    };
    var suggestions = [];
    var titleLower = title.toLowerCase();
    var matchedKeys = [];
    Object.keys(lsiMap).forEach(function(k) {
        if (titleLower.includes(k)) {
            matchedKeys.push(k);
            lsiMap[k].forEach(function(w) {
                if (suggestions.indexOf(w) === -1) suggestions.push(w);
            });
        }
    });
    // Generate longer contextual phrases (3-5 words) based on matched categories
    var phraseTemplates = {
        'أداة': ['كيفية استخدام الأداة بشكل احترافي', 'أفضل ممارسات الأداة للمبتدئين', 'دليل شامل لأدوات تحسين الإنتاجية', 'مقارنة بين الأدوات المتاحة أونلاين'],
        'نص': ['تقنيات متقدمة في تحرير النصوص العربية', 'أفضل طرق تنسيق المحتوى للنشر الإلكتروني', 'دليل كتابة المحتوى المتوافق مع معايير السيو', 'أسرار الصياغة الاحترافية للمقالات الطويلة'],
        'كتابة': ['كيف تكتب محتوى متوافق معSEO', 'أدوات الكتابة بالذكاء الاصطناعي', 'تقنيات تحسين الأسلوب الكتابي', 'دليل الكتابة الإبداعية للمحتوى الرقمي'],
        'عدد': ['طرق تحسين دقة الحسابات الإحصائية', 'دليل العمليات الحسابية المتقدمة', 'أفضل أدوات التحليل الرقمي أونلاين', 'تقنيات حساب النسب المئوية بدقة'],
        'صورة': ['معالجة الصور عبر الإنترنت بدون برامج', 'أفضل أدوات تحرير الصور المجانية', 'تقنيات ضغط الصور مع الحفاظ على الجودة', 'دليل تصميم الصور الاحترافي أونلاين'],
        'تحويل': ['محول الملفات الأسرع والأدق أونلاين', 'دليل تحويل صيغ المستندات بكفاءة', 'أفضل أدوات تحويل الفيديو مجاناً', 'تحويل الصور بين التنسيقات المختلفة'],
        'ضغط': ['تقنيات ضغط الملفات مع الحفاظ على الجودة', 'أفضل أدوات ضغط الصور أونلاين', 'كيف تقلل حجم الملفات بدون برامج', 'دليل تحسين سعة التخزين الرقمية'],
        'pdf': ['كيفية تعديل ملفات PDF أونلاين مجاناً', 'أفضل أدوات دمج وتقسيم المستندات', 'تحويل المستندات الورقية إلى PDF', 'دليل استخراج النصوص من PDF بسهولة'],
        'ترجمة': ['ترجمة احترافية للنصوص الطويلة أونلاين', 'أفضل أدوات الترجمة الفورية المجانية', 'كيف تحسن جودة الترجمة الآلية', 'دليل المترجم الذكي للنصوص التقنية'],
        'محرر': ['أفضل محررات النصوص المتقدمة أونلاين', 'تقنيات التحرير السريع للمحتوى', 'دليل استخدام أدوات التحرير الاحترافية', 'محرر متكامل لكتابة وتنسيق المقالات'],
        'بحث': ['كيف تحسن جودة نتائج البحث العلمي', 'أدوات البحث المتقدم في قواعد البيانات', 'تقنيات تحليل نتائج البحث بدقة', 'محركات البحث المتخصصة للمحتوى العربي'],
        'توليد': ['توليد المحتوى بالذكاء الاصطناعي', 'كيف تستخدم أدوات التوليد التلقائي', 'أفضل أدوات كتابة المحتوى الذكية', 'دليل توليد الأفكار الإبداعية للنصوص']
    };
    // Add multi-word phrases from templates
    matchedKeys.forEach(function(k) {
        if (phraseTemplates[k]) {
            phraseTemplates[k].forEach(function(p) {
                if (suggestions.indexOf(p) === -1) suggestions.push(p);
            });
        }
    });
    // Also check title for partial matches to add more phrases
    var fallbackPhrases = [
        'سهل الاستخدام للمبتدئين', 'أداة مجانية متكاملة أونلاين',
        'دليل استخدام احترافي خطوة بخطوة', 'أفضل بدائل مجانية متاحة',
        'نصائح وحيل لتحقيق أقصى استفادة', 'مقارنة شاملة مع أدوات منافسة',
        'تحسين تجربة المستخدم وزيادة الإنتاجية', 'معايير الجودة والأداء العالي',
        'تكامل مع أدوات وخدمات أخرى', 'آخر التحديثات والإصدارات الحديثة',
        'دعم فني ومساعدة عبر الإنترنت', 'مجتمع المستخدمين والتقييمات'
    ];
    if (suggestions.length < 6) {
        fallbackPhrases.forEach(function(p) {
            if (suggestions.indexOf(p) === -1) suggestions.push(p);
        });
    }
    return suggestions.slice(0, 20);
}

function stripHtml(html) {
    if (!html) return '';
    var d = document.createElement('div');
    d.innerHTML = html;
    return d.textContent || d.innerText || '';
}

function regEscape(str) {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Run SEO analysis on load
setTimeout(runSeoAnalysis, 500);

// ===== TinyMCE Editor Integration =====
['editor_ar', 'editor_en', 'editor_fr'].forEach(function(id) {
    var isRTL = (id === 'editor_ar');
    var editorLang = isRTL ? 'ar' : 'en';
    tinymce.init({
        selector: '#' + id,
        height: 450,
        directionality: isRTL ? 'rtl' : 'ltr',
        language: editorLang,
        plugins: 'lists link image table code charmap preview anchor searchreplace visualblocks fullscreen insertdatetime media wordcount',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist | link image table blockquote | code fullscreen',
        branding: false,
        menubar: true,
        statusbar: true,
        promotion: false,
        content_style: "@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap'); body { font-family: 'Cairo', sans-serif; font-size: 14px; line-height: 1.8; color: #0f172a; padding: 15px; } h2, h3, h4 { font-weight: 700; color: #1e293b; } a { color: #6366F1; text-decoration: underline; } blockquote { border-right: 4px solid #6366F1; margin: 1.5em 10px; padding: 0.5em 10px; background: #eef2ff; border-radius: 4px; color: #475569; }",
        setup: function(editor) {
            editor.on('init', function() {
                if (id === 'editor_ar') {
                    runSeoAnalysis();
                }
            });
            editor.on('input change keyup NodeChange', function() {
                if (id === 'editor_ar') {
                    runSeoAnalysis();
                }
            });
        }
    });
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
