Yii PHPDocCrontab extension
===========================

INSTALLATION
------------

Put directory PHPDocCrontab (or only PHPDocCrontab.php) into your framework extensions directory. 
Add PHPDocCrontab.php as new console command on framework config:

    'commandMap' => array(
        'cron' => 'ext.PHPDocCrontab.PHPDocCrontab'
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

* Discussion (ru): http://www.yiiframework.ru/forum/viewtopic.php?f=9&t=5274
* Discussion (en): http://www.yiiframework.com/forum/index.php/topic/28948-installing-cron-jobs-by-phpdoc-comment-on-consolecommand-files/