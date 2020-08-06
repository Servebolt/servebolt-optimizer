<?php

class CF_Cache_Purge_Queue_Item {

    private $type = null;
    private $item = null;
    private $datetime = null;

    public function __construct($item) {
        $this->type = $item['type'];
        $this->item = $item['item'];
        $this->datetime = $item['datetime'];
    }

}
