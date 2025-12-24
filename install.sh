#!/bin/bash

echo "==================================="
echo "Установка Blog App"
echo "Symfony 6.4 + MongoDB + PHP 8.2+"
echo "==================================="
echo ""

# Проверка PHP
echo "🔍 Проверка PHP..."
if ! command -v php &> /dev/null; then
    echo "❌ PHP не установлен!"
    echo "📖 Смотрите INSTALL_GUIDE.md для инструкций по установке"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")

echo "✅ PHP версия: $PHP_VERSION"

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 2 ]); then
    echo "❌ Требуется PHP 8.2 или выше!"
    echo "📖 Смотрите INSTALL_GUIDE.md для инструкций по установке PHP 8.2/8.3"
    exit 1
fi

# Проверка Composer
echo ""
echo "🔍 Проверка Composer..."
if ! command -v composer &> /dev/null; then
    echo "❌ Composer не установлен!"
    echo "📥 Установите Composer: https://getcomposer.org/download/"
    exit 1
fi
echo "✅ Composer установлен"

# Проверка расширения MongoDB
echo ""
echo "🔍 Проверка расширения mongodb..."
if ! php -m | grep -q "mongodb"; then
    echo "❌ Расширение PHP mongodb не установлено!"
    echo "📖 Смотрите INSTALL_GUIDE.md для инструкций по установке расширения mongodb"
    exit 1
fi
echo "✅ Расширение mongodb установлено"

# Проверка MongoDB
echo ""
echo "🔍 Проверка подключения к MongoDB..."
if php -r "try { new MongoDB\Driver\Manager('mongodb://localhost:27017'); echo 'OK'; } catch (Exception \$e) { echo 'FAIL'; exit(1); }" 2>/dev/null | grep -q "OK"; then
    echo "✅ MongoDB доступен"
else
    echo "⚠️  Не удалось подключиться к MongoDB на localhost:27017"
    echo "   Убедитесь, что MongoDB запущен:"
    echo "   - Linux: sudo systemctl start mongod"
    echo "   - macOS: brew services start mongodb-community"
    echo "   - Windows: запустите службу MongoDB"
fi

# Установка зависимостей
echo ""
echo "📦 Установка зависимостей через Composer..."
composer install --no-interaction --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "❌ Ошибка при установке зависимостей!"
    exit 1
fi

# Проверка .env
if [ ! -f .env ]; then
    echo "❌ Файл .env не найден!"
    exit 1
fi

# Генерация случайного APP_SECRET
if command -v openssl &> /dev/null; then
    SECRET=$(openssl rand -hex 32)
    if [[ "$OSTYPE" == "darwin"* ]]; then
        sed -i '' "s/your_secret_key_here_change_in_production/$SECRET/" .env
    else
        sed -i "s/your_secret_key_here_change_in_production/$SECRET/" .env
    fi
    echo "✅ Сгенерирован APP_SECRET"
else
    echo "⚠️  openssl не найден, пожалуйста, вручную измените APP_SECRET в .env"
fi

# Очистка кэша
echo ""
echo "🧹 Очистка кэша..."
if [ -d var/cache ]; then
    rm -rf var/cache/*
    echo "✅ Кэш очищен"
fi

echo ""
echo "==================================="
echo "✅ Установка завершена успешно!"
echo "==================================="
echo ""
echo "📝 Следующие шаги:"
echo ""
echo "1. Убедитесь, что MongoDB запущен"
echo ""
echo "2. Запустите приложение:"
echo "   php -S localhost:8000 -t public"
echo ""
echo "3. Откройте в браузере:"
echo "   http://localhost:8000"
echo ""
echo "4. Зарегистрируйте пользователя и создайте первый блог!"
echo ""
echo "📖 Дополнительная информация:"
echo "   - README.md - основная документация"
echo "   - INSTALL_GUIDE.md - подробная инструкция по установке"
echo "   - ROUTES.md - описание всех маршрутов"
echo ""
