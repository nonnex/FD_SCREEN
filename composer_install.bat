del composer.lock
rmdir /s /q vendor

composer install

composer update --with-all-dependencies