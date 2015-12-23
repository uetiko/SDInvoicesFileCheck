<?php
include_once realpath(__DIR__ . "/../../vendor/autoload.php");
/**
 * Class Invoice
 * @package cli
 * @version 0.2
 * @author Angel Barrientos <angel.barrintos@sindelantal.mx>
 */
class Invoice extends \utils\abstracts\AbstractClass{
    /** @var \utils\abstracts\Connection $cnn */
    private $cnn = null;
    /** @var ArrayObject $invoices */
    protected $invoices = null;
    /** @var \ArrayObject $listDTO */
    private $listDTO = null;
    /** @var array $listDocuments */
    private $listDocuments = null;
    /** @var string $xml_path */
    private $xml_path = null;

    public function __construct(){
        parent::eloquent();
        $this->cnn = new \utils\abstracts\Connection();
    }

    public function find(){
        var_dump(\model\InvoiceAccount::find(12));
    }

    /**
     * @param string $start Format: Y-m-d H:i:s example: 2015-01-01 00:00:00
     * @param string $end Format: Y-m-d H:i:s example: 2015-12-31 05:59:59
     * @return int numero de registros de la consulta
     */
    public function getAllDatabaseInvoices($start = null, $end = null){
        $query = $this->cnn->prepare("
            select
              from_unixtime(ai.updated_at),
              from_unixtime(ai.period_end_at),
              c.id,
              c.legal_name,
              c.rfc,
              ais.text,
              sum(aic.amount) as 'subtotal',
              ai.amount_tax,
              ai.status
            FROM accounting_invoices_statuses as ais
              INNER JOIN accounting_invoices as ai
                on ai.id = ais.invoice_id
              INNER JOIN clients as c
                ON c.id = ai.client_id
              INNER JOIN accounting_invoices_concepts as aic
                on ai.id = aic.invoice_id
            where text like 'CDFI%'
              and from_unixtime(ai.period_end_at) between :date1 and :date2
            GROUP BY ais.text
            ORDER BY ais.created_at;
        ");
        $query->execute([
            ':date1' => '2015-01-01 00:00:00',
            ':date2' => '2015-09-30 05:59:59'
        ]);
        $this->invoices = new \ArrayObject($query->fetchAll());
        return $query->rowCount();
    }

    public function listDTO(){
        $list = new \ArrayObject();
        $iterator = $this->invoices->getIterator();
        while($iterator->valid()){
            $list->append(\helper\FileSystemHelper::CreateNewInvoiceDTO($iterator->current()));
            $iterator->next();
        }
        $this->listDTO = $list;
        return $list;
    }

    /**
     * Crea un fichero csv con todas las facturas en sistema extraidas
     * en el corte de la consulta.
     */
    public function createCVS(){
        $message = null;
        try {
            \helper\FileSystemHelper::createCVS('allInvoicesFromDatabase', $this->listDTO);
            $dir = __DIR__;
            $message = "\033[34m Se ha creado el archivo \033[35m allInvoicesFromDatabase.csv \033[34m en {$dir}\n";
            echo "{$this->listDTO->count()}\n";
        }catch (\Exception $e){
            error_log($e->getMessage(), 0);
            error_log($e->getTraceAsString(), 0);
            $message = "\033[31m {$e->getMessage()}";
        }
        return $message;
    }

    /**
     *
     */
    public function createCVSReport(){
        try{
            \helper\FileSystemHelper::createCVS('AllInvoicesbetweenSystemAndSat', $this->listDocuments['exist']);
            $message = "\033[34m Se ha creado el archivo AllInvoicesbetweenSystemAndSat.csv";
        }catch (\Exception $e){
            error_log($e->getMessage(), 0);
            error_log($e->getTraceAsString(), 0);
            $message = "\033[31m {$e->getMessage()}";
        }
        return $message;
    }
    public function createCSVReportInvoiceNoMach(){
        $dir = __DIR__;
        $message = null;
        /** @var \ArrayObject $data */
        $data = $this->listDocuments['noexist'];
        try{
            \helper\FileSystemHelper::getSimpleCVSNoArray('InvoicesNoMach', $data);
            $message = "\033[24m Se ha creado el archivo \033[35m InvoicesNoMach.csv \033[34m en {$dir}";
        }catch (\Exception $e){
            error_log($e->getMessage(),0);
            error_log($e->getTraceAsString(), 0);
            $message = "\033[31m {$e->getMessage()}";
        }
        return $message;
    }

    /**
     *
     */
    public function processingRelationFromFS(){
        $sources = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(realpath(__DIR__ . "/../resources/sources.yml")));
        $invoiceList = null;
        $message = null;
        $this->listDocuments = $this->createRelations($sources['path']['invoice']['xml'], $this->listDTO);
    }
    public function createCSVFromAllFiles(){
        $dir = __DIR__;
        $message = null;
        $sources = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(realpath(__DIR__ . "/../resources/sources.yml")));
        try {
            \helper\FileSystemHelper::getFilesFromFileSystem($sources['path']['invoice']['xml'], 'invoicesOnFileSystem');
            $message = "\033[34m Se ha creado el archivo \033[35m invoicesOnFileSystem.csv \033[34m en {$dir}";
        }catch (\Exception $e){
            error_log($e->getMessage(), 0);
            error_log($e->getTraceAsString(), 0);
            $message = "\033[31m {$e->getMessage()}";
        }
        return $message;
    }

    /**
     *
     * @param string $path
     * @param ArrayIterator $collection
     * @return array
     */
    protected function createRelations($path, \ArrayObject $collection){
        $list = new \ArrayObject(scandir($path));
        $exist = new ArrayObject();
        $noExist = new \ArrayObject();
        /**
         * @param string $string
         * @return string
         */
        $cutName = function($string){
            $name = null;
            if(preg_match("/^[a-zA-Z]{4}\d{6}\w{3}/", substr($string,14))){
                $name = substr($string,28, 32);
            }else{
                $name = substr($string,27, 32);
            }
            return $name;
        };
        $getUuid = function($fileName){
            $uuid = null;
            if(preg_match("/^[a-zA-Z]{4}\d{6}\w{3}/", substr($string,14))){
                $uuid = substr($string,28, 36);
            }else{
                $uuid = substr($string,27, 36);
            }
            return $uuid;
        };

        $dirList = $list->getIterator();
        while($dirList->valid()){
            $bool = false;
            $iterator = $collection->getIterator();
            $file = $dirList->current();
            $string = $cutName($file);
            while($iterator->valid()){
                /** @var \dto\Invoice $invoice */
                $invoice = $iterator->current();
                if($string == $invoice->getPartOfNameFile()){
                    echo "\033[36m{$invoice->getPartOfNameFile()}\n";
                    $bool = true;
                    $exist->append($invoice);
                    break;
                }
                $iterator->next();
            }
            if(!$bool){
                echo "\033[35m este xml no se encontro en la base de datos\n\033[31m {$file}\n";
                $noExist->append([
                    'xml' => $file,
                    'uuid' => $getUuid($file)
                ]);
            }
            $dirList->next();
        }


        echo "\033[35m All relation are created\n";
        return [
            'exist' => $exist,
            'noexist' => $noExist
        ];
    }

    public function getNoMatchAmount(){}
    public function test(){
        \helper\FileSystemHelper::createCVS('test', $this->listDTO());
    }
}

