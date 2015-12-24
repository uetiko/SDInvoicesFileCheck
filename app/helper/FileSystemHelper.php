<?php
namespace helper;

/**
 * Class FileSystemHelper
 * @package helper
 */
class FileSystemHelper {
    /**
     * @param \Closure $name
     * @return string
     */
    final static public function createFileName(\Closure $name){
        return "{$name()}.xml";
    }

    /**
     * @param string $cvsName
     * @param \ArrayObject $list
     * @return bool
     */
    final static function createCVS($cvsName,  \ArrayObject $list){
        $create = false;
        $content = null;
        try {
            $handle = fopen("{$cvsName}.csv", 'w+');
            if(false != $handle) {
                $header = $list->offsetGet(0);
                fputcsv($handle, $header->getArrayHeader());
                /** @var \ArrayObject $list */
                foreach ($list as $invoice) {
                    /** @var \dto\Invoice $invoice */
                    fputcsv($handle, $invoice->toArray());
                }
            }else{
                throw new \Exception("\033[31m No se ha podido crear el Handle del csv.");
            }
            fclose($handle);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
        return $create;
    }
    final static function createSimpleCVS($cvsName,  \ArrayObject $list){
        $create = false;
        $content = null;
        try {
            $handle = fopen("{$cvsName}.csv", 'w+');
            if(false != $handle) {
                $header = $list->offsetGet(0);
                fputcsv($handle, $header->getArrayHeader());
                /** @var \ArrayObject $list */
                foreach ($list as $invoice) {
                    /** @var \dto\Invoice $invoice */
                    fputcsv($handle, $invoice->toArray());
                }
            }else{
                throw new \Exception("\033[31m No se ha podido crear el Handle del csv.");
            }
            fclose($handle);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
        return $create;
    }

    /**
     * @param string $file1
     * @param string $file2
     * @return bool
     */
    static public function equalString($file1, $file2){
        return ($file1 === $file2)? true:false;
    }
    static public function CreateNewInvoiceDTO(array $data){
        $dto = new \dto\Invoice();
        /**
         * @param array $invoice
         * @return float
         */
        $getTotal = function($invoice){
            $iva = $invoice['subtotal'] * (float)".{$invoice['amount_tax']}";
            return $invoice['subtotal'] + $iva;
        };
        /**
         * @param array $invoice
         * @return float
         */
        $getIva = function($invoice){
            return $invoice['subtotal'] * (float)".{$invoice['amount_tax']}";
        };

        $dto->setUpdateTime($data['from_unixtime(ai.updated_at)']);
        $dto->setPeriodEnd($data['from_unixtime(ai.period_end_at)']);
        $dto->setUuid(substr($data['text'], 15, 32));
        $dto->setClientName($data['legal_name']);
        $dto->setRfc($data['rfc']);
        $dto->setTotal($getTotal($data));
        $dto->setSubtotal($data['subtotal']);
        $dto->setIva($getIva($data));
        return $dto;
    }

    /**
     * @param $path
     * @param $fileName
     * @return boolean
     * @throws \Exception
     */
    static public function getFilesFromFileSystem($path, $fileName){
        $handle = fopen("{$fileName}.csv", 'w');
        if(false == $handle){
            throw new \Exception("\033[31m No se pudo abrir/crear el archivo {$fileName}\n");
        }
        foreach (scandir($path) as $file) {
            fputcsv($handle, [$file]);
        }
        $bool = fclose($handle);
        return $bool;
    }
    static public function getSimpleCVSNoArray($fileName, array $data){
        $handle = fopen("{$fileName}.csv", 'w');
        if(false == $handle){
            throw new \Exception("\033[31m No se pudo abrir/crear el archivo {$fileName}\n");
        }
        foreach ($data as $file) {
            fputcsv($handle, [$file]);
        }
        $bool = fclose($handle);
        return $bool;
    }

    final static function CVSFromArray($cvsName,  array $list){
        $create = false;
        $content = null;
        $headers = function(array $data){
            $head = new \ArrayObject();
            foreach($data as $k => $v){
                $head->append($k);
            }
            return $head->getArrayCopy();
        };
        try {
            $handle = fopen("{$cvsName}.csv", 'w+');
            if(false != $handle) {
                fputcsv($handle, $headers($list[0]));
                /** @var \ArrayObject $list */
                foreach ($list as $row) {
                    /** @var \dto\Invoice $invoice */
                    fputcsv($handle, $row);
                }
            }else{
                throw new \Exception("\033[31m No se ha podido crear el Handle del csv {$cvsName}.");
            }
            fclose($handle);
        }catch (\Exception $e){
            echo $e->getMessage();
        }
    }
}