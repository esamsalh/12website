const fs = require('fs');
const path = require('path');
const countries = require('./data/countries.js');
const countrySeeds = require('./data/countries.js').countryEeatSeeds || {};

const baseDir = __dirname;
if (!fs.existsSync(baseDir)) fs.mkdirSync(baseDir, { recursive: true });

const karats = [
  { id: '24k', name: '24', factor: 1.0, color: 'from-yellow-400 to-yellow-600', activeRing: 'ring-4 ring-yellow-400' },
  { id: '22k', name: '22', factor: 22/24, color: 'from-yellow-500 to-amber-600', activeRing: 'ring-4 ring-amber-500' },
  { id: '21k', name: '21', factor: 21/24, color: 'from-orange-400 to-orange-600', activeRing: 'ring-4 ring-orange-400' },
  { id: '18k', name: '18', factor: 18/24, color: 'from-amber-600 to-amber-800', activeRing: 'ring-4 ring-amber-600' }
];

function getFlagPath(code, relPath) { return relPath + `General/gold_price/flag-icons/flags/4x3/${code === 'uk' ? 'gb' : code}.svg`; }
function getRelPath(isKaratPage) { return isKaratPage ? '../../../' : '../../'; }

function buildEeatContent(country) {
  const s = countrySeeds[country.code] || countrySeeds.us;
  const popularKarat = country.popularKarat || '21';
  return `<div class="tool-desc-section">
  <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M2 12L12 2l10 10"/><path d="M12 2v20"/></svg> أسعار الذهب في ${country.name} مباشر</h2>
  <div class="tool-desc-text">
    <p>مرحباً بك في صفحة <strong>أسعار الذهب في ${country.name}</strong> المباشرة والمحدثة آنياً. نقدم لك في هذه الصفحة أسعار الذهب لجميع العيارات (24, 22, 21, 20, 18) مقومة بالعملة المحلية (<strong>${country.currency} - ${country.currencyCode}</strong>)، بالإضافة إلى سعر الأونصة والمؤشرات اليومية للتداول. سواء كنت مستثمراً تتطلع لشراء سبائك ذهبية أو مقبلاً على شراء المشغولات الذهبية، فإن هذه الصفحة تمنحك المعلومات الدقيقة والموثوقة لاتخاذ قرارك بثقة.</p>
    <h3>سوق الذهب في ${country.name}</h3>
    <p>${s.overview || ''} تقع أهم أسواق الذهب في ${country.capital} و${country.cities}، حيث تنتشر محلات الصاغة المعتمدة التي تقدم تشكيلات واسعة من المشغولات الذهبية والسبائك. يختلف سعر الذهب في ${country.name} بناءً على السعر العالمي للأونصة مضافاً إليه تكاليف الشحن والتأمين والرسوم الجمركية حسب القوانين المحلية. ${country.marketDesc}.</p>
    <h3>أهمية الذهب في الثقافة المحلية</h3>
    <p>${s.culture || ''} ${country.cultureNote}. يفضل معظم المشترين في ${country.name} الذهب عيار ${country.popularKarat} كخيار مثالي يجمع بين المتانة واللون الذهبي الجذاب، خاصة في المشغولات والحلي. ويعتبر الذهب هدية ثمينة في المناسبات الاجتماعية كحفلات الزفاف والخطوبة والمناسبات الدينية.</p>
    <h3>نصائح لشراء الذهب في ${country.name}</h3>
    <p>قبل شراء الذهب في ${country.name}، ننصحك بالآتي: أولاً، تابع الأسعار المباشرة على هذه الصفحة لتعرف السعر العادل قبل التوجه للسوق. ثانياً، اسأل عن المصنعية وهي تكلفة التصنيع التي يضيفها الصائغ على سعر الذهب الخام، وتختلف من محل لآخر. ثالثاً، تأكد من وجود الدمغة (الختم) الذي يثبت عيار الذهب ونقائه. رابعاً، احتفظ بفواتير الشراء فهي ضرورية لإعادة البيع أو الاستبدال. ${country.taxNote}.</p>
    <h3>الاستثمار في الذهب في ${country.name}</h3>
    <p>${s.investment || ''} ${country.investmentNote}. يتجه المستثمرون إلى شراء السبائك الذهبية ذات الأوزان المختلفة (1 جرام، 5 جرام، 10 جرام، 50 جرام، 100 جرام، كيلو جرام) والعملات الذهبية كالجنيهات والليرات. تعتبر المحافظ الاستثمارية الذهبية وسيلة فعالة لتنويع المخاطر والحماية من التضخم. نوصي دائماً بالشراء من مصادر موثوقة ومعتمدة لضمان نقاء الذهب وحقوقك.</p>
    <h3>العوامل المؤثرة على أسعار الذهب في ${country.name}</h3>
    <p>تتأثر أسعار الذهب في ${country.name} بعدة عوامل محلية وعالمية، من أبرزها: سعر الذهب العالمي في بورصة لندن ونيويورك، سعر صرف العملة المحلية مقابل الدولار الأميركي (${
      country.code === 'lb' || country.code === 'ye' || country.code === 'sy' || country.code === 'sd' ? 'حيث أن تقلبات سعر الصرف تؤثر بشكل مباشر على السعر المحلي' :
      'حيث أن قوة العملة المحلية تؤثر على القوة الشرائية للذهب'
    })، معدلات التضخم المحلية، الطلب الموسمي خلال المناسبات والأعياد، والسياسات النقدية للبنك المركزي المحلي. تابع صفحتنا المباشرة لمراقبة هذه التغيرات لحظة بلحظة.</p>
    <h3>مقارنة أسعار الذهب في ${country.name} مع الأسواق الأخرى</h3>
    <p>تختلف أسعار الذهب بين ${country.name} والدول المجاورة بسبب اختلاف الضرائب والرسوم الجمركية وتكاليف النقل. عادة ما يكون السعر العالمي للذهب موحداً بالدولار، لكن السعر المحلي يختلف حسب سعر الصرف والرسوم المحلية. ${country.taxNote}. في الصفحة الحالية نقدم لك السعر الفوري المستمد من الأسواق العالمية مباشرة، ليكون بمثابة مرجع لك قبل التوجه للسوق المحلي.</p>
  </div>
</div>`;
}

