<?php

namespace modules\usage;

use diversen\upload;
use diversen\html;
use diversen\lang;
use diversen\session;
use diversen\cache;

use modules\content\book\views;

class module {
    
    public function __construct() {
        \diversen\moduleloader::setModuleIniSettings('content');
    }
    
    public function indexAction () {
        
        $b = new \modules\content\book\module();
        $books = $b->getUserBooks(session::getUserId());
        
        $v = new \modules\video\size();
        $i = new \modules\image\size();
        $f = new \modules\files\size();
        
        $total = 0;
        
        $str = cache::get('usage', session::getUserId(), 60*60*24);
        if ($str) {
            echo $str;
            return;
        }
        
        foreach($books as $book) {

            $title = views::getBookLink($book);
            $str.= html::getHeadline($title, 'h3');
            
            // Video blob
            $v_b = $v->getFilesSizeFromParentId('content_book', $book['id']);
            $total+= $v_b;
            
            $str.= upload::bytesToGreek($v_b) . " (" . lang::translate('Videos ') . ")";
            $str.= "<br />";
            
            // Image blob
            $i_b = $i->getBlobsSizeFromParentId($book['id']);
            $total+= $i_b;
            
            $str.= upload::bytesToGreek($i_b) . ' (' . lang::translate('Images ') . ')';
            $str.= "<br />";
            
            // Files blob
            $f_b = $f->getBlobsSizeFromParentId($book['id']);
            $total+= $f_b;
            
            $str.= upload::bytesToGreek($f_b) . ' (' . lang::translate('Files ') . ')';           
            $str.= "<hr />";
        }
        
        $str.= lang::translate('Total') . ' ' . upload::bytesToGreek($total);
        cache::set('usage', session::getUserId(), $str);
        echo $str;
        
    }
}
