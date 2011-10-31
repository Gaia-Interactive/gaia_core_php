<?php
namespace Gaia\Souk;
use Gaia\Stockpile;

/**
* This class is just for demonstration purposes. You need to write your own
* class to bind stockpile and souk together in your application.
*/
class StockpileBinder implements Iface  {
    protected $item_app;
    protected $currency_app;
    protected $currency_id;
    
    public function __construct( $item_app, $currency_app, $currency_id ){
        $this->item_app = $item_app;
        $this->currency_app = $currency_app;
        $this->currency_id = $currency_id;
    }
    
    public function itemAccount( $user_id ){
        return  new Stockpile\Tally( $this->item_app, $user_id );
    }
    
    public function currencyAccount( $user_id ){
        return new Stockpile\Tally( $this->currency_app, $user_id );
    }
    
    public function currencyId(){
        return $this->currency_id;
    }
}
