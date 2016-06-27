<?php

namespace modules\usage;

use diversen\cache;
use diversen\conf;
use diversen\html;
use diversen\html\table;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\upload;
use diversen\http;
use modules\content\book\views;


class module {
    
    public function __construct() {
        
        if (!conf::getModuleIni('usage_max_bytes')) {
            moduleloader::setModuleIniSettings('usage');
        }
    }
    
    
    public function getPercentageMessageColored () {
        $total = $this->getPercentageUsageAllBooks();
        
        $html = $this->getOverviewHtml();
        if ($total >= 100) {
            return html::getError($html);
        } else if ($total >= 80) {
            return html::getWarning($html);
        } else {
            return html::getConfirm($html);
        }
    }
    
    public $cacheExpire = 3600; 
    
    public function indexAction () {
        
        echo $this->getOverviewHtml(session::getUserId());
        
        $b = new \modules\content\book\module();
        $books = $b->getUserBooks(session::getUserId());

        $str = '';        
        $str.= $this->tableHeader();
        $str.= $this->getTotal($books);
        $str.= $this->tableFooter();
        
        echo $str;
        
    }
    
    public function getPercentageUsageAllBooks () {
        
        
        $percentage = cache::get('usage_percentage', 1, $this->cacheExpire);
        if ($percentage === null) {

            $b = new \modules\content\book\module();
            $books = $b->getAllBooks(0, 0);
            $max = $this->getMaxUsageBytes();
            $usage = $this->getTotal($books, 'bytes');

            $percentage = $this->getPercentageUsage($usage, $max);
            cache::set('usage_percentage', 1, $percentage);
            
        }
        
        return $percentage;
    }
    
    public function setAction () {
        
        if (!session::checkAccess('super')) {
            return;
        }
        
        if (isset($_POST['submit'])) {
            $c = new \modules\configdb\module();
            $c->set('usage_max_bytes', $_POST['usage'], 'module' );
            $message = lang::translate('Usage setting has been updated');
            http::locationHeader($_SERVER['REQUEST_URI'], $message);
            
        }
        
        echo lang::translate('Set max usage, e.g. 100MB of 10GB');
        echo "<br />";
        $f = new html();
        $f->formStart();
        $f->legend(lang::translate('Set usage'));
        $f->label('usage', lang::translate('Max usage'));
        $f->text('usage', conf::getModuleIni('usage_max_bytes'));
        $f->submit('submit', lang::translate('Update'));
        $f->formEnd();
        echo $f->getStr();
        
    }
    
    public function totalAction () {
        
        echo $this->getPercentageMessageColored();
        
        $b = new \modules\content\book\module();
        $books = $b->getAllBooks(0, 0);
        
        $str = '';        
        $str.= $this->tableHeader();
        $str.= $this->getTotal($books);
        $str.= $this->tableFooter();
        
        echo $str;
    }
    
    public function tableFooter () {
        $str = '';
        $str.= table::trBegin();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td(upload::bytesToGreek($this->total));
        $str.= table::trEnd();
        $str.= table::tableEnd();
        return $str;
    }
    
    public function tableHeader () {
        $str = '';
        $str.= table::tableBegin(array('class' => 'uk-table'));
        $str.= table::trBegin();
        $str.= table::th(lang::translate('Book title'));
        $str.= table::th(lang::translate('Videos'));
        $str.= table::th(lang::translate('Images'));
        $str.= table::th(lang::translate('Files'));
        $str.= table::th(lang::translate('Book Total'));
        $str.= table::th(lang::translate('All books'));
        $str.= table::trEnd();
        
        return $str;
    }
    
    public $total = 0;

    
    /**
     * Get total as a display HTML string
     * You can fetch number of total bytes from $this->total
     * @param array $books
     * @return string $res html
     */
    public function getTotal($books, $type = 'string') {
        
        $v = new \modules\video\size();
        $i = new \modules\image\size();
        $f = new \modules\files\size();
        
        $str = '';
        foreach ($books as $book) {
            
            $total_current = 0;
            $str.= table::trBegin();

            $title = views::getBookLink($book);
            $str.= table::td($title); // html::getHeadline($title, 'h3');
            // Video blob
            $v_b = cache::get('usage_book_video', $book['id'], $this->cacheExpire);
            if ($v_b === null) {
                $v_b = $v->getFilesSizeFromParentId('content_book', $book['id']);
                cache::set('usage_book_video', $book['id'], $v_b);
            }

            $total_current+= $v_b;
            $this->total+= $v_b;

            $str.= table::td(upload::bytesToGreek($v_b));

            // Image blob
            $i_b = cache::get('usage_book_img', $book['id'], $this->cacheExpire);
            if ($i_b === null) {
                $i_b = $i->getBlobsSizeFromParentId($book['id']);
                cache::set('usage_book_img', $book['id'], $i_b);
            }

            $total_current+= $i_b;
            $this->total+= $i_b;

            $str.= table::td(upload::bytesToGreek($i_b));

            // File blob
            $f_b = cache::get('usage_book_files', $book['id'], $this->cacheExpire);
            if ($f_b === null) {
                $f_b = $f->getBlobsSizeFromParentId($book['id']);
                cache::set('usage_book_files', $book['id'], $f_b);
            }

            $total_current+= $f_b;
            $this->total+= $f_b;

            $str.= table::td(upload::bytesToGreek($f_b));
            $str.= table::td(upload::bytesToGreek($total_current));
            $str.= table::td();
            $str.= table::trEnd();
        }
        
        if ($type == 'bytes') {
            $total = $this->total;
            $this->total = 0;
            return $total;  
        }

        return $str;
        
    }
    
    public function getOverviewHtml ($user_id = 0) {

        $str = '';
        $usage = $this->getTotalUsageBytes($user_id, 'bytes');
        $str.= "<b>" . lang::translate('Total usage') . '</b>:&nbsp;' . upload::bytesToGreek($usage) . ".&nbsp;";
        
        $max = $this->getMaxUsageBytes($user_id);
        $str.= "<b>" . lang::translate('Max usage') . "</b>:&nbsp;" . upload::bytesToGreek($max) . ".&nbsp;";
        

        $percentage = $this->getPercentageUsage($usage, $max);
        
        $str.= "<b>" .lang::translate('Percentage used') . "</b>:&nbsp;" . $percentage . ".&nbsp;";
        return $str;
        
    }
    
    /**
     * Get percentage of usage
     * @param int $usage
     * @param int $max
     * @return float $percentage
     */
    public function getPercentageUsage ($usage, $max) {
        $percentage = ($usage / $max) * 100;
        $percentage = round($percentage, 2); 
        return $percentage;
    }

    
    public function getTotalUsageBytes ($user_id = 0) {
        $b = new \modules\content\book\module();
        
        if ($user_id) {
            $books = $b->getUserBooks($user_id);
        } else {
            $books = $b->getAllBooks(0, 0);
        }
        return $this->getTotal($books, 'bytes');

    }
    
    
    public function getMaxUsageBytes ($user_id = 0) {
        if ($user_id) {
            $user_total = conf::getModuleIni('usage_user_max');
            if (!$user_total) {
                return upload::greekToBytes(conf::getModuleIni('usage_max_bytes'));
            }
        }
        return upload::greekToBytes(conf::getModuleIni('usage_max_bytes'));
    }
}
