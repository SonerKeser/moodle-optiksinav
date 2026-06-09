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

**Bitnami tuzağı — iki aşamalı ilk kurulum:** Eklenti bind-mount'u, Bitnami'nin temiz
ilk kurulumunu bozar (dizin "boş değil" görünür). Bu yüzden:

```bash
# 1. AŞAMA — temiz kurulum (compose'da plugin mount satırları YORUMDA):
docker compose up -d
# Loglarda "Moodle setup finished" görene kadar bekle:
docker compose logs -f moodle
# http://localhost:8081  (admin / Admin12345!)

# 2. AŞAMA — eklenti geliştirme:
# docker-compose.yml içindeki plugin bind-mount satırlarının yorumunu kaldır, sonra:
docker compose up -d            # moodle konteynerini mount ile yeniden oluşturur
docker compose exec moodle php /opt/bitnami/moodle/admin/cli/upgrade.php --non-interactive
```

Eklenti kaynağı `./plugins/mod/optiksinav` → konteynerde `mod/optiksinav` olarak mount edilir.
Kod değişince: **Site yönetimi → Bildirimler** üzerinden, veya yukarıdaki `cli/upgrade.php` ile yükselt.

> **CLI tuzağı:** Apache `daemon` kullanıcısıyla çalışır. CLI scriptlerini `root` (varsayılan exec)
> ile çalıştırırsan moodledata içinde root-sahipli cache dizinleri oluşur ve web "Invalid permissions
> detected when trying to create a directory" hatası verir. Bu yüzden CLI'ları **daima `--user daemon`** ile çalıştır:
> ```bash
> docker compose exec --user daemon moodle php /opt/bitnami/moodle/admin/cli/upgrade.php --non-interactive
> ```
> Yine de bu hatayı alırsan: `docker compose exec moodle chown -R daemon:daemon /bitnami/moodledata`

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