function buildFaqs(country) {
  const code = country.code;
  const faqAnswers = {
    us: ['يبلغ سعر أونصة الذهب في أمريكا اليوم حوالي 4612 دولاراً أمريكياً. يختلف السعر لحظياً حسب تحركات السوق.',
      'تخضع أرباح بيع الذهب لضريبة أرباح رأس المال (Capital Gains Tax) التي تتراوح بين 0% و28% حسب فترة الاحتفاظ.',
      'يمكن شراء السبائك الذهبية من بنوك كبرى مثل JPMorgan وHSBC أو من تجار معتمدين في نيويورك.',
      'رفع الفائدة يضعف جاذبية الذهب كاستثمار، بينما خفضها يزيد الإقبال عليه.'],
    sa: ['سعر الذهب عيار 21 في الرياض اليوم يختلف بناءً على السعر العالمي. تابع الصفحة للحصول على التحديث المباشر.',
      'عادة ما تكون الأسعار موحدة بين مناطق السعودية مع فروق طفيفة في المصنعية بين محل وآخر.',
      'تشتهر محلات الذهب في شارع الوزير بجدة وفي مجمع الذهب بالرياض كأشهر الوجهات.',
      'يزداد الطلب على الذهب في موسم الحج والعمرة بسبب زيادة السياحة والمناسبات.'],
    ae: ['سعر الذهب عيار 22 في دبي اليوم متاح مباشر أعلاه. يشتهر سوق دبي للذهب بتنافسية أسعاره.',
      'أفضل وقت للشراء هو فترة الظهيرة في أيام الأسبوع حيث يكون الذهب في أدنى سعر.',
      'أسعار الذهب في دبي وأبوظبي متشابهة مع فروق طفيفة في المصنعية.',
      'تختلف رسوم المصنعية في دبي حسب التصميم والتعقيد، وتكون عادة بين 5-20 درهماً للجرام.'],
    eg: ['سعر الذهب في مصر اليوم عيار 21 متاح مباشر في الصفحة. يتغير السعر عدة مرات يومياً.',
      'يقع أكبر تجمع لمحلات الذهب في منطقة الصاغة (شارع المعز) في القاهرة.',
      'تطبق ضريبة القيمة المضافة 14% على المصنعية وليس على سعر الذهب الخام.',
      'من أشهر محلات الذهب في الإسكندرية: سوق الذهب بشارع سعد زغلول ومحلات الصاغة في المنشية.'],
    tr: ['سعر الذهب عيار 22 في إسطنبول اليوم متاح مباشر أعلى الصفحة. يتأثر بسعر الليرة التركية.',
      'الجراند بازار في إسطنبول يضم مئات المحالات لكن الأسعار قد تكون أعلى من المناطق الأخرى.',
      'انخفاض الليرة التركية يرفع أسعار الذهب بالعملة المحلية بينما يبقى ثابتاً عالمياً.',
      'الذهب في تركيا قد يكون أقل سعراً منه في دبي عند المقارنة بعد التكلفة.'],
    uk: ['سعر أونصة الذهب في لندن اليوم بالجنيه الإسترليني موضح أعلى الصفحة.',
      'LBMA هي بورصة لندن للمعادن الثمينة التي تحدد السعر المرجعي العالمي للذهب مرتين يومياً.',
      'يمكن شراء الذهب بأمان من بنك HSBC أو مجموعة بولنغ أو تجار معتمدين في لندن.',
      'تسبب خروج بريطانيا من الاتحاد الأوروبي تقلبات في سعر الإسترليني مما أثر على أسعار الذهب.'],
    in: ['سعر الذهب عيار 22 في مومباي اليوم متاح مباشر أعلاه.',
      'الرسوم الجمركية على استيراد الذهب تبلغ 12.5% بالإضافة إلى ضريبة السلع 3% مما يرفع السعر المحلي.',
      'أفضل أوقات الشراء هي خلال انخفاض الأسعار العالمي وقبل مواسم الزواج والمهرجانات.',
      'يختلف السعر قليلاً بين المدن الهندية بسبب اختلاف تكاليف النقل والطلب المحلي.'],
    lb: ['سعر الذهب عيار 21 في بيروت اليوم بالليرة اللبنانية متاح مباشر أعلى الصفحة.',
      'تتركز أفضل محلات الذهب في منطقة فردان والجميزة في بيروت.',
      'يفضل الاستثمار في السبائك الصغيرة كوسيلة آمنة لحفظ المدخرات في لبنان.',
      'نعم، سعر الذهب في لبنان يتأثر مباشرة بسعر صرف الدولار في السوق الموازي.'],
    de: ['سعر الذهب في ألمانيا اليوم باليورو متاح في الجدول أعلاه.',
      'أفضل البنوك لشراء الذهب في فرانكفورت هي دويتشه بنك وكومرتس بنك.',
      'توفر البنوك الألمانية صناديق تخزين آمنة للذهب في خزائنها.',
      'الأسعار متقاربة بين ألمانيا وفرنسا نظراً لاستخدام نفس العملة (اليورو).'],
    cn: ['سعر الذهب في شانغهاي اليوم باليوان الصيني متاح مباشر أعلاه.',
      'السياسات الصينية تؤثر على سوق الذهب العالمي لأن الصين أكبر منتج ومستهلك.',
      'يمكن شراء الذهب عبر البورصة الصينية للذهب أو من البنوك الكبرى.',
      'الذهب في هونغ كونغ غالباً أقل سعراً بسبب عدم وجود ضريبة قيمة مضافة.'],
    ch: ['سعر الذهب في زيورخ اليوم بالفرنك السويسري متاح أعلى الصفحة.',
      'أشهر مصافي الذهب في سويسرا هي فالكمبي وMKS وPAMP.',
      'توفر البنوك السويسرية مثل UBS وكريدي سويس خدمات تخزين الذهب.',
      'سويسرا محايدة سياسياً ومصافيها موثوقة عالمياً مما يجعلها مركزاً لتجارة الذهب.'],
    dz: ['سعر الذهب عيار 18 في الجزائر اليوم بالدينار الجزائري متاح مباشر أعلاه.',
      'تتركز محلات الذهب في الجزائر العاصمة في منطقة ساحة الشهداء وشارع ديدوش.',
      'يجب البحث عن الدمغة الرسمية والتأكد من العيار عند الشراء.',
      'أسعار الذهب متقاربة بين الجزائر والمغرب مع اختلافات طفيفة في المصنعية.'],
    jo: ['سعر الذهب عيار 21 في عمان الأردن اليوم متاح مباشر أعلى الصفحة.',
      'يقع سوق الذهب الرئيسي في منطقة وسط البلد قرب المدرج الروماني.',
      'الليرة الرشادي والجنيه الذهبي من أكثر أنواع الذهب رواجاً للاستثمار.',
      'نعم، تختلف الأسعار قليلاً بسبب اختلاف الضرائب وسعر الصرف.'],
    iq: ['سعر الذهب عيار 21 في بغداد اليوم بالدينار العراقي موضح أعلاه.',
      'أشهر أسواق الذهب في العراق هي سوق الذهب في شارع الرشيد ببغداد وسوق أربيل.',
      'نعم، تختلف الأسعار بين بغداد وأربيل بسبب اختلاف الظروف الأمنية واللوجستية.',
      'يمكن شراء السبائك من محلات الصاغة المعتمدة أو البنوك العراقية.'],
    ma: ['سعر الذهب عيار 18 في الدار البيضاء اليوم بالدرهم المغربي متاح مباشر أعلاه.',
      'أفضل أسواق الذهب في المغرب: سوق الذهب في الدار البيضاء ومراكش وفاس.',
      'يمكن التمييز بالبحث عن الدمغة الرسمية المغربية وشهادة الضمان.',
      'نعم، تختلف الأسعار قليلاً بين المدن بسبب الطلب المحلي.'],
    ru: ['سعر الذهب في موسكو اليوم بالروبل الروسي متاح مباشر أعلاه.',
      'مناطق إنتاج الذهب الرئيسية في روسيا هي سيبيريا وكراسنويارسك وماغادان.',
      'انخفاض أسعار النفط قد يضعف الروبل مما يرفع سعر الذهب محلياً.',
      'الذهب استثمار جيد في روسيا خاصة في ظل العقوبات الاقتصادية.'],
    ca: ['سعر الذهب في تورونتو اليوم بالدولار الكندي متاح في الجدول أعلاه.',
      'أشهر شركات التعدين الكندية هي باريك غولد وأغنيكو إيغل.',
      'يمكن شراء السبائك الذهبية من البنوك الكندية الكبرى أو تجار معتمدين.',
      'الأسعار متقاربة بين كندا وأمريكا مع فارق في سعر الصرف.'],
    au: ['سعر الذهب في سيدني اليوم بالدولار الأسترالي متاح مباشر أعلاه.',
      'أشهر مناجم الذهب في أستراليا: سوبر بيت وبودينغتون وكاديا.',
      'يمكن الاستثمار عبر شراء أسهم شركات التعدين المدرجة في البورصة الأسترالية.',
      'أسعار الذهب متقاربة بين أستراليا ونيوزيلندا مع اختلاف سعر الصرف.'],
    za: ['سعر الذهب في جوهانسبرغ اليوم بالراند متاح مباشر أعلاه.',
      'أشهر مناجم الذهب: ويتراند ومبونينغ وتاون.', 'إنتاج الذهب يشكل جزءاً كبيراً من الناتج المحلي الجنوب أفريقي.',
      'نعم، توجد فرص استثمارية عبر شراء أسهم شركات التعدين المدرجة في البورصة.'],
    gh: ['سعر الذهب في أكرا اليوم متاح مباشر أعلى الصفحة.', 'مناطق التعدين: تاركوا وبريما وغرب غانا.',
      'الإنتاج المحلي يؤثر على السعر العالمي حيث أن غانا من أكبر المنتجين.', 'يجب الحصول على تراخيص رسمية للتصدير.'],
    br: ['سعر الذهب في ساو باولو اليوم بالريال البرازيلي متاح أعلاه.',
      'مناطق التعدين الرئيسية في البرازيل: بارا وماتو غروسو وميناس جيرايس.',
      'التعدين غير القانوني يؤثر على البيئة في غابات الأمازون.',
      'الذهب استثمار جيد في البرازيل للتحوط من التضخم وتقلبات الريال.'],
    mx: ['سعر الذهب في مكسيكو سيتي اليوم بالبيزو المكسيكي متاح مباشر أعلاه.',
      'أفضل شركات التعدين: فروزنله ونيومونت.',
      'انخفاض البيزو يرفع سعر الذهب بالعملة المحلية.',
      'يمكن الشراء من البنوك الكبرى أو تجار الذهب المعتمدين في مكسيكو سيتي.'],
    ua: [], uz: [], id: [], mr: [],
    pl: ['سعر الذهب عيار 18 في وارسو اليوم بالزلوتي متاح مباشر أعلاه.',
      'أفضل محلات الذهب توجد في وسط وارسو وكراكوف.',
      'البولنديون يشترون السبائك والعملات الذهبية كاستثمار طويل الأجل.',
      'الأسعار متقاربة بين بولندا وألمانيا مع فارق ضريبة القيمة المضافة.'],
    it: ['سعر الذهب عيار 18 في ميلانو اليوم باليورو متاح مباشر أعلاه.',
      'أشهر ماركات المجوهرات الإيطالية: بولغري وبوميلاتو.',
      'التصاميم الإيطالية الفاخرة ترفع سعر المشغولات الذهبية.',
      'يقع سوق الذهب في فيتشنزا في المنطقة الصناعية للحلي.'],
    jp: ['سعر الذهب في طوكيو اليوم بالين الياباني متاح مباشر أعلاه.',
      'أشهر متاجر الذهب في غينزا: تاناكا كينزو وغينزا تاناكا.',
      'ارتفاع الين يخفض سعر الذهب بالعملة المحلية.',
      'الذهب استثمار شائع في اليابان خاصة لمدخرات التقاعد.'],
    bh: ['سعر الذهب عيار 21 في المنامة اليوم متاح مباشر أعلاه.',
      'أفضل محلات الذهب موجودة في سوق المنامة المركزي.',
      'نعم، يمكن شراء الذهب بالتقسيط في بعض محلات البحرين.',
      'الأسعار متقاربة مع الخليج مع فروق طفيفة في المصنعية.'],
    kw: ['سعر الذهب في الكويت اليوم عيار 21 متاح مباشر أعلاه.',
      'يقع سوق الذهب في منطقة المباركية وسط مدينة الكويت.',
      'أفضل استثمار هو السبائك والجنيهات الذهبية.',
      'الأسعار متقاربة مع السعودية مع اختلاف سعر الصرف.'],
    om: ['سعر الذهب عيار 21 في مسقط اليوم متاح مباشر أعلاه.',
      'أفضل محلات الذهب في سوق مطرح ومركز مسقط الكبير.',
      'عادة ما يكون الذهب في عمان أقل من الإمارات بسبب عدم وجود ضريبة.',
      'يمكن الاستثمار عبر شراء السبائك من محلات الصاغة المعتمدة.'],
    qa: ['سعر الذهب عيار 22 في الدوحة اليوم متاح مباشر أعلاه.',
      'أفضل مجمع للذهب في قطر هو سوق الذهب في مجمع لاندمارك.',
      'الأسعار متقاربة مع الخليج مع اختلاف في رسوم المصنعية.',
      'يُشترط أن يكون عمر المشتري فوق 18 سنة وإحضار الهوية.'],
    ye: ['سعر الذهب في صنعاء اليوم متاح مباشر أعلاه.',
      'أشهر الأسواق: سوق الذهب في صنعاء القديمة وعدن.',
      'يمكن التأكد من النقاء بفحص الدمغة لدى محامص الذهب.',
      'نعم، سعر الذهب يتأثر بتقلبات سعر الصرف.'],
    ps: ['سعر الذهب عيار 21 في رام الله اليوم متاح مباشر أعلاه.',
      'أفضل محلات الذهب في رام الله ونابلس والخليل.',
      'نعم، تختلف الأسعار بين الضفة وغزة بسبب تكاليف النقل.',
      'الوضع الاقتصادي يؤثر على الطلب وبالتالي على السعر المحلي.'],
    lb: ['سعر الذهب عيار 21 في بيروت اليوم بالليرة اللبنانية متاح مباشر أعلاه.',
      'أفضل محلات الذهب في منطقة فردان والجميزة.', 'السبائك الصغيرة هي الأفضل للاستثمار الآمن.',
      'نعم، سعر الذهب في لبنان يتأثر بسعر صرف الدولار.'],
    sy: ['سعر الذهب عيار 21 في دمشق اليوم بالليرة السورية متاح مباشر أعلاه.',
      'يقع سوق الذهب في سوق الحميدية بدمشق القديمة.',
      'سعر الصرف الرسمي والموازي يؤثران بشكل مباشر على السعر.',
      'أفضل أنواع الذهب للادخار هي السبائك الصغيرة'],
    sd: ['سعر الذهب في السودان اليوم عيار 21 متاح مباشر.',
      'مناطق الإنتاج الرئيسية: ولاية نهر النيل والبحر الأحمر.',
      'الإنتاج المحلي يساعد في استقرار الأسعار.',
      'يجب الحصول على تصاريح رسمية لشراء كميات كبيرة.'],
    tn: ['سعر الذهب عيار 18 في تونس اليوم بالدينار التونسي متاح مباشر.',
      'أشهر محلات الذهب في شارع الحبيب بورقيبة بالعاصمة.', 'يمكن شراء السبائك من البنوك التونسية.',
      'الأسعار متقاربة بين تونس والجزائر مع اختلاف في الضرائب.'],
    mr: ['سعر الذهب في نواكشوط اليوم متاح مباشر أعلى الصفحة.',
      'منجم تازيازت يبعد 300 كلم شمال نواكشوط.', 'الاستثمار يتم عبر شركات التعدين المرخصة.',
      'الأسعار تتأثر بتكاليف النقل والشحن.'],
    ua: [], uz: ['سعر الذهب في طشقند اليوم بالسوم متاح مباشر أعلاه.',
      'منجم مورنتاو في نافوي هو من أكبر المناجم المكشوفة.',
      'يمكن الشراء من البنك المركزي الأوزبكي أو محلات الصاغة.', 'أوزبكستان من أكبر 10 منتجين عالمياً.'],
    id: ['سعر الذهب عيار 22 في جاكرتا اليوم بالروبية الإندونيسية متاح مباشر.',
      'منجم غراسبرغ في بابوا من أكبر مناجم الذهب عالمياً.',
      'يمكن الاستثمار عبر شراء الذهب في بورصة جاكرتا.',
      'الأسعار في جاكرتا أعلى من المناطق الأخرى بسبب الطلب.'],
    fr: ['سعر الذهب عيار 18 في باريس اليوم باليورو متاح مباشر أعلاه.',
      'أشهر محلات الذهب في باريس: في ساحة فاندوم وشارع الشانزليزيه.',
      'ضريبة المعادن الثمينة 11% على الأرباح عند إعادة البيع.',
      'الأسعار متقاربة مع ألمانيا نظراً لاستخدام العملة الموحدة.'],
    tr: ['سعر الذهب عيار 22 في إسطنبول اليوم مباشر أعلاه.',
      'أفضل محلات الذهب في الجراند بازار.', 'ارتفاع سعر الصرف يرفع سعر الذهب.',
      'الذهب في تركيا قد يكون أقل سعراً من دبي.'],
    ua: [], uz: [], id: [], mr: []
  };
  return country.faqQs.map((q, i) => {
    const answers = faqAnswers[country.code] || [];
    const a = answers[i] || `يرجى متابعة الصفحة للحصول على أحدث المعلومات حول ${q}`;
    return `<div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
    <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
      <span>${i + 1}. ${q}</span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><polyline points="6 9 12 15 18 9"/></svg>
    </div>
    <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
      <div itemprop="text">${a}</div>
    </div>
  </div>`;
  }).join('\n');
}

