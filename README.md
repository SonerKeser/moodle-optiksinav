# OptikSınav — Moodle Optik Sınav Eklentisi

PDF soru kitapçığı + A–E **optik cevap formu** + **net puanlama** (doğru − yanlış×ceza)
deneyimini, ayrı bir uygulama yerine **native Moodle eklentisi** olarak sunar. Kimlik
doğrulama, kayıt/yetki kontrolü, not defteri, yedekleme ve gizlilik Moodle'dan gelir —
sızan token/şifre yok, URL ile kimlik taklidi yok, sunucu-zorlamalı süre.

> Bu proje, eski `MoodleExam` (.NET, ayrı uygulama) prototipinin profesyonel, güvenli ve
> tam entegre yeniden inşasıdır.

## İçindekiler
- [Bileşenler](#bileşenler)
- [Özellikler](#özellikler)
- [Gereksinimler](#gereksinimler)
- [Kurulum (üretim)](#kurulum-üretim)
- [Kullanım](#kullanım)
- [Geliştirme ortamı (Docker)](#geliştirme-ortamı-docker)
- [Test & kalite](#test--kalite)
- [Mimari](#mimari)
- [Güvenlik notları](#güvenlik-notları)
- [Sürüm geçmişi](#sürüm-geçmişi)

## Bileşenler
| Eklenti | Tür | Görev |
|---|---|---|
| **`mod_optiksinav`** | Aktivite modülü | Ders içi sınav: PDF görüntüleyici, optik form, süre, puanlama, raporlar |
| **`local_optiksinav`** | Yerel eklenti | Dersler-üstü **merkezi sınav paneli** (Site yönetimi → Raporlar) |

## Özellikler
- **PDF soru kitapçığı** — Moodle File API ile erişim kontrollü servis; öğrenciye tarayıcı içi gösterilir.
- **Optik cevap formu** — A–E (yapılandırılabilir 2–12 şık), anında otomatik kaydetme, canlı cevaplanan/boş sayacı.
- **Net puanlama** — doğru − (yanlış × ceza oranı); anahtarı girilmemiş sorular puanlamaya katılmaz.
- **Sunucu-zorlamalı süre** — son an sunucuda tutulur; süre dolunca otomatik teslim (anında + cron yedeği).
- **Not defteri entegrasyonu** — net puan ölçeklenip Moodle gradebook'a otomatik işlenir.
- **Cevap anahtarı editörü** — öğretmen her soru için doğru şıkkı tek ekranda girer.
- **Öğretmen raporu** — tüm denemeler (D/Y/B/Net/Not/süre/sekme-değişimi) + **CSV dışa aktarma**.
- **Merkezi panel** — site genelindeki tüm sınavlar, ders bazında, ortalama net ile.
- **Çözüm kitapçığı** — yalnızca öğretmene; öğrenciye **ancak kendi denemesi bitince**.
- **Anti-hile** — Safe Exam Browser zorunluluğu (opsiyonel), sekme-değişimi (odak kaybı) takibi, güvenli sayfa düzeni.
- **Erişilebilirlik** — her soru `fieldset`/`legend`, `aria-live` sayaçlar, `role=timer`, klavyeyle "temizle".
- **Tam entegrasyon** — Privacy (GDPR) API, Backup/Restore, completion, olaylar (events), Türkçe + İngilizce dil paketi.

## Gereksinimler
- Moodle **4.5 LTS** (2024100700) veya üzeri
- PHP **8.1+**
- MySQL/MariaDB veya PostgreSQL (Moodle'ın desteklediği herhangi biri)

## Kurulum (üretim)
1. `plugins/mod/optiksinav` klasörünü Moodle kurulumunuzda `mod/optiksinav` altına kopyalayın.
2. (İsteğe bağlı, merkezi panel için) `plugins/local/optiksinav` klasörünü `local/optiksinav` altına kopyalayın.
3. Yönetici olarak Moodle'a girin → **Site yönetimi → Bildirimler** → veritabanı yükseltmesini onaylayın.
4. Alternatif CLI: `php admin/cli/upgrade.php --non-interactive`

Hazır ZIP'ler `dist/` altında üretilebilir (bkz. [Test & kalite](#test--kalite)).

## Kullanım
### Öğretmen
1. Derste **Etkinlik veya kaynak ekle → Optik Sınav**.
2. Sınav adı, soru sayısı, şık sayısı, süre, erişim tarihleri, ceza oranı, (ops.) SEB zorunluluğunu girin.
3. **Soru kitapçığı PDF**'ini ve (ops.) **çözüm kitapçığı PDF**'ini yükleyin.
4. Aktivite sayfasında **"Cevap anahtarını düzenle"** ile her sorunun doğru şıkkını girin.
5. Hazırlık durumu ✓ olunca öğrenciler girebilir. Sonuçları **"Raporu gör"** ile izleyin (CSV indirilebilir).
6. Tüm sınavları tek yerden görmek için: **Site yönetimi → Raporlar → Optik Sınav Merkezi**.

### Öğrenci
1. Derste sınava tıklayın → **"Sınavı başlat"**.
2. Solda PDF kitapçık, sağda optik form. Şık işaretleyin (otomatik kaydedilir); çift tık/"temizle" ile silin.
3. Süre üstte sayar; bitince otomatik teslim olur. **"Sınavı bitir"** ile erken teslim edebilirsiniz.
4. Sonuç ekranında doğru/yanlış/boş/net, not ve (varsa) çözüm kitapçığı gösterilir.

## Geliştirme ortamı (Docker)
Gereksinim: Docker + Docker Compose.

> **Bitnami tuzağı — iki aşamalı ilk kurulum:** Eklenti bind-mount'u, Bitnami'nin temiz
> ilk kurulumunu bozar. Bu yüzden:

```bash
# 1. AŞAMA — temiz kurulum (compose'da plugin mount satırları YORUMDA):
docker compose up -d
docker compose logs -f moodle           # "Moodle setup finished" görene kadar bekle
# http://localhost:8081  (admin / Admin12345!)

# 2. AŞAMA — eklenti geliştirme:
# docker-compose.yml içindeki plugin mount satırlarının yorumunu kaldır, sonra:
docker compose up -d
docker compose exec --user daemon moodle php /opt/bitnami/moodle/admin/cli/upgrade.php --non-interactive
```

> **CLI tuzağı:** Apache `daemon` kullanıcısıyla çalışır. CLI'ları **daima `--user daemon`** ile
> çalıştır; aksi halde root-sahipli cache "Invalid permissions" hatası verir
> (düzeltme: `docker compose exec moodle chown -R daemon:daemon /bitnami/moodledata`).
> Windows Git Bash'te docker yollarında `MSYS_NO_PATHCONV=1` gerekir.

`dev/` altındaki yardımcı CLI fixture'ları (geliştirme amaçlı, eklentiye dahil değil):
`seed.php`, `seed2.php` (ders+sınav+öğrenci üretir), `backuptest.php`, `privacytest.php`.

## Test & kalite
**Birim testleri (PHPUnit):** `mod/optiksinav/tests/` — 12 test, 35 assertion (puanlama, cevap
anahtarı upsert, süre, not defteri, privacy export/silme).

```bash
# Konteynerde dev bağımlılıklarını kurduktan ve phpunit ortamını init ettikten sonra:
php vendor/bin/phpunit mod/optiksinav/tests/locallib_test.php
php vendor/bin/phpunit mod/optiksinav/tests/privacy/provider_test.php
```

**Behat:** `mod/optiksinav/tests/behat/optiksinav.feature` — öğretmen kurulumu + öğrenci başlatma senaryoları.

**Kod standardı (moodle-cs / PHPCS):** **0 hata.** Kalan uyarılar yalnızca tavsiye niteliğindedir
(çoğunlukla Türkçe satır-içi yorumların büyük harfle başlaması ve opsiyonel `MOODLE_INTERNAL`
korumaları) ve Moodle eklenti dizininde engelleyici değildir.

```bash
phpcs --standard=moodle mod/optiksinav local/optiksinav
```

**ZIP paketleme:**
```bash
cd plugins/mod && zip -r ../../dist/mod_optiksinav.zip optiksinav -x '*/tests/*'
cd ../local && zip -r ../../dist/local_optiksinav.zip optiksinav
```

## Mimari
İlişkisel şema (eski sistemin `"1.A-2.C"` string'lerinin tam tersi):
- `optiksinav` — aktivite örneği (ayarlar, süre, ceza oranı, SEB).
- `optiksinav_question` — cevap anahtarı (her soru için doğru şık).
- `optiksinav_attempt` — öğrenci denemesi (durum, süre, D/Y/B/net/not, odak-kaybı).
- `optiksinav_attempt_answer` — denemedeki her sorunun işaretlenen şıkkı.

PDF'ler Moodle **File API** ile (`exampdf`, `answerpdf` alanları); erişim `pluginfile.php`
geri çağrısında **capability** ile kontrol edilir.

## Güvenlik notları
- Kimlik **her zaman `$USER` oturumundan** alınır — URL'de `userId` yoktur (eski sistemin en kritik açığı kapatıldı).
- Süre **sunucu tarafında** zorlanır; geç yazımlar reddedilir; süresi geçen denemeler cron ile kapatılır.
- Çözüm PDF'i erişimi capability + "deneme bitti mi" kontrolüne bağlıdır.
- SEB zorunluluğu **request-hash başlığı** varlığını kontrol eder; tam config-key/browser-exam-key
  doğrulaması bu sürümde **kapsam dışıdır** ve istenirse eklenebilir.

## Sürüm geçmişi
- **v1.0.0-beta** (Faz 6) — PHPUnit/Behat testleri, moodle-cs (0 hata), paketleme, dokümantasyon.
- v0.6.0 (Faz 5) — cron oto-kapatma, SEB gate, odak-kaybı takibi, güvenli düzen, erişilebilirlik.
- v0.5.0 (Faz 4) — öğretmen raporu (CSV), merkezi panel, Privacy API, Backup/Restore.
- v0.4.0 (Faz 3) — not defteri entegrasyonu.
- v0.3.0 (Faz 2) — öğrenci sınav deneyimi (deneme state machine, otomatik kaydetme, net puanlama).
- v0.2.0 (Faz 1) — ilişkisel veri modeli, File API PDF yükleme, cevap anahtarı editörü.
- v0.1.0 (Faz 0) — ortam + kurulabilir eklenti iskeleti.

## Lisans
GNU GPL v3 or later (Moodle ile uyumlu).
