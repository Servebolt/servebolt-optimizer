<?php
namespace Servebolt\Optimizer\HttpHeaders;

defined( 'ABSPATH' ) || die();

class CacheHeaders {

    static public $second = 1;
    static public $minute = 60;
    static public $tenminutes = 600;
    static public $quarterhour = 900;
    static public $halfhour = 1800;
    static public $hour = 3600;
    static public $day = 86400;
    static public $week = 604800;
    static public $month = 2592000;



    static public function byExtension($ext = '')
    {
        $maxAge= self::$minute;
        $sMaxAge = self::$hour;
        if($ext == '') return;
        switch($ext) {
            case 'css':
                $maxAge = self::$hour;
                break;
            case 'js':
                $maxAge = self::$hour;
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
                $sMaxAge = self::$week;
                break;
            case 'svg':
                $sMaxAge = self::$week;
                break;
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'otf':
            case 'eot':
                $maxAge = self::$tenminutes;
                $sMaxAge = self::$week;
                break;
            default:
                $maxAge = 0;
                $sMaxAge = 0;
                break;
        }

        self::byTimeout($maxAge, $sMaxAge);
    }

    /** 
     * Set cache headers by timeout
     * 
     * @param int $maxAge = browser cache TTL
     * @param int $sMaxAge = CDN cache TTL
     * 
     * @return void
     */
    static public function byTimeout($maxAge = 0, $sMaxAge = 0)
    {
        self::cacheControl($maxAge, $sMaxAge);
        self::expires($maxAge);
    }

    static public function currentUrl()
    {
        header('Cache-Control: max-age=0, no-cache, no-store, must-revalidate');
    }

    static public function expires($maxAge = 0, $return = false) {
        if( $maxAge == 0 ) {
            $exp = 'Expires: ' . gmdate('D, d M Y H:i:s', time() - self::$hour) . ' GMT';
            if($return) {
                return $exp;
            } else {
                header($exp);
            }
        }        
    }

    static protected function cacheControl($maxAge = 0, $sMaxAge = 0, $return = false) {
        $cacheControl = 'Cache-Control: ';
        $cacheControl .= 'max-age=' . $maxAge . ', ';
        if($maxAge == 0) {
            $cacheControl .= 'no-cache, no-store, must-revalidate';
        }
        if($maxAge > 0) {
            $cacheControl .= 's-maxage=' . $sMaxAge . ', ';
            $cacheControl .= 'public';
        }

        if($return) {
            return $cacheControl;
        } else {
            header($cacheControl);
        }
    }
      
}