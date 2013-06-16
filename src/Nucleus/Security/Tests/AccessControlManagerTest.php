<?php

namespace Nucleus\Security\Tests;

use Nucleus\Security\AccessControlManager;
use Nucleus\IService\Security\IAccessControlUser;

class AccessControlManagetTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var Nucleus\Routing\Router
     */
    private $accessControlService;

    public function setUp()
    {
        $this->accessControlService = AccessControlManager::factory();
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
}

class TestAccessControlUser implements IAccessControlUser
{
    public $permissions;

    public function getPermissions()
    {
        return $this->permissions;
    }
}