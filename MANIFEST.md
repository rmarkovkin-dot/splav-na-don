# MANIFEST — Фазы 1–3 (инвентаризация → главная → хаб кемпинга)

Ветка: `qwen-code-d1eb4aad-f761-42de-aa45-edf0d4f0a022` (все коммиты поверх `origin/main`).
Даты: 2026-10-07. Синхронизация с main — вручную через GitHub Desktop (см. инструкцию ниже).

## Созданные файлы (публичная часть сайта)
| Путь | Статус | Что это |
|---|---|---|
| `camp/index.html` | **создан** (коммит `c09caff`) | Лендинг-хаб кемпинга «Парк Вертячий»: H1, trust-bar, 4 плюса, тарифы 250/1000(ПОПУЛЯРНО)/750 ₽, доп. услуги (угли 500, мяч 500, САП 500/час, дрова 300, мангал/туалет/душ бесплатно), «Чем заняться», галерея 9 реальных фото, SEO-блок о рыбалке, форма (formsubmit+mailto-fallback), sticky mobile CTA («Позвонить»/«Забронировать», прячется на форме), JSON-LD LocalBusiness, canonical https://rostur.expert/camp/, мета из архива конструктора |
| `camp/assets/img/*` (22 файла, ~2.8 МБ) | **создано** | Реальные фото кемпинга, скачаны с selstorage CDN по списку из `_constructor/camp/index.html`; размеры width/height в HTML соответствуют реальным пикселям |
| `index.html` | **заменён** (коммит `d6e241a`, фиксы `4661e79`,`ed0f5a9`) | Новая главная — хаб двух направлений (ПЛОТЫ + КЕМПИНГ). Байдарки/катамараны/охота удалены. JSON-LD @graph LocalBusiness+WebSite, один H1, canonical https://rostur.expert/, блок ТОП-10 из 8 статей |
| `splav-po-dony-na-ploty/index.html` | **перемещён** (коммит `26157c6`) | Прежний корневой лендинг плота перенесён из корня в подпапку; canonical исправлен на https://rostur.expert/splav-po-dony-na-ploty/ |
| `assets/img/hero-plot-don.png`, `assets/img/park-vertyachiy-kemping.jpeg`, `assets/img/plot-s-plavaniya.jpeg` | **создано** | Локальные копии изображений для новой главной |

## Служебные файлы (НЕ выкладывать в публичный корень при деплое)
| Путь | Статус | Назначение |
|---|---|---|
| `_inventory/*.md` (15 файлов) | создано (ba6c308, 80739be, d07fb76, cb0da82) | Инвентарь 12 переносимых страниц: title/desc/canonical/OG/H-структура/полный текст/img+alt |
| `_inventory/META_COMPARISON.md` | создан (cb0da82) | Сверка sitemap-дампа и архива конструктора: 12/12 совпадений мета |
| `_inventory/REDIRECT_MAP.md` | создан (80739be) | Карта 301: 24 удаляемых URL → оставшиеся |
| `_inventory/compare_meta.py`, `_tools_validate.py` | создано | QA-скрипты (запуск локально, не часть сайта) |
| `_constructor/**` | изменён/создан | Архив конструктора (источник, не трогать) |
| `_constructor_sitemap_backup/**` | создано | Мой временный sitemap-дамп для сверки (можно удалить после приёмки) |

## Инструкция владельцу: ручная синхронизация через GitHub Desktop
1. **Branch → Pull origin/main** (в main лежит архив `_constructor/` — он уже смёржен в рабочую ветку коммитом `4f1839f`, конфликтов быть не должно).
2. **Скачать/checkout ветку** `qwen-code-d1eb4aad…` (GitHub Desktop: Current Branch → Pick another branch → Fetch origin при необходимости).
3. **Что заменить в main:**
   - `index.html` — полностью заменить на версию из ветки (новая главная);
   - добавить папку `splav-po-dony-na-ploty/` (index.html внутри);
   - добавить папку `camp/` целиком (index.html + assets/img/);
   - добавить `assets/img/` в корне (3 файла для главной).
4. **Что НЕ трогать:** `_constructor/` (архив-источник), `robots.txt`/`sitemap.xml` живого домена rostur.expert (обновить sitemap после деплоя новых URL).
5. **Что можно удалить после приёмки:** `_constructor_sitemap_backup/`, `*.b64`, `part*.html` (если остались в рабочей копии).
6. **Перед приёмом заявок:** подтвердить адрес `zakaz@rostur.expert` первым письмом FormSubmit (приходит автоматически на первую отправку формы).
7. После merge в main — проверить GitHub Pages / хостинг rostur.expert: редиректы из `_inventory/REDIRECT_MAP.md` настроить на уровне сервера (nginx) или Cloudflare Rules (GitHub Pages 301 не умеет).

## Итоги валидации `camp/index.html` (автопроверка python)
TAG BALANCE OK · H1=1 · иерархия без пропусков · img 10/10 alt+width+height+decoding, 9 lazy + 1 eager(LCP, fetchpriority+preload) · все локальные src существуют · JSON-LD LocalBusiness парсится · canonical=https://rostur.expert/camp/ · TODO=0 · endpoint formsubmit.co/ajax/zakaz@rostur.expert ✓ · mailto-fallback ✓ · битвых якорей нет · noopener на всех target=_blank · mobile CTA = «Позвонить»+«Забронировать», body padding-bottom:72px ✓
