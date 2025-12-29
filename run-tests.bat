@echo off
echo Очистка кэша...
php bin/console cache:clear --env=test

echo.
echo Запуск тестов...
php bin/phpunit

echo.
echo Готово!
pause