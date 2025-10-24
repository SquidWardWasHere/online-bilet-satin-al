#!/bin/bash

echo "========================================"
echo "Bussio - Bilet Satın Alma Platformu"
echo "========================================"
echo ""

echo "Docker kontrol ediliyor..."
if ! command -v docker &> /dev/null; then
    echo "HATA: Docker bulunamadı!"
    echo "Lütfen Docker'ı indirip yükleyin: https://docs.docker.com/get-docker/"
    exit 1
fi

echo "Docker bulundu!"
echo ""

echo "Container başlatılıyor..."
docker-compose up -d --build

if [ $? -eq 0 ]; then
    echo ""
    echo "========================================"
    echo "Başarılı! Uygulama başlatıldı."
    echo "========================================"
    echo ""
    echo "Tarayıcınızda aşağıdaki adresi açın:"
    echo "http://localhost:8080"
    echo ""
    echo "Container'ı durdurmak için: docker-compose down"
    echo ""
    echo "Varsayılan kullanıcılar:"
    echo "  Admin: admin@bussio.com / Admin123!"
    echo "  Firma Admin: firmadmin@metro.com / Firma123!"
    echo "  User: user@example.com / User123!"
    echo ""
    echo "========================================"
else
    echo "HATA: Container başlatılamadı!"
    exit 1
fi
