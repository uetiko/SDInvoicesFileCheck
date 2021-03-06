<?php
namespace utils\abstracts;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractClass {
    /** @var \utils\abstracts\Connection $cnn */
    protected $cnn = null;
    /** @var array $sources */
    protected  $sources = null;

    public function _construct(){
        $this->sources = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(realpath(__DIR__ . "/../../resources/sources.yml")));
        //$this->cnn = new \utils\abstracts\Connection();
    }
    protected function eloquent(){
        /** @var array $yml */
        $yml = Yaml::parse(file_get_contents(realpath(__DIR__ . "/../../resources/database.yml")));
        $manager = new Manager();
        $manager->addConnection([
            'driver'    => 'mysql',
            'host'      => $yml['database']['host'],
            'database'  => $yml['database']['database'],
            'username'  => $yml['database']['user'],
            'password'  => $yml['database']['pass'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $manager->setAsGlobal();
        $manager->bootEloquent();
    }
}
