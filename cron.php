<?php

namespace modules\usage;

use Cron\CronExpression;
use diversen\conf;
use diversen\db\q;
use diversen\lang;
use diversen\mailer\markdown;
use diversen\mailsmtp;
use diversen\queue;
//use modules\configdb\module as config;

class cron extends \modules\usage\module {

    public $max = 80;
    
    public function __construct() {

        //$c = new config();
        //$c->overrideAll();
        
        $percentage_warning = conf::getModuleIni('usage_warning');
        if ($percentage_warning) {
            $this->max = $percentage_warning;
        }
    }
    
    public function run() {
        $cron = conf::getModuleIni('usage_cron');
        if (!$cron) {
            return; 
        }
        $minute = CronExpression::factory($cron);
        if ($minute->isDue()) {
            $this->cronCheckUsage();
        }
    }
    
    public function cronCheckUsage () {
        $max_greek = conf::getModuleIni('usage_max_bytes');
        $percentage = $this->getPercentageUsageAllBooks();
        if ($percentage >= $this->max) {
            $this->addToQueue($max_greek, $percentage);
        } 
    }
    
    public function addToQueue ($max_greek, $percentage) {
        $q = new queue(q::$dbh);
        
        $unique = "usage_notify_$max_greek";
        $res = $q->addOnce('usage', $unique);

        $rows = $q->getQueueRows('usage', $unique);
        if (!empty($rows)) {
            $admins = q::select('account')->filter('admin = ', 1)->fetch();        
            $this->sendMail($admins);
            $q->setQueueRowsDone($rows);
        } 
    }
    
    protected function sendMail ($admins) {
        $md = new markdown();
        
        $title = lang::translate('Storage quota soon to exceed');
        $txt = $this->emailTxt();
        $html = $md->getEmailHtml($title, $txt);
        
        foreach($admins as $admin) {
            $res = mailsmtp::mail($admin['email'], $title, $txt, $html);
        }      
    }
    
    protected function emailTxt () { 
        
        $max_greek = conf::getModuleIni('usage_max_bytes');
        $percentage = $this->getPercentageUsageAllBooks();
        
        $str = lang::translate('Hi') . PHP_EOL . PHP_EOL;
        $str.= lang::translate('You have used {PERCENTAGE}% of your {MAX_GREEK} storage!',
                array ('PERCENTAGE' => $percentage, 'MAX_GREEK' => $max_greek));
        
        $str.= PHP_EOL . PHP_EOL;
        
        $site = conf::getSchemeWithServerName();
        $str.= lang::translate('Consider upgrading your account on {SITE}', array ('SITE' => $site )) . PHP_EOL . PHP_EOL;
        $str.= lang::translate('Kind regards {SITE}', array ('SITE' => $site));
        
        return $str;
        
    }
}