const templateHtml = `<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{TITLE}} | ToolRar</title>
  <meta name="description" content="{{META_DESC}}">
  <script type="application/ld+json">{{BREADCRUMB_JSONLD}}</script>
  <script type="application/ld+json">{{WEBSITE_JSONLD}}</script>
  <script type="application/ld+json">{{SCHEMA_JSONLD}}</script>
  <script type="application/ld+json">{{FAQ_JSONLD}}</script>
  <script type="application/ld+json">{{ARTICLE_JSONLD}}</script>
  <script type="application/ld+json">{{PRODUCT_JSONLD}}</script>
  <link rel="preload" href="../../admin/assets/fonts/cairo/cairo-v31-arabic.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="../../admin/assets/fonts/cairo/cairo-v31-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="preload" href="../../admin/assets/fonts/cairo/cairo-v31-latin-ext.woff2" as="font" type="font/woff2" crossorigin>
  <style>
    @font-face { font-family:'Cairo'; font-style:normal; font-weight:200 900; font-display:swap; src:url(../../admin/assets/fonts/cairo/cairo-v31-arabic.woff2) format('woff2'); unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC,U+102E0-102FB,U+10E60-10E7E,U+10EC2-10EC4,U+10EFC-10EFF,U+1EE00-1EE03,U+1EE05-1EE1F,U+1EE21-1EE22,U+1EE24,U+1EE27,U+1EE29-1EE32,U+1EE34-1EE37,U+1EE39,U+1EE3B,U+1EE42,U+1EE47,U+1EE49,U+1EE4B,U+1EE4D-1EE4F,U+1EE51-1EE52,U+1EE54,U+1EE57,U+1EE59,U+1EE5B,U+1EE5D,U+1EE5F,U+1EE61-1EE62,U+1EE64,U+1EE67-1EE6A,U+1EE6C-1EE72,U+1EE74-1EE77,U+1EE79-1EE7C,U+1EE7E,U+1EE80-1EE89,U+1EE8B-1EE9B,U+1EEA1-1EEA3,U+1EEA5-1EEA9,U+1EEAB-1EEBB,U+1EEF0-1EEF1; }
    @font-face { font-family:'Cairo'; font-style:normal; font-weight:200 900; font-display:swap; src:url(../../admin/assets/fonts/cairo/cairo-v31-latin-ext.woff2) format('woff2'); unicode-range:U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF; }
    @font-face { font-family:'Cairo'; font-style:normal; font-weight:200 900; font-display:swap; src:url(../../admin/assets/fonts/cairo/cairo-v31-latin.woff2) format('woff2'); unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth;overflow-x:hidden;width:100%}
    body{overflow-x:hidden;width:100%;position:relative;font-family:'Cairo',system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif;min-height:100vh;display:flex;flex-direction:column;transition:background-color .3s ease,color .3s ease;background:#fff;color:#1e293b;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;line-height:1.6}
    img,svg,video,canvas,iframe{max-width:100%;height:auto}
    a{text-decoration:none;color:inherit}
    .dark body{background:#080C1A;color:#e2e8f0}
    .dark .header{background:#080C1A;border-color:rgba(255,255,255,.03)}
    .dark .hero-section{background:#192746!important}
    .dark .hero-section::before{opacity:.03}
    .dark .page-section{background:#0F172A}
    .dark .bg-white,.dark .tool-interface,.dark .content-card,.dark .related-btn{background:#1E293B!important;border-color:#334155!important;color:#e2e8f0}
    .dark .tool-desc-text,.dark .content-desc-text{color:#e2e8f0}
    .dark .faq-item{border-color:#334155}
    .dark .faq-question{color:#e2e8f0}
    .dark .faq-question:hover{background:#1E293B}
    .dark .faq-answer{color:#94a3b8}
    .dark .footer{background:#080C1A}
    .dark .bg-gray-50{background:#15172a!important}
    .dark .text-gray-700,.dark .text-gray-800{color:#e2e8f0!important}
    .dark .border-gray-100,.dark .border-gray-200{border-color:#2d2f45!important}
    .dark .dropdown-menu{background:#1E293B;border-color:#334155;color:#fff!important}
    .dark .dropdown-item:hover{background:#2d2f45}
    .dark .price-up{color:#10b981}
    .dark .price-down{color:#ef4444}
    .dark .gold-table thead{background:#0F172A}
    .dark .gold-table td{border-color:#2d2f45}
    .container{max-width:1280px;margin:0 auto;padding:0 1rem;width:100%}
    @media(min-width:640px){.container{padding:0 1.5rem}}
    @media(min-width:1024px){.container{padding:0 2rem}}
    .header{background:#0F172A;position:sticky;top:0;z-index:50;border-bottom:1px solid rgba(255,255,255,.05)}
    .header-inner{display:flex;align-items:center;justify-content:space-between;height:64px}
    .logo{display:flex;align-items:center;gap:10px;text-decoration:none;flex-shrink:0}
    .logo-icon{width:36px;height:36px;background:#6366F1;border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(99,102,241,.3)}
    .logo-icon svg{width:20px;height:20px;color:#fff}
    .logo-text{font-size:1.4rem;font-weight:900;color:#fff;letter-spacing:-.5px}
    .desktop-nav{display:none;flex:1;gap:4px;margin:0 8px}
    @media(min-width:1024px){.desktop-nav{display:flex;align-items:center;justify-content:space-between}}
    .nav-links-center{display:flex;align-items:center;gap:2px;margin:0 auto}
    .nav-link{color:#94a3b8;text-decoration:none;font-size:.82rem;font-weight:500;padding:7px 12px;border-radius:8px;transition:all .2s;display:flex;align-items:center;gap:4px;background:none;border:none;font-family:inherit;cursor:pointer;white-space:nowrap}
    .nav-link:hover{color:#fff;background:rgba(255,255,255,.06)}
    .dropdown-chevron{width:13px;height:13px;transition:transform .2s}
    .dropdown-wrap{position:relative}
    .dropdown-menu{position:absolute;top:100%;right:0;margin-top:8px;background:#1E293B;border:1px solid rgba(55,65,81,.5);border-radius:12px;padding:8px;min-width:220px;opacity:0;visibility:hidden;transform:translateY(8px);transition:all .2s ease;z-index:100;color:#fff!important}
    .dropdown-wrap.open .dropdown-menu{opacity:1;visibility:visible;transform:translateY(0)}
    .cat-dropdown{display:grid;grid-template-columns:1fr;gap:4px;min-width:260px}
    @media(min-width:1200px){.cat-dropdown{grid-template-columns:1fr 1fr;min-width:500px}}
    .cat-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;transition:background .15s;color:#e2e8f0;font-size:.82rem;font-weight:500}
    .cat-item:hover{background:rgba(99,102,241,.1);color:#6366F1}
    .cat-item-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .cat-item-icon svg{width:14px;height:14px;color:#fff}
    .nav-controls{display:none;align-items:center;gap:4px}
    @media(min-width:1024px){.nav-controls{display:flex}}
    .lang-dropdown{min-width:155px}
    .lang-item{display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border:none;background:none;color:#cbd5e1;font-size:.85rem;font-family:inherit;cursor:pointer;border-radius:8px;transition:background .15s}
    .lang-item:hover{background:rgba(255,255,255,.05)}
    .lang-item.active{color:#6366F1}
    .lang-flag{font-size:1.05rem}
    .lang-check{width:16px;height:16px;color:#6366F1}
    .dark-toggle{display:flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;background:none;border:none;color:#94a3b8;font-family:inherit;font-size:.78rem;cursor:pointer;transition:all .2s;white-space:nowrap}
    .dark-toggle:hover{color:#fff;background:rgba(255,255,255,.06)}
    .dark-toggle svg{width:16px;height:16px}
    .mobile-controls{display:flex;align-items:center;gap:6px}
    @media(min-width:1024px){.mobile-controls{display:none}}
    .mobile-btn{width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.06);border:none;cursor:pointer;color:#94a3b8;transition:background .2s}
    .mobile-btn:hover{background:rgba(255,255,255,.1);color:#fff}
    .mobile-btn svg{width:20px;height:20px}
    .mobile-lang-dropdown{min-width:140px}
    .mobile-lang-item{display:block;width:100%;padding:10px 14px;border:none;background:none;color:#cbd5e1;font-size:.85rem;font-family:inherit;cursor:pointer;text-align:right;border-radius:8px}
    .mobile-lang-item:hover{background:rgba(255,255,255,.05)}
    .mobile-lang-item.active{color:#6366F1}
    .mobile-menu{display:none;flex-direction:column;padding:8px 0;border-top:1px solid rgba(255,255,255,.05)}
    .mobile-menu.open{display:flex}
    .mobile-menu a{padding:12px 16px;color:#94a3b8;text-decoration:none;font-size:.9rem;font-weight:500;border-radius:8px;transition:all .2s}
    .mobile-menu a:hover{color:#fff;background:rgba(255,255,255,.06)}
    .hero-section{position:relative;overflow:hidden;background:#192746;padding:40px 0 32px;width:100%}
    .hero-section::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at 20% 80%,rgba(255,255,255,.06) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(255,255,255,.04) 0%,transparent 50%);pointer-events:none}
    .hero-section .container{position:relative;z-index:1}
    .hero-inner{max-width:800px;margin:0 auto;text-align:center}
    .hero-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 16px;border-radius:9999px;background:rgba(255,255,255,.15);color:#fff;font-size:.75rem;font-weight:700;margin-bottom:12px;backdrop-filter:blur(4px)}
    .hero-badge svg{width:13px;height:13px}
    .hero-section h1{font-size:1.6rem;font-weight:900;margin-bottom:8px;line-height:1.35;color:#fff}
    @media(min-width:640px){.hero-section h1{font-size:2rem}}
    .hero-section p{color:rgba(255,255,255,.8);font-size:.92rem;max-width:580px;margin:0 auto;line-height:1.7}
    .page-section{background:#F8FAFC;flex:1;width:100%;overflow-x:hidden}
    .page-inner{padding:28px 1rem;max-width:1100px;margin:0 auto;width:100%}
    @media(min-width:640px){.page-inner{padding:36px 1.5rem}}
    .tool-body{max-width:960px;margin:0 auto;width:100%}
    .tool-interface{background:#fff;border:1px solid #E2E8F0;border-radius:18px;padding:24px;margin-bottom:32px;box-shadow:0 1px 3px rgba(0,0,0,.04);overflow:hidden}
    @media(min-width:640px){.tool-interface{padding:32px}}
    .content-split{display:flex;flex-direction:column;gap:28px;margin-bottom:32px;width:100%}
    @media(min-width:768px){.content-split{flex-direction:row}}
    .content-main{flex:0 0 60%;max-width:100%}
    @media(min-width:768px){.content-main{max-width:60%}}
    .content-side{flex:0 0 40%;max-width:100%}
    @media(min-width:768px){.content-side{max-width:40%}}
    .content-side-inner{position:sticky;top:80px;z-index:1}
    .tool-desc-section h2{font-size:1.15rem;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .tool-desc-text{color:#475569;line-height:1.9;font-size:.95rem;word-wrap:break-word;overflow-wrap:break-word}
    .tool-desc-text h3{font-size:1.15rem;font-weight:800;color:#1e293b;margin-top:24px;margin-bottom:12px;display:flex;align-items:center;gap:8px}
    .dark .tool-desc-text h3{color:#e2e8f0}
    .tool-desc-text h4{font-size:1.05rem;font-weight:700;color:#1e293b;margin-top:20px;margin-bottom:10px}
    .dark .tool-desc-text h4{color:#e2e8f0}
    .tool-desc-text p{margin-bottom:16px;line-height:1.8;text-align:justify}
    .tool-desc-text ul,.tool-desc-text ol{margin-right:20px;margin-bottom:16px;list-style-type:square}
    .tool-desc-text li{margin-bottom:8px;line-height:1.7}
    .tool-breadcrumb{display:flex;align-items:center;justify-content:space-between;padding:10px 0;margin-bottom:16px;flex-wrap:wrap;gap:8px}
    .breadcrumb-nav{display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:.82rem}
    .breadcrumb-nav a{color:#475569;text-decoration:none;transition:color .2s}
    .breadcrumb-nav a:hover{color:#6366F1}
    .breadcrumb-nav span{color:#475569}
    .bc-sep{color:#475569;font-size:1rem}
    .bc-date{display:flex;align-items:center;gap:4px;font-size:.7rem;color:#475569;direction:ltr;white-space:nowrap;font-weight:500}
    .bc-date svg{width:13px;height:13px}
    .dark .breadcrumb-nav a{color:#cbd5e1}
    .dark .breadcrumb-nav a:hover{color:#6366F1}
    .dark .breadcrumb-nav span{color:#94a3b8}
    .dark .bc-date{color:#cbd5e1}
    .toc-wrap{background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:14px 18px;margin-bottom:20px}
    .dark .toc-wrap{background:#1E293B;border-color:#334155}
    .toc ul{list-style:none}
    .toc li{margin-bottom:6px}
    .toc a{color:#4f46e5;text-decoration:none;font-size:.85rem;font-weight:500;transition:color .2s}
    .toc a:hover{color:#4338ca;text-decoration:underline}
    .dark .toc a{color:#a5b4fc}
    .dark .toc a:hover{color:#c7d2fe}
    .faq-section h2{font-size:1.15rem;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .faq-item{border:1px solid #E2E8F0;border-radius:12px;margin-bottom:10px;overflow:hidden;transition:box-shadow .2s}
    .faq-item:hover{box-shadow:0 2px 8px rgba(0,0,0,.04)}
    .faq-question{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;cursor:pointer;font-weight:600;font-size:.92rem;transition:background .2s;color:#1e293b;gap:8px}
    .faq-question:hover{background:#F8FAFC}
    .faq-question svg{transition:transform .3s;flex-shrink:0;color:#10B981}
    .faq-item.open .faq-question svg{transform:rotate(180deg)}
    .faq-answer{padding:0 18px;max-height:0;overflow:hidden;transition:all .3s ease;color:#64748b;line-height:1.8;font-size:.9rem}
    .faq-item.open .faq-answer{padding:0 18px 14px;max-height:600px}
    .related-section{margin-bottom:32px;width:100%;clear:both}
    .related-section h2{font-size:1.15rem;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px}
    .related-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;width:100%}
    @media(min-width:600px){.related-grid{grid-template-columns:repeat(4,1fr)}}
    .related-btn{display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:12px;background:#fff;border:1px solid #E2E8F0;text-decoration:none;color:#1e293b;font-size:.82rem;font-weight:600;transition:all .2s;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .related-btn:hover{border-color:#6366F1;color:#6366F1;box-shadow:0 4px 12px rgba(99,102,241,.15);transform:translateY(-2px)}
    .related-btn svg{flex-shrink:0;width:18px;height:18px;color:#6366F1}
    .eeat-section{margin-bottom:32px;clear:both}
    .eeat-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(min-width:640px){.eeat-grid{grid-template-columns:repeat(4,1fr)}}
    .eeat-card{background:#fff;border:1px solid #E2E8F0;border-radius:14px;padding:18px 16px;text-align:center;transition:all .2s;box-shadow:0 1px 2px rgba(0,0,0,.04)}
    .eeat-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.06);transform:translateY(-2px)}
    .dark .eeat-card{background:#1E293B;border-color:#334155}
    .eeat-card-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px}
    .eeat-card-icon svg{width:20px;height:20px}
    .eeat-card h3{font-size:.85rem;font-weight:800;margin-bottom:4px;color:#1e293b}
    .dark .eeat-card h3{color:#e2e8f0}
    .eeat-card p{font-size:.72rem;color:#64748b;line-height:1.5}
    .dark .eeat-card p{color:#94a3b8}
    .footer{background:#0F172A}
    .footer-inner{padding:36px 1rem}
    @media(min-width:640px){.footer-inner{padding:40px 1.5rem}}
    .footer-grid{display:grid;grid-template-columns:1fr;gap:28px}
    @media(min-width:640px){.footer-grid{grid-template-columns:repeat(2,1fr)}}
    @media(min-width:1024px){.footer-grid{grid-template-columns:repeat(4,1fr);gap:40px}}
    .footer-logo{display:flex;align-items:center;gap:10px;margin-bottom:16px}
    .footer-desc{color:#e2e8f0;font-size:.875rem;line-height:1.625;margin-bottom:20px;max-width:300px}
    .footer-social{display:flex;gap:10px}
    .footer-social a{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;transition:background .2s;text-decoration:none}
    .footer-social a:hover{background:#6366F1}
    .footer-social a svg{width:16px;height:16px;color:#fff}
    .footer-col-title{color:#fff;font-weight:700;font-size:1rem;margin-bottom:18px}
    .footer-col ul{list-style:none}
    .footer-col li{margin-bottom:10px}
    .footer-link{color:#cbd5e1;text-decoration:none;font-size:.85rem;transition:color .2s}
    .footer-link:hover{color:#fff}
    .footer-bottom{border-top:1px solid rgba(255,255,255,.08);margin-top:28px;padding-top:18px;display:flex;flex-direction:column;gap:12px;align-items:center}
    @media(min-width:640px){.footer-bottom{margin-top:32px;padding-top:20px;flex-direction:row;justify-content:space-between}}
    .footer-bottom p{color:#cbd5e1;font-size:.78rem}
    .footer-bottom-links{display:flex;gap:16px;flex-wrap:wrap;justify-content:center}
    .footer-bottom-links a{color:#cbd5e1;text-decoration:none;font-size:.78rem;transition:color .2s}
    .footer-bottom-links a:hover{color:#fff}
    @media(max-width:1023px){.hero-section{padding:32px 0 24px}.hero-section h1{font-size:1.3rem}.content-split{flex-direction:column;gap:20px}.content-main,.content-side{flex:0 0 100%;max-width:100%}.tool-interface{padding:20px;border-radius:14px}}
    @media(max-width:480px){html{font-size:15px}.hero-section h1{font-size:1.15rem}.hero-section{padding:24px 0 16px}.hero-section p{font-size:.85rem}.breadcrumb-nav{font-size:.72rem}.related-grid{grid-template-columns:1fr 1fr;gap:8px}.related-btn{padding:10px 12px;font-size:.78rem}.tool-interface{padding:14px;border-radius:12px;margin-bottom:20px}.page-inner{padding:18px .6rem}.faq-question{padding:12px 14px;font-size:.85rem}.faq-answer{font-size:.85rem}.tool-desc-text{font-size:.88rem}.eeat-grid{grid-template-columns:1fr 1fr}}
    svg{max-width:100%;height:auto}
    ::-webkit-scrollbar{width:5px;height:5px}
    ::-webkit-scrollbar-track{background:transparent}
    ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px}
    .dark ::-webkit-scrollbar-thumb{background:#475569}
    .price-up{color:#10b981}
    .price-down{color:#ef4444}
    .gold-table{width:100%;border-collapse:collapse;text-align:right}
    .gold-table th,.gold-table td{padding:14px 16px;border-bottom:1px solid #E2E8F0;font-weight:600}
    .gold-table th{background:#F8FAFC;color:#64748b;font-size:.85rem}
    .dark .gold-table th{background:#0F172A;color:#cbd5e1}
    .gold-table tr:hover{background:#F8FAFC}
    .dark .gold-table tr:hover{background:#15172a}
    .gold-table td{font-size:1.05rem}
    .price-card{background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border-radius:16px;padding:20px;border:1px solid #C7D2FE;position:relative;overflow:hidden}
    .dark .price-card{background:linear-gradient(135deg,#1E1B4B,#312E81);border-color:#4338CA}
    .price-card .live-badge{position:absolute;top:12px;left:12px;font-size:.7rem;font-weight:700;padding:4px 10px;background:#DCFCE7;color:#16A34A;border-radius:20px;display:flex;align-items:center;gap:4px}
    .dark .price-card .live-badge{background:#166534;color:#86EFAC}
    .price-card .live-dot{display:inline-block;width:6px;height:6px;background:#16A34A;border-radius:50%;animation:pulse 1.5s ease-in-out infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
    .price-value{font-size:2rem;font-weight:900;color:#1e293b;display:flex;align-items:baseline;gap:6px}
    .dark .price-value{color:#fff}
    .price-curr{font-size:1rem;font-weight:700;color:#64748b}
    .price-change{font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:6px;margin-top:4px}
  </style>
</head>
<body>
  <header class="header">
    <div class="container">
      <div class="header-inner">
        <a href="../../index.html" class="logo">
          <div class="logo-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></div>
          <span class="logo-text">ToolRar</span>
        </a>
        <nav class="desktop-nav">
          <div class="nav-links-center">
            <a href="../../index.html" class="nav-link">الرئيسية</a>
            <a href="../../all-tools.html" class="nav-link">جميع الأدوات</a>
            <div class="dropdown-wrap" id="catDropdown">
              <a href="#" class="nav-link" onmouseenter="openCatDD()" onmouseleave="scheduleCloseCatDD()">التصنيفات<svg class="dropdown-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg></a>
              <div class="dropdown-menu cat-dropdown" onmouseenter="cancelCloseCatDD()" onmouseleave="closeCatDD()">
                <a href="../../text-tools/" class="cat-item"><div class="cat-item-icon" style="background:#6366F1"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" x2="15" y1="20" y2="20"/><line x1="12" x2="12" y1="4" y2="20"/></svg></div><span>أدوات النصوص والكلمات</span></a>
                <a href="../../Developer/" class="cat-item"><div class="cat-item-icon" style="background:#10B981"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg></div><span>أدوات البرمجة</span></a>
                <a href="../../Photo-Editing/" class="cat-item"><div class="cat-item-icon" style="background:#F59E0B"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg></div><span>أدوات الصور والرسوم</span></a>
                <a href="../../Calculators/" class="cat-item"><div class="cat-item-icon" style="background:#3B82F6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="20" x="2" y="2" rx="2"/><path d="M6 12h4"/><path d="M8 10v4"/><path d="M15 13h.01"/><path d="M18 11h.01"/></svg></div><span>أدوات الحاسبة</span></a>
                <a href="../../docs-tools/" class="cat-item"><div class="cat-item-icon" style="background:#EC4899"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg></div><span>أدوات PDF</span></a>
                <a href="../../zip-tools/" class="cat-item"><div class="cat-item-icon" style="background:#10B981"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg></div><span>أدوات ZIP والضغط</span></a>
                <a href="../../seo/" class="cat-item"><div class="cat-item-icon" style="background:#14B8A6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="m9 8 5 3-5 3V8z"/></svg></div><span>أدوات SEO</span></a>
                <a href="../../General/" class="cat-item"><div class="cat-item-icon" style="background:#F59E0B"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg></div><span>أدوات متنوعة</span></a>
                <a href="../../Social-media/" class="cat-item"><div class="cat-item-icon" style="background:#8B5CF6"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg></div><span>أدوات المشاركة</span></a>
              </div>
            </div>
            <a href="../../blog.html" class="nav-link">المدونة</a>
            <a href="../../pricing.html" class="nav-link">الأسعار</a>
            <a href="../../about.html" class="nav-link">من نحن</a>
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
        <a href="../../index.html">الرئيسية</a>
        <a href="../../all-tools.html">جميع الأدوات</a>
        <a href="#">التصنيفات</a>
        <a href="../../blog.html">المدونة</a>
        <a href="../../pricing.html">الأسعار</a>
        <a href="../../about.html">من نحن</a>
      </div>
    </div>
  </header>
  <main>
    <section class="hero-section">
      <div class="container">
        <div class="hero-inner">
          <div class="hero-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m16.24 16.24 2.83 2.83"/><path d="M2 12h4"/><path d="M18 12h4"/></svg>
            <span>أسعار الذهب المباشرة</span>
          </div>
          <h1>{{HERO_TITLE}}</h1>
          <p>{{HERO_DESC}}</p>
        </div>
      </div>
    </section>
    <section class="page-section">
      <div class="container">
        <div class="page-inner">
          <div class="tool-body">
            <div class="tool-breadcrumb">
              <nav class="breadcrumb-nav" aria-label="شجرة التنقل">
                <a href="../../index.html">الرئيسية</a>
                <span class="bc-sep">›</span>
                <a href="../../General/">أدوات متنوعة</a>
                <span class="bc-sep">›</span>
                <span>{{BREADCRUMB_LAST}}</span>
              </nav>
              <time datetime="2026-06-13" class="bc-date"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z"/></svg> 2026-06-13</time>
            </div>
            <!-- Tool Interface - Gold Prices -->
            <div class="tool-interface">
              {{PRICE_SECTION}}
            </div>
            <!-- Content Split -->
            <div class="content-split">
              <div class="content-main">
                <div class="tool-desc-section">
                  {{EEAT_CONTENT}}
                </div>
              </div>
              <div class="content-side">
                <div class="content-side-inner">
                  {{TOC_SECTION}}
                  {{FAQ_SECTION}}
                  {{KARAT_DISCOVERY}}
                </div>
              </div>
            </div>
            {{COUNTRY_GRID_SECTION}}
            <!-- EEAT Trust -->
            <div class="eeat-section">
              <h2 style="font-size:1.15rem;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg> لماذا تثق في ToolRar؟</h2>
              <div class="eeat-grid">
                <div class="eeat-card">
                  <div class="eeat-card-icon" style="background:#EEF2FF;"><svg viewBox="0 0 24 24" fill="none" stroke="#6366F1" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg></div>
                  <h3>خبرة عالية</h3>
                  <p>أدوات مطورة بخبرة متخصصة في تحليل أسواق الذهب والمعادن الثمينة</p>
                </div>
                <div class="eeat-card">
                  <div class="eeat-card-icon" style="background:#D1FAE5;"><svg viewBox="0 0 24 24" fill="none" stroke="#10B981" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg></div>
                  <h3>دقة احترافية</h3>
                  <p>أسعار محدثة آنياً من مصادر عالمية موثوقة لحظة بلحظة</p>
                </div>
                <div class="eeat-card">
                  <div class="eeat-card-icon" style="background:#FEF3C7;"><svg viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></div>
                  <h3>مجاني بالكامل</h3>
                  <p>جميع أدوات الذهب متاحة مجاناً بدون أي رسوم أو اشتراكات</p>
                </div>
                <div class="eeat-card">
                  <div class="eeat-card-icon" style="background:#F3E8FF;"><svg viewBox="0 0 24 24" fill="none" stroke="#8B5CF6" stroke-width="2"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0 1 12 2.944a11.955 11.955 0 0 1-8.618 3.04A12.02 12.02 0 0 0 3 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg></div>
                  <h3>خصوصية تامة</h3>
                  <p>معلوماتك آمنة ولا تُشارك مع أي طرف ثالث. تصفح آمن ومشفّر</p>
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
            <p class="footer-desc">منصة مجانية تقدم مجموعة شاملة من الأدوات التي تساعدك في مهامك اليومية والإبداعية</p>
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
              <li><a href="../../index.html" class="footer-link">الرئيسية</a></li>
              <li><a href="../../all-tools.html" class="footer-link">جميع الأدوات</a></li>
              <li><a href="#" class="footer-link">التصنيفات</a></li>
              <li><a href="../../blog.html" class="footer-link">المدونة</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4 class="footer-col-title">أدوات</h4>
            <ul>
              <li><a href="../../text-tools/" class="footer-link">أدوات النصوص</a></li>
              <li><a href="../../Developer/" class="footer-link">أدوات البرمجة</a></li>
              <li><a href="../../Photo-Editing/" class="footer-link">أدوات الصور</a></li>
              <li><a href="../../Calculators/" class="footer-link">أدوات الحاسبة</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4 class="footer-col-title">معلومات</h4>
            <ul>
              <li><a href="../../about.html" class="footer-link">من نحن</a></li>
              <li><a href="../../contact.html" class="footer-link">تواصل معنا</a></li>
              <li><a href="../../pricing.html" class="footer-link">الأسعار</a></li>
              <li><a href="../../blog.html" class="footer-link">المدونة</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <div class="footer-bottom-links">
            <a href="../../terms.html">شروط الاستخدام</a>
            <a href="../../privacy.html">سياسة الخصوصية</a>
            <a href="../../sitemap.html">خريطة الموقع</a>
          </div>
          <p>ToolRar &copy; 2026 جميع الحقوق محفوظة</p>
        </div>
      </div>
    </div>
  </footer>
  <script>
    let isDark = localStorage.getItem('toolrar-theme') === 'dark';
    function updateDarkUI() {
      const darkIcon = document.getElementById('darkIcon');
      const darkLabel = document.getElementById('darkLabel');
      const mobileDarkIcon = document.getElementById('mobileDarkIcon');
      if (isDark) {
        document.documentElement.classList.add('dark');
        darkIcon.innerHTML = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>';
        mobileDarkIcon.innerHTML = '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>';
        darkLabel.textContent = 'الوضع النهاري';
      } else {
        document.documentElement.classList.remove('dark');
        darkIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
        mobileDarkIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
        darkLabel.textContent = 'الوضع الليلي';
      }
    }
    function toggleDark() { isDark = !isDark; updateDarkUI(); localStorage.setItem('toolrar-theme', isDark ? 'dark' : 'light'); }
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
    updateDarkUI();

    {{LIVE_PRICES_JS}}
  </script>
</body>
</html>`;

