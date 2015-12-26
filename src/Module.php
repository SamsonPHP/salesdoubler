<?php
/**
 * Created by PhpStorm.
 * User: VITALYIEGOROV
 * Date: 17.12.15
 * Time: 15:40
 */
namespace samsonphp\salesdoubler;

use samson\core\CompressableService;

/**
 * SalesDoubler.com.ua CPA module inplementation.
 *
 * @package samsonphp\salesdoubler
 */
class Module extends CompressableService
{
    /** @var string User cookie identifier */
    public $cookieID = 'salesdoubler_clickid';

    /** @var string Cookie life period */
    public $cookiePeriod = 2592000;

    /** @var string User click GET param name */
    public $getParamName = 'aff_sub';

    /** @var string RegExp pattern for matching paid channels */
    public $paidPattern = '/utm_medium=/';

    /** @var string SalesDoubler post back url */
    public $postbackUrl = 'http://rdr.salesdoubler.com.ua/in/postback/@id/@clickid?trans_id=@transid&token=@token';

    /** @var string SalesDoubler post back url token value */
    public $postbackToken = 'bG92ZWdpZnRkdXJleEBzYWxlc2RvdWJsZXIuY29tLnVh';

    /** @var string SalesDoubler post back url identifier value */
    public $postbackID = '1240';

    /** @var string Current user internal identifier */
    public $transID = 'salesdoubler_transid';

    /** @var string Identifier */
    protected $id = 'salesdoubler';

    /**
     * Initialize module.
     *
     * @param array $params Paramters
     * @return bool Module initialization results
     */
    public function init(array $params = array())
    {
        // If this is paid channel
        if (isset($_COOKIE[$this->cookieID]) && preg_match($this->paidPattern, url()->text)) {
            // Remove cookie if use has come from paid channel
            unset($_COOKIE[$this->cookieID]);
            setcookie($this->cookieID, null, -1, '/');
            // Otherwise search for saledoubler get parameter
        } elseif (isset($_GET[$this->getParamName]) && !empty($_GET[$this->getParamName])) {
            $_SESSION[$this->cookieID] = $_GET[$this->getParamName];
            // Generate random internal trans identifier
            $_SESSION[$this->transID] = md5(rand(1, 1000) . $_GET[$this->getParamName] . time() . rand(1, 1000));
            setcookie($this->cookieID, $_GET[$this->getParamName], time() + $this->cookiePeriod);
        }
    }

    /**
     * Trigger success goal action
     */
    public function goal()
    {
        // If we have salesdoubler cookie and CURL is working
        if (isset($_COOKIE[$this->cookieID]) && $curl = curl_init()) {
            $url = $this->postbackUrl;
            $url = str_replace('@token', $this->postbackToken, $url);
            $url = str_replace('@id', $this->postbackID, $url);

            if (isset($_COOKIE[$this->cookieID])) {
                $url = str_replace('@clickid', $_COOKIE[$this->cookieID], $url);
            }

            if (isset($_SESSION[$this->transID])) {
                $url = str_replace('@transid', $_SESSION[$this->transID], $url);
            }

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_exec($curl);
            curl_close($curl);
        }
    }
}
