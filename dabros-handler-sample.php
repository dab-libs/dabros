<?php
// Подключаем библиотеку
require 'dabros/dabros.php';

// Считываем настройки
$config = require 'classes/config.php';

// Инициалируем библиотеку
dabros::initialize($config);

// Обробатываем rpc-запрос
dabros::getRemoteCallManager()->handle();
