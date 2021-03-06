<?php

namespace Nucleus\AssetManager;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\HashableInterface;
use Assetic\Filter\LessphpFilter;
use Assetic\Filter\ScssphpFilter;
use Exception;
use Nucleus\Framework\Nucleus;
use Nucleus\IService\AssetManager\IUrlBuilder;
use Nucleus\IService\AssetManager\IFilePersister;

/**
 * Description of Manager
 *
 * @author 
 */
class Manager implements \Nucleus\IService\AssetManager\IAssetManager
{
    private $configuration;

    /**
     * @var IFilePersister
     */
    private $filePersister;

    /**
     * @var \Nucleus\IService\AssetManager\IUrlBuilder 
     */
    private $urlBuilder;

    private $packages = array();

    const WATCH_DIRECTORY_DELAY = 5;

    /**
     * @param array $configuration
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$")
     */
    public function initialize(array $configuration, IFilePersister $assetManagerFilePersister, IUrlBuilder $urlBuilder)
    {
        $this->configuration = $configuration;
        $this->filePersister = $assetManagerFilePersister;
        $this->urlBuilder = $urlBuilder;

        if (isset($configuration['packages'])) {
            $this->addPackages($configuration['packages']);
        }
    }

    /**
     * @param string $path
     */
    public function getContent($path)
    {
        return $this->filePersister->recover($path);
    }

    /**
     * Gets the absolute URL to the target asset
     * 
     * @param AssetInterface $asset
     * @param string $fileType
     * @return string Absolute URI 
     * 
     * @\Nucleus\IService\Cache\Cacheable(namespace="assets")
     */
    public function getAssetUrl(AssetInterface $asset, $fileType = 'css')
    {
        $key = $this->getCacheKey($asset);

        $filePath = $asset->getSourcePath();
        if (!$filePath) {
            $filePath = '/aggregation.';
        }

        $targetPath = 'nucleus/copiedAsset' . $filePath . '.' . $key . '.' . $fileType;

        if (!$this->filePersister->exists($targetPath)) {
            $asset->setTargetPath($targetPath);
            $this->applyFilters($asset, $fileType);
            $this->filePersister->persist($targetPath, $asset->dump());
        }

        return $this->getUrl('/' . $targetPath);
    }

    public function getUrl($relativePath)
    {
        return $this->urlBuilder->getUrl($relativePath);
    }

    private function applyFilters(AssetInterface $asset, $fileType)
    {
        if ($fileType == 'css') {
            $asset->ensureFilter(new CssRewriteFilter()); //NECESSARY FOR IMAGE PATHS
        }
        
    }