function buildPriceSection(country, isKaratPage, karatObj) {
  const imgCode = country.code === 'uk' ? 'gb' : country.code;
  const relPath = getRelPath(false);
  const flagPath = getFlagPath(country.code, relPath);
  const karatLabel = isKaratPage ? `(عيار ${karatObj.name})` : '(عيار 24)';
  const priceFactor = isKaratPage ? karatObj.factor : 1;
  const activeKaratId = isKaratPage ? karatObj.id : '';
  const weightLinkPrefix = isKaratPage ? '../weights/' : 'weights/';

  const karatTabsHtml = karats.map(k => {
    let href;
    if (isKaratPage) {
      href = k.id === activeKaratId ? `${country.code}.html` : `../${k.id}/${country.code}.html`;
    } else {
      href = `${k.id}/${country.code}.html`;
    }
    const active = k.id === activeKaratId;
    return `<a href="${href}" class="related-btn" style="${active ? 'background:#EEF2FF;border-color:#6366F1;color:#6366F1;' : ''}"><span class="w-3 h-3 rounded-full inline-block" style="background:${k.id==='24k'?'#EAB308':k.id==='22k'?'#D97706':k.id==='21k'?'#F97316':'#B45309'}"></span> عيار ${k.name}</a>`;
  }).join('');

  const weightItems = [1, 2.5, 5, 10, 20, 50, 100, 250, 500, 1000];
  const weightGridHtml = weightItems.map(w => {
    const id = w === 1 ? 'w1' : w === 2.5 ? 'w2_5' : 'w' + w;
    const label = w >= 1000 ? (w/1000) + ' كيلو' : w + ' جرام';
    const href = weightLinkPrefix + country.code + '-' + id + '.html';
    return `<a href="${href}" style="display:block;background:#fff;border:1px solid #E2E8F0;border-radius:12px;padding:12px 8px;text-align:center;text-decoration:none;transition:all .2s;box-shadow:0 1px 2px rgba(0,0,0,.04);" onmouseover="this.style.borderColor='#6366F1';this.style.boxShadow='0 4px 12px rgba(99,102,241,.15)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none';this.style.transform='none'">
      <div style="font-size:.7rem;font-weight:800;color:#6366F1;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">${label}</div>
      <div id="wp_${id}" style="font-size:1rem;font-weight:900;color:#1e293b;direction:ltr;">--</div>
      <div style="font-size:.65rem;color:#94a3b8;margin-top:4px;">${country.currencyCode}</div>
    </a>`;
  }).join('');

  return `
    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
      <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:56px;height:42px;border-radius:8px;overflow:hidden;border:2px solid #E2E8F0;flex-shrink:0;background:#F8FAFC;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.06);">
          <img src="${flagPath}" alt="علم ${country.name}" style="width:100%;height:100%;object-fit:cover;">
        </div>
        <div>
          <h2 style="font-size:1.25rem;font-weight:800;color:#1e293b;">أسعار الذهب في ${country.name} مباشر ${karatLabel}</h2>
          <p style="font-size:.85rem;color:#64748b;">بالعملة المحلية (${country.currency})</p>
        </div>
      </div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">
      <a href="../index.html" class="related-btn"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/></svg><span>جميع الدول</span></a>
      ${karatTabsHtml}
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
      <div class="price-card">
        <div class="live-badge"><span class="live-dot"></span> مباشر</div>
        <p style="font-size:.85rem;font-weight:700;color:#64748b;margin-bottom:8px;">سعر الأونصة الذهبية</p>
        <div class="price-value" id="priceOunce">جاري التحميل...</div>
        <div style="font-size:.9rem;font-weight:700;color:#64748b;margin-bottom:6px;">${country.currencyCode}</div>
        <div class="price-change" id="changeOunce"><i class="fas fa-arrow-up"></i> -- (--%)</div>
      </div>
      <div style="background:#F8FAFC;border-radius:16px;padding:20px;border:1px solid #E2E8F0;">
        <p style="font-size:.85rem;font-weight:700;color:#64748b;margin-bottom:8px;">سعر الجرام ${karatLabel}</p>
        <div class="price-value" id="priceGram">جاري التحميل...</div>
        <div style="font-size:.9rem;font-weight:700;color:#64748b;margin-bottom:6px;">${country.currencyCode}</div>
        <div class="price-change" id="changeGram"><i class="fas fa-arrow-up"></i> -- (--%)</div>
      </div>
    </div>
    <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:12px;display:flex;align-items:center;gap:8px;color:#1e293b;">أسعار جميع العيارات اليوم في ${country.name}</h3>
    <div style="overflow-x:auto;border:1px solid #E2E8F0;border-radius:12px;margin-bottom:20px;">
      <table class="gold-table">
        <thead><tr><th>عيار الذهب</th><th>${country.currency} (${country.currencyCode})</th></tr></thead>
        <tbody>
          <tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#EAB308;margin-left:8px;"></span>ذهب عيار 24</td><td id="k24">--</td></tr>
          <tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#D97706;margin-left:8px;"></span>ذهب عيار 22</td><td id="k22">--</td></tr>
          <tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#F97316;margin-left:8px;"></span>ذهب عيار 21</td><td id="k21">--</td></tr>
          <tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#F59E0B;margin-left:8px;"></span>ذهب عيار 20</td><td id="k20">--</td></tr>
          <tr><td><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#B45309;margin-left:8px;"></span>ذهب عيار 18</td><td id="k18">--</td></tr>
        </tbody>
      </table>
    </div>
    <!-- Weight Prices -->
    <h3 style="font-size:1.1rem;font-weight:800;margin:24px 0 14px;display:flex;align-items:center;gap:8px;color:#1e293b;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M3 6h18"/><path d="M21 12H3"/><path d="M3 18h18"/></svg> أسعار الأوزان المختلفة</h3>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:24px;">${weightGridHtml}</div>
    <!-- Price Chart -->
    <h3 style="font-size:1.1rem;font-weight:800;margin:0 0 14px;display:flex;align-items:center;gap:8px;color:#1e293b;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M3 3v18h18"/><path d="M7 16l4-8 4 4 4-6"/></svg> تحركات السعر آخر 10 أيام (${country.currencyCode})</h3>
    <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:12px;position:relative;height:260px;margin-bottom:24px;">
      <canvas id="priceChart" style="width:100%;height:100%;"></canvas>
      <div id="chartLoading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:.85rem;color:#94a3b8;font-weight:600;">جاري تحميل البيانات...</div>
    </div>
    <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:12px;display:flex;align-items:center;gap:8px;color:#1e293b;">مؤشرات التداول اليومي</h3>
    <div style="overflow-x:auto;border:1px solid #E2E8F0;border-radius:12px;margin-bottom:20px;">
      <table class="gold-table">
        <tbody>
          <tr><td style="font-weight:700;color:#64748b;">أعلى سعر اليوم:</td><td id="tdHigh">--</td></tr>
          <tr><td style="font-weight:700;color:#64748b;">أدنى سعر اليوم:</td><td id="tdLow">--</td></tr>
          <tr><td style="font-weight:700;color:#64748b;">الفرق بين الأعلى والأدنى:</td><td id="tdDiff">--</td></tr>
          <tr><td style="font-weight:700;color:#64748b;">إغلاق سابق:</td><td id="tdPrevClose">--</td></tr>
          <tr><td style="font-weight:700;color:#64748b;">طلب / عرض:</td><td id="tdBidAsk">--</td></tr>
        </tbody>
      </table>
    </div>
    <div style="background:#EFF6FF;border-radius:12px;padding:14px;font-size:.85rem;font-weight:600;color:#2563EB;display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      <p>قد تختلف الأسعار قليلاً في السوق المحلي لدى محلات الصاغة بسبب فرض بعض الدول ضرائب على الذهب أو إضافة تكلفة المصنعية.</p>
    </div>
    <div style="border-top:1px solid #E2E8F0;padding-top:16px;margin-top:8px;">
      <p style="font-weight:700;text-align:center;color:#64748b;margin-bottom:12px;">شارك الأسعار المباشرة:</p>
      <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:8px;">
        <a id="fbShareBtn" href="#" target="_blank" style="flex:1;min-width:90px;text-align:center;padding:10px;border-radius:10px;font-weight:700;font-size:.85rem;background:#1877F2/10;color:#1877F2;border:1px solid #1877F2/30;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;" onmouseover="this.style.background='#1877F2';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='#1877F2'"><svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg><span>فيسبوك</span></a>
        <a id="twShareBtn" href="#" target="_blank" style="flex:1;min-width:90px;text-align:center;padding:10px;border-radius:10px;font-weight:700;font-size:.85rem;background:#000/10;color:#000;border:1px solid #000/20;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;" onmouseover="this.style.background='#000';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='#000'"><svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg><span>تويتر</span></a>
        <a id="waShareBtn" href="#" target="_blank" style="flex:1;min-width:90px;text-align:center;padding:10px;border-radius:10px;font-weight:700;font-size:.85rem;background:#25D366/10;color:#25D366;border:1px solid #25D366/30;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;" onmouseover="this.style.background='#25D366';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='#25D366'"><svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg><span>واتساب</span></a>
        <a id="inShareBtn" href="#" target="_blank" style="flex:1;min-width:90px;text-align:center;padding:10px;border-radius:10px;font-weight:700;font-size:.85rem;background:#0077B5/10;color:#0077B5;border:1px solid #0077B5/30;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;" onmouseover="this.style.background='#0077B5';this.style.color='#fff'" onmouseout="this.style.background='';this.style.color='#0077B5'"><svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg><span>لينكد إن</span></a>
      </div>
    </div>
  `;
}

