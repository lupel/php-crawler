<?php
class crawler{
    private $_url      = 'www.google.com';
    private $_depth    = 1;
    private $_maxLinks = 100;
    private $_links    = array();
    private $_started  = 0;

    public function setUrl( $url )
    {
        if( filter_var( $url, FILTER_VALIDATE_URL ) )
        {
            $this->_url = $url;
        }
    }

    public function setDepth( $depth )
    {
        if( is_int( $depth ) )
        {
            $this->_depth = $depth;
        }
    }

    private function getContent()
    {
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $this->_url );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $curl, CURLOPT_ENCODING , 'UTF-8' );
        curl_setopt( $curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5' );
		$data = curl_exec( $curl );
		curl_close( $curl );

        return $data;
    }
 
    public function getLinks()
    {
        if( !$this->_started )
        {
            $this->_started = 1;
            $curr_depth = 0;
        }else{
            $curr_depth++;
        }
        if( $curr_depth < $this->_depth )
        {
            $data = $this->getContent( $this->_url );
            if( preg_match_all( '/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $data, $urls12 ) )
            {
                foreach( $urls12[ 2 ] as $index => $value )
                {
                    $parsedUrl = parse_url( $value );
                    $parsedUrl = $parsedUrl[ 'host' ];

                    if( !empty( $parsedUrl ) && !stristr( $parsedUrl, $this->_url ) && !in_array( $parsedUrl, $this->_links ) && $curr_depth < $this->_depth && count( $this->_links ) < $this->_maxLinks )
                    {
                        $this->_links[] = $parsedUrl;
                        $this->getLinks( $parsedUrl );
                    }
                    if( count( $this->_links ) >= $this->_maxLinks )
                    {
                        break;
                    }
                }
            }
        }
        return $this->_links;
    }

    public function toJson()
    {
        header( 'Content-Type: application/json' );
        echo( json_encode( $this->_links ) );
    }
}


set_time_limit( 0 );

$url   = isset( $_GET[  'url'  ] )  ?  $_GET[  'url'  ]  :  null;
$depth = isset( $_GET[ 'depth' ] )  ?  $_GET[ 'depth' ]  :  null;

$spider  =  new crawler();
$spider->setUrl( $url );
$spider->setDepth( $depth );
$spider->getLinks();
$spider->toJson();
?>