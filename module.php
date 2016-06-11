<?php

namespace modules\usage;
use diversen\cache;


use diversen\html;
use diversen\html\table;
use diversen\lang;
use diversen\moduleloader;
use diversen\session;
use diversen\upload;
use modules\content\book\views;

class module {
    
    public function __construct() {
        moduleloader::setModuleIniSettings('content');
    }
    
    public $cacheExpire = 0; //60*60*24;
    
    public function indexAction () {
        
        $b = new \modules\content\book\module();
        $books = $b->getUserBooks(session::getUserId());

        $str = '';        
        $str.= $this->tableHeader();
        $str.= $this->displayBooks($books);
        $str.= $this->tableFooter();
        
        echo $str;
        
    }
    
    public function totalAction () {
        $b = new \modules\content\book\module();
        $books = $b->getAllBooks(0, 1000);
        
        $str = '';        
        $str.= $this->tableHeader();
        $str.= $this->displayBooks($books);
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
        $str.= table::th(lang::translate('Total'));
        $str.= table::trEnd();
        
        return $str;
    }
    
    public $total = 0;
    
    public function displayBooks($books) {
        
        $v = new \modules\video\size();
        $i = new \modules\image\size();
        $f = new \modules\files\size();
        
        $str = '';
        foreach ($books as $book) {
            $str.= table::trBegin();

            $title = views::getBookLink($book);
            $str.= table::td($title); // html::getHeadline($title, 'h3');
            // Video blob
            $v_b = cache::get('usage_book_video', $book['id'], $this->cacheExpire);
            if (!$v_b) {
                $v_b = $v->getFilesSizeFromParentId('content_book', $book['id']);
                cache::set('usage_book_video', $book['id'], $v_b);
            }

            $this->total+= $v_b;

            $str.= table::td(upload::bytesToGreek($v_b));

            // Image blob
            $i_b = cache::get('usage_book_img', $book['id'], $this->cacheExpire);
            if (!$i_b) {
                $i_b = $i->getBlobsSizeFromParentId($book['id']);
                cache::set('usage_book_img', $book['id'], $i_b);
            }

            $this->total+= $i_b;

            $str.= table::td(upload::bytesToGreek($i_b));

            // File blob
            $f_b = cache::get('usage_book_files', $book['id'], $this->cacheExpire);
            if (!$f_b) {
                $f_b = $f->getBlobsSizeFromParentId($book['id']);
                cache::set('usage_book_files', $book['id'], $f_b);
            }

            $this->total+= $f_b;

            $str.= table::td(upload::bytesToGreek($f_b));
            $str.= table::td('&nbsp;');
            $str.= table::trEnd();
        }
        return $str;
    }
}
