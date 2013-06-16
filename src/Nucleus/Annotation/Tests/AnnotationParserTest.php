<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Annotation\Tests;

use Nucleus\IService\Annotation\Tests\AnnotationParserServiceTest;
use Nucleus\Annotation\AnnotationParser;

/**
 * Description of AnnotationParserTest
 *
 * @author Martin
 */
class AnnotationParserTest extends AnnotationParserServiceTest
{

    protected function getAnnotationParserService($configuration)
    {
        return new AnnotationParser($configuration);
    }
}
