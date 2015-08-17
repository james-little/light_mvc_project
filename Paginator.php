<?php
/**
 * For using in pagination
 * =======================================================
 * Dealing with cases using pagination
 * . start page index set to 1 in default
 *
 * Example:
 *     // get total pages
 *     $total_pages = ...
 *     $pagination = new Paginator();
 *     $pagination->setTotalpages($total_pages);
 *     $pagination->setCurrentPage($current_page);
 *     $page_info = $pagination->getPageInfo();
 *
 * @author koketsu <jameslittle.private@gmail.com>
 * @version 1.0
 **/

class Paginator {

    private $start;
    private $last;
    private $total_pages;
    private $total_items;
    private $current;
    private $page_size;

    /**
     * __construct
     * @param int $page_size
     * @param int $total_item_count
     */
    public function __construct($page_size, $total_item_count) {
        $this->init();
        if($page_size) {
            $this->page_size = $page_size;
        }
        $total_pages = $total_item_count / $this->page_size;
        if($total_pages > 0) {
            $total_pages = intval($total_pages) + 1;
        }
        $this->total_pages = $total_pages;
        $this->total_items = $total_item_count;
    }
    /**
     * __destruct
     */
    public function __destruct() {
        $this->start = null;
        $this->last = null;
        $this->current = null;
        $this->page_size = null;
        $this->total_pages = null;
        $this->total_items = null;
    }
    /**
     * reset object when been cloned
     */
    public function __clone() {
        $this->init();
    }
    /**
     * init
     */
    private function init() {
        $this->start = 1;
        $this->last = 1;
        $this->current = 1;
        $this->page_size = 10;
        $this->total_pages = 1;
        $this->total_items = 0;
    }
    /**
     * set current page
     * @param int $current_page
     * @return Paginator
     */
    public function setCurrentPage($current_page) {
        $this->current = $current_page;
        $start = $current_page - $this->page_size;
        if($start <= 0) {
            $start = 1;
        }
        $this->start = $start;
        $last = $this->start + $this->page_size;
        if($last > $this->total_pages) {
            $last = $this->total_pages;
        }
        $this->last = $last;
        return $this;
    }
    /**
     * set page size
     * @param int $page_size
     * @return Paginator
     */
    public function setPageSize($page_size = 10) {
        if(!$page_size) {
            return $this;
        }
        $this->page_size = $page_size;
        return $this;
    }
    /**
     * get page info
     * @return array
     */
    public function getPageInfo() {
        return array(
            'start' => $this->start,
            'last' => $this->last,
            'current' => $this->current,
            'page_size' => $this->page_size,
            'total_pages' => $this->total_pages,
            'total_items' => $this->total_items
        );
    }
    /**
     * set total pages
     * @param int $total_pages
     * @return Paginator
     */
    public function setTotalPages($total_pages) {
        if(!$total_pages) {
            return $this;
        }
        $this->total_pages = $total_pages;
        return $this;
    }
}