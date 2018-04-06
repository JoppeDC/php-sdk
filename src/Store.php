<?php

namespace EventSquare;

use EventSquare\EventSquareException;
use EventSquare\Connection;

class Store {

    private $connection;
    private $data;

    private $preview_token;

    private $queueid;
    private $cartid;
    private $entry_url;

    private $expires_at;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }

    public $event = null;
    public $edition = null;
    public $channel = null;

    /**
    * Set language;
    */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
    * Get language;
    */
    public function getLanguage()
    {
        return $this->language;
    }


    /**
    * Get default language;
    */
    public function getDefaultLanguage()
    {
        return $this->event->languages[0];
    }

    /**
    * Set default language;
    */
    public function setDefaultLanguage()
    {
        $this->language = $this->getDefaultLanguage();
        return $this;
    }

    /**
    * Get store;
    */
    public function getUri($segments=null)
    {
        $link = '';

        if(!empty($segments['domain']) && !empty($segments['event'])){
            $link = $segments['event'] . '.' . $segments['domain'];
        }

        if(!empty($segments['language'])){
            $link .= '/' . $segments['language'];
        }
        if(!empty($segments['edition'])){
            $link .= '/' . $segments['edition'];
        }
        if(!empty($segments['channel'])){
            $link .= '/' . $segments['channel'];
        }
        if(!empty($segments['preview_token'])){
            $link .= '?preview_token=' . $segments['preview_token'];
        }

        if(!$segments){

            if($this->edition) {
                $link .= '/' . $this->edition->uri;
            }
            if($this->channel) {
                $link .= '/' . $this->channel->uri;
            }
            if($this->preview_token) {
                $link .= '?preview_token=' . $this->preview_token;
            }

        }

        return ltrim($link, '/');
    }

    /**
    * Set event;
    */
    public function event($event)
    {
        $parameters = [
            'language' => $this->language
        ];
        $parameters = array_merge($this->connection->meta,$parameters);

        $this->event = $this->connection->send('store/' . $event,'event')->get($parameters);
        return $this->event;
    }

    /**
    * Set event;
    */
    public function load($event,$edition,$channel,$preview_token = null)
    {
        $uri = $event.'/'.$edition;

        if($channel) {
            $uri .= '/' . $channel;
        }

        $parameters = [
            'cart' => $this->getCartId(),
            'queue' => $this->getQueueId(),
            'entry_url' => $this->getEntryUrl(),
            'language' => $this->language,
            'preview_token' => $preview_token,
        ];

        $parameters = array_merge($this->connection->meta,$parameters);

        $this->preview_token = $preview_token;
        $this->edition = $this->connection->send('store/' . $uri,'edition')->get($parameters);

        $this->updateQueueId();
        $this->updateCartId();
        return $this;
    }

    /**
    * Get cart
    */
    public function getCart()
    {
        $this->cart = $this->connection->send('cart/' . $this->getCartId(),'cart')->get([
            'language' => $this->language
        ]);
        return $this->cart;
    }

    /**
    * Find cartid in store and update instance property
    */
    public function getCartId()
    {
        return $this->cartid?: null;
    }

    /**
    * Set cartid
    */
    public function setCartId($cartid)
    {
        $this->cartid = $cartid;
        return $this;
    }

    /**
    * Update cartid
    */
    public function updateCartId()
    {
        if(!empty($this->edition->cart->cartid))
        {
            $this->cartid = $this->edition->cart->cartid;
            $this->getCart();
        }
        return $this;
    }

    /**
    * Get queueid
    */
    public function getQueueId()
    {
        return $this->queueid?: null;
    }

    /**
    * Set queueid
    */
    public function setQueueId($queueid)
    {
        $this->queueid = $queueid;
        return $this;
    }

    /**
    * Get entry_url
    */
    public function getEntryUrl()
    {
        return $this->entry_url?: null;
    }

    /**
    * Set entry_url
    */
    public function setEntryUrl($entry_url)
    {
        $this->entry_url = $entry_url;
        return $this;
    }

    /**
    * Update queueid
    */
    public function updateQueueId()
    {
        if(!empty($this->edition->queue->queueid))
        {
            $this->queueid = $this->edition->queue->queueid;
        }
        return $this;
    }

    /**
    * Check if we the store is open for public
    */
    public function isClosed()
    {
        return is_null($this->edition);
    }

    /**
    * Check if we are queued
    */
    public function isQueue()
    {
        if(!empty($this->edition->queue)) return true;
        return false;
    }


    /**
    * Check if we have a cart
    */
    public function isCart()
    {
        if(!empty($this->edition->cart)) return true;
        return false;
    }


    /**
    * Check if the current cart is pending
    */
    public function isPending()
    {
        if(!empty($this->cart) && !empty($this->cart->pending)) return true;
        return false;
    }

    /**
    * Update cart type
    */
    public function updateType($uid,$show,$seatmap,$quantity,$places)
    {
        $parameters = [
            'quantity' => $quantity
        ];

        if($show){
            $parameters['show'] = $show;
        }
        if($seatmap){
            $parameters['seatmap'] = $seatmap;
        }
        if($places){
            $parameters['places'] = $places;
        }

        $this->connection->send('cart/' . $this->getCartId() . '/types/' . $uid)->put($parameters);
        return;
    }

    /**
    * Remove cart type
    */
    public function removeType($uid,$show,$seatmap)
    {
        $parameters = [];

        if($show){
            $parameters['show'] = $show;
        }
        if($seatmap){
            $parameters['seatmap'] = $seatmap;
        }

        $this->connection->send('cart/' . $this->getCartId() . '/types/' . $uid)->delete($parameters);
        return;
    }

    /**
    * Get show
    */
    public function getShow($event,$edition,$channel,$show_id)
    {
        $show = $this->connection->send('store/'.$event . '/' . $edition . '/' . $channel . '/' . $show_id,'show')->get([
            'cart' => $this->getCartId(),
            'language' => $this->language
        ]);
        return $show;
    }

    /**
    * Get seatmap
    */
    public function getSeatmap($event,$edition,$channel,$show_id,$seatmap_id)
    {
        $show = $this->connection->send('store/'.$event . '/' . $edition . '/' . $channel . '/' . $show_id . '/' . $seatmap_id,'seatmap')->get([
            'cart' => $this->getCartId(),
            'language' => $this->language
        ]);
        return $show;
    }

    public function getSeatmapDetails($seatmap_id)
    {
        $seatmap = $this->connection->send('seatmap/'.$seatmap_id,'seatmap')->get([
            'language' => $this->language
        ]);
        return $seatmap;
    }


}