function buildLivePriceJs(country, isKaratPage, karatObj) {
  const factor = isKaratPage ? karatObj.factor : 1;
  const cc = country.currencyCode;
  const weights = [1, 2.5, 5, 10, 20, 50, 100, 250, 500, 1000];
  return `
    const currencyCode = '${cc}';
    const priceFactor = ${factor};
    const weights = ${JSON.stringify(weights)};
    let goldHistory = [];
    async function fetchLiveGold() {
      try {
        let exchangeRate = 1;
        if (currencyCode !== 'USD') {
          try {
            const res = await fetch('https://open.er-api.com/v6/latest/USD');
            const data = await res.json();
            exchangeRate = data.rates[currencyCode] || 1;
          } catch(e) { exchangeRate = 1; }
        }
        let liveGoldUSD = 4612.90, prevCloseUSD = 4559.45, highUSD = 4637.50, lowUSD = 4587.30;
        try {
          const histUrl = 'https://api.allorigins.win/get?url=' + encodeURIComponent('https://query1.finance.yahoo.com/v8/finance/chart/GC=F?range=10d&interval=1d');
          const yfRes = await fetch(histUrl);
          const yfText = await yfRes.text();
          const parsed = JSON.parse(yfText);
          if (parsed && parsed.contents) {
            const data = JSON.parse(parsed.contents);
            if (data && data.chart && data.chart.result && data.chart.result[0]) {
              const meta = data.chart.result[0].meta;
              const timestamps = data.chart.result[0].timestamp || [];
              const quotes = data.chart.result[0].indicators.quote[0] || {};
              const closes = quotes.close || [];
              goldHistory = timestamps.map(function(t,i) {
                return closes[i] ? { date: new Date(t*1000), close: closes[i] } : null;
              }).filter(function(h) { return h !== null; });
              if (meta && meta.regularMarketPrice) {
                liveGoldUSD = meta.regularMarketPrice;
                prevCloseUSD = meta.chartPreviousClose || liveGoldUSD;
                highUSD = meta.regularMarketDayHigh || liveGoldUSD + 15;
                lowUSD = meta.regularMarketDayLow || liveGoldUSD - 10;
              }
            }
          }
        } catch(e) {}
        if (goldHistory.length < 2) {
          goldHistory = [];
          var now = Date.now();
          for (var i = 9; i >= 0; i--) {
            var d = new Date(now - i * 86400000);
            var v = (Math.random() - 0.5) * 80;
            goldHistory.push({ date: d, close: liveGoldUSD + v });
          }
        }
        updatePrices(liveGoldUSD, prevCloseUSD, highUSD, lowUSD, exchangeRate);
        drawChart(exchangeRate);
      } catch(e) { console.error(e); }
    }
    function updateWeightPrices(gramPrice) {
      for (var i = 0; i < weights.length; i++) {
        var w = weights[i];
        var id = w === 1 ? 'w1' : w === 2.5 ? 'w2_5' : 'w' + w;
        var el = document.getElementById('wp_' + id);
        if (el) {
          var total = gramPrice * w;
          el.textContent = total.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
        }
      }
    }
    function updatePrices(liveUSD, prevUSD, highUSD, lowUSD, rate) {
      const localOunce = liveUSD * rate;
      const localGram = localOunce / 31.1035;
      const gram = localGram * priceFactor;
      const change = (liveUSD - prevUSD) * rate;
      const pct = ((liveUSD - prevUSD) / prevUSD) * 100;
      const fmt = (n) => n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
      document.getElementById('priceOunce').textContent = fmt(localOunce);
      document.getElementById('priceGram').textContent = fmt(gram);
      document.getElementById('k24').textContent = fmt(localGram);
      document.getElementById('k22').textContent = fmt(localGram * 22/24);
      document.getElementById('k21').textContent = fmt(localGram * 21/24);
      document.getElementById('k20').textContent = fmt(localGram * 20/24);
      document.getElementById('k18').textContent = fmt(localGram * 18/24);
      const color = change >= 0 ? '#10b981' : '#ef4444';
      const arrow = change >= 0 ? '▲' : '▼';
      ['changeOunce','changeGram'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = '<span style="color:'+color+'">'+arrow+' '+fmt(Math.abs(change))+' ('+fmt(Math.abs(pct))+'%)</span>';
      });
      document.getElementById('tdHigh').textContent = fmt(highUSD * rate);
      document.getElementById('tdLow').textContent = fmt(lowUSD * rate);
      document.getElementById('tdDiff').textContent = fmt((highUSD - lowUSD) * rate);
      document.getElementById('tdPrevClose').textContent = fmt(prevUSD * rate);
      const bid = (liveUSD - 0.50) * rate;
      const ask = (liveUSD + 0.30) * rate;
      document.getElementById('tdBidAsk').textContent = fmt(bid) + ' / ' + fmt(ask);
      updateWeightPrices(gram);
    }
    function drawChart(rate) {
      var canvas = document.getElementById('priceChart');
      if (!canvas || goldHistory.length < 2) return;
      document.getElementById('chartLoading').style.display = 'none';
      var ctx = canvas.getContext('2d');
      var rect = canvas.parentElement.getBoundingClientRect();
      var dpr = window.devicePixelRatio || 1;
      var w = rect.width, h = rect.height;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      canvas.style.width = w + 'px';
      canvas.style.height = h + 'px';
      ctx.scale(dpr, dpr);
      var pad = { top: 15, right: 10, bottom: 30, left: 50 };
      var cw = w - pad.left - pad.right;
      var ch = h - pad.top - pad.bottom;
      var isDark = document.documentElement.classList.contains('dark');
      var textColor = isDark ? '#94a3b8' : '#64748b';
      var gridColor = isDark ? '#1e293b' : '#E2E8F0';
      var lineColor = '#6366F1';
      var fillColor = isDark ? 'rgba(99,102,241,0.15)' : 'rgba(99,102,241,0.1)';
      var prices = goldHistory.map(function(h) { return h.close * rate; });
      var minP = Math.min.apply(null, prices);
      var maxP = Math.max.apply(null, prices);
      var range = maxP - minP || 1;
      var padP = range * 0.08;
      minP -= padP;
      maxP += padP;
      var fmt = function(n) { return n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); };
      // Store data points for tooltip interaction
      var points = [];
      ctx.clearRect(0, 0, w, h);
      // Grid lines
      ctx.strokeStyle = gridColor;
      ctx.lineWidth = 0.5;
      var gridLines = 5;
      for (var gi = 0; gi <= gridLines; gi++) {
        var yp = pad.top + (ch * gi / gridLines);
        ctx.beginPath();
        ctx.moveTo(pad.left, yp);
        ctx.lineTo(w - pad.right, yp);
        ctx.stroke();
        var val = maxP - (range * gi / gridLines);
        ctx.fillStyle = textColor;
        ctx.font = '10px Cairo, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(fmt(val), pad.left - 6, yp + 4);
      }
      // X-axis labels
      var step = Math.max(1, Math.floor(goldHistory.length / 7));
      ctx.textAlign = 'center';
      ctx.font = '9px Cairo, sans-serif';
      for (var xi = 0; xi < goldHistory.length; xi++) {
        if (xi % step === 0 || xi === goldHistory.length - 1) {
          var xp = pad.left + (cw * xi / (goldHistory.length - 1));
          var d = goldHistory[xi].date;
          var label = (d.getMonth()+1) + '/' + d.getDate();
          ctx.fillStyle = textColor;
          ctx.fillText(label, xp, h - 6);
        }
      }
      // Line path
      ctx.beginPath();
      for (var pi = 0; pi < prices.length; pi++) {
        var px = pad.left + (cw * pi / (prices.length - 1));
        var py = pad.top + ch - (ch * (prices[pi] - minP) / range);
        points.push({ x: px, y: py, price: prices[pi], date: goldHistory[pi].date });
        if (pi === 0) ctx.moveTo(px, py);
        else ctx.lineTo(px, py);
      }
      ctx.strokeStyle = lineColor;
      ctx.lineWidth = 2.5;
      ctx.lineJoin = 'round';
      ctx.stroke();
      // Fill area
      ctx.lineTo(pad.left + cw, pad.top + ch);
      ctx.lineTo(pad.left, pad.top + ch);
      ctx.closePath();
      ctx.fillStyle = fillColor;
      ctx.fill();
      // Dots and labels
      for (var di = 0; di < prices.length; di++) {
        var dx = pad.left + (cw * di / (prices.length - 1));
        var dy = pad.top + ch - (ch * (prices[di] - minP) / range);
        ctx.beginPath();
        ctx.arc(dx, dy, 3, 0, Math.PI * 2);
        ctx.fillStyle = '#fff';
        ctx.fill();
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2;
        ctx.stroke();
      }
      // Last price label
      var lastX = pad.left + cw;
      var lastY = pad.top + ch - (ch * (prices[prices.length-1] - minP) / range);
      ctx.fillStyle = lineColor;
      ctx.font = 'bold 11px Cairo, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(fmt(prices[prices.length-1]), lastX, lastY - 10);
      // ---- Interactive tooltip ----
      function rr(ctx, x, y, w, h, r) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.arcTo(x + w, y, x + w, y + r, r);
        ctx.lineTo(x + w, y + h - r);
        ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
        ctx.lineTo(x + r, y + h);
        ctx.arcTo(x, y + h, x, y + h - r, r);
        ctx.lineTo(x, y + r);
        ctx.arcTo(x, y, x + r, y, r);
        ctx.closePath();
      }
      function drawTooltip(idx) {
        if (idx < 0 || idx >= points.length) return;
        var pt = points[idx];
        // Vertical line
        ctx.beginPath();
        ctx.moveTo(pt.x, pad.top);
        ctx.lineTo(pt.x, pad.top + ch);
        ctx.strokeStyle = isDark ? 'rgba(99,102,241,0.5)' : 'rgba(99,102,241,0.4)';
        ctx.lineWidth = 1;
        ctx.setLineDash([4, 3]);
        ctx.stroke();
        ctx.setLineDash([]);
        // Dot highlight
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 6, 0, Math.PI * 2);
        ctx.fillStyle = '#6366F1';
        ctx.fill();
        ctx.strokeStyle = '#fff';
        ctx.lineWidth = 2.5;
        ctx.stroke();
        // Tooltip box
        var ttText = fmt(pt.price) + ' ' + '${country.currencyCode}';
        var d = pt.date;
        var dateStr = d.getFullYear() + '-' + (d.getMonth()+1).toString().padStart(2,'0') + '-' + d.getDate().toString().padStart(2,'0');
        ctx.font = 'bold 11px Cairo, sans-serif';
        var tw = ctx.measureText(ttText).width;
        var dw = ctx.measureText(dateStr).width;
        var bw = Math.max(tw, dw) + 20;
        var bh = 48;
        var bx = pt.x - bw / 2;
        var by = pt.y - bh - 14;
        if (bx < 5) bx = 5;
        if (bx + bw > w - 5) bx = w - bw - 5;
        if (by < 5) { by = pt.y + 14; }
        rr(ctx, bx, by, bw, bh, 6);
        ctx.fillStyle = isDark ? '#1E293B' : '#fff';
        ctx.fill();
        ctx.strokeStyle = '#6366F1';
        ctx.lineWidth = 1.5;
        ctx.stroke();
        // Arrow
        ctx.beginPath();
        ctx.moveTo(pt.x - 5, by + bh);
        ctx.lineTo(pt.x, by + bh + 6);
        ctx.lineTo(pt.x + 5, by + bh);
        ctx.fillStyle = isDark ? '#1E293B' : '#fff';
        ctx.fill();
        ctx.fillStyle = '#6366F1';
        ctx.font = 'bold 10px Cairo, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(ttText, bx + bw / 2, by + 18);
        ctx.fillStyle = textColor;
        ctx.font = '9px Cairo, sans-serif';
        ctx.fillText(dateStr, bx + bw / 2, by + 36);
      }
      // Mouse interaction
      var tooltipIdx = -1;
      function onMouseMove(e) {
        var r = canvas.getBoundingClientRect();
        var mx = e.clientX - r.left;
        var closest = -1;
        var closestDist = Infinity;
        for (var i = 0; i < points.length; i++) {
          var ddx = Math.abs(points[i].x - mx);
          if (ddx < closestDist) { closestDist = ddx; closest = i; }
        }
        if (closest !== tooltipIdx) {
          tooltipIdx = closest;
          ctx.clearRect(0, 0, w, h);
          redrawChart();
          if (closest >= 0) drawTooltip(closest);
        }
      }
      function onMouseLeave() {
        if (tooltipIdx >= 0) {
          tooltipIdx = -1;
          ctx.clearRect(0, 0, w, h);
          redrawChart();
        }
      }
      function redrawChart() {
        // Redraw grid lines
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 0.5;
        for (var gi = 0; gi <= 5; gi++) {
          var yp = pad.top + (ch * gi / 5);
          ctx.beginPath();
          ctx.moveTo(pad.left, yp);
          ctx.lineTo(w - pad.right, yp);
          ctx.stroke();
          ctx.fillStyle = textColor;
          ctx.font = '10px Cairo, sans-serif';
          ctx.textAlign = 'right';
          ctx.fillText(fmt(maxP - (range * gi / 5)), pad.left - 6, yp + 4);
        }
        // X-axis labels
        ctx.textAlign = 'center';
        ctx.font = '9px Cairo, sans-serif';
        for (var xi = 0; xi < goldHistory.length; xi++) {
          if (xi % step === 0 || xi === goldHistory.length - 1) {
            ctx.fillStyle = textColor;
            ctx.fillText((goldHistory[xi].date.getMonth()+1)+'/'+goldHistory[xi].date.getDate(), points[xi].x, h - 6);
          }
        }
        // Line
        ctx.beginPath();
        for (var pi = 0; pi < points.length; pi++) {
          if (pi === 0) ctx.moveTo(points[pi].x, points[pi].y);
          else ctx.lineTo(points[pi].x, points[pi].y);
        }
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2.5;
        ctx.lineJoin = 'round';
        ctx.stroke();
        // Fill
        ctx.lineTo(pad.left + cw, pad.top + ch);
        ctx.lineTo(pad.left, pad.top + ch);
        ctx.closePath();
        ctx.fillStyle = fillColor;
        ctx.fill();
        // Dots
        for (var di = 0; di < points.length; di++) {
          ctx.beginPath();
          ctx.arc(points[di].x, points[di].y, 3, 0, Math.PI * 2);
          ctx.fillStyle = '#fff';
          ctx.fill();
          ctx.strokeStyle = lineColor;
          ctx.lineWidth = 2;
          ctx.stroke();
        }
        // Last label
        ctx.fillStyle = lineColor;
        ctx.font = 'bold 11px Cairo, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(fmt(prices[prices.length-1]), points[points.length-1].x, points[points.length-1].y - 10);
      }
      canvas.removeEventListener('mousemove', onMouseMove);
      canvas.removeEventListener('mouseleave', onMouseLeave);
      canvas.addEventListener('mousemove', onMouseMove);
      canvas.addEventListener('mouseleave', onMouseLeave);
    }
    document.addEventListener('DOMContentLoaded', () => {
      const url = encodeURIComponent(window.location.href);
      const title = encodeURIComponent(document.title);
      document.getElementById('fbShareBtn').href = 'https://www.facebook.com/sharer/sharer.php?u='+url;
      document.getElementById('twShareBtn').href = 'https://twitter.com/intent/tweet?text='+title+'&url='+url;
      document.getElementById('waShareBtn').href = 'https://api.whatsapp.com/send?text='+title+' '+url;
      document.getElementById('inShareBtn').href = 'https://www.linkedin.com/sharing/share-offsite/?url='+url;
    });
    fetchLiveGold();
    setInterval(fetchLiveGold, 15000);
  `;
}

