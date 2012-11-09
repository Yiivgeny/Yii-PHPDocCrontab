<?php
/**
 * Файл примеров установки заданий.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package PHPDocCrontab
 */

/**
 * Класс команды-примера устновки заданий.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @package PHPDocCrontab
 * @subpackage example
 */
class ExampleRuCommand extends CConsoleCommand{

    /**
     * Простой пример.
     * Запуск каждый час на 10-ой минуте.
     *
     * @cron 10 * * * *
     */
    public function actionexample1(){}

    /**
     * Пример тегирования.
     * Действие будет запущено только если расширение запускается командой содержащий тег "dbserver" или "cacheserver".
     * Например так: `./yiic cron run   dbserver storageserver`
     *
     * @cron 10 * * * *
     * @cron-tags dbserver cacheserver
     */
    public function actionexample2(){}

    /**
     * Пример перенаправления STDOUT и STDERR в один и тот же файл.
     * Параметры stdout и stderr могут быть маской, включающей следующие варианты замены:
     *     %L - содержимое свойства logsDir
     *     %C - имя исполняемой команды
     *     %A - имя действия исполняемой команды
     *     %P - PID запускающего скрипта
     *     %D(format) - вывод даты в формате format; синтаксис повторяет используемый функций date()
     *
     * @cron 10 * * * *
     * @cron-stdout /tmp/Example_%C.%A.log
     */
    public function actionexample3(){}

    /**
     * Пример перенаправления STDOUT и STDERR в разные файлы.
     *
     * @cron 10 * * * *
     * @cron-stdout /tmp/ExampleCommand.log
     * @cron-stderr /tmp/ExampleCommandError.log
     */
    public function actionexample4(){}

    /**
     * Пример перенаправления STDERR в файл (STDOUT выводится в файл по умолчанию).
     *
     * @cron 10 * * * *
     * @cron-stderr /dev/null
     */
    public function actionexample5(){}

    /**
     * Пример команды с аргументами.
     * Аргументы вводятся точно так же как и при обычном запуске через консоль, с сохранением всех возможностей.
     *
     * @cron 10 * * * *
     * @cron-args --limit=5 --offset=10
     */
    public function actionexample6($limit, $offset){}

    /**
     * Пример расширенного использования времени запуска.
     * Действие будет запускаться
     * каждую 10, 25, 26, 27, 28, 29, 30, 40 минуту
     * каждого 2-го (четного) часа
     * с 15 по 21, и с 23 по 27 число
     * каждого 2-го (четного) месяца в период с января по июнь включительно
     * независимо от дня недели.
     *
     * @cron 10,25-30,40 *\2 15-21,23-27 1-6\2 *
     */
    public function actionexample7(){}

    /**
     * Все описанные конструкции могут использоваться совместно и в любом порядке.
     *
     * @cron 10,25-30,40 *\2 15-21,23-27 1-6\2 *
     * @cron-stderr /dev/null
     * @cron-args --limit=5 --offset=10
     * @cron-tags dbserver cacheserver
     * @cron-stdout /tmp/ExampleCommand.log
     */
    public function actionexample8($limit, $offset){}
}