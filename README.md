# Etkinlik Rezervasyon API

Bu proje, etkinlik rezervasyon işlemlerini yönetmek için geliştirilen bir **RESTful API** uygulamasıdır. Laravel ve JWT kullanılarak kimlik doğrulama sağlanmıştır.

---

## Özellikler

** Kullanıcı kayıt ve giriş işlemleri (JWT Authentication)  
** Admin yetkisiyle etkinlik yönetimi  
** Kullanıcıların etkinlikleri listeleyip rezervasyon yapabilmesi  
** Koltuk bloklama ve serbest bırakma mekanizması  
** Rezervasyon doğrulama ve iptal işlemleri  
** Bilet indirme ve transfer etme  

---

## Kurulum ve Çalıştırma

### ** Gerekli Bağımlılıkları Kur
Öncelikle, projenin çalışması için **Composer** ve **NPM** bağımlılıklarını yükleyin:

```bash
** composer install
** npm install

# Gerekli veritabanı bilgilerini ayarlayın:

# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=etkinlik_db
# DB_USERNAME=root
# DB_PASSWORD=

# Veritabanını Kur ve Seed Et

# php artisan migrate --seed

# Uygulamayı Çalıştır

# php artisan serve

# API Kullanımı

# POST /api/auth/register

# {
#   "name": "Test Kullanıcı",
#   "email": "test@example.com",
#   "password": "password",
#   "password_confirmation": "password"
# }

# 2. Kullanıcı Giriş

# POST /api/auth/login

# {
#   "email": "test@example.com",
#   "password": "password"
# }

# yanıt:

# {
#   "access_token": "jwt-token",
#   "token_type": "bearer"
# }

#  3. Etkinlik Listesi (Herkes erişebilir)
 
#  GET /api/events

# Yanıt:
# [
#   {
#     "id": 1,
#     "name": "Konser",
#     "venue": "İstanbul",
#     "date": "2025-03-10"
#   }
# ]

#  4. Admin Olarak Etkinlik Ekleme
 
#  POST /api/events
#  Sadece is_admin=true olan kullanıcılar erişebilir.

# Header: Authorization: Bearer {jwt-token}

# {
#   "name": "Yeni Etkinlik",
#   "venue": "Ankara",
#   "date": "2025-06-15"
# }

# Yanıt:
# {
#   "message": "Etkinlik başarıyla eklendi."
# }

# Tüm API Endpoint’leri için;

# php artisan route:list

# Geliştirme Komutları

#  Cache Temizleme;

# php artisan cache:clear
# php artisan config:clear
# php artisan route:clear
# php artisan view:clear

# Test Çalıştırma;

# php artisan test