function buildCountryMiniGrid(country, isKaratPage) {
  const relPath = getRelPath(false);
  return countries.map(c => {
    const imgCode = c.code === 'uk' ? 'gb' : c.code;
    const href = isKaratPage ? `../${c.code}.html` : `${c.code}.html`;
    return `<a href="${href}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:12px;background:#fff;border:1px solid #E2E8F0;text-decoration:none;color:#1e293b;transition:all .2s;font-size:.82rem;font-weight:600;" onmouseover="this.style.borderColor='#6366F1';this.style.color='#6366F1';this.style.boxShadow='0 4px 12px rgba(99,102,241,.15)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='#E2E8F0';this.style.color='#1e293b';this.style.boxShadow='none';this.style.transform='none'">
      <img src="${getFlagPath(c.code, relPath)}" alt="علم ${c.name}" style="width:36px;height:27px;border-radius:4px;object-fit:cover;border:1px solid #E2E8F0;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,.06);">
      <span>سعر الذهب في ${c.name} اليوم</span>
    </a>`;
  }).join('');
}

function buildKaratDiscovery(country) {
  const imgCode = country.code === 'uk' ? 'gb' : country.code;
  const relPath = getRelPath(false);
  const kData = [
    { id: '24k', name: '24', color: '#EAB308', bg: '#FEFCE8', icon: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5', desc: 'أنقى العيارات، مناسب للسبائك والاستثمار' },
    { id: '22k', name: '22', color: '#D97706', bg: '#FFFBEB', icon: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5', desc: 'عيار مثالي للمشغولات الفاخرة والمجوهرات' },
    { id: '21k', name: '21', color: '#F97316', bg: '#FFF7ED', icon: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5', desc: 'الأكثر شيوعاً في العالم العربي والخليج' },
    { id: '18k', name: '18', color: '#B45309', bg: '#FEF3C7', icon: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5', desc: 'مثالي للتصاميم العصرية والمجوهرات اليومية' }
  ];
  return kData.map(k => {
    const href = `${k.id}/${country.code}.html`;
    return `<a href="${href}" style="display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;background:${k.bg};border:1px solid ${k.color}30;text-decoration:none;transition:all .2s;margin-bottom:8px;" onmouseover="this.style.borderColor='${k.color}';this.style.boxShadow='0 4px 12px ${k.color}20';this.style.transform='translateX(-4px)'" onmouseout="this.style.borderColor='${k.color}30';this.style.boxShadow='none';this.style.transform='none'">
      <div style="width:40px;height:40px;border-radius:10px;background:${k.color};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" width="20" height="20"><path d="${k.icon}"/></svg>
      </div>
      <div style="flex:1;">
        <div style="font-weight:800;font-size:.9rem;color:#1e293b;">عيار ${k.name}</div>
        <div style="font-size:.75rem;color:#64748b;">${k.desc}</div>
      </div>
      <svg viewBox="0 0 24 24" fill="none" stroke="${k.color}" stroke-width="2" width="16" height="16" style="flex-shrink:0;"><path d="M9 18l6-6-6-6"/></svg>
    </a>`;
  }).join('\n');
}

function buildToc(countryName) {
  return `<div class="toc-wrap">
    <nav class="toc">
      <ul>
        <li><a href="#أسعار-الذهب-في-${countryName}-مباشر">أسعار الذهب في ${countryName} مباشر</a></li>
        <li><a href="#سوق-الذهب-في-${countryName}">سوق الذهب في ${countryName}</a></li>
        <li><a href="#الاستثمار-في-الذهب-في-${countryName}">الاستثمار في الذهب في ${countryName}</a></li>
      </ul>
    </nav>
  </div>`;
}

function buildFaqSection(faqsHtml) {
  return `<div class="faq-section">
    <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg> الأسئلة الشائعة</h2>
    ${faqsHtml}
  </div>`;
}

function generatePage(country, karatObj) {
  const isKaratPage = !!karatObj;
  const flagCode = country.code === 'uk' ? 'gb' : country.code;
  const countryName = country.name;

  // Get the E-E-A-T content from the seeds
  const eaatContent = buildEeatContent(country);
  const faqsHtml = buildFaqs(country);
  const priceSection = buildPriceSection(country, isKaratPage, karatObj);
  const livePricesJs = buildLivePriceJs(country, isKaratPage, karatObj);

  const title = isKaratPage
    ? `سعر الذهب عيار ${karatObj.name} في ${countryName} اليوم`
    : `أسعار الذهب في ${countryName} مباشر`;
  const heroTitle = isKaratPage
    ? `سعر الذهب عيار ${karatObj.name} في ${countryName}`
    : `أسعار الذهب في ${countryName} مباشر`;
  const heroDesc = `تابع أسعار الذهب المباشرة في ${countryName} محدثة آنياً بجميع العيارات (24, 22, 21, 20, 18) مقومة بالعملة المحلية (${country.currency}).`;
  const metaDesc = `أسعار الذهب في ${countryName} مباشر ${isKaratPage ? `عيار ${karatObj.name} ` : ''} محدث لحظة بلحظة. تعرف على سعر الجرام والأونصة بجميع العيارات (24, 22, 21, 18) بالعملة المحلية (${country.currency}). ${country.marketDesc}`.substring(0, 320);

  const faqItems = country.faqQs.map((q, i) => {
    const answers = {
      sa: ['سعر الذهب عيار 21 في الرياض اليوم...', 'الأسعار موحدة مع فروق طفيفة...', 'تشتهر محلات شارع الوزير...', 'يزداد الطلب في موسم الحج...'],
      us: ['سعر أونصة الذهب في أمريكا...', 'ضريبة أرباح رأس المال...', 'بنوك JPMorgan وHSBC...', 'رفع الفائدة يضعف جاذبية الذهب...'],
      ae: ['سعر الذهب عيار 22 في دبي...', 'فترة الظهيرة أيام الأسبوع...', 'أسعار متشابهة بين دبي وأبوظبي...', 'المصنعية تختلف حسب التصميم...'],
      eg: ['سعر الذهب عيار 21 في مصر...', 'منطقة الصاغة بالقاهرة...', 'الضريبة على المصنعية فقط...', 'شارع سعد زغلول بالإسكندرية...']
    };
    const ans = (answers[country.code] && answers[country.code][i]) ? answers[country.code][i] : `يرجى متابعة الأسعار المباشرة في الصفحة لمعرفة أحدث التحديثات حول ${q}`;
    return {
      '@type': 'Question',
      name: q,
      acceptedAnswer: { '@type': 'Answer', text: ans }
    };
  });

  const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: title,
    description: metaDesc.substring(0, 200),
    datePublished: '2026-06-13',
    dateModified: '2026-06-13',
    author: { '@type': 'Organization', name: 'ToolRar' }
  };

  const faqJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqItems
  };

  const toolJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: title,
    description: metaDesc.substring(0, 200),
    applicationCategory: 'FinanceApplication',
    offers: { '@type': 'Offer', priceCurrency: country.currencyCode, availability: 'https://schema.org/InStock' }
  };

  const tocHtml = buildToc(countryName);
  const faqSectionHtml = buildFaqSection(faqsHtml);
  const countryGridHtml = buildCountryMiniGrid(country, isKaratPage);
  const countryGridSection = `<div class="related-section">
    <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg> أسعار الذهب في جميع دول العالم</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">${countryGridHtml}</div>
  </div>`;
  const karatDiscoveryHtml = isKaratPage ? '' : `<div style="margin-top:24px;">
    <h2 style="font-size:1.15rem;font-weight:800;margin-bottom:14px;display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><circle cx="12" cy="12" r="10"/><path d="M12 2v4"/><path d="M12 18v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="m16.24 16.24 2.83 2.83"/></svg> اكتشف العيارات المناسبة</h2>
    ${buildKaratDiscovery(country)}
  </div>`;

  const countryBc = { "@context": "https://schema.org", "@type": "BreadcrumbList", "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "الرئيسية", "item": "https://toolrar.com/index.html" },
    { "@type": "ListItem", "position": 2, "name": "أدوات متنوعة", "item": "https://toolrar.com/General/" },
    { "@type": "ListItem", "position": 3, "name": "أسعار الذهب", "item": "https://toolrar.com/General/gold_price/index.html" },
    { "@type": "ListItem", "position": 4, "name": title }
  ]};
  const countryWs = { "@context": "https://schema.org", "@type": "WebSite", "name": "ToolRar", "url": "https://toolrar.com", "inLanguage": "ar" };
  const countrySchema = { "@context": "https://schema.org", "@type": "WebPage", "name": title, "description": metaDesc, "inLanguage": "ar", "isPartOf": { "@type": "WebSite", "name": "ToolRar", "url": "https://toolrar.com" } };

  let html = templateHtml
    .replace(/\{\{TITLE\}\}/g, title)
    .replace(/\{\{META_DESC\}\}/g, metaDesc)
    .replace(/\{\{BREADCRUMB_JSONLD\}\}/g, JSON.stringify(countryBc))
    .replace(/\{\{WEBSITE_JSONLD\}\}/g, JSON.stringify(countryWs))
    .replace(/\{\{SCHEMA_JSONLD\}\}/g, JSON.stringify(countrySchema))
    .replace(/\{\{FAQ_JSONLD\}\}/g, JSON.stringify(faqJsonLd))
    .replace(/\{\{ARTICLE_JSONLD\}\}/g, JSON.stringify(articleJsonLd))
    .replace(/\{\{PRODUCT_JSONLD\}\}/g, JSON.stringify(toolJsonLd))
    .replace(/\{\{HERO_TITLE\}\}/g, heroTitle)
    .replace(/\{\{HERO_DESC\}\}/g, heroDesc)
    .replace(/\{\{BREADCRUMB_LAST\}\}/g, title)
    .replace(/\{\{PRICE_SECTION\}\}/g, priceSection)
    .replace(/\{\{EEAT_CONTENT\}\}/g, eaatContent)
    .replace(/\{\{FAQS\}\}/g, faqsHtml)
    .replace(/\{\{TOC_SECTION\}\}/g, tocHtml)
    .replace(/\{\{FAQ_SECTION\}\}/g, faqSectionHtml)
    .replace(/\{\{KARAT_DISCOVERY\}\}/g, karatDiscoveryHtml)
    .replace(/\{\{COUNTRY_GRID_SECTION\}\}/g, countryGridSection)
    .replace(/\{\{COUNTRY_NAME\}\}/g, countryName)
    .replace(/\{\{LIVE_PRICES_JS\}\}/g, livePricesJs);

  // Karat pages are one level deeper (gold_price/22k/), so ../../ becomes ../../../
  if (isKaratPage) {
    html = html.split('../../').join('../../../');
  }

  const dir = isKaratPage ? path.join(baseDir, karatObj.id) : baseDir;
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  const filePath = path.join(dir, country.code + '.html');
  fs.writeFileSync(filePath, html, 'utf8');
  console.log('  Generated: ' + filePath);
}

