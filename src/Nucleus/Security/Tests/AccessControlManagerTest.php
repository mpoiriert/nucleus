<?php

namespace Nucleus\Security\Tests;

use Nucleus\IService\Security\IAccessControlUser;
use Nucleus\Framework\Nucleus;
use Nucleus\IService\Security\IAccessControlService;

class AccessControlManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Nucleus\Routing\Router
     */
    private $accessControlService;
    
    /**
     * @var Nucleus\IService\DependencyInjection\IServiceContainer
     */
    private $serviceContainer;

    public function setUp()
    {
        $this->serviceContainer = Nucleus::factory(
                array('imports' => array(
                        __DIR__ . '/..',
                        __DIR__ . '/../../Framework/Tests/fixtures/phpunit.json'
                    )
                )
        )->getServiceContainer();
        
        $this->accessControlService = $this->serviceContainer->getServiceByName(IAccessControlService::NUCLEUS_SERVICE_NAME);
    }

    /**
     * @dataProvider provideCheckPermissions
     * 
     * @param boolean $expected
     * @param array $permissions
     * @param \Nucleus\IService\Security\IAccessControlUser $accessControlUser
     */
    public function testCheckPermissions($expected, $permissions, IAccessControlUser $accessControlUser)
    {
        $this->assertEquals(
            $expected, $this->accessControlService->checkPermissions(
                $permissions, $accessControlUser
            )
        );
    }

    public function provideCheckPermissions()
    {
        $accessControlUser = new TestAccessControlUser();
        $accessControlUser->permissions = array('permission1', 'permission2', 'permission3');
        return array(
            array(false, array('permission'), $accessControlUser),
            array(true, array('permission1'), $accessControlUser),
            array(true, array('permission1', 'permission2'), $accessControlUser),
            array(false, array('permission1', '!permission3'), $accessControlUser),
            array(true, array(array('permission', 'permission1')), $accessControlUser),
        );
    }

    /**
     * @expectedException Nucleus\IService\Security\SecurityException
     */
    public function testSecureFailed()
    {
        $object = new SecuredClass();
        $object->impossibleCredentials();
    }
    
    public function testSecureWork()
    {
        $this->serviceContainer->getServiceByName("accessControlUser")->addPermission("existing");
        $object = new SecuredClass();
        $this->assertTrue($object->possibleCredentials());
    }
}

class TestAccessControlUser implements IAccessControlUser
{
    public $permissions;

    public function getPermissions()
    {
        return $this->permissions;
    }

    public function addPermission($permission)
    {
        
    }

    public function addPermissions($permissions)
    {
        
    }

    public function clearPermissions()
    {
        
    }
}