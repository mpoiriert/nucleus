<?php

namespace Nucleus\Dashboard;

/**
 * @\Nucleus\IService\Dashboard\Controller(name="home", title="Home")
 */
class HomeController
{
    protected $data = array();

    public function __construct()
    {
        $this->data = array(
            new HomeModel(0, 'foo', 'bar'),
            new HomeModel(1, 'paul', 'baz', 'pwd')
        );
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="List", icon="list", default=true)
     * @\Nucleus\IService\Dashboard\Paginate(auto=true, per_page=1)
     * @\Nucleus\IService\Dashboard\Sortable(order_param="order")
     * 
     * @return \Nucleus\Dashboard\HomeModel[]
     */
    public function listAll($sort = 'id', $order = 'asc')
    {
        usort($this->data, function($a, $b) use ($sort, $order) {
            if ($a->$sort == $b->$sort) {
                return 0;
            }
            $v = $a->$sort < $b->$sort ? -1 : 1;
            return $order == 'asc' ? $v : -$v;
        });
        return $this->data;
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="User")
     * 
     * @return \User
     */
    public function listUser()
    {
        $user = new \User();
        $user->setName('foo');
        $user->save();
        return $user;
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Add", icon="plus", menu="/Home/Add")
     * 
     * @return Nucleus\Dashboard\HomeModel
     */
    public function add(HomeModel $model)
    {
        $model->setId(2);
        return $model;
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Add (args)", icon="plus", menu="/Home/Add")
     * @\Nucleus\IService\Dashboard\Validate(property="firstname", constraint="NotBlank")
     *
     * @param string $firstname Firstname
     * @param string $lastname Lastname
     * @param string $password Password
     */
    public function createAndAdd($firstname, $lastname, $password = null)
    {
        return new HomeModel(2, $firstname, $lastname, $password);
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Search", icon="search", menu="/")
     *
     * @return \Nucleus\Dashboard\HomeModel[]
     */
    public function search($firstname)
    {
        return array_filter($this->data, function($m) use ($firstname) {
            return $m->firstname == $firstname;
        });
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Show", menu=false)
     * @param int $id
     * @return Nucleus\Dashboard\HomeModel
     */
    public function get($id)
    {
        return $this->data[$id];
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(title="Edit", icon="edit", on_model="Nucleus\Dashboard\HomeModel", pipe="save")
     * @return Nucleus\Dashboard\HomeModel
     */
    public function edit($id)
    {
        return $this->data[$id];
    }

    /**
     * @\Nucleus\IService\Dashboard\Action(menu=false, load_model=true)
     * @return Nucleus\Dashboard\HomeModel
     */
    public function save(HomeModel $model)
    {
        return $model;
    }
}
