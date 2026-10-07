# Карта редиректов 301 (старый rostur.expert → новый GitHub Pages)

## ПЕРЕНОСИМЫЕ СТРАНИЦЫ (соответствие URL 1:1)
| Старый URL | Новый URL |
|---|---|
| /splav-po-dony-na-ploty | /splav-po-dony-na-ploty/ (лендинг = корень /index.html на 1-м этапе) |
| / (root) | / |
| /camp | /camp/ |
| /camping | /camping/ |
| /10-oshibok-novichkov-na-splave | /blog/10-oshibok-novichkov-na-splave/ |
| /chto-vzyat-na-splav | /blog/chto-vzyat-na-splav/ |
| /stoimost-arendy-plota | /stoimost-arendy-plota/ |
| /recepty-na-plotu | /blog/recepty-na-plotu/ |
| /recept-na-plotu | /blog/recept-na-plotu/ |
| /foto-na-plotu-idei | /blog/foto-na-plotu-idei/ |
| /splav-po-donu-na-plotah | /blog/splav-po-donu-na-plotah/ |
| /kak-organizovat-korporativnii-splav | /blog/kak-organizovat-korporativnii-splav/ |

## УДАЛЯЕМЫЕ СТРАНИЦЫ → 301 на ближайшую по смыслу остающуюся
| Удаляемый URL | 301 → | Обоснование |
|---|---|---|
| /hunting-and-fishing-in-the-volgograd-and-saratov-badger | /camp/ | охот. тематика сворачивается; camp — рыбалка+природа |
| /hunting-and-fishing-in-the-volgograd-and-saratov-beaver | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-fox | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-groundhog | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-wolf | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions | /camp/ | общий хаб удалённой тематики |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-bird | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-birds | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-duck | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-hog | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-moose | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-roeder | /camp/ | то же |
| /hunting-and-fishing-in-the-volgograd-and-saratov-regions-zayaz | /camp/ | то же |
| /arenda-baidarki-supboard | /splav-po-dony-na-ploty | «прокат водного транспорта» → флагман |
| /splav-baidarki | /splav-po-dony-na-ploty | байдарочный тур → основной продукт |
| /splav-na-baidarkakh-volgograd | /splav-po-dony-na-ploty | то же (гео совпадает) |
| /splav-katamaran | /splav-po-dony-na-ploty | то же |
| /offer | / | юридический текст переезжает в футер главной |
| /on-line-oplata | /zayavka (якорь главной) | функция бронирования встроена в лендинг |
| /auto-trip-volgograd | /travel-guide-to-the-volgograd-region → /camping | см. ниже |
| /travel-guide-to-the-volgograd-region | /camping | путеводитель → список локаций |
| /raiting-camping-russia | /camping | рейтинг кемпингов → подборка |
| /polnyj-gid-po-splavu-po-donu | /splav-po-dony-na-ploty | полный гид дублирует лендинг (каннибал!) |
| /fishing-volgograd-oblast | /camp | рыбалка → кемпинг на Дону с рыбалкой |
| прочий мусор (page3 и т.п., вне sitemap) | / | дефолтный fallback |

Примечание: на GitHub Pages серверные 301 недоступны — реализуется через meta-refresh+canonical на старом домене rostur.expert (конструктор) либо Cloudflare Rules, если домен переедет. Файлы-заглушки для каждого URL выше генерируются на фазе создания страниц.
