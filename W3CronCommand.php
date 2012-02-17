<?php
/**
 * Расширение фреймворка Yii, позволяющее удобно управлять запуском консольных команд.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * Класс-команда обрабатывающий запланированные задания.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @package W3CronCommand
 */
class W3CronCommand extends CConsoleCommand {

    /**
     * @var string префикс для phpdoc-тегов используемый командой
     */
    public $tagPrefix = 'cron';
    /**
     * @var string путь к интерпретатору php (если пусто, путь определяется автоматически)
     */
    public $interpreterPath = null;
    /**
     * @var string путь до директории содержащей логи запусков
     */
    public $logsDir = null;
    /**
     * @var string Путь до скрипта начальной загрузки
     */
    public $bootstrapScript = null;
    /**
     * @var string Временная метка используемая в качестве текущего времени
     * @see http://php.net/manual/en/function.strtotime.php
     */
    public $timestamp = 'now';
    /**
     * @var string the name of the default action. Defaults to 'run'.
     */
    public $defaultAction = 'run';

    /**
     * Метод инициализации параметров недостающих в конфигурации.
     */
    public function init() {
        parent::init();
        //Попытка определить путь до интерпретатора, если он не установлен вручную
        if ($this->interpreterPath === null){
            if ($this->isWindowsOS()){
                //Windows OS
                $this->interpreterPath = 'php.exe';
            }
            else{
                //nix based OS
                $this->interpreterPath = '/usr/bin/env php';
            }
        }
        //Установка папки для сохранения логов
        if ($this->logsDir === null){
            $this->logsDir = Yii::app()->getRuntimePath();
        }
        //Установка скрипта начальной загрузки
        if ($this->bootstrapScript === null){
            $this->bootstrapScript = realpath($this->getCommandRunner()->getScriptName());
        }
    }

    /**
	 * Provides the command description.
	 * @return string the command description.
	 */
    public function getHelp() {
        $commandUsage = $this->getCommandRunner()->getScriptName().' '.$this->getName();
        return <<<RAW
Usage: {$commandUsage} <action>

Actions:
    view <tags> - Show active tasks, specified by tags.
    run <options> <tags> - Run suitable tasks, specified by tags (default action).
    help - Show this help.

Tags:
    [tag1] [tag2] [...] [tagN] - List of tags

Options:
    [--tagPrefix=value]
    [--interpreterPath=value]
    [--logsDir=value]
    [--bootstrapScript=value]
    [--timestamp=value]


RAW;
    }

    /**
     * Приводит массив входных текстовых данных в массив частей дат.
     *
     * @param array $parameters
     * @return array
     */
    protected function transformDatePieces(array $parameters){
        $dimensions = array(
            array(0,59), //Minutes
            array(0,23), //Hours
            array(1,31), //Days
            array(1,12), //Months
            array(0,6),  //Weekdays
        );
        foreach ($parameters AS $n => &$repeat) {
            list($repeat, $every) = explode('\\', $repeat, 2) + array(false, 1);
            if ($repeat === '*') $repeat = range($dimensions[$n][0], $dimensions[$n][1]);
            else {
                $repeatPiece = array();
                foreach (explode(',', $repeat) as $piece) {
                    $piece = explode('-', $piece, 2);
                    if (count($piece) === 2) $repeatPiece = array_merge($repeatPiece, range($piece[0], $piece[1]));
                    else                     $repeatPiece[] = $piece[0];
                }
                $repeat = $repeatPiece;
            }
            if ($every > 1) foreach ($repeat AS $key => $piece){
                if ($piece%$every !== 0) unset($repeat[$key]);
            }
        }
        return $parameters;
    }

    /**
     * Парсит DocComment в массив, выбирая только необходимые теги.
     *
     * @param string $comment Исходный комментарий
     * @return array Список отфильтрованных тегов комментария
     */
    protected function parseDocComment($comment){
        if (empty($comment)) return array();
        //Формирование маски тегов на основании $this->tagPrefix
        $pattern = '#^\s*\*\s+@('.$this->tagPrefix.'(-(\w+))?)\s*(.*?)\s*$#im';
        //Предполагается наличие тегов из списка:
        //cron, cron-tags, cron-args, cron-strout, cron-stderr
        if (preg_match_all($pattern, $comment, $matches, PREG_SET_ORDER)){
            foreach ($matches AS $match) $return[$match[3]?$match[3]:0] = $match[4];

            if (isset($return[0])){
                //Текстовый вариант времени запуска команды
                $return['_raw'] = preg_split('#\s+#', $return[0], 5);
                $return[0] = $this->transformDatePieces($return['_raw']);
                //Получение списка тегов и установка тега по умолчанию с случае отстутствия списка
                $return['tags'] = isset($return['tags'])?preg_split('#\W+#', $return['tags']):array('default');
                return $return;
            }
        }
    }