    /**
     * @param AssetInterface $asset
     * @return string 
     */
    protected function getCacheKey(AssetInterface $asset)
    {
        $cacheKey = '';

        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset->all() as $childAsset) {
                $cacheKey .= $childAsset->getSourcePath();
                $cacheKey .= $childAsset->getLastModified();
            }
        } else {
            $cacheKey .= $asset->getSourcePath();
            $cacheKey .= $asset->getLastModified();
        }

        foreach ($asset->getFilters() as $filter) {
            if ($filter instanceof HashableInterface) {
                $cacheKey .= $filter->hash();
            } else {
                $cacheKey .= serialize($filter);
            }
        }

        return md5($cacheKey);
    }

    /**
     * Returns a FileAsset object from the filepath and request
     * 
     * @param string $filepath
     * @return FileAsset 
     */
    public function getFileAsset($filepath, $path = null)
    {
        $rootDirectory = $this->getRootDirectory();
        if (is_null($path)) {
            $path = $this->getPath($filepath);
        }

        return new FileAsset($rootDirectory . $path, array(), $rootDirectory, $path);
    }

    public function addPackage($name, array $files)
    {
        $this->packages[$name] = $files;
    }

    public function addPackages(array $packages)
    {
        foreach ($packages as $name => $files) {
            $this->addPackage($name, $files);
        }
    }

    public function hasPackage($name)
    {
        return isset($this->packages[$name]);
    }

    public function getPackageFiles($name)
    {
        if (!isset($this->packages[$name])) {
            throw new Exception("Unknow asset package name: $name");
        }
        $files = array();
        foreach ($this->packages[$name] as $file) {
            if (substr($file, 0, 1) === '@') {
                $files = array_merge($files, $this->getPackageFiles(substr($file, 1)));
            } else {
                $files[] = $file;
            }
        }
        return $files;
    }

    public function getPackagePaths($name)
    {
        return array_map(array($this, 'getPath'), $this->getPackageFiles($name));
    }

    public function getPackageAsCollection($name)
    {
        list($cssFiles, $jsFiles) = $this->splitCssAndJsFiles($this->getPackageFiles($name));

        if (!empty($cssFiles)) {
            $cssFiles = new AssetCollection($cssFiles);
        } else {
            $cssFiles = null;
        }
        if (!empty($jsFiles)) {
            $jsFiles = new AssetCollection($jsFiles);
        } else {
            $jsFiles = null;
        }

        return array($cssFiles, $jsFiles);
    }

    public function dumpPackage($name)
    {
        list($css, $js) = $this->assetManager->getPackageAsCollection($name);
        if ($css) {
            $this->dumpPackageCollection($name, "$name.css");
        }
        if ($js) {
            $this->dumpPackageCollection($name, "$name.js");
        }
    }

    protected function dumpPackageCollection($name, $type, AssetCollection $coll)
    {
        $key = $this->getCacheKey($coll);
        $filename = "$name.$key.$type";
        $filepath = $this->getPackagesTargetPath() . "/$filename";
        if (!file_exists($filepath)) {
            $dir = dirname($filepath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            file_put_contents($filepath, $coll->dump());
        }
        return $filename;
    }

    /**
     * @\Nucleus\IService\Cache\Cacheable(namespace="asset_packages")
     */
    public function getPackageUrls($name)
    {
        list($css, $js) = $this->getPackageAsCollection($name);
        $baseUrl = $this->getPackagesTargetUrl();
        $cssUrl = null;
        if ($css) {
            $filename = $this->dumpPackageCollection($name, 'css', $css);
            $cssUrl = "$baseUrl/$filename";
        }
        $jsUrl = null;
        if ($js) {
            $filename = $this->dumpPackageCollection($name, 'js', $js);
            $jsUrl = "$baseUrl/$filename";
        }
        return array($cssUrl, $jsUrl);
    }

    /**
     * @param array $files
     * @return string
     * @throws Exception
     * 
     * @\Nucleus\IService\Cache\Cacheable(namespace="assets")
     */
    public function getHtmTags(array $files)
    {
        $cssUrls = array();
        $jsUrls = array();

        if ($this->configuration['aggregation'] === true) {
            list($packages, $files) = $this->extractPackages($files);
            list($cssFiles, $jsFiles) = $this->splitCssAndJsFiles($files);

            $cssFiles = !empty($cssFiles) ? array(new AssetCollection($cssFiles)) : array();
            $jsFiles = !empty($jsFiles) ? array(new AssetCollection($jsFiles)) : array();

            foreach ($packages as $name) {
                list($cssu, $jsu) = $this->getPackageUrls($name);
                if ($cssu) {
                    $cssUrls[] = $cssu;
                }
                if ($jsu) {
                    $jsUrls[] = $jsu;
                }
            }

        } else {
            list($cssFiles, $jsFiles) = $this->splitCssAndJsFiles($files);
        }

        foreach ($cssFiles as $file) {
            $cssUrls[] = $this->getAssetUrl($file, 'css');
        }
        foreach ($jsFiles as $file) {
            $jsUrls[] = $this->getAssetUrl($file, 'js');
        }

        $tags = array();
        foreach ($cssUrls as $url) {
            $tags[] = sprintf('<link rel="stylesheet" type="text/css" href="%s" />', $url);
        }
        foreach ($jsUrls as $url) {
            $tags[] = sprintf('<script type="text/javascript" src="%s"></script>', $url);
        }
        return $tags;
    }

    private function extractPackages(array $files)
    {
        $packages = array();
        $assets = array();
        foreach ($files as $file) {
            if (substr($file, 0, 1) === '@') {
                $packages[] = substr($file, 1);
            } else {
                $assets[] = $file;
            }
        }
        return array($packages, $assets);
    }

    private function splitCssAndJsFiles(array $files)
    {
        $cssFiles = array();
        $jsFiles = array();
        foreach ($files as $file) {
            if (substr($file, 0, 1) === '@') {
                $file = $this->getPackageFiles(substr($file, 1));
            }
            foreach ((array) $file as $f) {
                list($type, $asset) = $this->makeAsset($f);
                if ($type === 'css') {
                    $cssFiles[] = $asset;
                } else {
                    $jsFiles[] = $asset;
                }
            }
        }
        return array($cssFiles, $jsFiles);
    }

    private function makeAsset($file)
    {
        $path = $this->getPath($file);
        switch (true) {
            case strpos($file, '://') !== false:
                $asset = new HttpAsset($path);
                break;
            default:
                $asset = $this->getFileAsset($file, $path);
                break;
        }

        switch (pathinfo($path, PATHINFO_EXTENSION)) {
            case 'less':
                $asset->ensureFilter(new LessphpFilter());
                $type = 'css';
                break;
            case 'scss':
                $asset->ensureFilter(new ScssphpFilter());
                $type = 'css';
                break;
            case 'css':
                $type = 'css';
                break;
            case 'js':
                $type = 'js';
                break;
            default:
                throw new Exception('Not supported file [' . $file . ']');
        }

        return array($type, $asset);
    }

    private function getPath($source)
    {
        if (strpos($source, '://')) {
            return $source;
        }

        if (0 !== strpos($source, '/')) {
            throw new Exception('must be from root');
        }

        $query_string = '';
        if (false !== $pos = strpos($source, '?')) {
            $query_string = substr($source, $pos);
            $source = substr($source, 0, $pos);
        }

        return $source;
    }

    private function getRootDirectory()
    {
        return $this->configuration['rootDirectory'];
    }

    private function getPackagesTargetPath()
    {
        return $this->configuration['packagesTargetPath'];
    }

    private function getPackagesTargetUrl()
    {
        return $this->configuration['packagesTargetUrl'];
    }

    /**
     * @param mixed $configuration
     * @return Manager
     */
    public static function factory($configuration = null)
    {
        if (is_null($configuration)) {
            $configuration = __DIR__ . '/nucleus.json';
        }

        return Nucleus::serviceFactory($configuration, 'assetManager');
    }
}
