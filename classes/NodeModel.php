<?php

require_once(dirname(__FILE__) . "/../config/config.php");
require_once("Db.php");

class NodeModel{

    /** @var Db  */
    protected $Db;


    function __construct()
    {
        $this->db = new Db;

        $this->db->server = _DB_HOST_ . ":" . _DB_PORT_;
        $this->db->user = _DB_USER_;
        $this->db->password = _DB_PASS_;
        $this->db->database = _DB_NAME_;
        
        //perform a DB Connection: if fails throws an exception
        $this->db->connect();
    }

    /**
     * Get a list of child nodes
     *
     * @param int $node_id
     * @param string $language
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getNodes($node_id, $language = "english", $search_keyword = "", $page = _PAGE_DEFAULT_, $page_size = _PAGE_SIZE_){
        // get offset
        $offset = $page * $page_size;

        // put to lower
        $search_keyword = strtolower($search_keyword);
        
        // prepare SQL
        $sql = "Select 
                    t.idNode as node_id, level, nodeName as name,
                    (Select 
                        count(*) 
                        from node_tree t2
                        where t2.iLeft > t.iLeft
                        and t2.iRight < t.iRight
                        AND t2.`level` = t.level + 1
                        )  as children_count
                FROM `node_tree` t left outer join node_tree_names tn on
                    t.idNode = tn.idNode
                WHERE `iLeft` > (select iLeft from `node_tree` where idNode = " . (int)$node_id . ") 
                AND `iRight` < (select iRight from `node_tree` where idNode = " . (int)$node_id . ")
                AND `level` = (select level + 1 from `node_tree` where idNode = " . (int)$node_id . ")
                AND language = '" . $this->db->escape($language) . "'
                AND LOWER(nodeName) like '%" . $this->db->escape($search_keyword) . "%'
                LIMIT " . (int)$page_size . " OFFSET " . (int)$offset . "";

        // get nodes
        $nodes = $this->db->executeS($sql);
        
        // calc next page: if i have a page > 1 that's means that i have also a --page
        if($page_size <= count($nodes)){
            // if the pafe size is less or equal to my list
            $next_page = ($this->countChild($node_id, $language, $search_keyword) > ($offset + $page_size) ? ++$page : -1);
        }else{
            $next_page = -1;
        }
        
        
        // calc prev page: if i have a page > 1 that's means that i have also a --page
        $prev_page = (int)$page !== 0 ? --$page : -1;

        $return_array = [
            "nodes" => $nodes
        ];

        if($prev_page >= 0){
            // return only if present
            $return_array["prev_page"] = $prev_page;
        }

        if($next_page >= 0){
            // different here -> i have to count :(
            
            $return_array["next_page"] = $next_page;
        }

        
        return $return_array;
    }

    /**
     * count the children of a specific node
     *
     * @param int $node_id
     * @param string $language
     * @param string $search_keyword
     * @return int
     */
    private function countChild($node_id, $language, $search_keyword){
        $sql = "Select count(*)
                FROM `node_tree` t left outer join node_tree_names tn on
                    t.idNode = tn.idNode
                WHERE `iLeft` > (select iLeft from `node_tree` where idNode = " . (int)$node_id . ") 
                AND `iRight` < (select iRight from `node_tree` where idNode = " . (int)$node_id . ")
                AND `level` = (select level + 1 from `node_tree` where idNode = " . (int)$node_id . ")
                AND language = '$language'
                AND LOWER(nodeName) like '%$search_keyword%'
                ";

        // get value
        return $this->db->getValue($sql);
    }

    /**
     * Check if node exists on database
     *
     * @param int $node_id
     * @return bool
     */
    public function nodeExists($node_id){
        $sql = "Select count(*)
                FROM `node_tree` 
                where idNode = " . (int)$node_id;

        return ($this->db->getValue($sql) > 0);
    }
}