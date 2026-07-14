document.querySelectorAll('.faq-question').forEach((btn)=>{btn.addEventListener('click',()=>btn.closest('.faq-item').classList.toggle('open'))});
const search=document.querySelector('[data-concept-search]');
if(search){const cards=[...document.querySelectorAll('[data-concept-card]')];search.addEventListener('input',()=>{const q=search.value.trim().toLowerCase();cards.forEach(card=>{card.style.display=card.textContent.toLowerCase().includes(q)?'block':'none'})})}
let catTimer,langTimer,isDark=false;
function openCatDD(){document.getElementById('catDropdown')?.classList.add('open')}
function closeCatDD(){document.getElementById('catDropdown')?.classList.remove('open')}
function scheduleCloseCatDD(){catTimer=setTimeout(closeCatDD,180)}
function cancelCloseCatDD(){clearTimeout(catTimer)}
function openLangDD(){document.getElementById('langDropdown')?.classList.add('open')}
function closeLangDD(){document.getElementById('langDropdown')?.classList.remove('open')}
function scheduleCloseLangDD(){langTimer=setTimeout(closeLangDD,180)}
function cancelCloseLangDD(){clearTimeout(langTimer)}
function toggleLangDD(){document.getElementById('langDropdown')?.classList.toggle('open')}
function toggleMobileLangDD(){document.getElementById('mobileLangDropdown')?.classList.toggle('open')}
function closeMobileLangDD(){document.getElementById('mobileLangDropdown')?.classList.remove('open')}
function updateDarkUI(){const darkIcon=document.getElementById('darkIcon'),darkLabel=document.getElementById('darkLabel'),mobileDarkIcon=document.getElementById('mobileDarkIcon');if(isDark){document.documentElement.classList.add('dark');localStorage.setItem('toolrar-theme','dark');if(darkLabel)darkLabel.textContent='الوضع النهاري';[darkIcon,mobileDarkIcon].forEach(i=>{if(i)i.innerHTML='<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>'})}else{document.documentElement.classList.remove('dark');localStorage.setItem('toolrar-theme','light');if(darkLabel)darkLabel.textContent='الوضع الليلي';[darkIcon,mobileDarkIcon].forEach(i=>{if(i)i.innerHTML='<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>'})}}
function toggleDark(){isDark=!isDark;updateDarkUI()}
document.getElementById('darkToggle')?.addEventListener('click',toggleDark);
document.getElementById('mobileDarkToggle')?.addEventListener('click',toggleDark);
document.getElementById('hamburgerBtn')?.addEventListener('click',()=>document.getElementById('mobileMenu')?.classList.toggle('open'));
if(localStorage.getItem('toolrar-theme')==='dark')isDark=true;updateDarkUI();
