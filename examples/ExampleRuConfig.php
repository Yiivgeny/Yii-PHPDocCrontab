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
             * По умолчанию application.runtime
             */
            'logsDir' => '/var/log/yiiapp/',
            /**
             * Установка маски имени файла лога.
             * Параметр может включать варианты замены:
             *     %L - содержимое свойства logsDir
             *     %C - имя исполняемой команды
             *     %A - имя действия исполняемой команды
             *     %P - PID запускающего скрипта
             *     %D(format) - вывод даты в формате format; синтаксис повторяет используемый функций date()
             *
             * По умолчанию %L/%C.%A.log
             */
            'logFileName' => '%L/%C.%A-%D(Y-m-d H-i-s).log',
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