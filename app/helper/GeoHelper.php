<?php
namespace helper;


class GeoHelper {
    const GOOGLE_MAP = 'https://maps.googleapis.com';
    const GEOCODE = '/maps/api/geocode/json';
    /**
     * Read a file (txt) to filesystem and create a array for each line
     * @param string $path
     * @return \ArrayObject
     * @throws \Exception
     */
    static final public function readZipcodeFile($path){
        $bool = 0;
        $lines = new \ArrayObject();
        $handle = fopen($path, 'r');
        if(false == $handle){
            throw new \Exception("Error to open file {$path}");
        }
        do{
            $buffer = fgets($handle);
            $lines->append(new \ArrayObject(explode('|', $buffer)));
        }while(false != $buffer);
        fclose($handle);
        return $lines;
    }

    /**
     * Metodo de ayuda para hacer consultas en el bridge
     * @param array $params Array relacional (map) de parametros
     * @param string $method GET|POST
     * @return array Array relacional
     */
    static final public function bridge(array $params, $method = 'POST', $url, $uri){
        /**
         * @param array $params
         * @param $method
         * @return string
         */
        $createUri = function(array $params, $method){
            $uri = '?';
            $c = null;
            foreach($params as $k => $v){
                switch($method){
                    case 'POST';
                        $uri .= "{$k}={$v}&";
                        continue;
                    case 'GET':
                        $uri .= "{$k}={$v}&";
                        continue;
                }
            }
            return rtrim($uri, '&');
        };
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        switch($method){
            case 'POST':
                error_log($url . $uri.'?'.$createUri($params, 'POST'));
                curl_setopt($curl, CURLOPT_URL, $url . $uri);
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, "?{$createUri($params, 'POST')}");
                break;
            case 'GET':
                error_log($url . $uri .$createUri($params, 'GET'));
                curl_setopt($curl, CURLOPT_URL, $url . $uri .$createUri($params, 'GET'));
                break;
        }
        try{
            $response = curl_exec($curl);
            if(false == $response) throw new \Exception(curl_error($curl));
        }catch (\Exception $e){
            error_log($e->getMessage(), 0);
        }
        return json_decode($response, true);
    }

    /**
     * @param string $zipcode
     * @return array
     */
    final static public function geocodeGoogle($zipcode){
        $key = '&key=AIzaSyBzQM67cbya1QzQjTRH5_YlJ6hL1afh11w';
        return json_decode(file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address={$zipcode}+mexico{$key}"), true);
    }

    final static public function dividedByBlocks(\ArrayObject $obj, $element){
        $elements = new \ArrayObject();
        $n = ceil(($obj->count()/$element));
        $y = 0;
        $i = 0;
        do{
            $elements->append(array_slice($obj->getArrayCopy(), $i, $element));
            $i += $element;
            $y++;
        }while($n != $y);
        return $elements;
    }


    static final public function readZipcode($path){
        $bool = 0;
        $lines = new \ArrayObject();
        $handle = fopen($path, 'r');
        if(false == $handle){
            throw new \Exception("Error to open file {$path}");
        }
        do{
            $buffer = fgets($handle);
            $lines->append($buffer);
        }while(false != $buffer);
        fclose($handle);
        return $lines;
    }

    /**
     * @param $dir path directory
     * @param $file name of file
     * @return string full path
     */
    public static function createFile($dir, $file){
        try{
            if(!file_exists($dir)) {
                mkdir($dir, 0777);
            }
        }catch (\Exception $e){
            error_log($e->getMessage());
        }
        return "{$dir}/{$file}.txt";
    }

    /**
     * return 10 random hexadecimal characteres
     * @return string
     */
    public static function randomString($cant = 10){
        return bin2hex(openssl_random_pseudo_bytes($cant));
    }

    public static function log($string, \Closure $closure){
        error_log($closure($string), 3, '/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/query.log');
    }

    /**
     * @return \DateTime
     */
    public static function dateTime(){
        return new \DateTime('now', new \DateTimeZone('America/Mexico_City'));
    }
}