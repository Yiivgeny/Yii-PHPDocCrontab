#Yii PHPDocCrontab extension
Simple and convenient installing console commands as cron jobs.

- One control point to run all commands
- Crossplatform job installing
- Store schedule of launches with application source
- Grouping jobs (example: segmentation by server)

##Requirements
Yii Framework 1.1.6 or above

##Usage
Installing action 'Example1' of command 'Test' to run every 10 minutes. Just add doc-comment for console action.

```php
class TestCommand extends CConsoleCommand{
    /**
     * @cron 10 * * * *
     */
    public function actionExample1(){}
}
```

##Installation
- **Step 1:** Put directory PHPDocCrontab (or only PHPDocCrontab.php) into your framework extensions directory.
- **Step 2:** Add PHPDocCrontab.php as new console command on framework config:

```php
'commandMap' => array(
    'cron' => 'ext.PHPDocCrontab.PHPDocCrontab'
)
```

- **Step 3:**  Add task to system scheduler (cron on unix, task scheduler on windows) to run every minute:

```sh
* * * * * /path/to/yii/application/protected/yiic cron
```

##Resources
- [More examples on GitHub](https://github.com/Yiivgeny/Yii-PHPDocCrontab/blob/master/examples/ExampleRuCommand.php)
- [Extension page on YiiFramework Site](http://www.yiiframework.com/extension/phpdoc-crontab)
- [Discussion on YiiFramework Forum](http://www.yiiframework.com/forum/index.php/topic/28948-installing-cron-jobs-by-phpdoc-comment-on-consolecommand-files/)
- [Discussion on Russian YiiFramework Forum](http://www.yiiframework.ru/forum/viewtopic.php?f=9&t=5274)