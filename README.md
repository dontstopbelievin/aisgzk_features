## Requirements
- php 7.3+
- установить модули php: php7.3 php7.3-cli php7.3-common php7.3-json php7.3-opcache php7.3-mysql php7.3-mbstring php7.3-mcrypt php7.3-zip php7.3-fpm php7.3-xml
- установить composer

## Installation
- composer install
- cp env.example .env
- Приписать доступ к базе в .env
- Создать базу данных с названием как в .env
- php artisan migrate

## Controllers
- LandController - для работы с данными с xml
- QueryController - для работы с данными с парса другого сервиса

## Routes
- '/services/from_xml_file' - с xml файлов запись в базу
- '/services/get_geo_data_aisgzk' - данные без сдвига координат
- '/services/get_data' - данные со сдвигом координат
- '/queries/get_data' - данные без сдвига координат
- '/queries/get_data2' - данные со сдвигом координат