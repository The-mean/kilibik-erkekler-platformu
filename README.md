# Forum Uygulaması Optimizasyonları

Bu proje, PHP tabanlı bir forum uygulamasının performans optimizasyonlarını ve kategori/etiket sistemini içermektedir.

## Özellikler

### Kategori ve Etiket Sistemi
- Konuları kategorilere ayırma
- Çoklu etiket desteği
- Kategori bazlı filtreleme
- Popüler etiketler listesi
- Etiket bazlı arama
- Kategori ve etiket istatistikleri

### Performans Optimizasyonları
- Görsel optimizasyonu (otomatik sıkıştırma, WebP dönüşümü)
- CSS ve JavaScript dosyalarının minify edilmesi
- Görseller için lazy loading
- Veritabanı indeksleri
- Önbellek sistemi

## Kurulum

1. Veritabanı tablolarını oluşturun:
```sql
mysql -u [kullanıcı_adı] -p [veritabanı_adı] < database/migrations/create_categories_and_tags.sql
```

2. Başlangıç kategorilerini ekleyin:
```sql
mysql -u [kullanıcı_adı] -p [veritabanı_adı] < database/migrations/insert_initial_categories.sql
```

3. Gerekli PHP eklentilerinin yüklü olduğundan emin olun:
- GD veya ImageMagick (görsel işleme için)
- JSON
- FileInfo

4. Cache dizininin yazılabilir olduğundan emin olun:
```bash
chmod -R 777 public/assets/cache
```

## Kullanım

### Kategori ve Etiket Sistemi
- Yeni konu oluştururken kategori seçin ve etiketler ekleyin
- Ana sayfada kategoriye göre filtreleme yapın
- Etiketlere tıklayarak ilgili konuları görüntüleyin
- Popüler etiketleri takip edin

### Önbellek Temizleme
Önbelleği temizlemek için:
```php
$categoryManager = new CategoryManager();
$categoryManager->clearCache();
```

## Lisans
Bu proje MIT lisansı altında lisanslanmıştır. 