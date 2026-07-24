# ربط تقييم أدلة ToolRar مع Cloudflare D1

هذا المجلد يحتوي نقطة API جاهزة تحفظ تصويتًا واحدًا لكل دليل وبصمة مجهّلة. لا يُخزَّن عنوان IP الخام؛ يُستخدم مع وكيل المتصفح وسر سري لإنشاء تجزئة SHA-256 فقط.

## 1. إنشاء قاعدة D1

من هذا المجلد شغّل:

```powershell
npx wrangler@latest login
npx wrangler@latest d1 create toolrar-feedback
```

انسخ `database_id` الناتج إلى نسخة باسم `wrangler.toml` من ملف `wrangler.toml.example`.

## 2. إنشاء الجدول

```powershell
npx wrangler@latest d1 execute toolrar-feedback --remote --file=./schema.sql
```

## 3. نشر الـWorker

```powershell
npx wrangler@latest deploy
```

## 4. إضافة السر

لا تضع السر داخل Git أو `wrangler.toml`. أضفه بعد النشر هكذا:

```powershell
npx wrangler@latest secret put FEEDBACK_SALT
```

استخدم قيمة عشوائية طويلة لا تقل عن 32 محرفًا.

## 5. إضافة المسار

اربط الـWorker بالمسار:

```text
www.toolrar.com/api/knowledge-feedback*
```

يمكن إضافة المسار من Workers & Pages → Worker → Settings → Domains & Routes. لأن الواجهة تستدعي `/api/knowledge-feedback` من النطاق نفسه، لا يلزم تغيير صفحات HTML.

## 6. اختبار سريع

قراءة الإحصاءات:

```powershell
curl "https://www.toolrar.com/api/knowledge-feedback?page=%2Fknowledge%2Fgeneral%2Fqr-generator"
```

تسجيل تقييم:

```powershell
curl -X POST "https://www.toolrar.com/api/knowledge-feedback" -H "Content-Type: application/json" -d "{\"page\":\"/knowledge/general/qr-generator\",\"vote\":\"up\"}"
```

## ملاحظات الثقة والبيانات المنظمة

- يمكن للزائر تغيير رأيه؛ سجل الصفحة والبصمة يُحدَّث بدل إنشاء أصوات متكررة.
- الحماية الحالية تقلل التصويت المكرر، لكنها ليست بديلًا عن Cloudflare Rate Limiting أو Turnstile إذا ظهر إساءة استخدام.
- ملف `feedback.js` يضيف أعداد `LikeAction` و`DislikeAction` إلى `Article.interactionStatistic` بعد وصول الأرقام الحقيقية من D1.
- لا يحوّل النظام 👍/👎 إلى نجوم أو `AggregateRating`، لأن ذلك يوحي بمقياس تقييم لم يجمعه الموقع فعلًا.
- أفضل ظهور لمحركات البحث يتحقق عندما تُحقن الأعداد من الخادم داخل HTML أيضًا. التحديث الديناميكي في المتصفح صحيح دلاليًا، لكن ظهور نتيجة منسقة ليس مضمونًا.
