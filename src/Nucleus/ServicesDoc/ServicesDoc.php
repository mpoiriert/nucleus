<?php

namespace Nucleus\ServicesDoc;

class ServicesDoc
{
    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @Inject(docFilename="$[servicesDoc][filename]")
     */
    public function initialize($docFilename)
    {
        $this->docFilename = $docFilename;
    }

    /**
     * @\Nucleus\Routing\Route(name="servicesdoc", path="/nucleus/servicesdoc")
     */
    public function index()
    {
        $doc = json_decode(file_get_contents($this->docFilename), true);
        return $doc;
    }
}