function generateIndexPage(karatObj) {
  const isMain = !karatObj;
  const title = isMain ? 'أسعار الذهب حول العالم' : `أسعار الذهب عيار ${karatObj.name} حول العالم`;
  const heroTitle = isMain ? 'أسعار الذهب في جميع دول العالم' : `أسعار الذهب عيار ${karatObj.name}`;
  const heroDesc = isMain ? 'تصفح أسعار الذهب المباشرة في أكثر من 40 دولة حول العالم محدثة آنياً' : `تصفح أسعار الذهب عيار ${karatObj.name} في جميع دول العالم`;
  const flagRelPath = isMain ? '' : '../';

  const countryGrid = countries.map(c => {
    const imgCode = c.code === 'uk' ? 'gb' : c.code;
    const href = isMain ? `${c.code}.html` : `${c.code}.html`;
    return `<a href="${href}" style="display:flex;flex-direction:column;align-items:center;padding:20px;background:#F8FAFC;border-radius:16px;border:1px solid #E2E8F0;transition:all .3s;text-decoration:none;color:inherit;" onmouseover="this.style.borderColor='#6366F1';this.style.boxShadow='0 4px 12px rgba(99,102,241,.15)';this.style.transform='translateY(-4px)'" onmouseout="this.style.borderColor='#E2E8F0';this.style.boxShadow='none';this.style.transform='none'">
      <img src="${flagRelPath}flag-icons/flags/4x3/${imgCode}.svg" alt="علم ${c.name}" style="width:64px;height:48px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.1);margin-bottom:10px;object-fit:cover;">
      <span style="font-weight:700;font-size:.9rem;color:#1e293b;">${c.name}</span>
      <span style="font-size:.75rem;color:#64748b;margin-top:4px;">${c.currency}</span>
    </a>`;
  }).join('');

  const sidebarLinks = countries.map(c => {
    const imgCode = c.code === 'uk' ? 'gb' : c.code;
    const href = isMain ? `${c.code}.html` : `${c.code}.html`;
    return `<li><a href="${href}" style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:10px;font-size:.85rem;font-weight:500;color:#475569;transition:all .2s;text-decoration:none;" onmouseover="this.style.background='#F8FAFC';this.style.color='#6366F1'"><img src="${flagRelPath}flag-icons/flags/4x3/${imgCode}.svg" alt="${c.name}" style="width:28px;height:21px;border-radius:4px;object-fit:cover;box-shadow:0 1px 3px rgba(0,0,0,.08);"> أسعار الذهب في ${c.name}</a></li>`;
  }).join('');

  const karatTabs = karats.map(k => {
    const href = isMain ? `${k.id}/index.html` : `../${k.id}/index.html`;
    const active = !isMain && k.id === karatObj.id;
    return `<a href="${href}" style="flex:1;text-align:center;padding:14px;border-radius:12px;font-weight:700;font-size:.9rem;transition:all .3s;text-decoration:none;${active ? 'background:#EEF2FF;border:2px solid #6366F1;color:#6366F1;' : 'background:#fff;border:1px solid #E2E8F0;color:#1e293b;'}" onmouseover="this.style.borderColor='#6366F1';this.style.boxShadow='0 4px 12px rgba(99,102,241,.15)'" onmouseout="this.style.borderColor='${active ? '#6366F1' : '#E2E8F0'}';this.style.boxShadow='none'">عيار ${k.name}</a>`;
  }).join('');

  const mainContent = `
    <div style="background:#fff;border-radius:24px;box-shadow:0 4px 20px rgba(0,0,0,.06);padding:32px;border:1px solid #E2E8F0;">
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">${karatTabs}</div>
      <h2 style="font-size:1.3rem;font-weight:800;margin-bottom:20px;text-align:center;color:#1e293b;">اختر الدولة لمعرفة أسعار الذهب المباشرة${isMain ? '' : ' عيار ' + karatObj.name}</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px;">${countryGrid}</div>
    </div>
    <div style="margin-top:32px;">
      <h3 style="font-size:1.1rem;font-weight:800;margin-bottom:12px;color:#1e293b;">جميع دول أسعار الذهب</h3>
      <ul style="list-style:none;display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:4px;">${sidebarLinks}</ul>
    </div>
  `;

  const breadcrumbIdx = { "@context": "https://schema.org", "@type": "BreadcrumbList", "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "الرئيسية", "item": "https://toolrar.com/index.html" },
    { "@type": "ListItem", "position": 2, "name": "أدوات متنوعة", "item": "https://toolrar.com/General/" },
    { "@type": "ListItem", "position": 3, "name": title }
  ]};
  const websiteIdx = { "@context": "https://schema.org", "@type": "WebSite", "name": "ToolRar", "url": "https://toolrar.com", "description": "منصة مجانية تقدم مجموعة شاملة من الأدوات المفيدة", "inLanguage": "ar", "potentialAction": { "@type": "SearchAction", "target": "https://toolrar.com/search?q={search_term_string}", "query-input": "required name=search_term_string" } };
  const schemaIdx = { "@context": "https://schema.org", "@type": "CollectionPage", "name": title, "description": `صفحة متابعة أسعار الذهب المباشرة في ${isMain ? 'جميع دول العالم' : 'عيار ' + karatObj.name} محدثة آنياً`, "inLanguage": "ar", "isPartOf": { "@type": "WebSite", "name": "ToolRar", "url": "https://toolrar.com" } };

  let html = templateHtml
    .replace(/\{\{TITLE\}\}/g, title)
    .replace(/\{\{META_DESC\}\}/g, `صفحة متابعة أسعار الذهب المباشرة في ${isMain ? 'جميع دول العالم' : 'عيار ' + karatObj.name} محدثة آنياً`)
    .replace(/\{\{BREADCRUMB_JSONLD\}\}/g, JSON.stringify(breadcrumbIdx))
    .replace(/\{\{WEBSITE_JSONLD\}\}/g, JSON.stringify(websiteIdx))
    .replace(/\{\{SCHEMA_JSONLD\}\}/g, JSON.stringify(schemaIdx))
    .replace(/\{\{FAQ_JSONLD\}\}/g, '')
    .replace(/\{\{ARTICLE_JSONLD\}\}/g, '')
    .replace(/\{\{PRODUCT_JSONLD\}\}/g, '')
    .replace(/\{\{HERO_TITLE\}\}/g, heroTitle)
    .replace(/\{\{HERO_DESC\}\}/g, heroDesc)
    .replace(/\{\{BREADCRUMB_LAST\}\}/g, title)
    .replace(/\{\{PRICE_SECTION\}\}/g, mainContent)
    .replace(/\{\{EEAT_CONTENT\}\}/g, '')
    .replace(/\{\{FAQS\}\}/g, '')
    .replace(/\{\{TOC_SECTION\}\}/g, '')
    .replace(/\{\{FAQ_SECTION\}\}/g, '')
    .replace(/\{\{KARAT_DISCOVERY\}\}/g, '')
    .replace(/\{\{COUNTRY_GRID_SECTION\}\}/g, '')
    .replace(/\{\{COUNTRY_NAME\}\}/g, 'العالم')
    .replace(/\{\{LIVE_PRICES_JS\}\}/g, '');

  html = html.replace(/^\s*<script type="application\/ld\+json"><\/script>\s*$/gm, '').replace(/\n{3,}/g, '\n\n');

  const dir = isMain ? baseDir : path.join(baseDir, karatObj.id);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
  const filePath = path.join(dir, 'index.html');
  fs.writeFileSync(filePath, html, 'utf8');
  console.log('  Generated: ' + filePath);
}

const weightInfos = [
  { id: 'w1', value: 1, label: '1 جرام', display: '1 جرام' },
  { id: 'w2_5', value: 2.5, label: '2.5 جرام', display: '2.5 جرام' },
  { id: 'w5', value: 5, label: '5 جرام', display: '5 جرام' },
  { id: 'w10', value: 10, label: '10 جرام', display: '10 جرام' },
  { id: 'w20', value: 20, label: '20 جرام', display: '20 جرام' },
  { id: 'w50', value: 50, label: '50 جرام', display: '50 جرام' },
  { id: 'w100', value: 100, label: '100 جرام', display: '100 جرام' },
  { id: 'w250', value: 250, label: '250 جرام', display: '250 جرام' },
  { id: 'w500', value: 500, label: '500 جرام', display: '500 جرام' },
  { id: 'w1000', value: 1000, label: '1 كيلو', display: '1 كيلو جرام' }
];

function buildToolSchema(title, description) {
  return {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: title,
    description: description.substring(0, 200),
    applicationCategory: 'FinanceApplication',
    offers: { '@type': 'Offer', priceCurrency: 'USD', availability: 'https://schema.org/InStock' }
  };
}

