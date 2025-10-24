# Bussio


🚌 BUSSİO - Online Otobüs Bileti Satış Platformu

BUSSİO, kullanıcıların online olarak otobüs bileti arayıp satın alabileceği, firmalar için özel yönetim paneli ve admin kontrol sistemi barındıran modern bir web uygulamasıdır.
PHP, SQLite ve Docker teknolojileriyle geliştirilmiş, güvenli, dinamik ve kullanıcı dostu bir bilet satış deneyimi sunar.


✨ Özellikler
👥 Kullanıcı Özellikleri

Hesap Yönetimi: Kayıt olma, giriş yapma ve profil bilgilerini güncelleme işlemleri
Sefer Arama: Kalkış – varış noktası ve tarih kriterlerine göre uygun sefer arama
Koltuk Seçimi: Dinamik koltuk planı üzerinden koltuk seçimi
Cinsiyet Kontrolü: Farklı cinsiyet yolcuların yan yana oturmasını önleyen sistem
Bilet Satın Alma: Güvenli ödeme işlemleriyle bilet oluşturma
PDF Bilet İndirme: Satın alınan biletlerin PDF formatında çıktısının alınabilmesi
Bilet Geçmişi: Önceki biletlerin görüntülenmesi 
Bakiye Yönetimi: Hesap bakiyesi görüntüleme ve yönetme özellikleri

🏢 Firma Yetkilisi Özellikleri

Sefer Yönetimi: Yeni sefer oluşturma, mevcut seferleri düzenleme veya silme
Otobüs Yönetimi: Farklı otobüs tiplerinin (2+1, 2+2) tanımlanması
Satış Takibi: Firma bazlı seferlerin satış durumlarını anlık izleme
Bilet Yönetimi: Satılan biletlerin görüntülenmesi ve kontrol edilmesi

👨‍💼 Admin Özellikleri

Kullanıcı Yönetimi: Tüm kullanıcıların listelenmesi, düzenlenmesi veya engellenmesi
Firma Yönetimi: Yeni firma hesaplarını onaylama ve mevcut firmaları yönetme
Sistem Kontrol Paneli: Genel istatistikler, sistem durumu ve yönetim işlemleri
Veri Yönetimi: Veritabanı işlemleri, yedekleme ve raporlama

🧩 Kullanılan Teknolojiler  

| Katman | Teknoloji |
| :------------------- | :---------------------- |
| **Backend** | PHP 8.x |
| **Veritabanı** | SQLite |
| **Frontend** | HTML5, CSS3, JavaScript |
| **Containerization** | Docker & Docker Compose |
| **Web Sunucusu** | Apache |

📱 Kullanım Senaryoları

Bilet Satın Alma: Kullanıcı olarak giriş yap, sefer ara, koltuğunu seç ve biletini satın al.
Sefer Yönetimi: Firma yetkilisi olarak yeni sefer ekle, otobüs düzenini yönet.
Sistem Yönetimi: Admin olarak kullanıcı, firma ve genel sistem süreçlerini kontrol et.


⚙️ Kurulum ve Çalıştırma  

1. **Depoyu klonla:**

   ```bash
   git clone https://github.com/SquidWardWasHere/online-bilet-satin-al.git
   
2. **Docker ortamını başlat:**

   ```bash
   docker-compose up -d
   ```
   
3. **Tarayıcıdan eriş:**

   ```bash
   http://localhost:8080
   ```




