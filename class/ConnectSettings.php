<?php

require('Curl.php');
require('Helpers.php');


class ConnectSettings {
    
    protected static $wpdb;
    protected static $CONNECT = '';
    private $curl;
    private $response;
    var $url_kitagil = 'http://localhost:3000/api/1.0/hooks';

    public function __construct($wpdb = null)
    {
        try {
            $this->curl = (new CurlEX());
            $this->response = new Helpers;
            self::$wpdb = $wpdb;
            self::$CONNECT = $wpdb->prefix . 'connect_kitagil_db';
        } catch (Exception $e) {
              echo json_encode(['error' => $e->getMessage()]);
        }
    }

    protected function checkWC(){
        if ( !class_exists( 'WooCommerce' ) ) {
            die('😢 Parece que no tienes activado el plugin <a href="https://woocommerce.com/" target_="blank"><b>Woocommerce</b></a>');
          }
    }

    /**
     * Make a table in database when plugin activate
     */
    protected function installDB(){
        try {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $wpdb = self::$wpdb;
            $tableConnect = self::$CONNECT;
            $charset_collate = $wpdb->get_charset_collate();

            $sqlConnect = "CREATE TABLE $tableConnect (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                last_sync datetime DEFAULT  NULL,
                token_api text NOT NULL,
                meta_tag MEDIUMTEXT DEFAULT NULL ,
                PRIMARY KEY  (id)
            ) $charset_collate;";

            dbDelta( $sqlConnect );

        } catch (Exception $e) {
              echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function dropTableSetting(){
        try {
            // self::$wpdb->query("DROP TABLE  " . self::$CONNECT . ";");
        } catch (Exception $e) {
              echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * *********************************************
     * *** All functions about CONNECT *******
     * *********************************************
     */

    /**
     * CONNECT
     * Get all info by settings async
     * @return mixed
     */
    protected function getConfig()
    {
        try {
            return self::$wpdb->get_results("SELECT * FROM " . self::$CONNECT ,
                OBJECT);
        } catch (Exception $e) {
              echo json_encode(['error' => $e->getMessage()]);
        }
    }
    /**
     * CONNECT
     * Save token and info about the connect
     * @return mixed
     */
    protected function saveTokenApi($data = array())
    {
        try {
            $prev = self::$wpdb->get_results("SELECT * FROM " . self::$CONNECT, OBJECT);
            if(!count($prev)){
                return self::$wpdb->insert(
                    self::$CONNECT,
                    array(
                        'token_api' => $data['token_api'],
                        'meta_tag' => json_encode( ['relations' => []])
                    )
                );
            }else{
                $prev = $prev[0];
                return self::$wpdb->update(self::$CONNECT,
                array(
                    'token_api' => $data['token_api'],                    
                ),
                array('id' => $prev->id));
            }
            
            $lastId = self::$wpdb->insert_id;
            echo $lastId;
            wp_die();

        } catch (Exception $e) {
              echo json_encode(['error' => $e->getMessage()]);
        }
    }
    /**
     * CONNECT
     * Sync all products, only call when is the first sync
     * @return mixed
     */
    protected function syncAllProductsKitagil()
    {
        try {
            $config = self::$wpdb->get_results("SELECT * FROM " . self::$CONNECT, OBJECT);
            $config = $config[0];

            $meta_tag = $config->meta_tag = json_decode($config->meta_tag);
            $response = $this->curl->get(
                $this->url_kitagil . '/products',
                [],
                ['keyMachine: ' . $config->token_api]
            );
            if ($response['errno']) {
                throw new \Exception($response['errmsg']);
            }
     
            $response = $this->response->json(
                json_decode($response['content'], true),
                '', $response['http_code']);
            $response = json_decode($response);

            // Guardamos primero el producto en la tabla wp_posts asi obtenemos el id            
            $relations = (!$meta_tag->relations)? []:$meta_tag->relations;

    
            foreach ($response->data as $product) {
            
                $saved = $this->detecSavedProduct($product->_id, $relations);
                $product_id = $this->saveProduct($product, $saved);
                if( !$saved ){
                    array_push( $relations, [
                        'kitagil_id' => $product->_id, 'wc_id' => $product_id
                    ] );
                }
            }
            
            $meta_tag = [
                'relations' => $relations
            ];

            // Save meta_tag
            self::$wpdb->update(self::$CONNECT,
                array(
                    'last_sync' => date('Y-m-d H:i:s'),
                    'meta_tag' => json_encode($meta_tag)
                ),
                array('id' => $config->id));

            return json_encode([
                'status' => true, 'data' => 'Productos actualizados'
            ]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }    

    /**
     * ****************************
     * ** Admin panel functions ***
     * ****************************
     */

    /**
     * ADMIN_PANEL
     * @return string
     */
    public function adminPage()
    {
        try {
            ob_start();
            $config = $this->getConfig();
            require_once(__DIR__ . '/../template/admin/admin-page.php');
            echo ob_get_clean();
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * ************************
     * ** Private functions ***
     * ************************
     */    

    function saveProduct( $product, $product_saved ) {
        try{
            $objProduct = ( $product_saved ) ? wc_get_product($product_saved) : new WC_Product()  ;
            $objProduct = ($objProduct) ?:  new WC_Product();           
            $objProduct->set_name($product->name);
            $objProduct->set_status("publish");
            $objProduct->set_catalog_visibility('visible');
            $objProduct->set_description($product->description);
            if(!$product_saved){
                $objProduct->set_sku(rand(100, 999).'-'.$product->sku); 
            }    
            if( count($product->prices) != 0 ){
                $objProduct->set_price( $product->prices[0]->amount );
                if(  !empty($product->prices[1]) ){
                    $objProduct->set_regular_price( $product->prices[1]->amount );
                }else{
                    $objProduct->set_regular_price(0);
                }
            }else{
                $objProduct->set_price(0);
                $objProduct->set_regular_price(0);
            }
            $objProduct->set_manage_stock(true); // true or false
            $objProduct->set_stock_quantity( $product->qty );
            $objProduct->set_stock_status('instock'); // in stock or out of stock value
            $objProduct->set_backorders('no');
            $objProduct->set_reviews_allowed(true);
            $objProduct->set_sold_individually(false);
            // Categories
            $tag = [];
            foreach ($product->categories as $categorie) {
                if(!term_exists($categorie->name, 'product_cat')){
                    $term = wp_insert_term($categorie->name, 'product_cat');
                    array_push($tag, $term['term_id']);
                } else {
                    $term_s = get_term_by( 'name', $categorie->name, 'product_cat' );
                    array_push($tag , $term_s->term_id);
                }
            }
            // above function uploadMedia, I have written which takes an image url as an argument and upload image to wordpress and returns the media id, later we will use this id to assign the image to product.
            $productImagesIDs = array(); // define an array to store the media ids.
        
            foreach($product->gallery as $image){
                $image_exist = $this->imageAlredyExist($image->original);
                if( $image_exist ){
                    $mediaID = $image_exist;
                }else{
                    // $mediaID = $this->uploadMedia($image->original); // calling the uploadMedia function and passing image url to get the uploaded media id
                    $mediaID = $this->uploadMedia($image->original); // calling the uploadMedia function and passing image url to get the uploaded media id
                }
                if($mediaID) $productImagesIDs[] = $mediaID; // storing media ids in a array.
            }
            if($productImagesIDs){
                $objProduct->set_image_id($productImagesIDs[0]); // set the first image as primary image of the product
                    //in case we have more than 1 image, then add them to product gallery. 
                if(count($productImagesIDs) > 1){
                    $objProduct->set_gallery_image_ids($productImagesIDs);
                }
            }
            // End upload images
            $objProduct->set_category_ids($tag);
            $product_id = $objProduct->save();
            return $product_id;
            return false;

        }catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    function uploadMedia($image_url){
        $media = media_sideload_image($image_url,0);
        $attachments = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => null,
            'post_parent' => 0,
            'orderby' => 'post_date',
            'order' => 'DESC'
        ));
        return $attachments[0]->ID;
    }
    
    function detecSavedProduct($product_kitagil_id, $relations){
        $saved = false;
        foreach ($relations as $relation) {
            if( $relation->kitagil_id === $product_kitagil_id ) return $relation->wc_id;
        }
        return $saved;
    }
    
    function imageAlredyExist($url_image){
        $exist = false;
        // Get the name of the image by the url
        $fileParts = pathinfo($url_image);
        if(!isset($fileParts['filename']))
        {$fileParts['filename'] = substr($fileParts['basename'], 0, strrpos($fileParts['basename'], '.'));}
        $args = [
            'post_type' => 'attachment',
            'name' => $fileParts['filename'],
        ];
        $images_posts = get_posts( $args );
        if( ! empty( $images_posts ) ) $exist = $images_posts[0]->ID;
        return $exist;
    }
}
