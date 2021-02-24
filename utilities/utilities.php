<?php


#REGION: DEBUG
    /**
     * Debug: print trace and stop
     *
     * @param mixed $object: object to be printed
     * @param boolean $kill: (optional, def true) die after print
     * @return void
     */
    function ddd($object, $kill = true){
    
        if (_ENV_DEV_ !== null && _ENV_DEV_){
          
            echo '<pre style="text-align: left;">';

            echo "<br>";
            print_r($object);
            echo '</pre><br />';
            if ($kill)
                die('END');
            return ($object);


            
        }
        
    }

    /**
     * Debug: print trace and continue
     *
     * @param mixed $object
     * @return void
     */
    function ppp($object){

        if (_ENV_DEV_ !== null && _ENV_DEV_){
         
            echo '<pre style="text-align: left;">';

            echo "<br>";
            print_r($object);
            echo '</pre><br />';

            return ($object);

        }
        
    }
#ENDREGION

#REGION: Util
    /**
     * Display a value in json format
     *
     * @param array $values
     * @return void
     */
    function display(array $values){
        //set correct content type
        header('Content-Type: application/json');
        die(json_encode($values));
    }

    function getParameter($parameterName){

        // check if present
        if(!isset($_GET[$parameterName])){
            throw new Exception("Paramter $parameterName is mandatory", 1);
        }

        // get value
        $parValue = $_GET[$parameterName];

        //check if not empty
        if($parValue === "") throw new Exception("Paramter $parameterName is mandatory", 1);

        return $$parValue;

    }


    /**
     * Validate all the inputs
     *
     * @param int $node_id
     * @param int $page_num
     * @param int $page_size
     * @return bool
     * @throws Exception
     */
    function validateInput($node_id, $page_num, $page_size){
        if($page_num < 0 || !is_numeric($page_num)){
            throw new Exception("Numero di pagina richiesto non valido", 1);
        }

        if(!is_numeric($page_num) || $page_size < _PAGE_SIZE_MIN_ || $page_size > _PAGE_SIZE_MAX_){
            throw new Exception("Richiesto formato pagina non valido", 1);
        }

        if((int)$node_id === 0){
            throw new Exception("Parametri obbligatori mancanti");
        }

        return true;

    }

#ENDREGION