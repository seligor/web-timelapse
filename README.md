# Для работы требуется
1. Установить и настроить nginx + php-fpm
2. установить ffmpeg
```sudo apt install nginx php-fpm ffmpeg```
Это потребует примерно 400-500 МБ места из за большого количества зависимостей

По умолчанию nginx занимает порт 80, который у вас может быть занят какими то своими проектами. Его можно поправить в конфиге nginx


К настройке nginx относится буквально подключение php-fpm
Я просто добавил эту секцию

```
        location ~* \.php$ {
        try_files $uri = 404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # подключаем сокет php-fpm
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
```

Обратите внимание на то, что для ```fastcgi_pass``` указана версия php8.2-fpm - у вас версия может отличаться. 

```
cd /var/www/html
git clone https://github.com/seligor/web-timelapse.git
cd /var/www/html/web-timelapse
ln -s /home/tbot/ff5m1/timelapse_finished timelapse
```
вместо /home/tbot/ff5m1/timelapse_finished нужно подставить правильный путь, который у вас будет отличаться (вы указывали его при установке бота), проверьте каталог /home

адрес подключения ```http://{ip_address}/web-timelapse/index.php```
где {ip_address} - айпи адрес вашего устройства, на котором установлен nginx

Что делает: 
1. обращается к папке с таймлапсами, считает длительность видео, берёт кадр из последних секунд видео, отображает этот кадр в виде превью, чтобы вы видели конечный результат
2. По нажатию на превью - можно посмотреть в браузере видео
3. По нажатию кнопки "Скачать" - скачать

   предназначено для домашнего использования
   Можно свободно бесплатно распространять, копировать, форкать, использовать. не является коммерческим проектом
