<?php
namespace Gaia\Souk;

/**
* defines the interface for how to bind stockpile to souk.
* implement this interface as demonstrated in Souk_StockpileBinder
*/
interface StockpileBinder_Iface {
    public function itemAccount( $user_id );
    public function currencyAccount( $user_id );
    public function currencyId();
}
