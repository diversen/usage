<?php

namespace modules\usage;

use diversen\cache;
use diversen\conf;
use diversen\html\table;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\upload;
use modules\content\book\views;

class module {
    
    public function __construct() {
        moduleloader::setModuleIniSettings('usage');
        moduleloader::setModuleIniSettings('content');
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
    
    public function totalAction () {
        
        echo $this->getOverviewHtml();
        
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
            if (!$v_b) {
                $v_b = $v->getFilesSizeFromParentId('content_book', $book['id']);
                cache::set('usage_book_video', $book['id'], $v_b);
            }

            $total_current+= $v_b;
            $this->total+= $v_b;

            $str.= table::td(upload::bytesToGreek($v_b));

            // Image blob
            $i_b = cache::get('usage_book_img', $book['id'], $this->cacheExpire);
            if (!$i_b) {
                $i_b = $i->getBlobsSizeFromParentId($book['id']);
                cache::set('usage_book_img', $book['id'], $i_b);
            }

            $total_current+= $i_b;
            $this->total+= $i_b;

            $str.= table::td(upload::bytesToGreek($i_b));

            // File blob
            $f_b = cache::get('usage_book_files', $book['id'], $this->cacheExpire);
            if (!$f_b) {
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
        

        $percentage = ($usage / $max) * 100;
        $percentage = round($percentage, 2); 
        
        $str.= "<b>" .lang::translate('Percentage used') . "</b>:&nbsp;" . $percentage . ".&nbsp;";
        return $str;
        
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
                return conf::getModuleIni('usage_max_bytes');
            }
        }
        return conf::getModuleIni('usage_max_bytes');
    }
}
