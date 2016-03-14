<?php

namespace modules\usage;

use diversen\upload;
use diversen\html;
use diversen\lang;
use diversen\session;

use modules\image\size as image;
use modules\video\size as video;
use modules\content\book\module as book;
use modules\content\book\views;

class module {
    
    public function __construct() {
        \diversen\moduleloader::setModuleIniSettings('content');
    }
    
    public function indexAction () {

        
        $b = new book();
        $books = $b->getUserBooks(session::getUserId());
        
        $v = new video();
        $i = new image();
        
        $total = 0;
        foreach($books as $book) {
            
            //echo views::userBook($book, $book['id']);
            $title = views::getBookLink($book);
            echo html::getHeadline($title, 'h3');
            $v_b = $v->getFilesSizeFromParentId('content_book', $book['id']);
            $total+= $v_b;
            echo upload::bytesToGreek($v_b) . " (" . lang::translate('Videos ') . ")";
            echo "<br />";
            
            $i_b = $i->getBlobsSizeFromParentId($book['id']);

            $total+= $i_b;
            echo upload::bytesToGreek($i_b) . ' (' . lang::translate('Images ') . ')';

            echo "<hr />";
        }
        echo lang::translate('Total') . ' ' . upload::bytesToGreek($total);
        
    }
}
