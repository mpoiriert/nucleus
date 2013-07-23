<?php
/**
 * Created by JetBrains PhpStorm.
 * User: AxelBarbier
 * Date: 7/22/13
 * Time: 4:06 PM
 * To change this template use File | Settings | File Templates.
 */
namespace Nucleus\ServicesDoc;
use Sami\Sami;
use Symfony\Component\Finder\Finder;
use Sami\Project;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ApiDoc
{
    private $iteratorFiles;
    private $arrayPaths;
    private $arrayExcludes;
    private $fileTypes;

    private $docTitle;
    private $buildDir;
    private $redirect;
    private $sami;


    /**
     * @param \Nucleus\Routing\Router $routing
     *
     * @Inject(params="$")
     */
    public function initialize($params)
    {
        $webDir = $params['docParam']['webDirectory'];
        if(!isset($params['docParam']['buildPath'])){
            $buildPath = '/nucleus/apiDocumentation';
        }
        else {
            $buildPath = $params['docParam']['buildPath'];
        }

        $this->buildDir         = $webDir.$buildPath;
        $this->redirect         = $buildPath.'/index.html';
        $this->arrayPaths       = $params['docParam']['pathsDoc'];
        $this->arrayExcludes    = $params['docParam']['excludePaths'];
        $this->fileTypes        = $params['docParam']['fileTypes'];
        $this->docTitle         = $params['docParam']['docTitle'];
        $this->iteratorFiles    = Finder::create();

        if(is_array($this->fileTypes)){
            foreach($this->fileTypes as $fileType){
                $this->iteratorFiles->name($fileType);
            }
        }

        $this->iteratorFiles->exclude($this->arrayExcludes);
        $this->iteratorFiles->in($this->arrayPaths);

        $this->iteratorFiles->files();

        $this->sami = new Sami($this->iteratorFiles, array(
            'title'                => $this->docTitle,
            'build_dir'            => $this->buildDir,
            'cache_dir'            => __DIR__.'/resources/cache/',
            'default_opened_level' => 2,
        ));
    }

    /**
     * @\Nucleus\Routing\Route(name="apidoc", path="/nucleus/apidoc")
     */
    public function index()
    {
        if(!file_exists($this->buildDir.'/index.html')){
            $this->sami['project']->render(null, true);
        }
        //echo $this->redirect;die;
        return new RedirectResponse($this->redirect);
    }


    /**
     * Parses then renders a project
     * @param boolean $force Forces to rebuild from scratch
     * @\Nucleus\IService\CommandLine\Consolable(name="apidoc:update")
     */
    public function update($force = false){

        $this->sami['project']->update(null, $force);
    }

    /**
     * This command parses a project and generates a database with API information
     * @param boolean $force Forces to rebuild from scratch
     * @\Nucleus\IService\CommandLine\Consolable(name="apidoc:parse")
     */
    public function parse($force = false){
        $this->sami['project']->parse(null, $force);
    }

    /**
     * This command renders a project as a static set of HTML files
     * @param boolean $force Forces to rebuild from scratch
     * @\Nucleus\IService\CommandLine\Consolable(name="apidoc:render")
     */
    public function render($force = false){
        $this->sami['project']->render(null, $force);
    }
}