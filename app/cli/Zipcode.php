<?php
include_once realpath(__DIR__ . "/../../vendor/autoload.php");

class Zipcode extends \utils\abstracts\AbstractClass {
    /** @var ArrayObject $zipfile */
    private $zipfile = null;
    /** @var ArrayObject $zipfileLocation */
    private $zipfileLocation = null;
    /** @var ArrayObject $zipcodesBlocks */
    private $zipcodesBlocks = null;

    public function __construct(){
        $this->_construct();
    }
    /**
     * @param string $lat
     * @param string $lng
     * @return int
     */
    protected function countRestaurantsForlocation($lat, $lng){
        $query = $this->cnn->prepare("
SELECT mm_module_places.id ,mm_module_places.date_online ,mm_module_places.name ,mm_module_places.name_suffix ,
mm_module_places.name_nice ,mm_module_places.printer_last_request_date ,mm_module_places.orders_system ,
mm_module_places.id_category ,mm_module_places.id_categories ,mm_module_places.takeaway ,
mm_module_places.delivery ,mm_module_places.order_min_takeaway ,mm_module_places.takeaway_delay_minutes ,
mm_module_places.price_takeaway ,mm_module_places.delivery_delay_minutes ,mm_module_places.order_min ,
mm_module_places.price_delivery ,mm_module_places.price_delivery_type ,mm_module_places.payment_methods ,
mm_module_places.ranking ,mm_module_places.orders_last_month_accepted ,mm_module_places.orders_last_month_cancelled ,
mm_module_places.orders_last_month_cancelation_ratio ,mm_module_places.serialized_data ,
mm_module_places.profile_rating ,mm_module_cities.name AS city_name ,
mm_module_cities.name_nice AS city_name_nice ,mm_module_states.name AS state_name ,
mm_module_states.name_nice AS state_name_nice ,mm_module_groups.ranking AS group_ranking ,
mm_module_file_objects.path AS img_path ,mm_module_file_objects.file AS img_file ,pdz.id AS pdz_id ,
pdz.cost AS pdz_cost ,pdz.cost_type AS pdz_cost_type ,pdz.order_minimum AS pdz_order_minimum ,
pdz.delay_minutes AS pdz_delay_minutes ,pdzu.id AS pdzu_id ,pdzu.cost AS pdzu_cost ,
pdzu.cost_type AS pdzu_cost_type ,pdzu.order_minimum AS pdzu_order_minimum ,
pdzu.delay_minutes AS pdzu_delay_minutes ,sync_matching.provider_id AS atm_id ,
mm_module_places.group_id ,mm_module_places.latitude ,mm_module_places.longitude ,
mm_module_places.id_city ,mm_module_places.neighborhood_id ,mm_module_places.zip ,
mm_module_places.address ,mm_module_places.comments ,mm_module_places.has_menu ,
mm_module_places.has_photos ,mm_module_places_menu_categories.id AS has_combos ,
((ACOS(SIN({$lat} * PI() / 180) * SIN(mm_module_places.latitude * PI() / 180)
+ COS({$lat} * PI() / 180) * COS(mm_module_places.latitude * PI() / 180)
* COS(({$lng} - mm_module_places.longitude) * PI() / 180)) * 180 / PI()) * 60 * 1.1515 * 1.609344) AS distance
FROM mm_module_places
LEFT JOIN mm_module_cities ON mm_module_places.id_city=mm_module_cities.id
LEFT JOIN mm_module_states ON mm_module_cities.id_state=mm_module_states.id
LEFT JOIN mm_module_places_menu_categories ON mm_module_places_menu_categories.id_place=mm_module_places.id
	AND mm_module_places_menu_categories.type='combo'
LEFT JOIN mm_module_groups ON mm_module_groups.id=mm_module_places.group_id
LEFT JOIN mm_module_file_objects ON mm_module_places.id=mm_module_file_objects.table_id
	AND mm_module_file_objects.table='mm_module_places' AND mm_module_file_objects.variant='130px'
LEFT JOIN sync_matching ON sync_matching.object_id=mm_module_places.id
	AND sync_matching.object_type='place' AND sync_matching.provider='atm'
LEFT JOIN mm_module_places_delivery_zones AS pdz ON mm_module_places.id=pdz.place_id AND pdz.rule='include'
LEFT JOIN mm_module_places_delivery_zones AS pdzu ON mm_module_places.id=pdzu.place_id AND pdzu.rule='include'

WHERE has_menu = 1 AND delivery = 'SI' AND ((ACOS(SIN({$lat} * PI() / 180) *
                                                      SIN(mm_module_places.latitude * PI() / 180) +
                                                      COS({$lat} * PI() / 180) *
                                                      COS(mm_module_places.latitude * PI() / 180) * COS(
                                                          ({$lng} - mm_module_places.longitude) * PI() /
                                                          180)) * 180 / PI()) * 60 * 1.1515 * 1.609344) < 60 AND
      ST_Within(POINT({$lat}, {$lng}), pdzu.zone) = 1
GROUP BY mm_module_places.name
ORDER BY mm_module_places.has_menu DESC;
        ");

        try {
            $query->execute();
        }catch (Exception $e){
            error_log($e->getMessage(), 3, '/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/query.log');
            error_log($e->getTraceAsString(), 3, '/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/query.log');
            error_log($query->queryString, 3, '/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/query.log');
        }
        return $query->rowCount();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function readzipFile(){
        $txt = $this->sources['path']['txt']['zipcode'];
        $this->zipfile = \helper\GeoHelper::readZipcodeFile($txt);
    }

    /**
     * @throws Exception
     */
    public function readzipLocation(){
        $this->zipfileLocation = \helper\GeoHelper::readZipcodeFile($this->sources['path']['txt']['zipcodelocation']);
    }
    public function setZipcodeLocation($zipcode){
        $psotion = 0;
        $location = null;

        $result = \helper\GeoHelper::geocodeGoogle($zipcode);
        if('OK' == $result['status']){
            $location = new ArrayObject([
                ':lat' => $result['results'][0]['geometry']['location']['lat'],
                ':lng' => $result['results'][0]['geometry']['location']['lng']
            ]);
        }else{
            $location = new ArrayObject([
                'error_message' => $result['error_message'],
                'status' => 'OVER_QUERY_LIMIT',
            ]);
        }
        return $location;
    }
    public function createFileZipLocation(){
        $iterator = $this->zipfile->getIterator();
        $stringLocation = null;
        $file  = __DIR__ . "/zipcode_location.txt";
        if(file_exists($file)){
            $file = bin2hex(openssl_random_pseudo_bytes(10));
            $file  = __DIR__ . "/zipcode_location_{$file}.txt";
        }
        echo "\033[34m Se esta creando el archivo {$file}\n";
        $handle = fopen($file, 'w');
        while($iterator->valid()){
            /** @var ArrayObject $location */
            $location = $this->setZipcodeLocation($iterator->current()->offsetGet(0));
            if(!$location->offsetExists('error_message')){
                $stringLocation = "{$iterator->current()->offsetGet(0)}|{$location->offsetGet(':lat')}|{$location->offsetGet(':lng')}\n";
                //echo "\033[34m el string es: \033[32m {$stringLocation}";
            }else{
                $stringLocation = "{$iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
                echo "\033[34m el string es: \033[31m {$iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
            }
            fwrite($handle, $stringLocation);
            $iterator->next();
        }
        fclose($handle);
        echo "\033[34m Se ha terminado de crear el archivo\n";
    }
    public function mintcraftZip(){
        $this->readzipLocation();
        $iterator = $this->zipfileLocation->getIterator();
        $result = new \ArrayObject();
        $file = __DIR__ . '/zipcode_restaurant.txt';
        $handle = fopen($file, 'w');
        while($iterator->valid()){
            /** @var ArrayObject $location */
            $location = $iterator->current();
            $numMint = $this->countRestaurantsForlocation($location->offsetGet(1), $location->offsetGet(2));
            print_r("{$location->offsetGet(0)}|{$numMint[0]['num']}\n");
            $result->append([
            ]);
            fwrite($handle, "{$location->offsetGet(0)}|{$numMint[0]['num']}\n");
            $iterator->next();
        }
        fclose($handle);
    }

    /**
     * @throws Exception
     */
    public function mintcraftLocation(){
        $zipfileLocation = \helper\GeoHelper::readZipcodeFile('/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/zipcode_dif_location_3dd2bcbb3fbebdcca0be.txt');
        $iterator = $zipfileLocation->getIterator();
        $result = new \ArrayObject();
        $file = __DIR__ . '/test/zipcode_restaurant.txt';
        $handle = fopen($file, 'w');
        while($iterator->valid()){
            /** @var ArrayObject $location */
            $location = $iterator->current();
            $numMint = $this->countRestaurantsForlocation($location->offsetGet(1), $location->offsetGet(2));
            print_r("{$location->offsetGet(0)}|{$numMint}\n");
            $result->append([
            ]);
            fwrite($handle, "{$location->offsetGet(0)}|{$numMint}\n");
            $iterator->next();
        }
        fclose($handle);

    }


    public function byBlocks(){
        $path = __DIR__ . '/block';
        /** @var ArrayObject $blocks */
        $blocks = \helper\GeoHelper::dividedByBlocks($this->zipfile, 5000);
        $iterator = $blocks->getIterator();
        $part = 1;
        while($iterator->valid()){
            $i = new ArrayIterator($iterator->current());
            $handle = fopen("{$path}_{$part}.txt", 'w');
            echo "\033[34m Creando el archivo: {$path}_{$part}.txt\n";
            while($i->valid()){
                /** @var ArrayObject $data */
                $data = $i->current();
                fwrite($handle,"{$data->offsetGet(0)}|\n");
                $i->next();
            }
            fclose($handle);
            $iterator->next();
            $part++;
        }
    }

    /**
     * @deprecated
     * @param $pathFile
     * @param $block
     * @throws Exception
     */
    public function createBlock($pathFile, $block){
        $zips = \helper\GeoHelper::readZipcodeFile($pathFile);
        $iterator = $zips->getIterator();
        $stringLocation = null;
        $file  = __DIR__ . "/zipcode_location_{$block}.txt";

        echo "\033[32m Creando el archivo {$file}\n";
        $handle = fopen($file, 'w');
        while($iterator->valid()){
            /** @var ArrayObject $location */
            $location = $this->setZipcodeLocation($iterator->current()->offsetGet(0));
            if(!$location->offsetExists('error_message')){
                $stringLocation = "{$iterator->current()->offsetGet(0)}|{$location->offsetGet(':lat')}|{$location->offsetGet(':lng')}\n";
                //echo "\033[34m el string es: \033[32m {$stringLocation}";
            }else{
                $stringLocation = "{$iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
                echo "\033[34m el string es: \033[31m {$iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
            }
            fwrite($handle, $stringLocation);
            $iterator->next();
        }
        fclose($handle);
        echo "\033[33m Se ha terminado de crear el archivo.\n";
    }

    public function mintcraftBlocks($pathFile, $block){
        /** @var ArrayObject $location */
        $location = \helper\GeoHelper::readZipcodeFile($pathFile);
        $iterator = $location->getIterator();
        $dir = __DIR__ . '/text';
        mkdir($dir);
        $file = bin2hex(openssl_random_pseudo_bytes(10));
        $file = "{$dir}/zipcode_restaurant_num_{$file}.txt";
        $handle = fopen($file, 'w');
        while($iterator->valid()){
            /** @var ArrayObject $location_ref */
            $location_ref = $iterator->current();
            $num = $this->countRestaurantsForlocation($location_ref->offsetGet(1), $location_ref->offsetGet(2));
            fwrite($handle, "{$location_ref->offsetGet(0)}|{$num[0]['num']}\n");
            $iterator->next();
        }
        fclose($handle);
    }

    /**
     * @param $path
     * @throws Exception
     */
    public function mintcraftUniqueValues($path){
        $data  = \helper\GeoHelper::readZipcode($path);
        $data_array = new ArrayObject(array_unique($data->getArrayCopy()));
        $iterator = $data_array->getIterator();
        $dir = __DIR__ . '/text';
        mkdir($dir, 0777);
        $file = bin2hex(openssl_random_pseudo_bytes(10));
        $file = "{$dir}/zipcode_restaurant_num_{$file}.txt";
        $handle = fopen($file, 'w');
        echo "\033[32m Creando el archivo {$file}\n";
        while($iterator->valid()){
            /** @var ArrayObject $location_ref */
            $location_ref = new ArrayObject(explode('|', $iterator->current()));
            $num = $this->countRestaurantsForlocation(trim($location_ref->offsetGet(1)), trim($location_ref->offsetGet(2)));
            fwrite($handle, "{$location_ref->offsetGet(0)}|{$num[0]['num']}\n");
            $iterator->next();
        }
        fclose($handle);
        echo "\033[33m Se ha terminado de crear el archivo.\n";
    }
    public function diffZipcodes($fullFile){
        $dir = __DIR__ . '/test';
        mkdir($dir, 0777);
        $file = bin2hex(openssl_random_pseudo_bytes(10));
        $file = "{$dir}/zipcode_diff_{$file}.txt";
        $data = \helper\GeoHelper::readZipcode($fullFile);
        $unique = array_unique($data->getArrayCopy());
        $zip = $data->getIterator();
        $code = new ArrayObject();
        while($zip->valid()){
            $content = $zip->current();
            $code->append($content->offsetGet(0));
            $zip->next();
        }

        $result = new ArrayIterator(array_diff($code->getArrayCopy(), $this->zipcodesBlocks->getArrayCopy()));
        $handle = fopen($file, 'w');
        while($result->valid()){
            fwrite($handle, "{$result->current()}\n");
            $result->next();
        }
        fclose($handle);
    }
    public function locationUnique($path){
        $code = new ArrayObject();
        $location = \helper\GeoHelper::readZipcode($path);
        $unique = array_unique($location->getArrayCopy());
        $iterator = new ArrayIterator($unique);
        $dir = __DIR__ . '/test';
        mkdir($dir, 0777);
        $file = bin2hex(openssl_random_pseudo_bytes(10));
        $file = "{$dir}/zipcode_unique_location_{$file}.txt";
        $handle = fopen($file, 'w');
        while($iterator->valid()){
            $data = explode('|', $iterator->current());
            $data = trim($iterator->current());
            fwrite($handle, "{$data}\n");
            $iterator->next();
        }
        fclose($handle);
    }

    /**
     * Lee el archivo de codigos postales y crea un nuevo vector con valores unicos.
     * @throws Exception
     */
    public function zipcodesUniqueValues(){
        $code = new ArrayObject();
        $data  = \helper\GeoHelper::readZipcodeFile($this->sources['path']['txt']['zipcode']);
        $zip = $data->getIterator();
        while($zip->valid()){
            $content = $zip->current();
            $code->append("{$content->offsetGet(0)}||");
            $zip->next();
        }
        $this->zipcodesBlocks = new ArrayObject(array_unique($code->getArrayCopy()));
        $iterator = $this->zipcodesBlocks->getIterator();
        $dir = __DIR__ . '/test';
        mkdir($dir, 0777);
        $file = bin2hex(openssl_random_pseudo_bytes(10));
        $file = "{$dir}/zipcode_location_{$file}.txt";
        $handle = fopen($file, 'w');
        echo "\033[32m Creando el archivo {$file}\n";
        while($iterator->valid()){
            fwrite($handle, "{$iterator->current()}\n");
            $iterator->next();
        }
        fclose($handle);
        echo "\033[33m Se ha terminado de crear el archivo.\n";
    }

    /**
     * @param $path
     * @throws Exception
     */
    public function diffFileLocation($path){
        $data = \helper\GeoHelper::readZipcodeFile($path);
        $iterator = $data->getIterator();
        $dir = __DIR__ . '/test';
        mkdir($dir, 0777);
        $file = bin2hex(openssl_random_pseudo_bytes(10));
        $file = "{$dir}/zipcode_dif_location_{$file}.txt";
        $handle = fopen($file, 'w');
        echo "\033[32m Creando el archivo {$file}\n";
        while($iterator->valid()){
            $location = $this->setZipcodeLocation($iterator->current()->offsetGet(0));
            if(!$location->offsetExists('error_message')){
                $stringLocation = "{$iterator->current()->offsetGet(0)}|{$location->offsetGet(':lat')}|{$location->offsetGet(':lng')}\n";
                //echo "\033[34m el string es: \033[32m {$stringLocation}";
            }else{
                $stringLocation = "{$iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
                echo "\033[34m el string es: \033[31m {$iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
            }
            $location = null;
            fwrite($handle, $stringLocation);
            $iterator->next();
        }
        fclose($handle);
        echo "\033[33m Se ha terminado de crear el archivo.\n";
    }

    /**
     * @param $path
     * @throws Exception
     */
    public function moreInfo($path){
        $dir = __DIR__ . '/test';
        /** @var ArrayObject $zip_code */
        $zip_code = \helper\GeoHelper::readZipcode($this->sources['path']['txt']['zipcode']);
        $more_info = \helper\GeoHelper::readZipcodeFile($path);

        $array = [];
        $count = 0;
        $mi_iterator = $more_info->getIterator();
        echo "\033[36m procesando...\n";
        while($mi_iterator->valid()){
            if(empty($mi_iterator->current()->offsetGet(0))) break;
            $grep = preg_grep("/{$mi_iterator->current()->offsetGet(0)}/", $zip_code->getArrayCopy());
            $count_current = count($grep);
            echo "\033[35m procesando... \033[36m {$count_current} para el codigo postal: {$mi_iterator->current()->offsetGet(0)}\n";
            $array = array_merge($array, $grep);
            $mi_iterator->next();
            $count += $count_current;
//            if(1 == $count){
//                break;
//            }
        }
        echo "\n\n\033[35m procesado... \033[36m {$count} referencias de codigos postales\n\n";
        $data = \helper\GeoHelper::dividedByBlocks(new ArrayObject($array), 500);
        $mi_iterator = $data->getIterator();
        while($mi_iterator->valid()){
            $this->iterador($dir, new ArrayIterator($mi_iterator->current()));
            $mi_iterator->next();
        }
    }


    public final function needMoreInformation($path){
        $date = \helper\GeoHelper::dateTime();
        $dir = __DIR__ ."/{$date->format('YmdHmi')}";
        /** @var ArrayObject $zip_code */
        $zip_code = \helper\GeoHelper::readZipcode($this->sources['path']['txt']['zipcode']);
        $more_info = \helper\GeoHelper::readZipcodeFile($path);
        $bug = 0;

        $array = [];
        $count = 0;
        $mi_iterator = $more_info->getIterator();
        echo "\033[36m procesando...\n";
        while($mi_iterator->valid()){
            $grep = array();
            if(empty($mi_iterator->current()->offsetGet(0))) break;
            $grep = preg_grep("/{$mi_iterator->current()->offsetGet(0)}/", $zip_code->getArrayCopy());
            $array = array_merge($array, [array_shift(array_slice($grep, 0, 1))]);
            $data = array_shift(array_slice($grep, 0, 1));
            $count_current = count($grep);
            echo "\033[35m procesando... \033[36m {$count_current} para el codigo postal: {$mi_iterator->current()->offsetGet(0)} :: {$data}\n";
            $mi_iterator->next();
            $count += $count_current;
            //if(15 == $bug) break;
            //$bug++;
        }
        echo "\n\n\033[35m procesado... \033[36m {$count} referencias de codigos postales\n\n";
        $data = \helper\GeoHelper::dividedByBlocks(new ArrayObject($array), 500);
        $mi_iterator = $data->getIterator();
        while($mi_iterator->valid()){
            $this->iterador($dir, new ArrayIterator($mi_iterator->current()));
            $mi_iterator->next();
        }
    }

    /**
     * @param $dir
     * @param ArrayIterator $mi_iterator
     */
    protected function iterador($dir, ArrayIterator $mi_iterator){
        $stringLocation = '';
        $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $zip_location = new ArrayIterator();
        $txt = \helper\GeoHelper::createFile($dir, "need_more_information_{$date->format('YmdHmi')}");
        echo "\033[35m Se esta creando el archivo \033[37m {$txt}\n";
        echo "\033[32m procesando... Obteniendo geolocalizacion detallada\n";
        $handle = fopen($txt, 'a');
        while($mi_iterator->valid()){
            $array = explode('|', $mi_iterator->current());
            try {
                $location = $this->setZipcodeLocation(urlencode("{$array[0]}+{$array[3]}+{$array[4]}"));
                if (!$location->offsetExists('error_message')) {
                    $stringLocation = "{$array[0]}|{$location->offsetGet(':lat')}|{$location->offsetGet(':lng')}\n";
                    $zip_location->append($stringLocation);
                    //echo "\033[34m el string es: \033[32m {$stringLocation}";
                } else {
                    $stringLocation = "{$mi_iterator->current()->offsetGet(0)}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
                    echo "\033[34m el string es: \033[31m {$array[0]}|{$location->offsetGet('status')}|{$location->offsetGet('error_message')}\n";
                }
            }catch (Exception $e){
                \helper\GeoHelper::log($e->getMessage(), function($e){
                    return $e;
                });
                \helper\GeoHelper::log($e->getTraceAsString(), function($e){
                    return $e;
                });
            }
            fwrite($handle, $stringLocation);
            $mi_iterator->next();
        }
        fclose($handle);

        $txt = \helper\GeoHelper::createFile($dir, "need_more_information_count_{$date->format('YmdHmi')}");
        echo "\033[35m Se esta creando el archivo \033[37m {$txt}\n";
        $handle = fopen($txt, 'a');
        while($zip_location->valid()){
            $num = 0;
            $decode = explode('|', $zip_location->current());
            $num = $this->countRestaurantsForlocation($decode[1], $decode[2]);
            fwrite($handle, "{$decode[0]}|{$num}\n");
            $zip_location->next();
        }
        fclose($handle);
        echo "\033[34m Ha terminado el proceso sobre el archivo {$txt}\n";
    }

    /**
     * @param $path
     * @throws Exception
     */
    public function readfileLocation($path){
        $location = \helper\GeoHelper::readZipcode($path);
        $this->countRestaurants($location->getIterator());
    }

    /**
     * @param ArrayIterator $zip_location
     */
    public function countRestaurants(ArrayIterator $zip_location){
        $date = \helper\GeoHelper::dateTime();
        $dir = __DIR__ . "/consolidated{$date->format('YmdHmi')}";
        $txt = \helper\GeoHelper::createFile($dir, "restaurant_count_{$date->format('YmdHmi')}");
        echo "\033[35m Se esta creando el archivo \033[37m {$txt}\n";
        $handle = fopen($txt, 'a');
        while($zip_location->valid()){
            $decode = explode('|', $zip_location->current());
            $num = $this->countRestaurantsForlocation($decode[1], $decode[2]);
            echo "\033[32m para el codigo postal \033[33m{$decode[0]}\033[32m  hay \033[33m {$num} \033[32m restaurantses\033[33m \n";
            fwrite($handle, "{$decode[0]}|{$num}\n");
            $zip_location->next();
        }
        fclose($handle);
        echo "\033[34m Ha terminado el proceso sobre el archivo {$txt}\n";
    }

}

switch($argv[1]){
    case '--by-block':
        $zipcode = new Zipcode();
        $zipcode->zipcodesUniqueValues('');
        $zipcode->mintcraftLocation();
        break;
    case '--location':
        $zipcode = new Zipcode();
        $zipcode->readzipFile();
        $zipcode->createFileZipLocation();
        break;
    case '--restaurant-num-by-location';
        $zipcode = new Zipcode();
        $zipcode->mintcraftZip();
        break;
    case '--all':
        $zipcode = new Zipcode();
        $zipcode->readzipFile();
        $zipcode->createFileZipLocation();
        $zipcode->mintcraftZip();
        break;
    case '--location-by-blocks':
        $zipcode = new Zipcode();
        $zipcode->readzipFile();
        $zipcode->byBlocks();
        break;
    case '--location-by-block':
        $zipcode = new Zipcode();
        $zipcode->createBlock($argv[3], $argv[2]);
        break;
    case '--restaurant-num-by-block':
        $zipcode = new Zipcode();
        $zipcode->mintcraftBlocks($argv[3], $argv[2]);
        break;
    case '--restaurant-unique-value':
        $zipcode = new Zipcode();
        $zipcode->mintcraftUniqueValues($argv[2]);
        break;
    case '--diff':
        $zipcode = new Zipcode();
        $zipcode->zipcodesUniqueValues();
        $zipcode->locationUnique($argv[2]);
        //$zipcode->diffZipcodes($argv[2]);
        $zipcode->diffFileLocation('/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/diff.txt');
        break;
    case '--more-information':
        $zipcode = new Zipcode();
        $zipcode->needMoreInformation('/usr/share/nginx/cli/SDInvoicesFileCheck/app/cli/test/zipcode_unique.txt');
        break;
    case '--count-restaurants':
        $zipcode = new Zipcode();
        $zipcode->readfileLocation($argv[2]);
        break;
    case '--help':
    default;
echo "\033[33m Usage:
\033[37m command [options] [arguments]

\033[46m Options\033[0m:
\033[32m   --help                               \033[37m Display this help message
\033[32m   --more-information                   \033[37m Takes all zip codes and the divided in blocks with the location.
\033[32m   --count-restaurants  < filepath >    \033[37m Display the number of the restaurants per zip code and save in a file text.
\033[32m   --all                                \033[37m Force ANSI output
";
        break;
}
