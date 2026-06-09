# OptikSınav — Moodle Optik Sınav Eklentisi

`MoodleExam` (.NET prototip) projesinin profesyonel, Moodle ile tam entegre yeniden inşası.

PDF soru kitapçığı + A–E optik cevap formu + net puanlama (doğru − yanlış×ceza) deneyimini,
ayrı bir uygulama yerine **native Moodle eklentisi** olarak sunar. Böylece kimlik doğrulama,
kayıt/yetki kontrolü, gradebook, yedekleme ve gizlilik Moodle'dan gelir — sızan token/şifre yok.

## Bileşenler
- **`mod_optiksinav`** — ders içi aktivite (çekirdek sınav deneyimi).
- **`local_optiksinav`** — dersler-üstü merkezi yönetim/raporlama paneli *(Faz 4)*.

## Geliştirme ortamı (Docker)
Gereksinim: Docker + Docker Compose.

```bash
docker compose up -d        # Moodle 4.5 LTS + MariaDB başlat
# http://localhost:8080  (admin / Admin12345!)
```

Eklenti kaynağı `./plugins/mod/optiksinav` → konteynerde `mod/optiksinav` olarak mount edilir.
Kod değişince: **Site yönetimi → Bildirimler** üzerinden yükseltmeyi çalıştır
(veya `php admin/cli/upgrade.php`).

```bash
docker compose down         # durdur (veriyi korur)
docker compose down -v      # her şeyi sil (temiz başlangıç)
```

## Yol haritası (fazlar)
- **Faz 0** — Ortam + kurulabilir eklenti iskeleti ✅
- **Faz 1** — Veri modeli (sorular/cevap anahtarı/denemeler) + PDF yükleme
- **Faz 2** — Öğrenci sınav deneyimi (PDF.js, optik form, sunucu-zorlamalı süre)
- **Faz 3** — Net puanlama + gradebook entegrasyonu
- **Faz 4** — Raporlama + `local_` merkezi panel + Privacy/Backup
- **Faz 5** — Güvenlik/anti-hile/erişilebilirlik
- **Faz 6** — Test (PHPUnit/Behat) + paketleme

## Lisans
GNU GPL v3 or later (Moodle ile uyumlu).
