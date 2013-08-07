<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\IService\ApplicationContext;

/**
 * This annotation allow a service property to be bound in the session.
 * If no value is present in the session the default value of the property
 * will be use.
 *
 * @Annotation
 * 
 * @Target({"PROPERTY"})
 * 
 */
class BoundToSession
{

}