<?php
// Подключаем библиотеку
require 'dabros/dabros.php';

// Считываем настройки
$config = require 'classes/config.php';

// Инициалируем библиотеку
dabros::initialize($config);
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

		<?php dabros::printJavaScriptTags(); ?>

		<script>
			$(function()
			{
				// Получаем сессионный фасад серверной части приложения
				var sessionFacade = dabros.getSessionFacade();
				
				// Вызов методов сессионного фасада
			});

		</script>
	</head>

	<body>
		<!-- HTML-код интерфейса приложения -->
	</body>
</html>