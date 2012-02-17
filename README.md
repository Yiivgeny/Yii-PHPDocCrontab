Yii W3CronCommand extension
===========================

INSTALLATION
------------

Put directory W3CronCommand (or only W3CronCommand.php) into your framework extensions directory. 
Add W3CronCommand.php as new console command on framework config:

    'commandMap' => array(
        'cron' => 'ext.W3CronCommand.W3CronCommand'
    )
    
Install cron job:

    * * * * * /path/to/yii/application/protected/yiic cron


REQUIREMENTS
------------

Yii Framework 1.1.6 or above


QUICK START
-----------

Add doc-comment for console actions

    /**
     * @cron 10 * * * *
     */
    public function actionExapmle1(){}

LINKS
-----

Discussion (ru): http://www.yiiframework.ru/forum/viewtopic.php?f=9&t=5274
