<?php
/**
 * Файл примеров конфигурации приложения.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package PHPDocCrontab
 * @subpackage example
 */

/**
 * Простая конфигурация.
 * Вам необходимо распаковать расширение в папку extensions вашего приложения.
 * В соответствии с индексом (cron) массива вам следует использовать команду `./yiic cron`
 */
return array (
    'commandMap' => array(
        'cron' => 'ext.PHPDocCrontab.PHPDocCrontab'
    )
);

/**
 * Конфигурация с заменой параметров.
 * Примечание: каждая из перечисленных ниже опций может быть динамически определена во время запуска.
 * Пример: `./yiic cron run --optionName=optionValue`
 */
return array (
    'commandMap' => array(
        'cron' => array(
            'class' => 'ext.PHPDocCrontab.PHPDocCrontab',
            /**
             * Префикс тегов при парсинге (по умолчанию cron)
             * Изменяя вы можете иные теги-задания, например
             * @mycron * * * * *
             * @mycron-stderr /dev/null
             */
            'tagPrefix' => 'mycron',
            /**
             * Принудительная установка исполняемого файла интерпретатора
             */
            'interpreterPath' => '/usr/local/bin/php -d foo=bar',
            /**
             * Установка папки по умолчанию для сохранения логов.
             * Используется если задание указано без @cron-stdout
             * По умолчанию application.runtime
             */
            'logsDir' => '/var/log/yiiapp/',
            /**
             * Принудительная установка bootstrap-скрипта.
             * По умолчанию используется скрипт с помощью которого запущено само расширение.
             */
            'bootstrapScript' => __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'yiicMod.php',
            /**
             * Временная метка в формате поддерживаемом функцией strtotime,
             * которая будет использована в качестве текущей при запуске заданий.
             *
             * Можно использовать для запуска всех необходимых скриптов, если время запуска было пропущено.
             * Так же можно использовать как метод коррекции часовых поясов сервера по отношению к приложению.
             */
            'timestamp' => 'now - 1 hour 25 minutes'
        )
    )
);