<?php

namespace Nucleus\AssetManager\Tests;

use Nucleus\AssetManager\Manager;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    private $assetManager;

    public function setUp()
    {
        $this->assetManager = Manager::factory(
                array(
                    "imports" => array(__DIR__ . "/../nucleus.json"),
                    "services" => array(
                        "assetManager" => array(
                            "configuration" => array(
                                "rootDirectory" => $this->getRootDir()
                            )
                        )
                    )
                )
        );
    }

    public function getRootDir()
    {
        return __DIR__ . "/fixtures/web";
    }

    /**
     * @dataProvider provideFileConversion
     * @param type $source
     * @param type $expected
     */
    public function testFileConversion($source, $expected)
    {
        $tags = $this->assetManager->getHtmTags(array($source));
        $simpleXmlTag = new \SimpleXMLElement($tags[0]);
        $this->assertEquals(
            file_get_contents($this->getRootDir() . $expected), $this->assetManager->getContent((string) $simpleXmlTag['href'])
        );
    }

    public function provideFileConversion()
    {
        $testData = array();
        foreach (scandir($this->getRootDir() . '/source') as $file) {
            if (!is_file($this->getRootDir() . '/source/' . $file)) {
                continue;
            }
            $testData[] = array('/source/' . $file, '/expected/' . $file);
        }

        return $testData;
    }
}