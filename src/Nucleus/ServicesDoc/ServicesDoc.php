<?php

namespace Nucleus\ServicesDoc;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Nucleus\ServicesDoc\DocDumper;

class ServicesDoc
{
    private $docFilename;
    
    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @Inject(cacheDirectory="$[configuration][generatedDirectory]")
     */
    public function initialize($cacheDirectory)
    {
        $this->docFilename = $cacheDirectory . '/docs/docs.json';
    }

    /**
     * @\Nucleus\Routing\Route(name="servicesdoc", path="/nucleus/servicesdoc")
     */
    public function index()
    {
        $doc = json_decode(file_get_contents($this->docFilename), true);
        return $doc;
    }
    
    /**
     * @Listen(eventName="ServiceContainer.postDump")
     *
     * @param ContainerBuilder $containerBuilder
     */
    public function generateDoc(ContainerBuilder $containerBuilder)
    {
        $docs = new DocDumper($containerBuilder);
        file_put_contents($this->docFilename, $docs->dump(array()));
    }
}
