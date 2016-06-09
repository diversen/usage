<?php

namespace modules\usage;
// use diversen\cache;


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
    
    public function indexAction () {
        
        $b = new \modules\content\book\module();
        $books = $b->getUserBooks(session::getUserId());
        
        $v = new \modules\video\size();
        $i = new \modules\image\size();
        $f = new \modules\files\size();
        
        $total = 0;
        /*
        $str = cache::get('usage', session::getUserId(), 60*60*24);
        if ($str) {
            echo $str;
            return;
        }*/
        $str = '';
        $str.= table::tableBegin(array('class' => 'uk-table'));
        $str.= table::trBegin();
        $str.= table::th(lang::translate('Book title'));
        $str.= table::th(lang::translate('Videos'));
        $str.= table::th(lang::translate('Images'));
        $str.= table::th(lang::translate('Files'));
        $str.= table::th(lang::translate('Total'));
        $str.= table::trEnd();
        foreach($books as $book) {
            $str.= table::trBegin();
            
            $title = views::getBookLink($book);
            $str.= table::td($title); // html::getHeadline($title, 'h3');
            
            // Video blob
            $v_b = $v->getFilesSizeFromParentId('content_book', $book['id']);
            $total+= $v_b;
            
            $str.= table::td(upload::bytesToGreek($v_b));
// league/flysystem-azure
            
            // Image blob
            $i_b = $i->getBlobsSizeFromParentId($book['id']);
            $total+= $i_b;
            
            $str.= table::td(upload::bytesToGreek($i_b));
            //$str.= "<br />";
            
            // Files blob
            $f_b = $f->getBlobsSizeFromParentId($book['id']);
            $total+= $f_b;
            
            $str.= table::td(upload::bytesToGreek($f_b));
            $str.= table::td('&nbsp;');
            // $str.= "<hr />";
            $str.= table::trEnd();
        }
        
        $str.= table::trBegin();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td();
        $str.= table::td(upload::bytesToGreek($total));
        $str.= table::trEnd();
        // cache::set('usage', session::getUserId(), $str);
        $str.= "</table>";
        echo $str;
        
    }
}
