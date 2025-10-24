# Bilet Satın Alma Platformu - Kullanım Kılavuzu

## İçindekiler
1. [Hızlı Başlangıç](#hızlı-başlangıç)
2. [Kullanıcı Rolleri](#kullanıcı-rolleri)
3. [Özellikler](#özellikler)
4. [Güvenlik](#güvenlik)

## Hızlı Başlangıç

### Docker ile Kurulum

1. **Docker Desktop'ı İndirin ve Kurun**
   - Windows: https://www.docker.com/products/docker-desktop
   - Mac: https://www.docker.com/products/docker-desktop
   - Linux: https://docs.docker.com/engine/install/

2. **Projeyi GitHub'dan İndirin**
   ```bash
   git clone https://github.com/SquidWardWasHere/online-bilet-satin-al.git
   
   ```

3. **Uygulamayı Başlatın**
   ```bash
   docker-compose up -d
   ```

4. **Tarayıcınızda Açın**
   - Adres: http://localhost:8080

5. **Giriş Yapın**
   - Admin: admin@bussio.com / Admin123!
   - Firma Admin: firmadmin@metro.com / Firma123!
   - Kullanıcı: user@example.com / User123!

## Kullanıcı Rolleri

### 1. Ziyaretçi (Giriş Yapmamış)
**Yapabilecekleri:**
- Seferleri arama
- Sefer detaylarını görüntüleme
- Kayıt olma
- Giriş yapma

**Yapamayacakları:**
- Bilet satın alma
- Koltuk rezervasyonu

### 2. User (Yolcu)
**Yapabilecekleri:**
- Tüm ziyaretçi özellikleri
- Bilet satın alma
- Koltuk seçimi
- Kupon kodu kullanma
- Bilet iptal etme (kalkışa 1 saatten fazla varsa)
- PDF bilet indirme
- Hesap bakiyesi görüntüleme

**Başlangıç Bakiyesi:** 5.000 ₺

### 3. Firma Admin
**Yapabilecekleri:**
- Firma seferlerini görüntüleme
- Yeni sefer ekleme
- Sefer düzenleme
- Sefer silme
- Firma istatistiklerini görüntüleme
- Doluluk oranlarını takip etme

**Kısıtlamalar:**
- Sadece kendi firmasının seferlerini yönetebilir
- Diğer firmaların verilerine erişemez

### 4. Admin (Sistem Yöneticisi)
**Yapabilecekleri:**
- Tüm sistem kontrolü
- Otobüs firması ekleme/düzenleme/silme
- Firma Admin oluşturma
- Firmaya admin atama
- İndirim kuponu oluşturma/düzenleme/silme
- Sistem istatistiklerini görüntüleme

## Özellikler

### Sefer Arama ve Listeleme
1. Ana sayfada "Nereden", "Nereye" ve "Tarih" alanlarını doldurun
2. "Sefer Ara" butonuna tıklayın
3. Sonuçları inceleyin
4. İstediğiniz seferin "Detaylar" butonuna tıklayın

### Bilet Satın Alma Süreci

**Adım 1: Sefer Seçimi**
- Uygun seferi bulun ve "Bilet Satın Al" butonuna tıklayın

**Adım 2: Koltuk Seçimi**
- Yeşil koltuklar: Boş (seçilebilir)
- Kırmızı koltuklar: Dolu (seçilemez)
- Mavi koltuklar: Seçtiğiniz koltuklar
- İstediğiniz koltuğa tıklayarak seçim yapın

**Adım 3: Kupon Kodu (Opsiyonel)**
- Varsa kupon kodunuzu girin
- İndirim otomatik olarak uygulanacak

**Adım 4: Ödeme**
- Toplam tutarı kontrol edin
- Bakiyenizin yeterli olduğundan emin olun
- "Ödemeyi Tamamla" butonuna tıklayın

### Bilet İptal Etme

**Koşullar:**
- Bilet aktif olmalı
- Kalkışa en az 1 saat olmalı

**İptal Süreci:**
1. "Biletlerim" sayfasına gidin
2. İptal etmek istediğiniz biletin yanındaki "İptal Et" butonuna tıklayın
3. Onaylayın
4. Ücret otomatik olarak bakiyenize iade edilir

### PDF Bilet İndirme

1. "Biletlerim" sayfasına gidin
2. İlgili biletin yanındaki "PDF İndir" butonuna tıklayın
3. Açılan sayfada "Yazdır" veya "PDF Olarak Kaydet" seçeneğini kullanın

### Firma Admin - Sefer Yönetimi

**Yeni Sefer Ekleme:**
1. Firma Admin paneline giriş yapın
2. "Seferler" menüsüne tıklayın
3. Form alanlarını doldurun:
   - Kalkış Şehri
   - Varış Şehri
   - Kalkış Zamanı
   - Varış Zamanı
   - Bilet Fiyatı
   - Koltuk Kapasitesi
4. "Sefer Ekle" butonuna tıklayın

**Sefer Düzenleme:**
1. Sefer listesinde düzenlemek istediğiniz seferin "Düzenle" butonuna tıklayın
2. Açılan formda değişiklikleri yapın
3. "Güncelle" butonuna tıklayın

**Sefer Silme:**
1. Silmek istediğiniz seferin "Sil" butonuna tıklayın
2. Onaylayın

### Admin - Sistem Yönetimi

**Firma Ekleme:**
1. Admin paneline giriş yapın
2. "Firmalar" menüsüne tıklayın
3. Firma adını girin
4. "Firma Ekle" butonuna tıklayın

**Firma Admin Oluşturma:**
1. "Firma Adminleri" menüsüne tıklayın
2. Formu doldurun:
   - Ad Soyad
   - E-posta
   - Şifre (güvenlik gereksinimlerine uygun)
   - Firma seçimi
3. "Firma Admin Ekle" butonuna tıklayın

**Kupon Oluşturma:**
1. "Kuponlar" menüsüne tıklayın
2. Kupon bilgilerini girin:
   - Kupon Kodu (örn: SUMMER2025)
   - İndirim Oranı (%)
   - Kullanım Limiti
   - Son Kullanma Tarihi
3. "Kupon Ekle" butonuna tıklayın

## Güvenlik

### Şifre Gereksinimleri
- Minimum 8 karakter
- En az 1 büyük harf
- En az 1 küçük harf
- En az 1 rakam
- En az 1 özel karakter (@$!%*?&)

**Örnek Güçlü Şifre:** MyPass123!

### Güvenli Kullanım İpuçları
1. Şifrenizi kimseyle paylaşmayın
2. Farklı platformlarda farklı şifreler kullanın
3. Düzenli olarak şifrenizi değiştirin
4. Genel bilgisayarlarda "Beni Hatırla" kullanmayın
5. İşiniz bittiğinde mutlaka çıkış yapın

## Sık Sorulan Sorular (SSS)

### Biletimi nasıl iptal edebilirim?
Kalkışa en az 1 saat varsa "Biletlerim" sayfasından iptal edebilirsiniz. İptal ettiğinizde ücret otomatik olarak iade edilir.

### Kupon kodum çalışmıyor, ne yapmalıyım?
- Kupon kodunun doğru yazıldığından emin olun
- Kuponun süresinin dolmadığını kontrol edin
- Kupon kullanım limitinin dolmadığını kontrol edin
- Daha önce bu kuponu kullanmadığınızdan emin olun

### Bakiyemi nasıl artırabilirim?
Şu anda bakiye yükleme özelliği bulunmamaktadır. Test amaçlı olarak kullanıcılar 5.000 ₺ başlangıç bakiyesi ile oluşturulur.

### Aynı seferde birden fazla koltuk alabilir miyim?
Evet, koltuk seçim ekranında birden fazla koltuk seçerek toplu bilet alabilirsiniz.

### PDF biletimi nasıl indirebilirim?
"Biletlerim" sayfasından "PDF İndir" butonuna tıklayın. Açılan sayfada tarayıcınızın yazdır özelliğini kullanarak PDF olarak kaydedebilirsiniz.

## Sorun Giderme

### Giriş yapamıyorum
- E-posta adresinizi doğru yazdığınızdan emin olun
- Şifrenizi kontrol edin (büyük/küçük harf duyarlıdır)
- Tarayıcınızın çerezleri kabul ettiğinden emin olun

### Sefer bulamıyorum
- Kalkış ve varış şehirlerinin doğru olduğundan emin olun
- Geçmiş tarih seçmediğinizden emin olun
- O tarih için sefer olmayabilir, farklı bir tarih deneyin

### Ödeme yapamıyorum
- Bakiyenizin yeterli olduğunu kontrol edin
- Seçtiğiniz koltukların hala boş olduğunu kontrol edin
- Sayfayı yenileyin ve tekrar deneyin

## Destek

Sorunlarınız için:
1. Bu kılavuzu inceleyin
2. README.md dosyasını okuyun
3. GitHub'da issue açın

---

**Son Güncelleme:** Ekim 2025
**Versiyon:** 1.0.0
