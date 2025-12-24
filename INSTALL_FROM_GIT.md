## Установка из репозитория

1. Клонируйте репозиторий:
```bash
   git clone https://github.com/ваш-username/blog-app.git
   cd blog-app
```

2. Скопируйте .env.example в .env:
```bash
   copy .env.example .env  # Windows
   cp .env.example .env    # Linux/Mac
```

3. Сгенерируйте APP_SECRET в .env:
```bash
   php -r "echo bin2hex(random_bytes(32));"
```

4. Установите зависимости:
```bash
   composer install
```

5. Запустите сервер:
```bash
   php -S localhost:8000 -t public
```