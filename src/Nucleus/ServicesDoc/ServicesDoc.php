<?php

namespace Nucleus\ServicesDoc;

use Nucleus\Routing\Router;

class ServicesDoc
{
    /**
     * @var \Nucleus\Routing\Router 
     */
    private $routing;

    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @Inject(docFilename="$[servicesDoc][filename]")
     */
    public function initialize(Router $routing, $docFilename)
    {
        $this->routing = $routing;
        $this->docFilename = $docFilename;
    }

    /**
     * @Route(name="servicesdoc", path="/nucleus/servicesdoc")
     */
    public function index()
    {
        $doc = json_decode(file_get_contents($this->docFilename), true);
        return $doc;
    }
}
