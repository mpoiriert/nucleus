<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Martin
 * Date: 13-10-11
 * Time: 15:27
 * To change this template use File | Settings | File Templates.
 */

namespace Nucleus\Curl;

use Curl;

class CurlFactory
{
    private static $defaultConfiguration = array('headers'=>array());

    /**
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$")
     *
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = array_deep_merge(self::$defaultConfiguration,$configuration);
    }

    /**
     * Create a new curl object for call
     *
     * @return Curl
     */
    public function create()
    {
        $curl = new Curl();
        $curl->cookie_file = null;
        $curl->user_agent = 'Curl/PHP '.PHP_VERSION.' (http://github.com/shuber/curl)';
        $this->setDefaultOptions($curl);
        return $curl;
    }

    private function setDefaultOptions(Curl $curl)
    {
        $curl->options = $this->configuration['options'];
    }
}