function generateWeightPage(country, weightInfo) {
  const flagCode = country.code === 'uk' ? 'gb' : country.code;
  const relPath = '../../../';
  const flagPath = getFlagPath(country.code, relPath);
  const weightValue = weightInfo.value;
  const weightLabel = weightInfo.display;
  const weightId = weightInfo.id;
  const fileName = country.code + '-' + weightId + '.html';

  const title = 'سعر ' + weightLabel + ' ذهب في ' + country.name + ' اليوم';
  const heroTitle = 'سعر ' + weightLabel + ' ذهب في ' + country.name;
  const heroDesc = 'تعرف على سعر ' + weightLabel + ' من الذهب في ' + country.name + ' محدث آنياً بجميع العيارات (24, 22, 21, 20, 18) مقومة بالعملة المحلية (' + country.currency + ').';
  const metaDesc = 'سعر ' + weightLabel + ' ذهب في ' + country.name + ' اليوم محدث لحظة بلحظة. تعرف على قيمة ' + weightLabel + ' ذهب بجميع العيارات بالعملة المحلية (' + country.currency + ').'.substring(0, 320);

  const articleJsonLd = {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: title,
    description: metaDesc.substring(0, 200),
    datePublished: '2026-06-13',
    dateModified: '2026-06-13',
    author: { '@type': 'Organization', name: 'ToolRar' }
  };

  const toolSchema = buildToolSchema(title, metaDesc);

  // Build weight navigation links
  const weightNavHtml = weightInfos.map(function(w) {
    const active = w.id === weightId;
    const href = country.code + '-' + w.id + '.html';
    if (active) {
      return '<span style="padding:8px 16px;border-radius:9999px;font-size:.82rem;font-weight:700;background:#6366F1;color:#fff;">' + w.label + '</span>';
    }
    return '<a href="' + href + '" style="padding:8px 16px;border-radius:9999px;font-size:.82rem;font-weight:700;background:#fff;border:1px solid #E2E8F0;color:#1e293b;text-decoration:none;transition:all .2s;" onmouseover="this.style.background=\'#6366F1\';this.style.color=\'#fff\';this.style.borderColor=\'#6366F1\'" onmouseout="this.style.background=\'#fff\';this.style.color=\'#1e293b\';this.style.borderColor=\'#E2E8F0\'">' + w.label + '</a>';
  }).join('');

  // Build karat cards for this weight
  const karatCardsHtml = karats.map(function(k) {
    const colors = { '24k': { bg: '#FEFCE8', dot: '#EAB308' }, '22k': { bg: '#FFFBEB', dot: '#D97706' }, '21k': { bg: '#FFF7ED', dot: '#F97316' }, '18k': { bg: '#FEF3C7', dot: '#B45309' } };
    const c = colors[k.id] || { bg: '#F8FAFC', dot: '#94a3b8' };
    return '<div style="background:' + c.bg + ';border:1px solid ' + c.dot + '30;border-radius:12px;padding:16px;text-align:center;">' +
      '<div style="font-size:.75rem;font-weight:700;color:#64748b;margin-bottom:6px;">عيار ' + k.name + '</div>' +
      '<div style="font-size:1.6rem;font-weight:900;color:#1e293b;direction:ltr;" id="wpKarat_' + k.id + '">--</div>' +
      '<div style="font-size:.7rem;color:#94a3b8;margin-top:4px;">' + country.currencyCode + '</div>' +
      '</div>';
  }).join('');

  // E-E-A-T content
  const s = countrySeeds[country.code] || countrySeeds.us;
  const eeatContent = '<div class="tool-desc-section">' +
    '<h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="24" height="24"><path d="M2 12L12 2l10 10"/><path d="M12 2v20"/></svg> ' + title + '</h2>' +
    '<div class="tool-desc-text">' +
    '<p>صفحة <strong>' + title + '</strong> تقدم لك السعر المباشر المحدث آنياً لـ ' + weightLabel + ' من الذهب في ' + country.name + ' بجميع العيارات المتاحة (24, 22, 21, 20, 18) مقومة بالعملة المحلية (<strong>' + country.currency + ' - ' + country.currencyCode + '</strong>).</p>' +
    '<h3>قيمة ' + weightLabel + ' ذهب في ' + country.name + '</h3>' +
    '<p>تختلف قيمة ' + weightLabel + ' من الذهب في ' + country.name + ' حسب العيار والنقاء. الذهب عيار 24 هو الأنقى والأعلى سعراً، يليه عيار 22 ثم عيار 21 الأكثر شيوعاً في الأسواق العربية، وعيار 18 المناسب للمشغولات اليومية. ' + (s.culture || '') + '</p>' +
    '<h3>نصائح لشراء ' + weightLabel + ' ذهب</h3>' +
    '<p>عند شراء ' + weightLabel + ' من الذهب في ' + country.name + '، تأكد من وجود الدمغة الرسمية التي تثبت العيار، واسأل عن المصنعية (تكلفة التصنيع) التي تضاف على سعر الذهب الخام. قارن الأسعار بين عدة محلات قبل الشراء، واحتفظ بالفواتير لإعادة البيع. ' + (country.investmentNote || '') + '</p>' +
    '<h3>أسعار ' + weightLabel + ' ذهب في دول أخرى</h3>' +
    '<p>يمكنك مقارنة سعر ' + weightLabel + ' ذهب في ' + country.name + ' مع الأسعار في الدول الأخرى من خلال القائمة أدناه. تختلف الأسعار حسب سعر الصرف والضرائب المحلية والرسوم الجمركية.</p>' +
    '</div></div>';

  const priceSection = '<div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:24px;">' +
    '<div style="display:flex;align-items:center;gap:14px;">' +
    '<div style="width:56px;height:42px;border-radius:8px;overflow:hidden;border:2px solid #E2E8F0;flex-shrink:0;background:#F8FAFC;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,.06);">' +
    '<img src="' + flagPath + '" alt="علم ' + country.name + '" style="width:100%;height:100%;object-fit:cover;">' +
    '</div>' +
    '<div>' +
    '<h2 style="font-size:1.25rem;font-weight:800;color:#1e293b;">' + title + '</h2>' +
    '<p style="font-size:.85rem;color:#64748b;">بالعملة المحلية (' + country.currency + ') - جميع العيارات</p>' +
    '</div>' +
    '</div>' +
    '</div>' +
    '<div style="background:linear-gradient(135deg,#EEF2FF,#E0E7FF);border-radius:16px;padding:20px;border:1px solid #C7D2FE;margin-bottom:20px;position:relative;overflow:hidden;">' +
    '<div style="position:absolute;top:12px;left:12px;font-size:.7rem;font-weight:700;padding:4px 10px;background:#DCFCE7;color:#16A34A;border-radius:20px;display:flex;align-items:center;gap:4px;"><span style="display:inline-block;width:6px;height:6px;background:#16A34A;border-radius:50%;animation:pulse 1.5s ease-in-out infinite;"></span> مباشر</div>' +
    '<p style="font-size:.85rem;font-weight:700;color:#64748b;margin-bottom:8px;">' + title + '</p>' +
    '<div style="display:flex;align-items:baseline;gap:6px;"><span style="font-size:2rem;font-weight:900;color:#1e293b;" id="weightPriceMain">جاري التحميل...</span><span style="font-size:1rem;font-weight:700;color:#64748b;">' + country.currencyCode + '</span></div>' +
    '<div style="font-size:.85rem;font-weight:700;display:flex;align-items:center;gap:6px;margin-top:4px;" id="changeWeight"><i class="fas fa-arrow-up"></i> -- (--%)</div>' +
    '</div>' +
    '<h3 style="font-size:1.1rem;font-weight:800;margin-bottom:12px;display:flex;align-items:center;gap:8px;color:#1e293b;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg> سعر ' + weightLabel + ' لجميع العيارات</h3>' +
    '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:24px;">' + karatCardsHtml + '</div>' +
    '<div style="background:#EFF6FF;border-radius:12px;padding:14px;font-size:.85rem;font-weight:600;color:#2563EB;display:flex;align-items:flex-start;gap:10px;margin-bottom:24px;">' +
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" style="flex-shrink:0;margin-top:2px;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>' +
    '<p>ملاحظة: السعر المعروض هو سعر الذهب الخام. عند الشراء من محلات الصاغة، يتم إضافة "المصنعية" والتي تختلف من تاجر لآخر.</p>' +
    '</div>' +
    '<h3 style="font-size:1.1rem;font-weight:800;margin-bottom:12px;display:flex;align-items:center;gap:8px;color:#1e293b;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><path d="M3 6h18"/><path d="M21 12H3"/><path d="M3 18h18"/></svg> تصفح أوزان أخرى في ' + country.name + '</h3>' +
    '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;">' + weightNavHtml + '</div>' +
    '<a href="../' + country.code + '.html" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;border-radius:12px;background:#6366F1;color:#fff;font-weight:700;font-size:.9rem;text-decoration:none;transition:all .2s;" onmouseover="this.style.boxShadow=\'0 4px 12px rgba(99,102,241,.4)\'" onmouseout="this.style.boxShadow=\'none\'">' +
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg> العودة لصفحة ' + country.name + '</a>';

  const livePricesJs = `
    const currencyCode = '${country.currencyCode}';
    const weightValue = ${weightValue};
    const karatList = ${JSON.stringify(karats)};
    async function fetchWeightGold() {
      try {
        let exchangeRate = 1;
        if (currencyCode !== 'USD') {
          try {
            const res = await fetch('https://open.er-api.com/v6/latest/USD');
            const data = await res.json();
            exchangeRate = data.rates[currencyCode] || 1;
          } catch(e) { exchangeRate = 1; }
        }
        let liveGoldUSD = 4612.90, prevCloseUSD = 4559.45;
        try {
          const yfRes = await fetch('https://api.allorigins.win/get?url=' + encodeURIComponent('https://query1.finance.yahoo.com/v8/finance/chart/GC=F'));
          const yfText = await yfRes.text();
          const parsed = JSON.parse(yfText);
          if (parsed && parsed.contents) {
            const data = JSON.parse(parsed.contents);
            if (data && data.chart && data.chart.result && data.chart.result[0]) {
              const meta = data.chart.result[0].meta;
              if (meta && meta.regularMarketPrice) {
                liveGoldUSD = meta.regularMarketPrice;
                prevCloseUSD = meta.chartPreviousClose || liveGoldUSD;
              }
            }
          }
        } catch(e) {}
        updateWeightPrices(liveGoldUSD, prevCloseUSD, exchangeRate);
      } catch(e) { console.error(e); }
    }
    function updateWeightPrices(liveUSD, prevUSD, rate) {
      const localOunce = liveUSD * rate;
      const localGram = localOunce / 31.1035;
      const currentPrice = localGram * weightValue;
      const dailyChangeUSD = liveUSD - prevUSD;
      const changePercent = (dailyChangeUSD / prevUSD) * 100;
      const changeLocal = (dailyChangeUSD * rate / 31.1035) * weightValue;
      const fmt = (n) => n.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
      document.getElementById('weightPriceMain').textContent = fmt(currentPrice);
      const color = changeLocal >= 0 ? '#10b981' : '#ef4444';
      const arrow = changeLocal >= 0 ? '▲' : '▼';
      const elChange = document.getElementById('changeWeight');
      if (elChange) elChange.innerHTML = '<span style="color:'+color+'">'+arrow+' '+fmt(Math.abs(changeLocal))+' ('+fmt(Math.abs(changePercent))+'%)</span>';
      karatList.forEach(function(k) {
        var p = localGram * k.factor * weightValue;
        var el = document.getElementById('wpKarat_' + k.id);
        if (el) el.textContent = fmt(p);
      });
    }
    document.addEventListener('DOMContentLoaded', fetchWeightGold);
    setInterval(fetchWeightGold, 15000);
  `;

  const faqItems = country.faqQs.map(function(q, i) {
    return { '@type': 'Question', name: q, acceptedAnswer: { '@type': 'Answer', text: 'يرجى متابعة الأسعار المباشرة في الصفحة لمعرفة أحدث التحديثات حول ' + q } };
  });
  const faqJsonLd = { '@context': 'https://schema.org', '@type': 'FAQPage', mainEntity: faqItems };

  // Country mini grid for sidebar
  const countryGridHtml = countries.map(function(c) {
    const ic = c.code === 'uk' ? 'gb' : c.code;
    const href = '../' + c.code + '.html';
    return '<a href="' + href + '" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:12px;background:#fff;border:1px solid #E2E8F0;text-decoration:none;color:#1e293b;transition:all .2s;font-size:.82rem;font-weight:600;" onmouseover="this.style.borderColor=\'#6366F1\';this.style.color=\'#6366F1\';this.style.boxShadow=\'0 4px 12px rgba(99,102,241,.15)\';this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.borderColor=\'#E2E8F0\';this.style.color=\'#1e293b\';this.style.boxShadow=\'none\';this.style.transform=\'none\'">' +
      '<img src="' + getFlagPath(c.code, relPath) + '" alt="علم ' + c.name + '" style="width:36px;height:27px;border-radius:4px;object-fit:cover;border:1px solid #E2E8F0;flex-shrink:0;box-shadow:0 1px 4px rgba(0,0,0,.06);">' +
      '<span>سعر الذهب في ' + c.name + ' اليوم</span>' +
    '</a>';
  }).join('');
  const countryGridSection = '<div class="related-section">' +
    '<h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="22" height="22"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg> أسعار الذهب في جميع دول العالم</h2>' +
    '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;">' + countryGridHtml + '</div>' +
    '</div>';

  const wBc = { "@context": "https://schema.org", "@type": "BreadcrumbList", "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "الرئيسية", "item": "https://toolrar.com/index.html" },
    { "@type": "ListItem", "position": 2, "name": "أدوات متنوعة", "item": "https://toolrar.com/General/" },
    { "@type": "ListItem", "position": 3, "name": "أسعار الذهب", "item": "https://toolrar.com/General/gold_price/index.html" },
    { "@type": "ListItem", "position": 4, "name": "الأوزان", "item": "https://toolrar.com/General/gold_price/weights/index.html" },
    { "@type": "ListItem", "position": 5, "name": title }
  ]};
  const wWs = { "@context": "https://schema.org", "@type": "WebSite", "name": "ToolRar", "url": "https://toolrar.com", "inLanguage": "ar" };
  const wSchema = { "@context": "https://schema.org", "@type": "WebPage", "name": title, "description": metaDesc, "inLanguage": "ar", "isPartOf": { "@type": "WebSite", "name": "ToolRar", "url": "https://toolrar.com" } };

  let html = templateHtml
    .replace(/\{\{TITLE\}\}/g, title)
    .replace(/\{\{META_DESC\}\}/g, metaDesc)
    .replace(/\{\{BREADCRUMB_JSONLD\}\}/g, JSON.stringify(wBc))
    .replace(/\{\{WEBSITE_JSONLD\}\}/g, JSON.stringify(wWs))
    .replace(/\{\{SCHEMA_JSONLD\}\}/g, JSON.stringify(wSchema))
    .replace(/\{\{FAQ_JSONLD\}\}/g, JSON.stringify(faqJsonLd))
    .replace(/\{\{ARTICLE_JSONLD\}\}/g, JSON.stringify(articleJsonLd))
    .replace(/\{\{PRODUCT_JSONLD\}\}/g, JSON.stringify(toolSchema))
    .replace(/\{\{HERO_TITLE\}\}/g, heroTitle)
    .replace(/\{\{HERO_DESC\}\}/g, heroDesc)
    .replace(/\{\{BREADCRUMB_LAST\}\}/g, title)
    .replace(/\{\{PRICE_SECTION\}\}/g, priceSection)
    .replace(/\{\{EEAT_CONTENT\}\}/g, eeatContent)
    .replace(/\{\{FAQS\}\}/g, '')
    .replace(/\{\{TOC_SECTION\}\}/g, '')
    .replace(/\{\{FAQ_SECTION\}\}/g, '')
    .replace(/\{\{KARAT_DISCOVERY\}\}/g, '')
    .replace(/\{\{COUNTRY_GRID_SECTION\}\}/g, countryGridSection)
    .replace(/\{\{COUNTRY_NAME\}\}/g, country.name)
    .replace(/\{\{LIVE_PRICES_JS\}\}/g, livePricesJs);

  const weightsDir = path.join(baseDir, 'weights');
  if (!fs.existsSync(weightsDir)) fs.mkdirSync(weightsDir, { recursive: true });
  const filePath = path.join(weightsDir, fileName);
  fs.writeFileSync(filePath, html, 'utf8');
  console.log('  Generated: ' + filePath);
}

// Generate all pages
console.log('Generating gold price pages...');

// Index pages
console.log('Creating index pages...');
generateIndexPage(null);
karats.forEach(k => generateIndexPage(k));

// Country pages
console.log('Creating country pages...');
countries.forEach(country => {
  console.log(`  ${country.name} (${country.code})...`);
  generatePage(country, null);
  karats.forEach(k => {
    const dir = path.join(baseDir, k.id);
    if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });
    generatePage(country, k);
  });
});

// Weight detail pages
console.log('Creating weight detail pages...');
countries.forEach(country => {
  console.log(`  Weight pages for ${country.name}...`);
  weightInfos.forEach(w => generateWeightPage(country, w));
});

console.log('Done! All pages generated successfully.');
