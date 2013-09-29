<?php

return array(
    // Реквизиты доступа к базе данных
    'pdo'                 => array(
        // Строка соединения
        'connectionString' => 'mysql:host=localhost;dbname=dabros',
        // Имя пользователя
        'username'         => 'root',
        // Пароль
        'password'         => '',
    ),
    'RemoteObjectManager' => array(
        // Реквизиты доступа к базе данных
        'storage' => array(
            // Название таблицы. Таблица создается автоматически при первом использовании библиотеки dabros
            'table' => 'dabros_storage',
        ),
    ),
    'RemoteUserSession'   => array(
        // Имя класса сессионного фасада
        'sessionFacadeClassName' => 'Facade',
    ),
    // Путь к папке с PHP-классами приложения
    'phpClassPath'        => 'classes',
    // Путь к папке со скриптами на JavaScript относительно корня сайта
    'javaScrptPath'       => '/js',
    // Путь к входному скрипту rpc-запросов относительно корня сайта
    'dabrosUrl'           => '/dabros-handler.php',
);