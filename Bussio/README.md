# Bilet Satın Alma Platformu (Bussio)

Modern web teknolojileri kullanılarak geliştirilmiş, güvenli ve kullanıcı dostu otobüs bileti satış platformu.

## 🌟 Özellikler

### Genel Özellikler
- 🎫 Online bilet satın alma ve yönetimi
- 👥 Çoklu kullanıcı rolleri (Admin, Firma Admin, User)
- 💳 Sanal kredi sistemi ile güvenli ödeme
- 🎟️ İndirim kuponu sistemi
- 📄 PDF formatında bilet indirme
- 🔒 OWASP Top 10 güvenlik standartlarına uygun
- 📱 Responsive tasarım (mobil uyumlu)
- 🎨 Modern mavi tonlarda kullanıcı arayüzü

### Kullanıcı Özellikleri
- Sefer arama ve filtreleme
- Koltuk seçimi ile bilet satın alma
- Bilet iptal etme (kalkışa 1 saatten fazla varsa)
- Bilet geçmişi görüntüleme
- Kupon kodu kullanma
- PDF bilet indirme

### Firma Admin Özellikleri
- Firma seferlerini yönetme (CRUD)
- Sefer istatistikleri
- Doluluk oranı takibi

### Admin Özellikleri
- Otobüs firmaları yönetimi
- Firma Admin kullanıcıları oluşturma
- İndirim kuponları yönetimi
- Sistem istatistikleri

## 🛠️ Teknolojiler

- **Backend:** PHP 8.2
- **Database:** SQLite 3
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **Containerization:** Docker & Docker Compose
- **Web Server:** Apache 2.4

## 📋 Gereksinimler

- Docker Desktop (Windows/Mac) veya Docker Engine (Linux)
- Docker Compose v2.0+
- Web Browser (Chrome, Firefox, Safari, Edge)

## 🚀 Kurulum

### Docker ile Çalıştırma (Önerilen)

1. **Projeyi Klonlayın:**
```bash
git clone https://github.com/SquidWardWasHere/online-bilet-satin-al.git
cd bussio
```

2. **Docker Container'ı Başlatın:**
```bash
docker-compose up -d --build
```

3. **Tarayıcınızda Açın:**
```
http://localhost:8080
```

4. **Container'ı Durdurmak İçin:**
```bash
docker-compose down
```

### Manuel Kurulum (Docker Olmadan)

1. PHP 8.2 ve SQLite3 kurulu olmalıdır
2. Apache veya Nginx web sunucusu
3. Projeyi web sunucu dizinine kopyalayın
4. `config/config.php` dosyasındaki ayarları yapın
5. Web tarayıcısından erişin

## 👤 Varsayılan Kullanıcılar

### Sistem Yöneticisi (Admin)
- **Email:** admin@bussio.com
- **Şifre:** Admin123!
- **Yetkiler:** Tam sistem kontrolü

### Firma Yöneticisi (Firma Admin)
- **Email:** firmadmin@metro.com
- **Şifre:** Firma123!
- **Firma:** Metro Turizm
- **Yetkiler:** Firma seferleri yönetimi

### Standart Kullanıcı (User)
- **Email:** user@example.com
- **Şifre:** User123!
- **Bakiye:** 5.000 ₺
- **Yetkiler:** Bilet satın alma ve yönetimi

## 📊 Veritabanı Şeması

Proje otomatik olarak aşağıdaki tabloları oluşturur:

- **User:** Kullanıcı bilgileri (admin, firma admin, user)
- **Bus_Company:** Otobüs firmaları
- **Trips:** Seferler
- **Tickets:** Satın alınan biletler
- **Booked_Seats:** Rezerve edilen koltuklar
- **Coupons:** İndirim kuponları
- **User_Coupons:** Kullanılan kuponlar

## 📁 Proje Yapısı

```
bilet-satin-alma/
├── admin/                  # Admin panel sayfaları
│   ├── index.php
│   ├── companies.php
│   ├── company_admins.php
│   └── coupons.php
├── company_admin/          # Firma admin panel
│   ├── index.php
│   └── trips.php
├── assets/
│   └── css/
│       └── style.css       # Ana stil dosyası
├── config/
│   ├── config.php          # Yapılandırma
│   └── database.php        # Veritabanı bağlantısı
├── includes/
│   ├── header.php
│   ├── footer.php
│   ├── security.php        # Güvenlik fonksiyonları
│   └── functions.php       # Yardımcı fonksiyonlar
├── database/               # SQLite veritabanı (otomatik oluşur)
├── index.php               # Ana sayfa
├── login.php               # Giriş sayfası
├── register.php            # Kayıt sayfası
├── book_ticket.php         # Bilet satın alma
├── my_tickets.php          # Biletlerim
├── download_ticket.php     # PDF bilet
├── Dockerfile
├── docker-compose.yml
└── README.md
```

## 🎯 Kullanım Senaryoları

### Yeni Kullanıcı Kaydı
1. Ana sayfada "Kayıt Ol" butonuna tıklayın
2. Formu doldurun (şifre en az 8 karakter, 1 büyük harf, 1 küçük harf, 1 rakam, 1 özel karakter)
3. Kayıt sonrası otomatik olarak 5.000 ₺ bakiye yüklenir

### Bilet Satın Alma
1. Ana sayfada kalkış/varış noktası ve tarih seçin
2. Seferleri listeleyin
3. İstediğiniz seferi seçin
4. Koltuk numaranızı seçin
5. İsteğe bağlı kupon kodu girin
6. Ödemeyi tamamlayın

### Bilet İptal Etme
1. "Biletlerim" sayfasına gidin
2. İptal etmek istediğiniz biletin yanındaki "İptal Et" butonuna tıklayın
3. Onaylayın (kalkışa 1 saatten fazla zaman varsa iptal edilir)
4. Ücret otomatik olarak bakiyenize iade edilir

### Firma Admin - Sefer Ekleme
1. Firma admin paneline giriş yapın
2. "Seferler" menüsüne tıklayın
3. Sefer bilgilerini doldurun
4. "Sefer Ekle" butonuna tıklayın

### Admin - Kupon Oluşturma
1. Admin paneline giriş yapın
2. "Kuponlar" menüsüne tıklayın
3. Kupon bilgilerini girin (kod, indirim oranı, limit, süre)
4. "Kupon Ekle" butonuna tıklayın

## 🐛 Sorun Giderme

### Docker container başlamıyor
```bash
# Container loglarını kontrol edin
docker-compose logs

# Container'ı yeniden başlatın
docker-compose down
docker-compose up -d --build
```

### Veritabanı hatası
```bash
# Database klasörünü ve içeriğini silin, yeniden oluşturulsun
rm -rf database/
docker-compose restart
```

### Port 8080 kullanımda
```yaml
# docker-compose.yml dosyasında port'u değiştirin
ports:
  - "8090:80"  # 8080 yerine 8090 kullanın
```

## 📝 Lisans

MIT License

## 👨‍💻 Geliştirici

Hasan Ali Kahraman

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

