<?php

class JSONDatabase implements TextDatabase {

    private static $instance = NULL;

    private function JSONDatabase() {
        
    }

    public static function getInstance() {
        return (is_null(self::$instance)) ? new JSONDatabase() : self::$instance;
    }

    public function getData($filePath) {
        $file = file($filePath);
        $jsonData = '';
        echo count($file).'<br>';
        if(count($file)==0)
        	return null;
        foreach ($file as $line) {
            $jsonData .= $line;
        }
        $arr = json_decode($jsonData, 1);
        
        print_r($arr);

        return $arr;
    }

    public function saveData($filePath, $data) {
        $isSaved = FALSE;
        $jsonData = json_encode($data);
        $file = fopen($filePath, 'w'); // replaces the whole content

        $data = json_encode($data);

        if (fwrite($file, $data)) {
            $isSaved = TRUE;
        }

        fclose($file);
        return $isSaved;
    }

}

?>
