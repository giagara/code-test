<?php

require_once("utilities/utilities.php");
require_once("classes/NodeModel.php");

try{

    /** CHECK METHOD */
    if($_SERVER['REQUEST_METHOD'] != "GET"){
        throw new Exception("Allowed only GET method");
    }

    /** GET PARAMETERS */
    $node_id = $_GET["node_id"] ?? 0;
    $language = $_GET["language"] ?? "english";
    $search_keyword = $_GET["search_keyword"] ?? "";
    $page_num = $_GET["page_num"] ?? _PAGE_DEFAULT_;
    $page_size = $_GET["page_size"] ?? _PAGE_SIZE_;

    /** input validation */
    validateInput($node_id, $page_num, $page_size);
    
    /** Create new Node model */
    $node = new NodeModel();

    /** check if node exists */
    if(!$node->nodeExists($node_id)){
        throw new Exception("ID Nodo non valido");
    }

    /** display the results */
    display($node->getNodes($node_id, $language, $search_keyword, $page_num, $page_size));


}catch(Exception $ex){
    display(["nodes" => [], "error" => $ex->getMessage()]);
}




