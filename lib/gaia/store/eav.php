<?php
namespace Gaia\Store;
use Gaia\Container;

/**
 * see http://en.wikipedia.org/wiki/Entity-attribute-value_model
 * The EAV class has a really simple interface. It is a container/iterator, with the ability
 * to load and store peristent data for an entity in a given storage object.
 * @examples
    
    // wrap the object instantation up in a factory method so it will always be consistent.
    class MyApp {
        protected static $storage;
        
        public static function car( $owner ){
            return new \Gaia\Store\EAV( self::storage(), $owner );
        }
        
        protected static function storage(){
            if( isset( self::$storage ) ) return self::$storage;
            return self::$storage = new \Gaia\Store\Prefix( new \Gaia\Store\Redis, 'myapp/car/');
        }
    }
    
    // load a new EAV in a car namespace for user_id 3.
    $owner = 3;
    
    $car = MyApp::car($owner);
    
    // assign a bunch of properties to the car. completely arbitrary.
    $car->tires = 'goodyear';
    $car->doors = 2;
    $car->convertible = FALSE;
    $car->year = 1978;
    $car->make = 'ford';
    $car->model = 'pinto';
    $car->type = 'wagon';
    $car->color = 'red';
    $car->store();
    foreach( $car as $key=>$value ){
        print $key . ': ' . $value . "\n";
    }
    
    // print a list of all the key names currently set in the car.
    print_r( $car->keys() );
    
    // use any container method you want.
 */
class EAV extends Container {

    protected $entity;
    protected $storage;
   
   /**
    * Specify storage and an entity key.
    * This will load a bunch of key/value pairs associated with the entity
    */
    public function __construct( Iface $storage, $entity ){
        $this->storage = $storage;
        
        // a way to populate an entity without having to read from storage.
        // for internal use only. used by the EAV::bulkLoad() method.
        if( is_array( $entity ) ) {
            $this->entity = $entity["\0._entity"];
            unset( $entity["\0._entity"]);
            $this->load( $entity );
            return;
        }
        
        // standard approach.
        $this->entity = $entity;
        parent::__construct( $this->storage->get( $entity ) );
    }
    
    public static function bulkLoad( Iface $storage, array $keys ){
        $response = $storage->get( $keys );
        $list = array();
        foreach( $keys as $key ){
            $entity = isset( $response[ $key ] ) ? $response[ $key ] : array();
            $entity["\0._entity"] = $key;
            $list[ $key ] = new self( $storage, $entity );
        }
        return $list;
    }
    
   /**
    * Make changes to the object then write back to persistent storage.
    */
    public function store(){
        return $this->storage->set( $this->entity, $this->all() );
    }
    
   /**
    * remove all the keys currently loaded for this entity.
    */
    public function clear(){
        $this->flush();
        return $this->store();
    }
    
} // EOC