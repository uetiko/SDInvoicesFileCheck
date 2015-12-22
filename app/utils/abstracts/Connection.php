<?php
namespace utils\abstracts;
use Symfony\Component\Yaml\Yaml;
use PDO;

class Connection extends PDO{
    private $engine = null;
    private $host = null;
    private $database = null;
    private $user = null;
    private $pass = null;

    public function __construct(){
        /** @var array $yml */
        $yml = Yaml::parse(file_get_contents(realpath(__DIR__ . "/../../resources/database.yml")));
        $this->engine = 'mysql';
        $this->host = $yml['database']['host'];
        $this->database = $yml['database']['database'];
        $this->user = $yml['database']['user'];
        $this->pass = $yml['database']['pass'];
        $dns = $this->engine.':dbname='.$this->database.";host=".$this->host;
        parent::__construct( $dns, $this->user, $this->pass );
    }
}