    /**
     * Получение списка заданий для возможного запуска.
     *
     * @return array Список экшенов для возможного запуска
     */
    protected function prepareActions(){
        $actions = array();
        $Runner = $this->getCommandRunner();
        // Command loop
        foreach ($Runner->commands AS $command => $file){
            $CommandObject = $Runner->createCommand($command);
            if ($CommandObject instanceof $this) continue;
            $Reflection = new ReflectionObject($CommandObject);
            // Methods loop
            $Methods = $Reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($Methods AS $Method){
                $name = $Method->getName();
                //Фильтрация методов. Вывод только корректных консольных комманд.
                if(
                    !strncasecmp($name,'action',6) &&
                    strlen($name) > 6 &&
                    ($docComment = $this->parseDocComment($Method->getDocComment()))
                ){
                    $name=substr($name, 6);
                    $name[0]=strtolower($name[0]);
                    $actions[] = array(
                        'command' => $command,
                        'action' => $name,
                        'docs' => $docComment
                    );
                }
            }
        }
        return $actions;
    }

    /**
     * ОС-независимый фоновый запуск консольной команды.
     *
     * @param string $command Команда
     * @param string $output Файл для вывода
     */
    protected function runCommandBackground($command, $stdout, $stderr){
        $command =
            $this->interpreterPath.' '.
            $command.
            ' >'.escapeshellarg($stdout).
            ' 2>'.(($stdout === $stderr)?'&1':escapeshellarg($stderr));

        if ($this->isWindowsOS()){
            //Windows OS
            pclose(popen('start /B "Yii run command" '.$command, 'r'));
        }
        else{
            //nix based OS
            system($command.' &');
        }
    }

    /**
     * @return boolean Возвращает истину, если ОС семейства Windows
     */
    protected function isWindowsOS(){
        return strncmp(PHP_OS, 'WIN', 3) === 0;
    }

    /**
     * Команда запуска запланированных заданий.
     *
     * @param array $args Список тегов для экшенов которые будут запущены
     */
    public function actionRun($args = array()){
        $tags = &$args;
        $tags[] = 'default';

        //Получаем текущее время в необходимом формате
        $time = strtotime($this->timestamp);
        if ($time === false) throw new CException('Bad timestamp format');
        $now = explode(' ', date('i G j n w', $time));
        $runned = 0;
        foreach ($this->prepareActions() as $task) {
            if (array_intersect($tags, $task['docs']['tags'])){
                foreach ($now AS $key => $piece){
                    //Проверяем наличие текущей части даты в дате исполнения задания
                    if (!in_array($piece, $task['docs'][0][$key])) continue 2;
                }

                //Формирование команды для запуска
                $command = $this->bootstrapScript.' '.$task['command'].' '.$task['action'];
                if (isset($task['docs']['args'])) $command .= ' '.escapeshellarg($task['docs']['args']);

                //Установка stdout и stderr, если они не будут указаны
                $stdout = $this->logsDir.DIRECTORY_SEPARATOR.$task['command'].'.'.$task['action'].'.log';

                if (isset($task['docs']['stdout'])) $stdout = $task['docs']['stdout'];
                $stderr = isset($task['docs']['stderr'])?$task['docs']['stderr']:$stdout;

                $this->runCommandBackground($command, $stdout, $stderr);
                Yii::log('Running task ['.(++$runned).']: '.$task['command'].' '.$task['action'], CLogger::LEVEL_INFO, 'ext.W3CronCommand');
            }
        }
        if ($runned > 0){
                Yii::log('Runned '.$runned.' task(s) at '.date('r', $time), CLogger::LEVEL_INFO, 'ext.W3CronCommand');
        }
        else{
                Yii::log('No task on '.date('r', $time), CLogger::LEVEL_INFO, 'ext.W3CronCommand');
        }
    }

    /**
     * Команда просмотра всех запланированных заданий.
     *
     * @param $args Список тегов интересующих заданий (оставить пустым для вывода всех возможных заданий)
     */
    public function actionView($args = array()){
        $tags = &$args;

        foreach ($this->prepareActions() as $task) {
            if (!$tags || array_intersect($tags, $task['docs']['tags'])){
                //Формирование аргументов для функции printf
                $times = $task['docs']['_raw'];
                array_unshift($times, $task['command'].'.'.$task['action']);
                array_unshift($times, "Action %-40s on %6s %6s %6s %6s %6s %s\n");
                array_push($times, empty($task['docs']['tags'])?'':(' ('.implode(', ', $task['docs']['tags']).')'));
                call_user_func_array('printf', $times);
            }
        }
    }

    /**
     * Команда показывающая справку по использованию расширения.
     */
    public function actionHelp(){
       echo $this->getHelp();
    }
}
