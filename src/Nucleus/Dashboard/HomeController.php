<?php

namespace Nucleus\Dashboard;

/**
 * @\Nucleus\IService\Dashboard\Service(name="Home")
 */
class HomeController
{
    /**
     * @\Nucleus\IService\Dashboard\Action(title="List", icon="list", type="list", default=true)
     * @\Nucleus\IService\Dashboard\ListColumn(name="ID", property="id")
     * @\Nucleus\IService\Dashboard\ListColumn(name="First name", property="firstname")
     * @\Nucleus\IService\Dashboard\ListColumn(name="Last name", property="lastname")
     * @\Nucleus\IService\Dashboard\ListAction(name="delete", icon="trash", title="Delete")
     */
    public function listAll()
    {
        return array(
            array(1, 'foo', 'bar'),
            array(2, 'paul', 'baz')
        );
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Add", icon="plus", type="form")
     * @\Nucleus\IService\Dashboard\FormField(name="firstname", label="First name", type="text")
     * @\Nucleus\IService\Dashboard\FormField(name="lastname", label="Last name", type="text")
     */
    public function add($firstname, $lastname)
    {
        return array('added' => "$firstname $lastname");
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Delete", icon="plus", global=false)
     */
    public function delete($id)
    {
        return array('deleted' => $id);
    }
}
