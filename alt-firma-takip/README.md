# Alt Firma Takip Sistemi

Halı yıkama ana firmasının alt firmalarla olan finansal ilişkilerini yönetmek için geliştirilmiş bağımsız Progressive Web Application (PWA).

## Özellikler

- **Alt Firma Yönetimi**: Alt firma kayıtlarını oluşturma ve yönetme
- **Yıkama İşi Kaydı**: m² bazında halı yıkama işlerinin takibi
- **Komisyon Hesaplama**: Teslimat tipine göre otomatik komisyon hesaplama (%40 veya %30)
- **Para Hareketi Takibi**: Ödeme ve tahsilat kayıtları
- **Bakiye Görüntüleme**: Anlık borç/alacak durumu
- **PWA Desteği**: Offline çalışma, kurulabilir uygulama
- **Android APK**: Capacitor ile native mobil uygulama

## Teknoloji Stack

### Frontend
- HTML5 + CSS3 (Tailwind CSS)
- Vanilla JavaScript (ES6+)
- Service Worker (offline destek)
- PWA (Progressive Web App)

### Backend
- PHP 8.0+
- MySQL 8.0+
- REST API
- JWT Authentication

### Mobile
- Capacitor (Android APK)

## Kurulum

### Gereksinimler

- PHP 8.0 veya üzeri
- MySQL 8.0 veya üzeri
- Apache/Nginx web sunucusu
- Node.js (Tailwind CSS ve Capacitor için)

### Backend Kurulumu

1. Veritabanı oluşturun:
```bash
mysql -u root -p
CREATE DATABASE alt_firma_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Veritabanı şemasını içe aktarın:
```bash
mysql -u root -p alt_firma_takip < backend/schema.sql
```

3. Veritabanı bağlantı ayarlarını yapılandırın:
- `backend/config/database.php` dosyasını düzenleyin
- Veritabanı kullanıcı adı, şifre ve veritabanı adını güncelleyin

4. JWT secret key'i ayarlayın:
- `backend/config/jwt.php` dosyasını düzenleyin
- `JWT_SECRET` değerini güvenli bir değerle değiştirin

5. Web sunucusunu yapılandırın:
- Apache için mod_rewrite'ı etkinleştirin
- `.htaccess` dosyasının çalıştığından emin olun

### Frontend Kurulumu

1. Node.js ve npm'i kurun (eğer kurulu değilse):
   - https://nodejs.org/ adresinden Node.js'i indirin ve kurun
   - Kurulumu doğrulamak için: `node --version` ve `npm --version`

2. Bağımlılıkları yükleyin:
```bash
cd alt-firma-takip
npm install
```

3. Tailwind CSS'i derleyin:
```bash
# Geliştirme için (tek seferlik)
npm run build:css

# Geliştirme için (otomatik derleme)
npm run watch:css

# Production için (minified)
npm run build:css:prod
```

2. API base URL'ini yapılandırın:
- `frontend/js/api.js` dosyasını açın
- `baseURL` değerini backend API URL'iniz ile güncelleyin

3. PWA manifest'i kontrol edin:
- `frontend/manifest.json` dosyasını kontrol edin
- `start_url` ve diğer ayarları gerekirse güncelleyin

### İlk Kullanıcı Oluşturma

```bash
# MySQL'e bağlanın
mysql -u root -p alt_firma_takip

# Kullanıcı ekleyin (şifre: admin123)
INSERT INTO users (username, password_hash, name) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User');
```

## Kullanım

### Web Uygulaması

1. Tarayıcınızda uygulamayı açın: `http://localhost/alt-firma-takip/frontend/`
2. Giriş yapın (kullanıcı adı: `admin`, şifre: `admin123`)
3. Ana ekranda alt firma listesini görüntüleyin
4. Yeni alt firma ekleyin veya mevcut firmaların detaylarını görüntüleyin

### PWA Kurulumu

1. Chrome veya Edge tarayıcısında uygulamayı açın
2. Adres çubuğundaki "Kur" butonuna tıklayın
3. Uygulama masaüstünüze veya ana ekranınıza eklenecektir
4. Artık offline modda da kullanabilirsiniz

### Android APK Oluşturma

1. Capacitor'ı kurun:
```bash
npm install @capacitor/core @capacitor/cli @capacitor/android
```

2. Capacitor'ı başlatın:
```bash
npx cap init
```

3. Android platformunu ekleyin:
```bash
npx cap add android
```

4. Web varlıklarını kopyalayın:
```bash
npx cap copy
```

5. Android Studio'da açın:
```bash
npx cap open android
```

6. Android Studio'da APK'yı derleyin:
- Build > Build Bundle(s) / APK(s) > Build APK(s)

## Proje Yapısı

```
alt-firma-takip/
├── backend/                 # PHP REST API
│   ├── api/                # API endpoint'leri
│   ├── config/             # Yapılandırma dosyaları
│   ├── models/             # Veri modelleri
│   └── utils/              # Yardımcı sınıflar
├── frontend/               # PWA uygulaması
│   ├── css/               # Stil dosyaları
│   ├── js/                # JavaScript dosyaları
│   ├── pages/             # HTML sayfaları
│   └── assets/            # Görseller ve ikonlar
└── README.md              # Bu dosya
```

## API Dokümantasyonu

### Authentication

- `POST /api/auth/login` - Giriş yap
- `POST /api/auth/verify` - Token doğrula

### Subcontractors

- `GET /api/subcontractors` - Tüm alt firmaları listele
- `GET /api/subcontractors/{id}` - Alt firma detayı
- `POST /api/subcontractors` - Yeni alt firma ekle
- `PUT /api/subcontractors/{id}` - Alt firma güncelle
- `PATCH /api/subcontractors/{id}/status` - Durum değiştir

### Jobs

- `POST /api/jobs` - Yeni iş ekle
- `PUT /api/jobs/{id}` - İş güncelle
- `DELETE /api/jobs/{id}` - İş sil

### Payments

- `POST /api/payments` - Yeni para hareketi ekle
- `PUT /api/payments/{id}` - Para hareketi güncelle
- `DELETE /api/payments/{id}` - Para hareketi sil

### Reports

- `GET /api/reports/summary` - Özet rapor

## Güvenlik

- JWT tabanlı kimlik doğrulama
- Password hashing (bcrypt)
- SQL injection koruması (prepared statements)
- XSS koruması
- HTTPS kullanımı önerilir

## Lisans

Bu proje özel kullanım içindir.

## Destek

Sorularınız için lütfen sistem yöneticinizle iletişime geçin.
