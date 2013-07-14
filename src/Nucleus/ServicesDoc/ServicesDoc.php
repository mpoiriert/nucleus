<?php

namespace Nucleus\ServicesDoc;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Nucleus\ServicesDoc\DocDumper;
use Nucleus\IService\FileSystem\IFileSystemService;

class ServicesDoc
{
    private $docFilename;
    
    /**
     * @var IFileSystemService 
     */
    private $fileSystem;
    
    /**
     * @param \Nucleus\Routing\Router $routing
     * 
     * @Inject(cacheDirectory="$[configuration][generatedDirectory]")
     */
    public function initialize($cacheDirectory, IFileSystemService $fileSystem)
    {
        $this->docFilename = $cacheDirectory . '/docs/docs.json';
        $this->fileSystem = $fileSystem;
    }

    /**
     * @\Nucleus\Routing\Route(name="servicesdoc", path="/nucleus/servicesdoc")
     * @\Nucleus\IService\FrontController\ViewDefinition(template="documentation/services.twig")
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
        $this->fileSystem->dumpFile($this->docFilename, $docs->dump(array()));
    }
}