$invoice = new Invoice();
/*
$invoice->getAllDatabaseInvoices();
$invoice->listDTO();
echo "{$invoice->createCSVFromAllFiles()}\n";
echo "{$invoice->createCVS()}\n";
$invoice->processingRelationFromFS();
echo "{$invoice->createCVSReport()}\n";
echo "{$invoice->createCSVReportInvoiceNoMach()}\n";
*/
switch($argv[1]){
    case '--file-database':
        $invoice->getAllDatabaseInvoices();
        $invoice->listDTO();
        echo "{$invoice->createCSVFromAllFiles()}\n";
        echo "{$invoice->createCVS()}\n";
        break;
    case '--only-document-exist':
        $invoice->getAllDatabaseInvoices();
        $invoice->listDTO();
        $invoice->processingRelationFromFS();
        echo "{$invoice->createCVSReport()}\n";
        break;
    case '--all':
        $invoice->getAllDatabaseInvoices();
        $invoice->listDTO();
        echo "{$invoice->createCSVFromAllFiles()}\n";
        echo "{$invoice->createCVS()}\n";
        $invoice->processingRelationFromFS();
        echo "{$invoice->createCVSReport()}\n";
        echo "{$invoice->createCSVReportInvoiceNoMach()}\n";
        break;
    case '--no-mach':
        $invoice->getAllDatabaseInvoices();
        $invoice->listDTO();
        $invoice->processingRelationFromFS();
        echo "{$invoice->createCSVReportInvoiceNoMach()}\n";
        break;
    case '--no-mach-amount':
        break;
    case '--help':
    default;
        echo "\033[34m El siguiente programa permite \n";
        echo "\033[32m --file-database \033[33m         Crea dos archivos, uno con todos los nombres de los xml del filesystem y otro con todos los registos de la base de datos.\n";
        echo "\033[32m --only-document-exist\033[33m    Crea un archivo que crea solo las facturas del sistema que tienen su correspondiente con un xml físicos\n";
        echo "\033[32m --all\033[33m                    Corre todas las tareas del script\n";
        echo "\033[32m --no-mach\033[33m                Crea un archivo que de aquellos xml que no encuentra relación con la base de datos.\n";
        echo "\033[32m \033[33m                         \n";
        break;
}