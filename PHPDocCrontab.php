<?php
/**
 * Yii Framework extension. Better installing console commands as cron jobs.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/**
 * PHPDocCrontab is a CConsoleCommand to automaticly running marked actions.
 *
 * @author Evgeny Blinov <e.a.blinov@gmail.com>
 * @package PHPDocCrontab
 */
class PHPDocCrontab extends CConsoleCommand {

    /**
     * @var string PHPDoc tag prefix for using by PHPDocCrontab extension.
     */
    public $tagPrefix = 'cron';
    /**
     * @var string PHP interpriter path (if empty, path will be checked automaticly)
     */
    public $interpreterPath = null;
    /**
     * @var string path to writing logs
     */
    public $logsDir = null;
    /**
     * Placeholders:
     *     %L - logsDir path
     *     %C - name of command
     *     %A - name of action
     *     %P - pid of runner-script (current)
     *     %D(string formatted as arg of date() function) - formatted date
     * @var string mask log file name
     */
    public $logFileName = '%L/%C.%A.log';
    /**
     * @var string Bootstrap script path (if empty, current command runner will be used)
     */
    public $bootstrapScript = null;
    /**
     * @var string Timestamp used as current datetime
     * @see http://php.net/manual/en/function.strtotime.php
     */
    public $timestamp = 'now';
    /**
     * @var string the name of the default action. Defaults to 'run'.
     */
    public $defaultAction = 'run';

    /**
     * Initialize empty config parameters.
     */
    public function init() {
        parent::init();
        //Checking PHP interpriter path
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
        //Checking logs directory
        if ($this->logsDir === null){
            $this->logsDir = Yii::app()->getRuntimePath();
        }
        //Checking bootstrap script
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
    [--logFileName=value]
    [--bootstrapScript=value]
    [--timestamp=value]


RAW;
    }

    /**
     * Transform string datetime expressions to array sets
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
        foreach ($parameters AS $n => $repeat) {
            $parameters[$n] = array();
            foreach (explode(',', $repeat) as $piece) {
                $piecesStorage = array();
                list($piece, $every) = explode('\\', $piece, 2) + array(false, 1);

                if ($piece === '*') $piecesStorage = range($dimensions[$n][0], $dimensions[$n][1]);
                else {
                    $piece = explode('-', $piece, 2);
                    if (count($piece) === 2) $piecesStorage = range($piece[0], $piece[1]);
                    else                     $piecesStorage = array((int)$piece[0]);
                }
                if ($every > 1) foreach ($piecesStorage AS $key => $element){
                    if ($element%$every !== 0) unset($piecesStorage[$key]);
                }
                $parameters[$n] = array_merge($parameters[$n], $piecesStorage);
            }
        }
        return $parameters;
    }

    /**
     * Parsing and filtering PHPDoc comments.
     *
     * @param string $comment Raw PHPDoc comment
     * @return array List of valid tags
     */
    protected function parseDocComment($comment){
        if (empty($comment)) return array();
        //Forming pattern based on $this->tagPrefix
        $pattern = '#^\s*\*\s+@('.$this->tagPrefix.'(-(\w+))?)\s*(.*?)\s*$#im';
        //Miss tags:
        //cron, cron-tags, cron-args, cron-strout, cron-stderr
        if (preg_match_all($pattern, $comment, $matches, PREG_SET_ORDER)){
            foreach ($matches AS $match) $return[$match[3]?$match[3]:0] = $match[4];

            if (isset($return[0])){
                $return['_raw'] = preg_split('#\s+#', $return[0], 5);
                $return[0] = $this->transformDatePieces($return['_raw']);
                //Getting tag list. If empty, string "default" will be used.
                $return['tags'] = isset($return['tags'])?preg_split('#\W+#', $return['tags']):array('default');
                return $return;
            }
        }
    }

    /**
     * Getting tasklist.
     *
     * @return array List of command actions associated with {@link PHPDocCrontab} runner.
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
                //Filetring methods. Valid only public actions.
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
     * OS-independent background command execution .
     *
     * @param string $command
     * @param string $stdout path to file for writing stdout
     * @param string $stderr path to file for writing stderr
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
     * Checking is windows family OS
     *
     * @return boolean return true if script running under windows OS
     */
    protected function isWindowsOS(){
        return strncmp(PHP_OS, 'WIN', 3) === 0;
    }

    /**
     * Running actions associated with {@link PHPDocCrontab} runner and matched with timestamp.
     *
     * @param array $args List of run-tags to running actions (if empty, only "default" run-tag will be runned).
     */
    public function actionRun($args = array()){
        $tags = &$args;
        $tags[] = 'default';

        //Getting timestamp will be used as current
        $time = strtotime($this->timestamp);
        if ($time === false) throw new CException('Bad timestamp format');
        $now = explode(' ', date('i G j n w', $time));
        $runned = 0;
        foreach ($this->prepareActions() as $task) {
            if (array_intersect($tags, $task['docs']['tags'])){
                foreach ($now AS $key => $piece){
                    //Checking current datetime on timestamp piece array.
                    if (!in_array($piece, $task['docs'][0][$key])) continue 2;
                }

                //Forming command to run
                $command = $this->bootstrapScript.' '.$task['command'].' '.$task['action'];
                if (isset($task['docs']['args'])) $command .= ' '.escapeshellcmd($task['docs']['args']);

                //Setting default stdout & stderr
                if (isset($task['docs']['stdout'])) $stdout = $task['docs']['stdout'];
                else                                $stdout = $this->logFileName;

                $stdout = $this->formatFileName($stdout, $task);
                $stderr = isset($task['docs']['stderr'])?$this->formatFileName($task['docs']['stderr'], $task):$stdout;

                $this->runCommandBackground($command, $stdout, $stderr);
                Yii::log('Running task ['.(++$runned).']: '.$task['command'].' '.$task['action'], CLogger::LEVEL_INFO, 'ext.'.__CLASS__);
            }
        }
        if ($runned > 0){
                Yii::log('Runned '.$runned.' task(s) at '.date('r', $time), CLogger::LEVEL_INFO, 'ext.'.__CLASS__);
        }
        else{
                Yii::log('No task on '.date('r', $time), CLogger::LEVEL_INFO, 'ext.'.__CLASS__);
        }
    }

    /**
     * Show actions associated with {@link PHPDocCrontab} runner.
     *
     * @param $args array List of run-tags for filtering action list (if empty, show all).
     */
    public function actionView($args = array()){
        $tags = &$args;

        foreach ($this->prepareActions() as $task) {
            if (!$tags || array_intersect($tags, $task['docs']['tags'])){
                //Forming to using with printf function
                $times = $task['docs']['_raw'];
                array_unshift($times, $task['command'].'.'.$task['action']);
                array_unshift($times, "Action %-40s on %6s %6s %6s %6s %6s %s\n");
                array_push($times, empty($task['docs']['tags'])?'':(' ('.implode(', ', $task['docs']['tags']).')'));
                call_user_func_array('printf', $times);
            }
        }
    }

    protected function formatFileName($pattern, $task){
        $pattern = str_replace(
            array('%L', '%C', '%A', '%P'),
            array($this->logsDir, $task['command'], $task['action'], getmypid()),
            $pattern
        );
        return preg_replace_callback('#%D\((.+)\)#U', create_function('$str', 'return date($str[1]);'), $pattern);
    }

    /**
     * Help command. Show command usage.
     */
    public function actionHelp(){
       echo $this->getHelp();
    }
}
