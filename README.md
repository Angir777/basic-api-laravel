# basic-api-laravel

Basic REST API in Laravel

# Uruchomienie lokalne kolejki od maili

sail artisan queue:work --queue=register-emails,reset-password-emails

# Uruchomienie projektu (docker + sail)

docker run --rm -u "$(id -u):$(id -g)" -v $(pwd):/var/www/html -w /var/www/html laravelsail/php84-composer:latest composer install --ignore-platform-reqs
cp .env.example .env - poprawienie pliku env aby odpowiadał środowisku lokalnemu
sail up lub sail up -d (-d = detach mode)
sail artisan key:generate
sail artisan storage:link
sail composer fresh-db

lub 

php composer setup (jesli środowisko ma juz php i composera)
